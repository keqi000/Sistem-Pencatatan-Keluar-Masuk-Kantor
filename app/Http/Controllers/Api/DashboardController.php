<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PasanganKeluarMasuk;
use App\Models\Pindaian;
use App\Models\Pengaturan;
use Carbon\Carbon;

class DashboardController extends Controller
{
    private function getJamPulangHariIni(): string
    {
        if (now()->dayOfWeek === Carbon::FRIDAY && (int)Pengaturan::get('jumat_aktif', 0)) {
            return Pengaturan::get('jumat_jam_pulang', '11:30');
        }
        return Pengaturan::get('jam_pulang', '16:00');
    }

    public function getStatsHariIni(Request $request)
    {
        $user = $request->user();
        $unitKerjaId = (int)$request->query('unit_kerja_id', 0);

        if ($user && $user->role === 'pimpinan' && $user->pegawai?->unit_kerja_id) {
            $unitKerjaId = (int)$user->pegawai->unit_kerja_id;
        }

        $query = PasanganKeluarMasuk::with(['pindaianKeluar.izinDinas'])
            ->whereDate('jam_keluar', today());

        if ($unitKerjaId > 0) {
            $query->whereHas('pegawai', fn($q) => $q->where('unit_kerja_id', $unitKerjaId));
        }

        $records   = $query->get();
        $threshold = (int)Pengaturan::get('ambang_terlambat_menit', 120);
        $jamPulang = $this->getJamPulangHariIni();

        $belumKembali = $records->where('status', 'terbuka')->filter(function ($r) use ($threshold, $jamPulang) {
            $durasi = (int)$r->jam_keluar->diffInMinutes(now());
            if ($durasi >= $threshold) return true;
            if (now()->format('H:i') > $jamPulang) return true;
            $izin = $r->pindaianKeluar?->izinDinas;
            return $izin && !empty($izin->perkiraan_jam_kembali)
                && now()->format('H:i:s') > $izin->perkiraan_jam_kembali;
        })->count();

        return response()->json([
            'success'     => true,
            'tanggal'     => today()->toDateString(),
            'server_time' => now()->toDateTimeString(),
            'stats'       => [
                'total_keluar'         => $records->count(),
                'sedang_diluar'        => $records->where('status', 'terbuka')->count(),
                'sudah_kembali'        => $records->where('status', 'kembali')->count(),
                'belum_kembali'        => $belumKembali,
                'total_dinas'          => $records->filter(fn($r) => $r->pindaianKeluar?->keperluan_jenis === 'dinas')->count(),
                'total_keperluan_lain' => $records->filter(fn($r) => $r->pindaianKeluar?->keperluan_jenis === 'keperluan_lain')->count(),
                'total_durasi_menit'   => (int)$records->sum('durasi_menit'),
            ],
        ]);
    }

    public function getSedangDiluar(Request $request)
    {
        $user = $request->user();
        $unitKerjaId = (int)$request->query('unit_kerja_id', 0);

        if ($user && $user->role === 'pimpinan' && $user->pegawai?->unit_kerja_id) {
            $unitKerjaId = (int)$user->pegawai->unit_kerja_id;
        }

        $threshold = (int)Pengaturan::get('ambang_terlambat_menit', 120);
        $jamPulang = $this->getJamPulangHariIni();

        $query = PasanganKeluarMasuk::with(['pegawai.unitKerja', 'pindaianKeluar.izinDinas'])
            ->where('status', 'terbuka')
            ->whereDate('jam_keluar', today());

        if ($unitKerjaId > 0) {
            $query->whereHas('pegawai', fn($q) => $q->where('unit_kerja_id', $unitKerjaId));
        }

        $records = $query->orderBy('jam_keluar', 'asc')->get()->map(function ($item) use ($threshold, $jamPulang) {
            $durasi    = max(0, (int)$item->jam_keluar->diffInMinutes(now()));
            $isOverdue = ($durasi >= $threshold) || (now()->format('H:i') > $jamPulang);

            $izin = $item->pindaianKeluar?->izinDinas;
            if ($izin && !empty($izin->perkiraan_jam_kembali)) {
                if (now()->format('H:i:s') > $izin->perkiraan_jam_kembali) {
                    $isOverdue = true;
                }
            }

            return [
                'pasangan_id'           => $item->id,
                'jam_keluar'            => $item->jam_keluar?->toDateTimeString(),
                'durasi_berjalan_menit' => $durasi,
                'durasi_format'         => $this->formatDurasi($durasi),
                'is_overdue'            => $isOverdue,
                'pegawai_id'            => $item->pegawai_id,
                'nama_lengkap'          => $item->pegawai?->nama_lengkap,
                'nip'                   => $item->pegawai?->nip,
                'jabatan'               => $item->pegawai?->jabatan,
                'foto'                  => $item->pegawai?->foto,
                'nomor_hp'              => $item->pegawai?->nomor_hp,
                'nama_unit'             => $item->pegawai?->unitKerja?->nama_unit,
                'kode_unit'             => $item->pegawai?->unitKerja?->kode_unit,
                'keperluan_jenis'       => $item->pindaianKeluar?->keperluan_jenis,
                'tujuan'                => $izin?->tujuan,
                'keperluan'             => $izin?->keperluan,
                'perkiraan_jam_kembali' => $izin?->perkiraan_jam_kembali,
            ];
        });

        return response()->json([
            'success'     => true,
            'count'       => $records->count(),
            'server_time' => now()->toDateTimeString(),
            'data'        => $records,
        ]);
    }

    public function getChartData(Request $request)
    {
        $tanggal    = $request->query('tanggal', today()->toDateString());
        $hours      = ['07','08','09','10','11','12','13','14','15','16','17'];
        $labels     = [];
        $dataKeluar = [];
        $dataMasuk  = [];

        foreach ($hours as $h) {
            $labels[]       = "{$h}:00";
            $dataKeluar[$h] = 0;
            $dataMasuk[$h]  = 0;
        }

        foreach (Pindaian::whereDate('jam', $tanggal)->get() as $p) {
            $h = $p->jam->format('H');
            if (isset($dataKeluar[$h]) && $p->jenis === 'keluar') $dataKeluar[$h]++;
            if (isset($dataMasuk[$h])  && $p->jenis === 'masuk')  $dataMasuk[$h]++;
        }

        return response()->json([
            'success' => true,
            'tanggal' => $tanggal,
            'chart'   => [
                'labels'   => $labels,
                'datasets' => [
                    ['label' => 'Pegawai Keluar (Lobby)',       'backgroundColor' => 'rgba(214,137,16,0.8)', 'borderColor' => '#D68910', 'data' => array_values($dataKeluar)],
                    ['label' => 'Pegawai Kembali (Pos Security)','backgroundColor' => 'rgba(30,132,73,0.8)',  'borderColor' => '#1E8449', 'data' => array_values($dataMasuk)],
                ],
            ],
        ]);
    }

    private function formatDurasi(int $menit): string
    {
        if ($menit <= 0) return '0 menit';
        if ($menit < 60) return "{$menit} menit";
        $jam  = floor($menit / 60);
        $sisa = $menit % 60;
        return $sisa > 0 ? "{$jam} jam {$sisa} menit" : "{$jam} jam";
    }
}
