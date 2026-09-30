<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pengaturan;
use App\Models\UnitKerja;
use App\Models\ActivityLog;

class PengaturanController extends Controller
{
    /**
     * Ambil pengaturan sistem
     */
    public function getJamKerja()
    {
        $settings = Pengaturan::pluck('setting_value', 'setting_key');

        return response()->json([
            'success'  => true,
            'settings' => [
                'nama_instansi'          => $settings['nama_instansi'] ?? 'Balai Penjaminan Mutu Pendidikan Provinsi Gorontalo',
                'kementerian'            => $settings['kementerian'] ?? 'Kementerian Pendidikan Dasar dan Menengah',
                'alamat_instansi'        => $settings['alamat_instansi'] ?? 'Jl. Kasmat Lahay, Gorontalo',
                'jam_masuk'              => $settings['jam_masuk'] ?? '07:30',
                'jam_pulang'             => $settings['jam_pulang'] ?? '16:00',
                'jam_istirahat_mulai'    => $settings['jam_istirahat_mulai'] ?? '12:00',
                'jam_istirahat_selesai'  => $settings['jam_istirahat_selesai'] ?? '13:00',
                'hitung_jam_istirahat'   => (int)($settings['hitung_jam_istirahat'] ?? 0),
                'qr_interval'            => (int)($settings['qr_interval'] ?? 30),
                'ambang_terlambat_menit' => (int)($settings['ambang_terlambat_menit'] ?? 120),
            ],
        ]);
    }

    /**
     * Simpan pengaturan sistem (Admin)
     */
    public function updateJamKerja(Request $request)
    {
        $allowedKeys = [
            'nama_instansi', 'kementerian', 'alamat_instansi',
            'jam_masuk', 'jam_pulang', 'jam_istirahat_mulai', 'jam_istirahat_selesai',
            'hitung_jam_istirahat', 'qr_interval', 'ambang_terlambat_menit',
        ];

        $updated = [];
        foreach ($allowedKeys as $key) {
            if ($request->has($key)) {
                $val = trim((string)$request->input($key));
                Pengaturan::set($key, $val);
                $updated[$key] = $val;
            }
        }

        ActivityLog::log('update_pengaturan', 'pengaturan', null, 'Admin memperbarui konfigurasi sistem');

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan sistem berhasil diperbarui',
            'updated' => $updated,
        ]);
    }

    /**
     * Daftar Unit Kerja
     */
    public function getUnitKerja()
    {
        $units = UnitKerja::with('pimpinan:id,full_name,username')
            ->withCount(['pegawais' => fn($q) => $q->where('status', 'aktif')])
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $units->count(),
            'data'    => $units,
        ]);
    }

    /**
     * CRUD Unit Kerja
     */
    public function crudUnitKerja(Request $request)
    {
        $subaction = $request->input('subaction', $request->input('action_type'));

        switch ($subaction) {
            case 'create':
                $request->validate([
                    'nama_unit'   => 'required|string|max:150',
                    'kode_unit'   => 'required|string|max:50|unique:unit_kerja,kode_unit',
                    'pimpinan_id' => 'nullable|exists:users,id',
                ]);

                $unit = UnitKerja::create([
                    'nama_unit'   => $request->nama_unit,
                    'kode_unit'   => strtoupper($request->kode_unit),
                    'pimpinan_id' => $request->pimpinan_id,
                ]);

                ActivityLog::log('create_unit_kerja', 'unit_kerja', $unit->id, "Menambah unit kerja: {$unit->nama_unit}");

                return response()->json([
                    'success' => true,
                    'message' => 'Unit kerja berhasil ditambahkan',
                    'data'    => $unit,
                ], 201);

            case 'update':
                $request->validate([
                    'id'          => 'required|exists:unit_kerja,id',
                    'nama_unit'   => 'required|string|max:150',
                    'kode_unit'   => 'required|string|max:50|unique:unit_kerja,kode_unit,' . $request->id,
                    'pimpinan_id' => 'nullable|exists:users,id',
                ]);

                $unit = UnitKerja::findOrFail($request->id);
                $unit->update([
                    'nama_unit'   => $request->nama_unit,
                    'kode_unit'   => strtoupper($request->kode_unit),
                    'pimpinan_id' => $request->pimpinan_id,
                ]);

                ActivityLog::log('update_unit_kerja', 'unit_kerja', $unit->id, "Memperbarui unit kerja: {$unit->nama_unit}");

                return response()->json([
                    'success' => true,
                    'message' => 'Unit kerja berhasil diperbarui',
                    'data'    => $unit,
                ]);

            case 'delete':
                $request->validate([
                    'id' => 'required|exists:unit_kerja,id',
                ]);

                $unit = UnitKerja::findOrFail($request->id);
                $count = $unit->pegawais()->count();

                if ($count > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => "Unit kerja tidak dapat dihapus karena masih menaungi {$count} pegawai.",
                    ], 400);
                }

                $unit->delete();
                ActivityLog::log('delete_unit_kerja', 'unit_kerja', $request->id, "Menghapus unit kerja #{$request->id}");

                return response()->json([
                    'success' => true,
                    'message' => 'Unit kerja berhasil dihapus',
                ]);

            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Subaction tidak valid (pilih: create, update, delete)',
                ], 400);
        }
    }
}
