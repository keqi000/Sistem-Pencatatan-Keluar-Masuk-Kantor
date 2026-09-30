<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\ActivityLog;

class UserController extends Controller
{
    /**
     * Daftar akun pengguna
     */
    public function index(Request $request)
    {
        $query = User::with('pegawai.unitKerja');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhereHas('pegawai', function ($sub) use ($search) {
                      $sub->where('nama_lengkap', 'like', "%{$search}%")
                          ->orWhere('nip', 'like', "%{$search}%");
                  });
            });
        }

        $users = $query->orderBy('id', 'asc')->get();

        return response()->json([
            'success' => true,
            'count'   => $users->count(),
            'data'    => $users,
        ]);
    }

    /**
     * Buat akun pengguna baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'username'   => 'required|string|max:50|unique:users,username',
            'password'   => 'required|string|min:6',
            'full_name'  => 'required|string|max:150',
            'role'       => 'required|in:pegawai,atasan,lobby,pos,admin,pimpinan',
            'pegawai_id' => 'nullable|exists:pegawai,id',
        ]);

        $user = User::create([
            'username'   => $request->username,
            'password'   => Hash::make($request->password),
            'full_name'  => $request->full_name,
            'role'       => $request->role,
            'status'     => 'aktif',
            'pegawai_id' => $request->pegawai_id,
        ]);

        ActivityLog::log('create_user', 'users', $user->id, "Admin membuat akun user: {$user->username} ({$user->role})");

        return response()->json([
            'success' => true,
            'message' => 'Akun pengguna berhasil dibuat',
            'data'    => $user,
        ], 201);
    }

    /**
     * Update akun pengguna
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'username'   => 'required|string|max:50|unique:users,username,' . $id,
            'full_name'  => 'required|string|max:150',
            'role'       => 'required|in:pegawai,atasan,lobby,pos,admin,pimpinan',
            'status'     => 'nullable|in:aktif,tidak_aktif',
            'pegawai_id' => 'nullable|exists:pegawai,id',
        ]);

        $user->update([
            'username'   => $request->username,
            'full_name'  => $request->full_name,
            'role'       => $request->role,
            'status'     => $request->input('status', $user->status),
            'pegawai_id' => $request->pegawai_id,
        ]);

        ActivityLog::log('update_user', 'users', $id, "Admin memperbarui akun user: {$user->username}");

        return response()->json([
            'success' => true,
            'message' => 'Akun pengguna berhasil diperbarui',
            'data'    => $user,
        ]);
    }

    /**
     * Nonaktifkan akun pengguna
     */
    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($request->user() && $request->user()->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif',
            ], 400);
        }

        $user->update(['status' => 'tidak_aktif']);
        ActivityLog::log('deactivate_user', 'users', $id, "Admin menonaktifkan akun user: {$user->username}");

        return response()->json([
            'success' => true,
            'message' => "Akun {$user->username} berhasil dinonaktifkan",
        ]);
    }

    /**
     * Reset password pengguna
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'id'           => 'required|exists:users,id',
            'new_password' => 'required|string|min:6',
        ]);

        $user = User::findOrFail($request->id);
        $user->password = Hash::make($request->new_password);
        $user->save();

        ActivityLog::log('reset_password', 'users', $user->id, "Admin mereset kata sandi akun user #{$user->id}");

        return response()->json([
            'success' => true,
            'message' => 'Kata sandi berhasil direset',
        ]);
    }
}
