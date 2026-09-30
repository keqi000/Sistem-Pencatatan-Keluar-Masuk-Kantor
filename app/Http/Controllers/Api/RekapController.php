<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PasanganKeluarMasuk;
use App\Models\Pegawai;
use App\Models\Pengaturan;
use Carbon\Carbon;

class RekapController extends Controller
{
    /**
     * Rekap harian
     */
    public function harian(Request $request)
    {
        $user = $request->user();
        $tanggal = $request->query('tanggal', today()->toDateString());
        $unitKerjaId = (int)($request->query('unit_id') ?? $request->query('unit_kerja_id', 0));

        if ($user && $user->role === 'pimpinan' && $user->pegawai?->unit_kerja_id) {
            $unitKerjaId = (int)$user->pegawai->unit_kerja_id;
        }

        $query = PasanganKeluarMasuk::with(['pegawai.unitKerja', 'pindaianKeluar.izinDinas'])
            ->whereDate('jam_keluar', $tanggal);

        if ($unitKerjaId > 0) {
            $query->whereHas('pegawai', function ($q) use ($unitKerjaId) {
                $q->where('unit_kerja_id', $unitKerjaId);
            });
        }

        $records = $query->orderBy('jam_keluar', 'asc')->get();

        $summary = [
            'total_keluar'         => $records->count(),
            'sedang_diluar'        => $records->where('status', 'terbuka')->count(),
            'sudah_kembali'        => $records->where('status', 'kembali')->count(),
            'belum_kembali'        => $records->where('status', 'belum_kembali')->count(),
            'total_dinas'          => $records->filter(fn($r) => $r->pindaianKeluar?->keperluan_jenis === 'dinas')->count(),
            'total_keperluan_lain' => $records->filter(fn($r) => $r->pindaianKeluar?->keperluan_jenis === 'keperluan_lain')->count(),
            'total_durasi_menit'   => (int)$records->sum('durasi_menit'),
        ];
        $summary['total_durasi_format'] = $this->formatDurasi($summary['total_durasi_menit']);

        $data = $records->map(function ($r) {
            $durasi = $r->status === 'terbuka'
                ? max(0, (int)now()->diffInMinutes($r->jam_keluar))
                : (int)$r->durasi_menit;

            return [
                'pasangan_id'     => $r->id,
                'pegawai_id'      => $r->pegawai_id,
                'nama_lengkap'    => $r->pegawai?->nama_lengkap,
                'nip'             => $r->pegawai?->nip,
                'jabatan'         => $r->pegawai?->jabatan,
                'nama_unit'       => $r->pegawai?->unitKerja?->nama_unit,
                'jam_keluar'      => $r->jam_keluar?->format('H:i'),
                'jam_kembali'     => $r->jam_kembali ? $r->jam_kembali->format('H:i') : '-',
                'durasi_menit'    => $durasi,
                'durasi_format'   => $this->formatDurasi($durasi),
                'keperluan_jenis' => $r->pindaianKeluar?->keperluan_jenis,
                'tujuan'          => $r->pindaianKeluar?->izinDinas?->tujuan,
                'status'          => $r->status,
            ];
        });

        return response()->json([
            'success' => true,
            'tanggal' => $tanggal,
            'summary' => $summary,
            'data'    => $data,
        ]);
    }

    /**
     * Rekap bulanan per pegawai
     */
    public function bulanan(Request $request)
    {
        $user = $request->user();
        $bulan = $request->query('bulan', now()->format('Y-m'));
        $unitKerjaId = (int)($request->query('unit_id') ?? $request->query('unit_kerja_id', 0));

        if ($user && $user->role === 'pimpinan' && $user->pegawai?->unit_kerja_id) {
            $unitKerjaId = (int)$user->pegawai->unit_kerja_id;
        }

        $pegawaiQuery = Pegawai::with(['unitKerja', 'pasanganKeluarMasuk' => function ($q) use ($bulan) {
            $q->whereYear('jam_keluar', substr($bulan, 0, 4))
              ->whereMonth('jam_keluar', substr($bulan, 5, 2))
              ->with('pindaianKeluar');
        }])->where('status', 'aktif');

        if ($unitKerjaId > 0) {
            $pegawaiQuery->where('unit_kerja_id', $unitKerjaId);
        }

        $list = $pegawaiQuery->get()->map(function ($p) {
            $sesi = $p->pasanganKeluarMasuk;
            $hariKeluar = $sesi->pluck('jam_keluar')->map(fn($d) => $d->toDateString())->unique()->count();
            $dinasCount = $sesi->filter(fn($s) => $s->pindaianKeluar?->keperluan_jenis === 'dinas')->count();
            $tanpaIzinCount = $sesi->filter(fn($s) => $s->pindaianKeluar?->keperluan_jenis === 'keperluan_lain')->count();
            $belumKembaliCount = $sesi->where('status', 'belum_kembali')->count();
            $totalMenit = (int)$sesi->sum('durasi_menit');

            return [
                'pegawai_id'          => $p->id,
                'nama_lengkap'        => $p->nama_lengkap,
                'nip'                 => $p->nip,
                'jabatan'             => $p->jabatan,
                'nama_unit'           => $p->unitKerja?->nama_unit,
                'kode_unit'           => $p->unitKerja?->kode_unit,
                'jumlah_hari_keluar'  => $hariKeluar,
                'total_kali_keluar'   => $sesi->count(),
                'jumlah_dinas'        => $dinasCount,
                'jumlah_tanpa_izin'   => $tanpaIzinCount,
                'jumlah_belum_kembali'=> $belumKembaliCount,
                'total_menit_keluar'  => $totalMenit,
                'total_durasi_format' => $this->formatDurasi($totalMenit),
            ];
        })->sortByDesc('total_kali_keluar')->values();

        return response()->json([
            'success' => true,
            'bulan'   => $bulan,
            'count'   => $list->count(),
            'data'    => $list,
        ]);
    }

