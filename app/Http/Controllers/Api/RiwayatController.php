<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PasanganKeluarMasuk;

class RiwayatController extends Controller
{
    /**
     * Riwayat pindaian keluar masuk pegawai yang sedang login
     */
    public function getSaya(Request $request)
    {
        $pegawai = $request->user()?->pegawai;

        if (!$pegawai) {
            return response()->json(['success' => false, 'message' => 'Data pegawai tidak ditemukan'], 404);
        }

        $limit = max(1, min(100, (int)$request->query('limit', 15)));

        $query = PasanganKeluarMasuk::with(['pindaianKeluar.izinDinas', 'pindaianMasuk'])
            ->where('pegawai_id', $pegawai->id);

        if ($request->filled('start_date')) {
            $query->whereDate('jam_keluar', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('jam_keluar', '<=', $request->end_date);
        }
        if ($request->filled('keperluan_jenis')) {
            $query->whereHas('pindaianKeluar', function ($q) use ($request) {
                $q->where('keperluan_jenis', $request->keperluan_jenis);
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $paginated = $query->orderBy('jam_keluar', 'desc')->paginate($limit);

        $data = collect($paginated->items())->map(function ($item) {
            $durasi = $item->status === 'terbuka'
                ? max(0, (int)now()->diffInMinutes($item->jam_keluar))
                : (int)$item->durasi_menit;

            return [
                'pasangan_id'        => $item->id,
                'jam_keluar'         => $item->jam_keluar?->toDateTimeString(),
                'jam_kembali'        => $item->jam_kembali?->toDateTimeString(),
                'durasi_menit'       => $durasi,
                'durasi_format'      => $this->formatDurasi($durasi),
                'status'             => $item->status,
                'catatan'            => $item->catatan,
                'keperluan_jenis'    => $item->pindaianKeluar?->keperluan_jenis,
                'tempat_keluar'      => $item->pindaianKeluar?->tempat,
                'tempat_masuk'       => $item->pindaianMasuk?->tempat,
                'tujuan'             => $item->pindaianKeluar?->izinDinas?->tujuan,
                'keperluan'          => $item->pindaianKeluar?->izinDinas?->keperluan,
            ];
        });

        return response()->json([
            'success'    => true,
            'page'       => $paginated->currentPage(),
            'limit'      => $paginated->perPage(),
            'total'      => $paginated->total(),
            'total_page' => $paginated->lastPage(),
            'data'       => $data,
        ]);
    }

    /**
     * Seluruh catatan keluar masuk untuk Admin dan Pimpinan
     */
    public function getAll(Request $request)
    {
        $user = $request->user();
        $limit = max(1, min(100, (int)$request->query('limit', 20)));

        $query = PasanganKeluarMasuk::with(['pegawai.unitKerja', 'pindaianKeluar.izinDinas', 'pindaianMasuk']);

        if ($user->role === 'pimpinan' && $user->pegawai?->unit_kerja_id) {
            $query->whereHas('pegawai', function ($q) use ($user) {
                $q->where('unit_kerja_id', $user->pegawai->unit_kerja_id);
            });
        } elseif ($request->filled('unit_kerja_id')) {
            $query->whereHas('pegawai', function ($q) use ($request) {
                $q->where('unit_kerja_id', $request->unit_kerja_id);
            });
        }

        if ($request->filled('pegawai_id')) {
            $query->where('pegawai_id', $request->pegawai_id);
        }
        if ($request->filled('pasangan_id')) {
            $query->where('id', $request->pasangan_id);
        }
        if ($request->filled('tanggal')) {
            $query->whereDate('jam_keluar', $request->tanggal);
        } else {
            if ($request->filled('start_date')) {
                $query->whereDate('jam_keluar', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('jam_keluar', '<=', $request->end_date);
            }
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('keperluan_jenis')) {
            $query->whereHas('pindaianKeluar', function ($q) use ($request) {
                $q->where('keperluan_jenis', $request->keperluan_jenis);
            });
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('pegawai', function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        $paginated = $query->orderBy('jam_keluar', 'desc')->paginate($limit);

        $data = collect($paginated->items())->map(function ($item) {
            $durasi = $item->status === 'terbuka'
                ? max(0, (int)now()->diffInMinutes($item->jam_keluar))
                : (int)$item->durasi_menit;

            return [
                'pasangan_id'     => $item->id,
                'pegawai_id'      => $item->pegawai_id,
                'nama_lengkap'    => $item->pegawai?->nama_lengkap,
                'nip'             => $item->pegawai?->nip,
                'jabatan'         => $item->pegawai?->jabatan,
                'foto'            => $item->pegawai?->foto,
                'nama_unit'       => $item->pegawai?->unitKerja?->nama_unit,
                'kode_unit'       => $item->pegawai?->unitKerja?->kode_unit,
                'jam_keluar'      => $item->jam_keluar?->toDateTimeString(),
                'jam_kembali'     => $item->jam_kembali?->toDateTimeString(),
                'durasi_menit'    => $durasi,
                'durasi_format'   => $this->formatDurasi($durasi),
                'status'          => $item->status,
                'catatan'         => $item->catatan,
                'keperluan_jenis' => $item->pindaianKeluar?->keperluan_jenis,
                'tempat_keluar'   => $item->pindaianKeluar?->tempat,
                'tempat_masuk'    => $item->pindaianMasuk?->tempat,
                'tujuan'          => $item->pindaianKeluar?->izinDinas?->tujuan,
                'keperluan'       => $item->pindaianKeluar?->izinDinas?->keperluan,
            ];
        });

        return response()->json([
            'success'    => true,
            'page'       => $paginated->currentPage(),
            'limit'      => $paginated->perPage(),
            'total'      => $paginated->total(),
            'total_page' => $paginated->lastPage(),
            'data'       => $data,
        ]);
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
