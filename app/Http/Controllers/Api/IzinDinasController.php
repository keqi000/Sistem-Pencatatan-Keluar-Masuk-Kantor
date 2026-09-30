<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\IzinDinas;
use App\Models\User;
use App\Models\ActivityLog;

class IzinDinasController extends Controller
{
    /**
     * Daftar izin dinas milik pegawai sendiri
     */
    public function getListSaya(Request $request)
    {
        $pegawai = $request->user()?->pegawai;

        if (!$pegawai) {
            return response()->json(['success' => false, 'message' => 'Data pegawai tidak ditemukan'], 404);
        }

        $list = IzinDinas::with('atasan:id,full_name,username')
            ->where('pegawai_id', $pegawai->id)
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $list->count(),
            'data'    => $list,
        ]);
    }

    /**
     * Ajukan izin dinas oleh pegawai
     */
    public function ajukan(Request $request)
    {
        $pegawai = $request->user()?->pegawai;

        if (!$pegawai) {
            return response()->json(['success' => false, 'message' => 'Hanya akun terhubung pegawai yang dapat mengajukan izin'], 403);
        }

        $request->validate([
            'tanggal'               => 'required|date|after_or_equal:today',
            'perkiraan_jam_pergi'   => 'required',
            'perkiraan_jam_kembali' => 'required',
            'tujuan'                => 'required|string|max:255',
            'keperluan'             => 'required|string',
            'atasan_id'             => 'nullable|exists:users,id',
        ]);

        $atasanUserId = $request->atasan_id;

        if (!$atasanUserId && $pegawai->atasan_id) {
            $atasanUser = User::where('pegawai_id', $pegawai->atasan_id)->where('status', 'aktif')->first();
            $atasanUserId = $atasanUser?->id;
        }

        if (!$atasanUserId) {
            $defaultAtasan = User::whereIn('role', ['atasan', 'admin'])->where('status', 'aktif')->first();
            $atasanUserId = $defaultAtasan?->id;
        }

        if (!$atasanUserId) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ditemukan atasan aktif untuk memverifikasi izin dinas Anda',
            ], 422);
        }

        $izin = IzinDinas::create([
            'pegawai_id'            => $pegawai->id,
            'atasan_id'             => $atasanUserId,
            'tanggal'               => $request->tanggal,
            'perkiraan_jam_pergi'   => $request->perkiraan_jam_pergi,
            'perkiraan_jam_kembali' => $request->perkiraan_jam_kembali,
            'tujuan'                => $request->tujuan,
            'keperluan'             => $request->keperluan,
            'status'                => 'menunggu',
        ]);

        ActivityLog::log('ajukan_izin_dinas', 'izin_dinas', $izin->id, "Pengajuan izin dinas ke {$request->tujuan} pada {$request->tanggal}");

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan izin dinas berhasil dikirim dan menunggu persetujuan atasan.',
            'data'    => $izin,
        ], 201);
    }

    /**
     * Daftar izin dinas bawahan (Atasan / Admin)
     */
    public function getListBawahan(Request $request)
    {
        $user = $request->user();

        $query = IzinDinas::with(['pegawai.unitKerja']);

        if ($user->role === 'atasan') {
            $query->where(function ($q) use ($user) {
                $q->where('atasan_id', $user->id)
                  ->orWhereHas('pegawai', function ($sub) use ($user) {
                      $sub->where('atasan_id', $user->pegawai_id);
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $list = $query->orderByRaw("CASE WHEN status = 'menunggu' THEN 0 ELSE 1 END")
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $pendingCountQuery = IzinDinas::where('status', 'menunggu');
        if ($user->role === 'atasan') {
            $pendingCountQuery->where(function ($q) use ($user) {
                $q->where('atasan_id', $user->id)
                  ->orWhereHas('pegawai', function ($sub) use ($user) {
                      $sub->where('atasan_id', $user->pegawai_id);
                  });
            });
        }
        $pendingCount = $pendingCountQuery->count();

        return response()->json([
            'success'       => true,
            'count'         => $list->count(),
            'pending_count' => $pendingCount,
            'data'          => $list,
        ]);
    }

    /**
     * Putuskan izin dinas (Setujui / Tolak)
     */
    public function putuskan(Request $request)
    {
        $request->validate([
            'izin_id'        => 'required|exists:izin_dinas,id',
            'status'         => 'required|in:disetujui,ditolak',
            'catatan_atasan' => 'nullable|string',
        ]);

        if ($request->status === 'ditolak' && empty(trim($request->catatan_atasan ?? ''))) {
            return response()->json([
                'success' => false,
                'message' => 'Alasan penolakan izin dinas wajib diisi',
            ], 422);
        }

        $izin = IzinDinas::findOrFail($request->izin_id);
        $izin->update([
            'status'         => $request->status,
            'catatan_atasan' => $request->catatan_atasan,
        ]);

        ActivityLog::log('putuskan_izin_dinas', 'izin_dinas', $izin->id, "Memutuskan {$request->status} izin dinas #{$izin->id}");

        return response()->json([
            'success' => true,
            'message' => 'Izin dinas berhasil ' . ($request->status === 'disetujui' ? 'disetujui' : 'ditolak'),
            'data'    => $izin,
        ]);
    }

    /**
     * Mendapatkan izin dinas yang sudah disetujui untuk hari ini
     */
    public function getAktifHariIni(Request $request)
    {
        $pegawai = $request->user()?->pegawai;

        if (!$pegawai) {
            return response()->json(['success' => false, 'message' => 'Data pegawai tidak ditemukan'], 404);
        }

        $list = IzinDinas::where('pegawai_id', $pegawai->id)
            ->whereDate('tanggal', today())
            ->where('status', 'disetujui')
            ->orderBy('perkiraan_jam_pergi', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $list->count(),
            'data'    => $list,
        ]);
    }
}
