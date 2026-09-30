<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Pegawai;
use App\Models\User;
use App\Models\ActivityLog;

class PegawaiController extends Controller
{
    /**
     * Daftar pegawai
     */
    public function index(Request $request)
    {
        $limit = max(1, min(100, (int)$request->query('limit', 12)));
        $all = $request->boolean('all', false);

        $query = Pegawai::with(['unitKerja', 'atasan', 'user:id,username,role,pegawai_id']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('jabatan', 'like', "%{$search}%");
            });
        }

        if ($request->filled('unit_kerja_id')) {
            $query->where('unit_kerja_id', $request->unit_kerja_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $query->orderBy('unit_kerja_id', 'asc')->orderBy('nama_lengkap', 'asc');

        if ($all) {
            $data = $query->get();
            return response()->json([
                'success' => true,
                'count'   => $data->count(),
                'data'    => $data,
            ]);
        }

        $paginated = $query->paginate($limit);

        return response()->json([
            'success'    => true,
            'page'       => $paginated->currentPage(),
            'limit'      => $paginated->perPage(),
            'total'      => $paginated->total(),
            'total_page' => $paginated->lastPage(),
            'data'       => $paginated->items(),
        ]);
    }

    /**
     * Detail pegawai
     */
    public function show($id)
    {
        $pegawai = Pegawai::with(['unitKerja', 'atasan', 'user'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $pegawai,
        ]);
    }

    /**
     * Tambah pegawai baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'nip'           => 'required|string|max:30|unique:pegawai,nip',
            'nama_lengkap'  => 'required|string|max:150',
            'jabatan'       => 'required|string|max:150',
            'unit_kerja_id' => 'required|exists:unit_kerja,id',
            'atasan_id'     => 'nullable|exists:pegawai,id',
            'nomor_hp'      => 'nullable|string|max:25',
            'status'        => 'nullable|in:aktif,tidak_aktif',
            'foto'          => 'nullable|image|max:2048',
            'username'      => 'nullable|string|unique:users,username',
            'password'      => 'nullable|string|min:6',
            'role'          => 'nullable|in:pegawai,atasan,admin,pimpinan',
        ]);

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $filename = 'pegawai_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('assets/img/foto-pegawai'), $filename);
            $fotoPath = 'assets/img/foto-pegawai/' . $filename;
        }

        $result = DB::transaction(function () use ($request, $fotoPath) {
            $pegawai = Pegawai::create([
                'nip'           => $request->nip,
                'nama_lengkap'  => $request->nama_lengkap,
                'jabatan'       => $request->jabatan,
                'unit_kerja_id' => $request->unit_kerja_id,
                'atasan_id'     => $request->atasan_id,
                'nomor_hp'      => $request->nomor_hp,
                'foto'          => $fotoPath,
                'status'        => $request->input('status', 'aktif'),
            ]);

            if ($request->filled('username') && $request->filled('password')) {
                User::create([
                    'username'   => $request->username,
                    'password'   => Hash::make($request->password),
                    'full_name'  => $request->nama_lengkap,
                    'role'       => $request->input('role', 'pegawai'),
                    'status'     => 'aktif',
                    'pegawai_id' => $pegawai->id,
                ]);
            }

            ActivityLog::log('create_pegawai', 'pegawai', $pegawai->id, "Menambah pegawai: {$pegawai->nama_lengkap}");

            return $pegawai;
        });

        return response()->json([
            'success' => true,
            'message' => 'Data pegawai berhasil ditambahkan',
            'data'    => $result,
        ], 201);
    }

    /**
     * Update pegawai
     */
    public function update(Request $request, $id)
    {
        $pegawai = Pegawai::findOrFail($id);

        $request->validate([
            'nip'           => 'required|string|max:30|unique:pegawai,nip,' . $id,
            'nama_lengkap'  => 'required|string|max:150',
            'jabatan'       => 'required|string|max:150',
            'unit_kerja_id' => 'required|exists:unit_kerja,id',
            'atasan_id'     => 'nullable|exists:pegawai,id',
            'nomor_hp'      => 'nullable|string|max:25',
            'status'        => 'nullable|in:aktif,tidak_aktif',
            'foto'          => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $filename = 'pegawai_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('assets/img/foto-pegawai'), $filename);
            $pegawai->foto = 'assets/img/foto-pegawai/' . $filename;
        }

        $pegawai->update([
            'nip'           => $request->nip,
            'nama_lengkap'  => $request->nama_lengkap,
            'jabatan'       => $request->jabatan,
            'unit_kerja_id' => $request->unit_kerja_id,
            'atasan_id'     => $request->atasan_id,
            'nomor_hp'      => $request->nomor_hp,
            'status'        => $request->input('status', $pegawai->status),
        ]);

        // Sinkronisasi full_name pada akun user terkait
        User::where('pegawai_id', $id)->update(['full_name' => $request->nama_lengkap]);

        ActivityLog::log('update_pegawai', 'pegawai', $id, "Admin memperbarui data pegawai: {$pegawai->nama_lengkap}");

        return response()->json([
            'success' => true,
            'message' => 'Data pegawai berhasil diperbarui',
            'data'    => $pegawai,
        ]);
    }

    /**
     * Hapus / Nonaktifkan pegawai
     */
    public function destroy($id)
    {
        $pegawai = Pegawai::findOrFail($id);
        $hasHistory = $pegawai->pindaians()->exists();

        if ($hasHistory) {
            $pegawai->update(['status' => 'tidak_aktif']);
            User::where('pegawai_id', $id)->update(['status' => 'tidak_aktif']);

            ActivityLog::log('deactivate_pegawai', 'pegawai', $id, "Menonaktifkan pegawai: {$pegawai->nama_lengkap}");

            return response()->json([
                'success' => true,
                'message' => 'Pegawai memiliki riwayat pindaian. Status berhasil diubah menjadi tidak aktif.',
            ]);
        }

        DB::transaction(function () use ($pegawai, $id) {
            User::where('pegawai_id', $id)->delete();
            $pegawai->delete();
            ActivityLog::log('delete_pegawai', 'pegawai', $id, "Menghapus data pegawai: {$pegawai->nama_lengkap}");
        });

        return response()->json([
            'success' => true,
            'message' => 'Data pegawai berhasil dihapus permanen',
        ]);
    }
}
