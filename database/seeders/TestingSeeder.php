<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Pegawai;
use App\Models\UnitKerja;
use App\Models\Pengaturan;

class TestingSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Pengaturan default
        $settings = [
            ['nama_instansi', 'Balai Penjaminan Mutu Pendidikan (BPMP) Provinsi Gorontalo', 'Nama resmi instansi'],
            ['kementerian', 'Kementerian Pendidikan Dasar dan Menengah', 'Nama kementerian penaung'],
            ['alamat_instansi', 'Jl. Kasmat Lahay, Desa Bulila, Kec. Telaga, Kab. Gorontalo', 'Alamat kantor balai'],
            ['jam_masuk', '07:30', 'Jam mulai dinas'],
            ['jam_pulang', '16:00', 'Jam selesai dinas'],
            ['jam_istirahat_mulai', '12:00', 'Jam mulai istirahat siang'],
            ['jam_istirahat_selesai', '13:00', 'Jam selesai istirahat siang'],
            ['hitung_jam_istirahat', '0', '1 = dihitung, 0 = tidak dihitung'],
            ['qr_interval', '30', 'Durasi masa berlaku QR dalam detik'],
            ['ambang_terlambat_menit', '120', 'Ambang batas menit di luar untuk peringatan'],
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

        // 3. Data Pegawai
        $pKepala = Pegawai::updateOrCreate(['nip' => '197505102000031001'], [
            'nama_lengkap' => 'Dr. H. Rusdianto, M.Pd.',
            'jabatan' => 'Kepala Balai BPMP Gorontalo',
            'unit_kerja_id' => $u1->id,
            'atasan_id' => null,
            'nomor_hp' => '081234567801',
            'status' => 'aktif',
        ]);

        $pAtasan = Pegawai::updateOrCreate(['nip' => '198003152005011002'], [
            'nama_lengkap' => 'Drs. Ramdan Wartabone, M.Si.',
            'jabatan' => 'Kepala Subbagian Umum',
            'unit_kerja_id' => $u1->id,
            'atasan_id' => $pKepala->id,
            'nomor_hp' => '081234567802',
            'status' => 'aktif',
        ]);

        $pAdmin = Pegawai::updateOrCreate(['nip' => '199204182019031005'], [
            'nama_lengkap' => 'Budi Santoso, S.AP.',
            'jabatan' => 'Pengadministrasi Kepegawaian',
            'unit_kerja_id' => $u1->id,
            'atasan_id' => $pAtasan->id,
            'nomor_hp' => '081234567805',
            'status' => 'aktif',
        ]);

        $pPegawai1 = Pegawai::updateOrCreate(['nip' => '198508202010011003'], [
            'nama_lengkap' => 'Andi Saputra, S.Kom.',
            'jabatan' => 'Pranata Komputer Ahli Pertama',
            'unit_kerja_id' => $u1->id,
            'atasan_id' => $pAtasan->id,
            'nomor_hp' => '081234567803',
            'status' => 'aktif',
        ]);

        $pPegawai2 = Pegawai::updateOrCreate(['nip' => '199012012015022001'], [
            'nama_lengkap' => 'Siti Rahmawati, S.Pd.',
            'jabatan' => 'Pengembang Teknologi Pembelajaran',
            'unit_kerja_id' => $u3->id,
            'atasan_id' => $pAtasan->id,
            'nomor_hp' => '081234567804',
            'status' => 'aktif',
        ]);

        $pPegawai3 = Pegawai::updateOrCreate(['nip' => '198807102012031003'], [
            'nama_lengkap' => 'Irwan Dunggio, M.Pd.',
            'jabatan' => 'Widyaprada Ahli Muda',
            'unit_kerja_id' => $u2->id,
            'atasan_id' => $pAtasan->id,
            'nomor_hp' => '081234567808',
            'status' => 'aktif',
        ]);

        // 4. Akun semua role
        $accounts = [
            [
                'username' => 'admin',
                'full_name' => 'Budi Santoso, S.AP.',
                'role' => 'admin',
                'password' => 'admin123',
                'pegawai_id' => $pAdmin->id,
            ],
            [
                'username' => 'pimpinan',
                'full_name' => 'Dr. H. Rusdianto, M.Pd.',
                'role' => 'pimpinan',
                'password' => 'pimpinan123',
                'pegawai_id' => $pKepala->id,
            ],
            [
                'username' => 'atasan',
                'full_name' => 'Drs. Ramdan Wartabone, M.Si.',
                'role' => 'atasan',
                'password' => 'atasan123',
                'pegawai_id' => $pAtasan->id,
            ],
            [
                'username' => 'pegawai1',
                'full_name' => 'Andi Saputra, S.Kom.',
                'role' => 'pegawai',
                'password' => 'pegawai123',
                'pegawai_id' => $pPegawai1->id,
            ],
            [
                'username' => 'pegawai2',
                'full_name' => 'Siti Rahmawati, S.Pd.',
                'role' => 'pegawai',
                'password' => 'pegawai123',
                'pegawai_id' => $pPegawai2->id,
            ],
            [
                'username' => 'pegawai3',
                'full_name' => 'Irwan Dunggio, M.Pd.',
                'role' => 'pegawai',
                'password' => 'pegawai123',
                'pegawai_id' => $pPegawai3->id,
            ],
            [
                'username' => 'lobby',
                'full_name' => 'Layar Lobby BPMP',
                'role' => 'lobby',
                'password' => 'lobby123',
                'pegawai_id' => null,
            ],
            [
                'username' => 'pos',
                'full_name' => 'Layar Pos Satpam',
                'role' => 'pos',
                'password' => 'pos123',
                'pegawai_id' => null,
            ],
        ];

        foreach ($accounts as $a) {
            User::updateOrCreate(
                ['username' => $a['username']],
                [
                    'full_name' => $a['full_name'],
                    'password' => Hash::make($a['password']),
                    'role' => $a['role'],
                    'status' => 'aktif',
                    'pegawai_id' => $a['pegawai_id'],
                ]
            );
        }

        // Set pimpinan_id di semua unit kerja
        $userPimpinan = User::where('username', 'pimpinan')->first();
        if ($userPimpinan) {
            UnitKerja::query()->update(['pimpinan_id' => $userPimpinan->id]);
        }

        $this->command->info('✓ TestingSeeder selesai.');
        $this->command->table(
            ['Role', 'Username', 'Password'],
            [
                ['Admin', 'admin', 'admin123'],
                ['Pimpinan', 'pimpinan', 'pimpinan123'],
                ['Atasan', 'atasan', 'atasan123'],
                ['Pegawai', 'pegawai1', 'pegawai123'],
                ['Pegawai', 'pegawai2', 'pegawai123'],
                ['Pegawai', 'pegawai3', 'pegawai123'],
                ['Lobby', 'lobby', 'lobby123'],
                ['Pos', 'pos', 'pos123'],
            ]
        );
    }
}
