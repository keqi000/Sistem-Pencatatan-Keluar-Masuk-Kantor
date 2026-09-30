<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\ActivityLog;

class AuthController extends Controller
{
    /**
     * Login user
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::with('pegawai.unitKerja')
            ->where('username', $request->username)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Username atau kata sandi tidak sesuai',
            ], 401);
        }

        if ($user->status !== 'aktif') {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda dinonaktifkan. Silakan hubungi admin kepegawaian.',
            ], 403);
        }

        // Login via session & generate Sanctum token
        Auth::login($user);
        $token = $user->createToken('SIKMA_TOKEN')->plainTextToken;

        ActivityLog::log('login', 'users', $user->id, "Login berhasil sebagai peran {$user->role}");

        return response()->json([
            'success'      => true,
            'message'      => 'Login berhasil',
            'token'        => $token,
            'user'         => [
                'id'            => $user->id,
                'username'      => $user->username,
                'full_name'     => $user->full_name,
                'role'          => $user->role,
                'pegawai_id'    => $user->pegawai_id,
                'nip'           => $user->pegawai?->nip,
                'jabatan'       => $user->pegawai?->jabatan,
                'unit_kerja_id' => $user->pegawai?->unit_kerja_id,
                'nama_unit'     => $user->pegawai?->unitKerja?->nama_unit,
            ],
            'redirect_url' => $this->getRedirectUrlForRole($user->role),
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $user = $request->user() ?: Auth::user();

        if ($user) {
            ActivityLog::log('logout', 'users', $user->id, 'Logout akun');
            // Revoke current token if using Sanctum
            if (method_exists($user, 'currentAccessToken') && $user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }
        }

        Auth::logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'success'      => true,
            'message'      => 'Berhasil logout',
            'redirect_url' => '/pages/auth/login.php',
        ]);
    }

    /**
     * Cek status sesi saat ini
     */
    public function checkSession(Request $request)
    {
        $user = $request->user() ?: Auth::user();

        if ($user) {
            $user->load(['pegawai.unitKerja', 'pegawai.atasan']);
            return response()->json([
                'success'   => true,
                'logged_in' => true,
                'user'      => $user,
                'pegawai'   => $user->pegawai,
            ]);
        }

        return response()->json([
            'success'   => false,
            'logged_in' => false,
            'message'   => 'Belum login',
        ]);
    }

    /**
     * Profil user saat ini
     */
    public function me(Request $request)
    {
        $user = $request->user() ?: Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $user->load(['pegawai.unitKerja', 'pegawai.atasan']);

        return response()->json([
            'success' => true,
            'user'    => $user,
            'pegawai' => $user->pegawai,
        ]);
    }

    private function getRedirectUrlForRole(string $role): string
    {
        return match ($role) {
            'pegawai'  => '/pages/pegawai/dashboard/',
            'atasan'   => '/pages/atasan/izin-dinas/',
            'lobby'    => '/pages/lobby/layar/',
            'pos'      => '/pages/pos/layar/',
            'admin'    => '/pages/admin/dashboard/',
            'pimpinan' => '/pages/pimpinan/rekap/',
            default    => '/',
        };
    }
}
