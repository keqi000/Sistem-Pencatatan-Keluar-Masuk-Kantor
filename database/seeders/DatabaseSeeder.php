<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Pegawai;
use App\Models\UnitKerja;
use App\Models\Pengaturan;
use App\Models\IzinDinas;
use App\Models\Pindaian;
use App\Models\PasanganKeluarMasuk;

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

        // Tambah pegawai ekstra agar tampilan lebih penuh
        $pegawaiEkstra = [
            ['199301012018011001', 'Hendra Monoarfa, S.E.',       'Bendahara Pengeluaran',          $u1->id, $p2->id, '081234567806'],
            ['199405152019022002', 'Nurhayati Pakaya, S.Pd.',     'Analis Kepegawaian',              $u1->id, $p2->id, '081234567807'],
            ['198807102012031003', 'Irwan Dunggio, M.Pd.',        'Widyaprada Ahli Muda',            $u2->id, $p2->id, '081234567808'],
            ['199106202016042004', 'Rini Hulopi, S.Pd.',          'Widyaprada Ahli Pertama',         $u2->id, $p2->id, '081234567809'],
            ['198912112014031005', 'Faisal Usman, S.T.',          'Analis Data & Informasi',         $u3->id, $p2->id, '081234567810'],
            ['199507082020022006', 'Dewi Nento, S.Pd.',           'Pengembang Teknologi Pembelajaran',$u3->id, $p2->id, '081234567811'],
            ['198604252011011007', 'Yusuf Liputo, S.Sos.',        'Analis Kebijakan Ahli Pertama',   $u4->id, $p2->id, '081234567812'],
            ['199208302017042008', 'Maryam Daud, S.AP.',          'Pengadministrasi Umum',           $u4->id, $p2->id, '081234567813'],
        ];

        $pEkstra = [];
        foreach ($pegawaiEkstra as $pe) {
            $pEkstra[] = Pegawai::updateOrCreate(['nip' => $pe[0]], [
                'nama_lengkap'  => $pe[1],
                'jabatan'       => $pe[2],
                'unit_kerja_id' => $pe[3],
                'atasan_id'     => $pe[4],
                'nomor_hp'      => $pe[5],
                'status'        => 'aktif',
            ]);
        }

        // Tambah user pegawai ekstra
        foreach ($pEkstra as $i => $pe) {
            User::updateOrCreate(['username' => 'pegawai' . ($i + 3)], [
                'password'   => $defaultPassword,
                'full_name'  => $pe->nama_lengkap,
                'role'       => 'pegawai',
                'status'     => 'aktif',
                'pegawai_id' => $pe->id,
            ]);
        }

        // Semua pegawai aktif (untuk seed pindaian)
        $semuaPegawai = Pegawai::where('status', 'aktif')->get();
        $userAtasan   = User::where('username', 'atasan')->first();
        $today        = now()->toDateString();
        $yesterday    = now()->subDay()->toDateString();

        // ── IZIN DINAS ──────────────────────────────────────────────────────────
        $izinData = [
            // hari ini - disetujui
            [$p3->id, $userAtasan->id, $today,     '08:00', '12:00', 'Dinas Pendidikan Kota Gorontalo',  'Koordinasi program PKB guru SD',          'disetujui', null],
            [$p4->id, $userAtasan->id, $today,     '09:00', '14:00', 'LPMP Sulawesi Utara',              'Rapat sinkronisasi data PMP',             'disetujui', null],
            // hari ini - menunggu
            [$pEkstra[2]->id, $userAtasan->id, $today, '10:00', '15:00', 'Dinas Pendidikan Provinsi', 'Pembahasan kurikulum merdeka belajar',    'menunggu',  null],
            [$pEkstra[4]->id, $userAtasan->id, $today, '08:30', '11:30', 'BPS Provinsi Gorontalo',    'Pengambilan data statistik pendidikan',   'menunggu',  null],
            // kemarin - disetujui
            [$p5->id,         $userAtasan->id, $yesterday, '08:00', '16:00', 'BPKP Perwakilan Gorontalo', 'Konsultasi laporan keuangan semester I', 'disetujui', 'Disetujui, harap bawa dokumen lengkap.'],
            [$pEkstra[0]->id, $userAtasan->id, $yesterday, '09:00', '13:00', 'Bank BRI Cabang Gorontalo', 'Pencairan anggaran operasional',         'disetujui', null],
            // kemarin - ditolak
            [$pEkstra[1]->id, $userAtasan->id, $yesterday, '08:00', '17:00', 'Jakarta',                   'Menghadiri seminar nasional',            'ditolak',   'Tidak ada anggaran perjalanan dinas ke luar daerah bulan ini.'],
            // masa depan - menunggu
            [$p3->id, $userAtasan->id, now()->addDays(2)->toDateString(), '08:00', '16:00', 'Universitas Negeri Gorontalo', 'Workshop pengembangan modul ajar', 'menunggu', null],
            [$pEkstra[5]->id, $userAtasan->id, now()->addDays(3)->toDateString(), '09:00', '15:00', 'Dinas Pendidikan Bone Bolango', 'Monitoring implementasi Kurikulum Merdeka', 'menunggu', null],
        ];

        $izinRecords = [];
        foreach ($izinData as $iz) {
            $izinRecords[] = IzinDinas::updateOrCreate(
                ['pegawai_id' => $iz[0], 'tanggal' => $iz[2], 'tujuan' => $iz[5]],
                [
                    'atasan_id'             => $iz[1],
                    'perkiraan_jam_pergi'   => $iz[3],
                    'perkiraan_jam_kembali' => $iz[4],
                    'keperluan'             => $iz[6],
                    'status'                => $iz[7],
                    'catatan_atasan'        => $iz[8],
                ]
            );
        }

        // ── PINDAIAN & PASANGAN HARI INI ────────────────────────────────────────
        // Hapus data hari ini dulu agar tidak duplikat saat re-seed
        DB::table('pindaian')->whereDate('jam', $today)->delete();
        DB::table('pasangan_keluar_masuk')->whereDate('jam_keluar', $today)->delete();

        $skenario = [
            // [pegawai, jam_keluar, jam_kembali|null, keperluan_jenis, izin_index|null]
            [$p3,           '07:45', '10:30', 'dinas',        0],  // sudah kembali, pakai izin[0]
            [$p4,           '08:15', null,    'dinas',        1],  // masih di luar, pakai izin[1]
            [$p5,           '08:30', '09:45', 'keperluan_lain', null], // sudah kembali
            [$pEkstra[0],   '09:00', '11:15', 'keperluan_lain', null], // sudah kembali
            [$pEkstra[1],   '09:30', null,    'keperluan_lain', null], // masih di luar
            [$pEkstra[2],   '10:00', null,    'dinas',        2],  // masih di luar, izin menunggu
            [$pEkstra[3],   '10:20', '13:00', 'keperluan_lain', null], // sudah kembali
            [$pEkstra[4],   '11:00', null,    'dinas',        3],  // masih di luar, izin menunggu
            [$pEkstra[6],   '07:50', '08:30', 'keperluan_lain', null], // sudah kembali cepat
            [$pEkstra[7],   '13:00', null,    'keperluan_lain', null], // masih di luar (siang)
        ];

        foreach ($skenario as $s) {
            [$pegawai, $jamKeluar, $jamKembali, $keperluanJenis, $izinIdx] = $s;

            $izinId    = ($izinIdx !== null && isset($izinRecords[$izinIdx])) ? $izinRecords[$izinIdx]->id : null;
            $dtKeluar  = now()->setTimeFromTimeString($jamKeluar);

            // Buat pindaian keluar
            $pindaianKeluar = Pindaian::create([
                'pegawai_id'     => $pegawai->id,
                'jenis'          => 'keluar',
                'jam'            => $dtKeluar,
                'tempat'         => 'lobby',
                'keperluan_jenis'=> $keperluanJenis,
                'izin_dinas_id'  => $izinId,
                'pasangan_id'    => null,
            ]);

            if ($jamKembali) {
                $dtKembali    = now()->setTimeFromTimeString($jamKembali);
                $durasiMenit  = (int) $dtKeluar->diffInMinutes($dtKembali);

                // Buat pindaian masuk
                $pindaianMasuk = Pindaian::create([
                    'pegawai_id'     => $pegawai->id,
                    'jenis'          => 'masuk',
                    'jam'            => $dtKembali,
                    'tempat'         => 'pos',
                    'keperluan_jenis'=> null,
                    'izin_dinas_id'  => null,
                    'pasangan_id'    => null,
                ]);

                // Buat pasangan
                $pasangan = PasanganKeluarMasuk::create([
                    'pegawai_id'        => $pegawai->id,
                    'pindaian_keluar_id'=> $pindaianKeluar->id,
                    'jam_keluar'        => $dtKeluar,
                    'pindaian_masuk_id' => $pindaianMasuk->id,
                    'jam_kembali'       => $dtKembali,
                    'durasi_menit'      => $durasiMenit,
                    'status'            => 'kembali',
                ]);

                // Update pasangan_id di kedua pindaian
                $pindaianKeluar->update(['pasangan_id' => $pasangan->id]);
                $pindaianMasuk->update(['pasangan_id'  => $pasangan->id]);
            } else {
                // Masih di luar — buat pasangan terbuka
                $pasangan = PasanganKeluarMasuk::create([
                    'pegawai_id'        => $pegawai->id,
                    'pindaian_keluar_id'=> $pindaianKeluar->id,
                    'jam_keluar'        => $dtKeluar,
                    'pindaian_masuk_id' => null,
                    'jam_kembali'       => null,
                    'durasi_menit'      => null,
                    'status'            => 'terbuka',
                ]);

                $pindaianKeluar->update(['pasangan_id' => $pasangan->id]);
            }
        }

        // ── PINDAIAN KEMARIN (untuk rekap) ──────────────────────────────────────
        DB::table('pindaian')->whereDate('jam', $yesterday)->delete();
        DB::table('pasangan_keluar_masuk')->whereDate('jam_keluar', $yesterday)->delete();

        $skenariosKemarin = [
            [$p3,         '08:00', '12:30', 'dinas',         null],
            [$p4,         '09:15', '14:00', 'dinas',         null],
            [$p5,         '08:45', '10:00', 'keperluan_lain',null],
            [$pEkstra[0], '10:00', '15:30', 'keperluan_lain',null],
            [$pEkstra[2], '08:30', '11:00', 'dinas',         null],
            [$pEkstra[5], '13:00', null,    'keperluan_lain',null], // belum kembali kemarin
        ];

        foreach ($skenariosKemarin as $s) {
            [$pegawai, $jamKeluar, $jamKembali, $keperluanJenis, $izinId] = $s;

            $dtKeluar = now()->subDay()->setTimeFromTimeString($jamKeluar);

            $pindaianKeluar = Pindaian::create([
                'pegawai_id'     => $pegawai->id,
                'jenis'          => 'keluar',
                'jam'            => $dtKeluar,
                'tempat'         => 'lobby',
                'keperluan_jenis'=> $keperluanJenis,
                'izin_dinas_id'  => $izinId,
                'pasangan_id'    => null,
            ]);

            if ($jamKembali) {
                $dtKembali   = now()->subDay()->setTimeFromTimeString($jamKembali);
                $durasiMenit = (int) $dtKeluar->diffInMinutes($dtKembali);

                $pindaianMasuk = Pindaian::create([
                    'pegawai_id'     => $pegawai->id,
                    'jenis'          => 'masuk',
                    'jam'            => $dtKembali,
                    'tempat'         => 'pos',
                    'keperluan_jenis'=> null,
                    'izin_dinas_id'  => null,
                    'pasangan_id'    => null,
                ]);

                $pasangan = PasanganKeluarMasuk::create([
                    'pegawai_id'        => $pegawai->id,
                    'pindaian_keluar_id'=> $pindaianKeluar->id,
                    'jam_keluar'        => $dtKeluar,
                    'pindaian_masuk_id' => $pindaianMasuk->id,
                    'jam_kembali'       => $dtKembali,
                    'durasi_menit'      => $durasiMenit,
                    'status'            => 'kembali',
                ]);

                $pindaianKeluar->update(['pasangan_id' => $pasangan->id]);
                $pindaianMasuk->update(['pasangan_id'  => $pasangan->id]);
            } else {
                $pasangan = PasanganKeluarMasuk::create([
                    'pegawai_id'        => $pegawai->id,
                    'pindaian_keluar_id'=> $pindaianKeluar->id,
                    'jam_keluar'        => $dtKeluar,
                    'pindaian_masuk_id' => null,
                    'jam_kembali'       => null,
                    'durasi_menit'      => null,
                    'status'            => 'belum_kembali',
                ]);

                $pindaianKeluar->update(['pasangan_id' => $pasangan->id]);
            }
        }
    }
}
