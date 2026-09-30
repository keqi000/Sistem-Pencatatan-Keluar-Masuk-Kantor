<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['username' => 'Username atau kata sandi tidak sesuai.'])->withInput();
        }

        if ($user->status !== 'aktif') {
            return back()->withErrors(['username' => 'Akun Anda dinonaktifkan. Hubungi admin kepegawaian.'])->withInput();
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect($this->redirectForRole($user->role));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectForRole(string $role): string
    {
        return match ($role) {
            'pegawai'  => route('pegawai.dashboard'),
            'atasan'   => route('atasan.izin-dinas'),
            'lobby'    => route('lobby.layar'),
            'pos'      => route('pos.layar'),
            'admin'    => route('admin.dashboard'),
            'pimpinan' => route('pimpinan.rekap'),
            default    => '/',
        };
    }
}
