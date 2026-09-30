<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Pindaian;
use App\Models\PasanganKeluarMasuk;
use App\Models\QrSesaat;
use App\Models\IzinDinas;
use App\Models\ActivityLog;

class PindaianController extends Controller
{
    /**
     * Catat pindaian keluar di Lobby
     */
    public function catatKeluar(Request $request)
    {
        $user = $request->user();
        $pegawai = $user?->pegawai;

        if (!$pegawai) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak terhubung dengan data pegawai yang aktif',
            ], 403);
        }

        $request->validate([
            'token'           => 'required|string',
            'keperluan_jenis' => 'nullable|in:dinas,keperluan_lain',
            'izin_dinas_id'   => 'nullable|integer',
        ]);

        // 1. Verifikasi QR Token Keluar
        $this->verifyQR($request->token, 'keluar');

        // 2. Mencegah pindaian keluar ganda jika sesi sebelumnya masih terbuka
        $openSession = PasanganKeluarMasuk::where('pegawai_id', $pegawai->id)
            ->where('status', 'terbuka')
            ->first();

        if ($openSession) {
            return response()->json([
                'success' => false,
                'message' => 'Anda masih memiliki catatan keluar yang belum ditutup. Harap pindai QR Masuk di pos security saat kembali.',
            ], 400);
        }

        $keperluanJenis = $request->input('keperluan_jenis', 'keperluan_lain');
        $izinDinasId = $request->input('izin_dinas_id');

        // Jika memilih dinas, verifikasi izin dinas
        if ($keperluanJenis === 'dinas') {
            if ($izinDinasId) {
                $izin = IzinDinas::where('id', $izinDinasId)
                    ->where('pegawai_id', $pegawai->id)
                    ->where('status', 'disetujui')
                    ->first();

                if (!$izin) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Izin dinas yang dipilih belum disetujui atasan. Pilih keperluan lain atau tunggu persetujuan.',
                    ], 422);
                }
            } else {
                $todayIzin = IzinDinas::where('pegawai_id', $pegawai->id)
                    ->whereDate('tanggal', today())
                    ->where('status', 'disetujui')
                    ->latest()
                    ->first();

                if ($todayIzin) {
                    $izinDinasId = $todayIzin->id;
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tidak ditemukan izin dinas yang sudah disetujui untuk hari ini. Silakan ajukan izin atau pilih keperluan lain.',
                    ], 422);
                }
            }
        } else {
            $izinDinasId = null;
        }

        // 3. Catat transaksi
        $result = DB::transaction(function () use ($pegawai, $keperluanJenis, $izinDinasId) {
            $now = now();

            $pindaian = Pindaian::create([
                'pegawai_id'      => $pegawai->id,
                'jenis'           => 'keluar',
                'jam'             => $now,
                'tempat'          => 'lobby',
                'keperluan_jenis' => $keperluanJenis,
                'izin_dinas_id'   => $izinDinasId,
            ]);

            $pasangan = PasanganKeluarMasuk::create([
                'pegawai_id'         => $pegawai->id,
                'pindaian_keluar_id' => $pindaian->id,
                'jam_keluar'         => $now,
                'status'             => 'terbuka',
            ]);

            $pindaian->update(['pasangan_id' => $pasangan->id]);

            ActivityLog::log('catat_keluar', 'pindaian', $pindaian->id, "Pegawai {$pegawai->nama_lengkap} keluar dari lobby ({$keperluanJenis})");

            return [
                'pindaian_id'     => $pindaian->id,
                'pasangan_id'     => $pasangan->id,
                'pegawai_nama'    => $pegawai->nama_lengkap,
                'jam_keluar'      => $now->toDateTimeString(),
                'keperluan_jenis' => $keperluanJenis,
                'status'          => 'sedang_diluar',
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Pindaian keluar berhasil dicatat di Lobby.',
            'data'    => $result,
        ]);
    }

    /**
     * Catat pindaian masuk di Pos Security
     */
    public function catatMasuk(Request $request)
    {
        $user = $request->user();
        $pegawai = $user?->pegawai;

        if (!$pegawai) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak terhubung dengan data pegawai yang aktif',
            ], 403);
        }

        $request->validate([
            'token' => 'required|string',
        ]);

        // 1. Verifikasi QR Token Masuk
        $this->verifyQR($request->token, 'masuk');

        // 2. Cari sesi keluar yang masih terbuka
        $openSession = PasanganKeluarMasuk::where('pegawai_id', $pegawai->id)
            ->where('status', 'terbuka')
            ->latest('id')
            ->first();

        if (!$openSession) {
            return response()->json([
                'success' => false,
                'message' => 'Pindaian masuk ditolak: Anda tidak memiliki catatan keluar dari lobby yang masih aktif.',
            ], 400);
        }

        $result = DB::transaction(function () use ($pegawai, $openSession) {
            $now = now();
            $durasiMenit = max(0, (int)$now->diffInMinutes($openSession->jam_keluar));

            $pindaianMasuk = Pindaian::create([
                'pegawai_id'  => $pegawai->id,
                'jenis'       => 'masuk',
                'jam'         => $now,
                'tempat'      => 'pos',
                'pasangan_id' => $openSession->id,
            ]);

            $openSession->update([
                'pindaian_masuk_id' => $pindaianMasuk->id,
                'jam_kembali'       => $now,
                'durasi_menit'      => $durasiMenit,
                'status'            => 'kembali',
            ]);

            ActivityLog::log('catat_masuk', 'pindaian', $pindaianMasuk->id, "Pegawai {$pegawai->nama_lengkap} kembali di pos satpam (durasi: {$durasiMenit} menit)");

            return [
                'pindaian_id'   => $pindaianMasuk->id,
                'pasangan_id'   => $openSession->id,
                'pegawai_nama'  => $pegawai->nama_lengkap,
                'jam_keluar'    => $openSession->jam_keluar->toDateTimeString(),
                'jam_kembali'   => $now->toDateTimeString(),
                'durasi_menit'  => $durasiMenit,
                'durasi_format' => $this->formatDurasi($durasiMenit),
                'status'        => 'di_kantor',
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Pindaian masuk di pos security berhasil dicatat. Selamat datang kembali di kantor!',
            'data'    => $result,
        ]);
    }

    /**
     * Status terkini pegawai yang login
     */
    public function getStatusSaya(Request $request)
    {
        $pegawai = $request->user()?->pegawai;

        if (!$pegawai) {
            return response()->json([
                'success' => false,
                'message' => 'Data pegawai tidak ditemukan',
            ], 404);
        }

        $openSession = PasanganKeluarMasuk::with(['pindaianKeluar.izinDinas'])
            ->where('pegawai_id', $pegawai->id)
            ->where('status', 'terbuka')
            ->latest('id')
            ->first();

        $status = $openSession ? 'sedang_diluar' : 'di_kantor';

        $izinHariIni = IzinDinas::where('pegawai_id', $pegawai->id)
            ->whereDate('tanggal', today())
            ->latest()
            ->first();

        $recentScans = Pindaian::with('pasangan')
            ->where('pegawai_id', $pegawai->id)
            ->whereDate('jam', today())
            ->orderBy('jam', 'desc')
            ->take(5)
            ->get();

        $durasiBerjalan = $openSession ? max(0, (int)now()->diffInMinutes($openSession->jam_keluar)) : 0;

        return response()->json([
            'success'        => true,
            'pegawai'        => [
                'id'           => $pegawai->id,
                'nip'          => $pegawai->nip,
                'nama_lengkap' => $pegawai->nama_lengkap,
                'jabatan'      => $pegawai->jabatan,
                'nama_unit'    => $pegawai->unitKerja?->nama_unit,
            ],
            'status'         => $status,
            'active_session' => $openSession ? [
                'pasangan_id'            => $openSession->id,
                'jam_keluar'             => $openSession->jam_keluar->toDateTimeString(),
                'durasi_berjalan_menit'  => $durasiBerjalan,
                'durasi_berjalan_format' => $this->formatDurasi($durasiBerjalan),
                'keperluan_jenis'        => $openSession->pindaianKeluar?->keperluan_jenis,
                'tujuan'                 => $openSession->pindaianKeluar?->izinDinas?->tujuan,
                'keperluan'              => $openSession->pindaianKeluar?->izinDinas?->keperluan,
            ] : null,
            'izin_hari_ini'  => $izinHariIni,
            'recent_scans'   => $recentScans,
            'server_time'    => now()->toDateTimeString(),
        ]);
    }

    /**
     * Realtime feed layar Pos Satpam hari ini
     */
    public function getHariIniPos(Request $request)
    {
        $sinceId = (int)$request->query('since_id', 0);

        $query = Pindaian::with(['pegawai.unitKerja', 'pasangan'])
            ->whereDate('jam', today());

        if ($sinceId > 0) {
            $query->where('id', '>', $sinceId);
        }

        $scans = $query->orderBy('jam', 'desc')
            ->take(50)
            ->get()
            ->map(function ($p) {
                return [
                    'pindaian_id'            => $p->id,
                    'jenis'                  => $p->jenis,
                    'jam'                    => $p->jam->toDateTimeString(),
                    'tempat'                 => $p->tempat,
                    'keperluan_jenis'        => $p->keperluan_jenis,
                    'nama_lengkap'           => $p->pegawai?->nama_lengkap,
                    'nip'                    => $p->pegawai?->nip,
                    'jabatan'                => $p->pegawai?->jabatan,
                    'foto'                   => $p->pegawai?->foto,
                    'nama_unit'              => $p->pegawai?->unitKerja?->nama_unit,
                    'kode_unit'              => $p->pegawai?->unitKerja?->kode_unit,
                    'pasangan_id'            => $p->pasangan_id,
                    'jam_keluar'             => $p->pasangan?->jam_keluar?->toDateTimeString(),
                    'jam_kembali'            => $p->pasangan?->jam_kembali?->toDateTimeString(),
                    'durasi_menit'           => $p->pasangan?->durasi_menit,
                    'status_pasangan'        => $p->pasangan?->status,
                    'durasi_berjalan_menit'  => $p->pasangan && $p->pasangan->status === 'terbuka'
                        ? max(0, (int)now()->diffInMinutes($p->pasangan->jam_keluar))
                        : null,
                ];
            });

        return response()->json([
            'success'     => true,
            'count'       => $scans->count(),
            'server_time' => now()->toDateTimeString(),
            'data'        => $scans,
        ]);
    }

    /**
     * Tutup manual oleh Admin
     */
    public function tutupManual(Request $request)
    {
        $request->validate([
            'pasangan_id' => 'required|exists:pasangan_keluar_masuk,id',
            'jam_kembali' => 'nullable|date',
            'status'      => 'nullable|in:kembali,belum_kembali',
            'catatan'     => 'nullable|string',
        ]);

        $pasangan = PasanganKeluarMasuk::findOrFail($request->pasangan_id);
        $jamKembali = $request->filled('jam_kembali') ? Carbon::parse($request->jam_kembali) : now();
        $status = $request->input('status', 'kembali');
        $durasiMenit = max(0, (int)$jamKembali->diffInMinutes($pasangan->jam_keluar));

        $pasangan->update([
            'jam_kembali'  => $jamKembali,
            'durasi_menit' => $durasiMenit,
            'status'       => $status,
            'catatan'      => $request->input('catatan', 'Ditutup manual oleh admin kepegawaian'),
        ]);

        ActivityLog::log('tutup_manual', 'pasangan_keluar_masuk', $pasangan->id, "Catatan ditutup manual dengan status {$status}");

        return response()->json([
            'success' => true,
            'message' => 'Catatan berhasil ditutup manual',
            'data'    => $pasangan,
        ]);
    }

    /**
     * Update catatan oleh Admin
     */
    public function update(Request $request, $id)
    {
        $pasangan = PasanganKeluarMasuk::findOrFail($id);

        $request->validate([
            'jam_keluar'      => 'nullable|date',
            'jam_kembali'     => 'nullable|date',
            'keperluan_jenis' => 'nullable|in:dinas,keperluan_lain',
            'catatan'         => 'nullable|string',
        ]);

        if ($request->filled('jam_keluar')) {
            $pasangan->jam_keluar = Carbon::parse($request->jam_keluar);
        }
        if ($request->filled('jam_kembali')) {
            $pasangan->jam_kembali = Carbon::parse($request->jam_kembali);
            $pasangan->durasi_menit = max(0, (int)$pasangan->jam_kembali->diffInMinutes($pasangan->jam_keluar));
            $pasangan->status = 'kembali';
        }
        if ($request->has('catatan')) {
            $pasangan->catatan = $request->catatan;
        }
        $pasangan->save();

        if ($request->filled('keperluan_jenis') && $pasangan->pindaian_keluar_id) {
            Pindaian::where('id', $pasangan->pindaian_keluar_id)->update(['keperluan_jenis' => $request->keperluan_jenis]);
        }

        ActivityLog::log('update_catatan', 'pasangan_keluar_masuk', $pasangan->id, "Admin mengoreksi catatan #{$pasangan->id}");

        return response()->json([
            'success' => true,
            'message' => 'Data catatan berhasil diperbarui',
            'data'    => $pasangan,
        ]);
    }

    /**
     * Hapus catatan oleh Admin
     */
    public function destroy($id)
    {
        $pasangan = PasanganKeluarMasuk::findOrFail($id);

        DB::transaction(function () use ($pasangan, $id) {
            Pindaian::where('pasangan_id', $id)->delete();
            $pasangan->delete();
            ActivityLog::log('delete_catatan', 'pasangan_keluar_masuk', $id, "Admin menghapus catatan #{$id}");
        });

        return response()->json([
            'success' => true,
            'message' => 'Catatan berhasil dihapus dari sistem',
        ]);
    }

    private function verifyQR(string $token, string $expectedJenis): void
    {
        $qr = QrSesaat::where('token', $token)->first();

        if (!$qr) {
            abort(response()->json([
                'success' => false,
                'message' => 'Kode QR tidak dikenali atau salah tempat.',
            ], 404));
        }

        if ($qr->jenis !== $expectedJenis) {
            $tempat = ($expectedJenis === 'keluar') ? 'Lobby (QR Keluar)' : 'Pos Satpam (QR Masuk)';
            abort(response()->json([
                'success' => false,
                'message' => "Kode QR ini bukan untuk pindaian {$expectedJenis}. Silakan scan kode di {$tempat}.",
            ], 400));
        }

        if ($qr->expired_at->isPast()) {
            abort(response()->json([
                'success' => false,
                'message' => 'Kode QR sudah kedaluwarsa. Silakan scan kode QR terbaru di layar.',
            ], 400));
        }
    }

    private function formatDurasi(int $menit): string
    {
        if ($menit < 60) return "{$menit} menit";
        $jam = floor($menit / 60);
        $sisa = $menit % 60;
        return ($sisa > 0) ? "{$jam} jam {$sisa} menit" : "{$jam} jam";
    }
}