    /**
     * Export tampilan cetak resmi
     */
    public function exportPdf(Request $request)
    {
        $tipe = $request->query('tipe', 'harian');
        $tanggal = $request->query('tanggal', today()->toDateString());
        $bulan = $request->query('bulan', now()->format('Y-m'));
        $unitKerjaId = (int)($request->query('unit_id') ?? $request->query('unit_kerja_id', 0));

        $user = $request->user();
        if ($user && $user->role === 'pimpinan' && $user->pegawai?->unit_kerja_id) {
            $unitKerjaId = (int)$user->pegawai->unit_kerja_id;
        }

        $namaInstansi = Pengaturan::get('nama_instansi', 'Balai Penjaminan Mutu Pendidikan (BPMP) Provinsi Gorontalo');
        $kementerian = Pengaturan::get('kementerian', 'Kementerian Pendidikan Dasar dan Menengah');
        $alamatInstansi = Pengaturan::get('alamat_instansi', 'Jl. Kasmat Lahay, Gorontalo');

        return response()->view('export_rekap', [
            'tipe'           => $tipe,
            'tanggal'        => $tanggal,
            'bulan'          => $bulan,
            'unitKerjaId'    => $unitKerjaId,
            'namaInstansi'   => $namaInstansi,
            'kementerian'    => $kementerian,
            'alamatInstansi' => $alamatInstansi,
        ])->header('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Export file Excel (CSV)
     */
    public function exportExcel(Request $request)
    {
        $tipe = $request->query('tipe', 'harian');
        $tanggal = $request->query('tanggal', today()->toDateString());
        $bulan = $request->query('bulan', now()->format('Y-m'));

        $filename = "Rekap_SIKMA_BPMP_{$tipe}_" . ($tipe === 'bulanan' ? $bulan : $tanggal) . ".csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($tipe, $tanggal, $bulan) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            if ($tipe === 'bulanan') {
                fputcsv($output, ['No', 'NIP', 'Nama Pegawai', 'Unit Kerja', 'Jumlah Hari Keluar', 'Total Keluar', 'Dinas', 'Tanpa Izin Dinas', 'Belum Kembali', 'Total Menit']);

                $pegawais = Pegawai::with(['unitKerja', 'pasanganKeluarMasuk' => function ($q) use ($bulan) {
                    $q->whereYear('jam_keluar', substr($bulan, 0, 4))
                      ->whereMonth('jam_keluar', substr($bulan, 5, 2))
                      ->with('pindaianKeluar');
                }])->where('status', 'aktif')->get();

                foreach ($pegawais as $i => $p) {
                    $sesi = $p->pasanganKeluarMasuk;
                    $hari = $sesi->pluck('jam_keluar')->map(fn($d) => $d->toDateString())->unique()->count();
                    $dinas = $sesi->filter(fn($s) => $s->pindaianKeluar?->keperluan_jenis === 'dinas')->count();
                    $tanpaIzin = $sesi->filter(fn($s) => $s->pindaianKeluar?->keperluan_jenis === 'keperluan_lain')->count();
                    $belum = $sesi->where('status', 'belum_kembali')->count();

                    fputcsv($output, [
                        $i + 1,
                        "'" . $p->nip,
                        $p->nama_lengkap,
                        $p->unitKerja?->nama_unit ?? '-',
                        $hari,
                        $sesi->count(),
                        $dinas,
                        $tanpaIzin,
                        $belum,
                        (int)$sesi->sum('durasi_menit'),
                    ]);
                }
            } else {
                fputcsv($output, ['No', 'NIP', 'Nama Pegawai', 'Unit Kerja', 'Jam Keluar', 'Jam Kembali', 'Durasi Menit', 'Keperluan', 'Status']);

                $records = PasanganKeluarMasuk::with(['pegawai.unitKerja', 'pindaianKeluar.izinDinas'])
                    ->whereDate('jam_keluar', $tanggal)
                    ->orderBy('jam_keluar', 'asc')
                    ->get();

                foreach ($records as $i => $r) {
                    fputcsv($output, [
                        $i + 1,
                        "'" . $r->pegawai?->nip,
                        $r->pegawai?->nama_lengkap,
                        $r->pegawai?->unitKerja?->nama_unit ?? '-',
                        $r->jam_keluar?->format('H:i'),
                        $r->jam_kembali ? $r->jam_kembali->format('H:i') : '-',
                        $r->durasi_menit ?? '-',
                        $r->pindaianKeluar?->keperluan_jenis === 'dinas' ? 'Dinas: ' . ($r->pindaianKeluar?->izinDinas?->tujuan ?? '') : 'Keperluan Lain',
                        $r->status,
                    ]);
                }
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function formatDurasi(int $menit): string
    {
        if ($menit <= 0) return "0 menit";
        if ($menit < 60) return "{$menit} menit";
        $jam = floor($menit / 60);
        $sisa = $menit % 60;
        return ($sisa > 0) ? "{$jam} jam {$sisa} menit" : "{$jam} jam";
    }
}
