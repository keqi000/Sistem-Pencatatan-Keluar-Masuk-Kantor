<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\QrSesaat;
use App\Models\Pengaturan;
use Carbon\Carbon;

class QRController extends Controller
{
    /**
     * Mendapatkan kode QR sesaat yang aktif atau generate baru
     */
    public function getCurrent(Request $request)
    {
        $jenis = $request->query('jenis', $request->input('jenis'));

        if (!in_array($jenis, ['keluar', 'masuk'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter jenis wajib bernilai "keluar" atau "masuk"',
            ], 422);
        }

        $interval = (int)Pengaturan::get('qr_interval', 10);
        $interval = max(5, min(300, $interval));

        // Bersihkan token lama
        QrSesaat::where('expired_at', '<', now()->subMinutes(5))->delete();

        // Cari token aktif yang masih memiliki sisa waktu minimal 2 detik
        $current = QrSesaat::where('jenis', $jenis)
            ->where('expired_at', '>', now()->addSeconds(2))
            ->orderBy('expired_at', 'desc')
            ->first();

        if ($current) {
            $remaining = max(0, (int)now()->diffInSeconds($current->expired_at, false));
            return response()->json([
                'success'           => true,
                'token'             => $current->token,
                'jenis'             => $current->jenis,
                'expired_at'        => $current->expired_at->toDateTimeString(),
                'remaining_seconds' => $remaining,
                'interval'          => $interval,
                'server_time'       => now()->toDateTimeString(),
            ]);
        }

        // Buat token baru
        $token = (string)Str::uuid();
        $expiredAt = now()->addSeconds($interval);

        $newQr = QrSesaat::create([
            'token'      => $token,
            'jenis'      => $jenis,
            'expired_at' => $expiredAt,
        ]);

        return response()->json([
            'success'           => true,
            'token'             => $newQr->token,
            'jenis'             => $newQr->jenis,
            'expired_at'        => $newQr->expired_at->toDateTimeString(),
            'remaining_seconds' => $interval,
            'interval'          => $interval,
            'server_time'       => now()->toDateTimeString(),
        ]);
    }

    /**
     * Validasi kode QR
     */
    public function validateToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'jenis' => 'nullable|in:keluar,masuk',
        ]);

        $query = QrSesaat::where('token', $request->token);
        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        $qr = $query->first();

        if (!$qr) {
            return response()->json([
                'success' => false,
                'valid'   => false,
                'message' => 'Kode QR tidak dikenali atau salah tempat.',
            ], 404);
        }

        if ($qr->expired_at->isPast()) {
            return response()->json([
                'success' => false,
                'valid'   => false,
                'message' => 'Kode QR sudah kedaluwarsa. Silakan scan kode QR terbaru di layar.',
            ], 400);
        }

        return response()->json([
            'success'           => true,
            'valid'             => true,
            'token'             => $qr->token,
            'jenis'             => $qr->jenis,
            'remaining_seconds' => max(0, (int)now()->diffInSeconds($qr->expired_at, false)),
            'message'           => 'Kode QR valid dan aktif',
        ]);
    }
}
