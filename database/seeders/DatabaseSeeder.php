<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Pegawai;
use App\Models\UnitKerja;
use App\Models\Pengaturan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Pengaturan Default
        $settings = [
            ['nama_instansi', 'Balai Penjaminan Mutu Pendidikan (BPMP) Provinsi Gorontalo', 'Nama resmi instansi'],
            ['kementerian', 'Kementerian Pendidikan Dasar dan Menengah', 'Nama kementerian penaung'],
            ['alamat_instansi', 'Jl. Kasmat Lahay, Desa Bulila, Kec. Telaga, Kab. Gorontalo', 'Alamat kantor balai'],
            ['jam_masuk', '07:30', 'Jam mulai dinas'],
            ['jam_pulang', '16:00', 'Jam selesai dinas'],
            ['jam_istirahat_mulai', '12:00', 'Jam mulai istirahat siang'],
            ['jam_istirahat_selesai', '13:00', 'Jam selesai istirahat siang'],
            ['hitung_jam_istirahat', '0', '1 = pindaian istirahat dihitung di rekap, 0 = tidak dihitung'],
            ['qr_interval', '30', 'Durasi masa berlaku kode QR dalam detik (default 30s)'],
            ['ambang_terlambat_menit', '120', 'Ambang batas menit berada di luar kantor untuk highlight peringatan'],
        ];

        foreach ($settings as $s) {
            Pengaturan::updateOrCreate(
                ['setting_key' => $s[0]],
                ['setting_value' => $s[1], 'keterangan' => $s[2]]
            );
        }

        // 2. Unit Kerja
        $u1 = UnitKerja::updateOrCreate(['kode_unit' => 'SU'], ['nama_unit' => 'Subbagian Umum']);
        $u2 = UnitKerja::updateOrCreate(['kode_unit' => 'PMS'], ['nama_unit' => 'Pokja Penjaminan Mutu & Supervisi']);
        $u3 = UnitKerja::updateOrCreate(['kode_unit' => 'TP'], ['nama_unit' => 'Pokja Transformasi Pembelajaran']);
        $u4 = UnitKerja::updateOrCreate(['kode_unit' => 'TKK'], ['nama_unit' => 'Pokja Tata Kelola & Kemitraan']);

        // 3. Pegawai
        $p1 = Pegawai::updateOrCreate(['nip' => '197505102000031001'], [
            'nama_lengkap'  => 'Dr. H. Rusdianto, M.Pd.',
            'jabatan'       => 'Kepala Balai BPMP Gorontalo',
            'unit_kerja_id' => $u1->id,
            'atasan_id'     => null,
            'nomor_hp'      => '081234567801',
            'status'        => 'aktif',
        ]);

        $p2 = Pegawai::updateOrCreate(['nip' => '198003152005011002'], [
            'nama_lengkap'  => 'Drs. Ramdan Wartabone, M.Si.',
            'jabatan'       => 'Kepala Subbagian Umum',
            'unit_kerja_id' => $u1->id,
            'atasan_id'     => $p1->id,
            'nomor_hp'      => '081234567802',
            'status'        => 'aktif',
        ]);

        $p3 = Pegawai::updateOrCreate(['nip' => '198508202010011003'], [
            'nama_lengkap'  => 'Andi Saputra, S.Kom.',
            'jabatan'       => 'Pranata Komputer Ahli Pertama',
            'unit_kerja_id' => $u1->id,
            'atasan_id'     => $p2->id,
            'nomor_hp'      => '081234567803',
            'status'        => 'aktif',
        ]);

        $p4 = Pegawai::updateOrCreate(['nip' => '199012012015022001'], [
            'nama_lengkap'  => 'Siti Rahmawati, S.Pd.',
            'jabatan'       => 'Pengembang Teknologi Pembelajaran',
            'unit_kerja_id' => $u3->id,
            'atasan_id'     => $p2->id,
            'nomor_hp'      => '081234567804',
            'status'        => 'aktif',
        ]);

        $p5 = Pegawai::updateOrCreate(['nip' => '199204182019031005'], [
            'nama_lengkap'  => 'Budi Santoso, S.AP.',
            'jabatan'       => 'Pengadministrasi Kepegawaian',
            'unit_kerja_id' => $u1->id,
            'atasan_id'     => $p2->id,
            'nomor_hp'      => '081234567805',
            'status'        => 'aktif',
        ]);

        // 4. Users (Password: password123)
        $defaultPassword = Hash::make('password123');

        $users = [
            [
                'username'   => 'admin',
                'full_name'  => 'Admin Kepegawaian BPMP',
                'role'       => 'admin',
                'pegawai_id' => $p5->id,
            ],
            [
                'username'   => 'pimpinan',
                'full_name'  => 'Dr. H. Rusdianto, M.Pd.',
                'role'       => 'pimpinan',
                'pegawai_id' => $p1->id,
            ],
            [
                'username'   => 'atasan',
                'full_name'  => 'Drs. Ramdan Wartabone, M.Si.',
                'role'       => 'atasan',
                'pegawai_id' => $p2->id,
            ],
            [
                'username'   => 'pegawai',
                'full_name'  => 'Andi Saputra, S.Kom.',
                'role'       => 'pegawai',
                'pegawai_id' => $p3->id,
            ],
            [
                'username'   => 'pegawai2',
                'full_name'  => 'Siti Rahmawati, S.Pd.',
                'role'       => 'pegawai',
                'pegawai_id' => $p4->id,
            ],
            [
                'username'   => 'lobby',
                'full_name'  => 'Layar Lobby BPMP (QR Keluar)',
                'role'       => 'lobby',
                'pegawai_id' => null,
            ],
            [
                'username'   => 'pos',
                'full_name'  => 'Layar Pos Satpam (QR Masuk)',
                'role'       => 'pos',
                'pegawai_id' => null,
            ],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['username' => $u['username']],
                [
                    'password'   => $defaultPassword,
                    'full_name'  => $u['full_name'],
                    'role'       => $u['role'],
                    'status'     => 'aktif',
                    'pegawai_id' => $u['pegawai_id'],
                ]
            );
        }

        // Set pimpinan_id di unit kerja
        $userPimpinan = User::where('username', 'pimpinan')->first();
        if ($userPimpinan) {
            UnitKerja::query()->update(['pimpinan_id' => $userPimpinan->id]);
        }
    }
}
