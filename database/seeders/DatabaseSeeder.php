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
    public function run(): void
    {
        // ── 1. PENGATURAN ────────────────────────────────────────────────────────
        $settings = [
            ['nama_instansi',              'Balai Penjaminan Mutu Pendidikan (BPMP) Provinsi Gorontalo', 'Nama resmi instansi'],
            ['kementerian',                'Kementerian Pendidikan Dasar dan Menengah',                  'Nama kementerian penaung'],
            ['alamat_instansi',            'Jl. Kasmat Lahay, Desa Bulila, Kec. Telaga, Kab. Gorontalo','Alamat kantor balai'],
            ['jam_masuk',                  '07:30',  'Jam mulai dinas'],
            ['jam_pulang',                 '16:00',  'Jam selesai dinas'],
            ['jam_istirahat_mulai',        '12:00',  'Jam mulai istirahat siang'],
            ['jam_istirahat_selesai',      '13:00',  'Jam selesai istirahat siang'],
            ['hitung_jam_istirahat',       '0',      '1 = dihitung, 0 = tidak dihitung'],
            ['qr_interval',                '30',     'Durasi masa berlaku QR dalam detik'],
            ['ambang_terlambat_menit',     '120',    'Ambang batas menit di luar untuk peringatan'],
            ['jumat_aktif',                '0',      '1 = aktif jam kerja khusus Jumat'],
            ['jumat_jam_masuk',            '07:30',  'Jam masuk hari Jumat'],
            ['jumat_jam_pulang',           '11:30',  'Jam pulang hari Jumat'],
            ['jumat_jam_istirahat_mulai',  '',       'Jam istirahat mulai hari Jumat (kosong = tidak ada)'],
            ['jumat_jam_istirahat_selesai','',       'Jam istirahat selesai hari Jumat'],
            ['jumat_keterangan',           'WFA / Jam Pendek', 'Keterangan jam kerja Jumat'],
        ];

        foreach ($settings as $s) {
            Pengaturan::updateOrCreate(
                ['setting_key' => $s[0]],
                ['setting_value' => $s[1], 'keterangan' => $s[2]]
            );
        }

        // ── 2. UNIT KERJA ────────────────────────────────────────────────────────
        $u1 = UnitKerja::updateOrCreate(['kode_unit' => 'SU'],  ['nama_unit' => 'Subbagian Umum']);
        $u2 = UnitKerja::updateOrCreate(['kode_unit' => 'PMS'], ['nama_unit' => 'Pokja Penjaminan Mutu & Supervisi']);
        $u3 = UnitKerja::updateOrCreate(['kode_unit' => 'TP'],  ['nama_unit' => 'Pokja Transformasi Pembelajaran']);
        $u4 = UnitKerja::updateOrCreate(['kode_unit' => 'TKK'], ['nama_unit' => 'Pokja Tata Kelola & Kemitraan']);

        // ── 3. PEGAWAI UTAMA ─────────────────────────────────────────────────────
        $pKepala = Pegawai::updateOrCreate(['nip' => '197505102000031001'], [
            'nama_lengkap'  => 'Dr. H. Rusdianto, M.Pd.',
            'jabatan'       => 'Kepala Balai BPMP Gorontalo',
            'unit_kerja_id' => $u1->id, 'atasan_id' => null,
            'nomor_hp' => '081234567801', 'status' => 'aktif',
        ]);

        $pAtasan = Pegawai::updateOrCreate(['nip' => '198003152005011002'], [
            'nama_lengkap'  => 'Drs. Ramdan Wartabone, M.Si.',
            'jabatan'       => 'Kepala Subbagian Umum',
            'unit_kerja_id' => $u1->id, 'atasan_id' => $pKepala->id,
            'nomor_hp' => '081234567802', 'status' => 'aktif',
        ]);

        $pAdmin = Pegawai::updateOrCreate(['nip' => '199204182019031005'], [
            'nama_lengkap'  => 'Budi Santoso, S.AP.',
            'jabatan'       => 'Pengadministrasi Kepegawaian',
            'unit_kerja_id' => $u1->id, 'atasan_id' => $pAtasan->id,
            'nomor_hp' => '081234567805', 'status' => 'aktif',
        ]);

        $pPegawai1 = Pegawai::updateOrCreate(['nip' => '198508202010011003'], [
            'nama_lengkap'  => 'Andi Saputra, S.Kom.',
            'jabatan'       => 'Pranata Komputer Ahli Pertama',
            'unit_kerja_id' => $u1->id, 'atasan_id' => $pAtasan->id,
            'nomor_hp' => '081234567803', 'status' => 'aktif',
        ]);

        $pPegawai2 = Pegawai::updateOrCreate(['nip' => '199012012015022001'], [
            'nama_lengkap'  => 'Siti Rahmawati, S.Pd.',
            'jabatan'       => 'Pengembang Teknologi Pembelajaran',
            'unit_kerja_id' => $u3->id, 'atasan_id' => $pAtasan->id,
            'nomor_hp' => '081234567804', 'status' => 'aktif',
        ]);

        $pPegawai3 = Pegawai::updateOrCreate(['nip' => '198807102012031003'], [
            'nama_lengkap'  => 'Irwan Dunggio, M.Pd.',
            'jabatan'       => 'Widyaprada Ahli Muda',
            'unit_kerja_id' => $u2->id, 'atasan_id' => $pAtasan->id,
            'nomor_hp' => '081234567808', 'status' => 'aktif',
        ]);

        // ── 4. PEGAWAI EKSTRA ────────────────────────────────────────────────────
        $ekstraData = [
            ['199301012018011001', 'Hendra Monoarfa, S.E.',    'Bendahara Pengeluaran',               $u1->id, '081234567806'],
            ['199405152019022002', 'Nurhayati Pakaya, S.Pd.',  'Analis Kepegawaian',                  $u1->id, '081234567807'],
            ['199106202016042004', 'Rini Hulopi, S.Pd.',       'Widyaprada Ahli Pertama',             $u2->id, '081234567809'],
            ['198912112014031005', 'Faisal Usman, S.T.',       'Analis Data & Informasi',             $u3->id, '081234567810'],
            ['199507082020022006', 'Dewi Nento, S.Pd.',        'Pengembang Teknologi Pembelajaran',   $u3->id, '081234567811'],
            ['198604252011011007', 'Yusuf Liputo, S.Sos.',     'Analis Kebijakan Ahli Pertama',       $u4->id, '081234567812'],
            ['199208302017042008', 'Maryam Daud, S.AP.',       'Pengadministrasi Umum',               $u4->id, '081234567813'],
        ];

        $pEkstra = [];
        foreach ($ekstraData as $e) {
            $pEkstra[] = Pegawai::updateOrCreate(['nip' => $e[0]], [
                'nama_lengkap'  => $e[1], 'jabatan' => $e[2],
                'unit_kerja_id' => $e[3], 'atasan_id' => $pAtasan->id,
                'nomor_hp' => $e[4], 'status' => 'aktif',
            ]);
        }

        // ── 5. AKUN USER ─────────────────────────────────────────────────────────
        $accounts = [
            ['admin',    'Budi Santoso, S.AP.',            'admin',    'admin123',    $pAdmin->id],
            ['pimpinan', 'Dr. H. Rusdianto, M.Pd.',        'pimpinan', 'pimpinan123', $pKepala->id],
            ['atasan',   'Drs. Ramdan Wartabone, M.Si.',   'atasan',   'atasan123',   $pAtasan->id],
            ['pegawai1', 'Andi Saputra, S.Kom.',           'pegawai',  'pegawai123',  $pPegawai1->id],
            ['pegawai2', 'Siti Rahmawati, S.Pd.',          'pegawai',  'pegawai123',  $pPegawai2->id],
            ['pegawai3', 'Irwan Dunggio, M.Pd.',           'pegawai',  'pegawai123',  $pPegawai3->id],
            ['lobby',    'Layar Lobby BPMP',               'lobby',    'lobby123',    null],
            ['pos',      'Layar Pos Satpam',               'pos',      'pos123',      null],
        ];

        foreach ($accounts as [$username, $fullName, $role, $password, $pegawaiId]) {
            User::updateOrCreate(
                ['username' => $username],
                ['full_name' => $fullName, 'password' => Hash::make($password),
                 'role' => $role, 'status' => 'aktif', 'pegawai_id' => $pegawaiId]
            );
        }

        // Akun pegawai ekstra
        foreach ($pEkstra as $i => $pe) {
            User::updateOrCreate(['username' => 'pegawai' . ($i + 4)], [
                'full_name' => $pe->nama_lengkap, 'password' => Hash::make('pegawai123'),
                'role' => 'pegawai', 'status' => 'aktif', 'pegawai_id' => $pe->id,
            ]);
        }

        // Set pimpinan_id unit kerja
        $userPimpinan = User::where('username', 'pimpinan')->first();
        if ($userPimpinan) {
            UnitKerja::query()->update(['pimpinan_id' => $userPimpinan->id]);
        }

        $userAtasan = User::where('username', 'atasan')->first();
        $today      = now()->toDateString();
        $yesterday  = now()->subDay()->toDateString();

        // ── 6. IZIN DINAS ────────────────────────────────────────────────────────
        $izinData = [
            [$pPegawai1->id, $userAtasan->id, $today,                        '08:00','12:00','Dinas Pendidikan Kota Gorontalo',    'Koordinasi program PKB guru SD',              'disetujui', null],
            [$pPegawai2->id, $userAtasan->id, $today,                        '09:00','14:00','LPMP Sulawesi Utara',                 'Rapat sinkronisasi data PMP',                 'disetujui', null],
            [$pEkstra[2]->id,$userAtasan->id, $today,                        '10:00','15:00','Dinas Pendidikan Provinsi',           'Pembahasan kurikulum merdeka belajar',        'menunggu',  null],
            [$pEkstra[3]->id,$userAtasan->id, $today,                        '08:30','11:30','BPS Provinsi Gorontalo',              'Pengambilan data statistik pendidikan',       'menunggu',  null],
            [$pAdmin->id,    $userAtasan->id, $yesterday,                    '08:00','16:00','BPKP Perwakilan Gorontalo',           'Konsultasi laporan keuangan semester I',      'disetujui', 'Disetujui, harap bawa dokumen lengkap.'],
            [$pEkstra[0]->id,$userAtasan->id, $yesterday,                    '09:00','13:00','Bank BRI Cabang Gorontalo',           'Pencairan anggaran operasional',              'disetujui', null],
            [$pEkstra[1]->id,$userAtasan->id, $yesterday,                    '08:00','17:00','Jakarta',                            'Menghadiri seminar nasional',                 'ditolak',   'Tidak ada anggaran perjalanan dinas ke luar daerah bulan ini.'],
            [$pPegawai1->id, $userAtasan->id, now()->addDays(2)->toDateString(),'08:00','16:00','Universitas Negeri Gorontalo',    'Workshop pengembangan modul ajar',            'menunggu',  null],
            [$pEkstra[4]->id,$userAtasan->id, now()->addDays(3)->toDateString(),'09:00','15:00','Dinas Pendidikan Bone Bolango',   'Monitoring implementasi Kurikulum Merdeka',   'menunggu',  null],
        ];

        $izinRecords = [];
        foreach ($izinData as $iz) {
            $izinRecords[] = IzinDinas::updateOrCreate(
                ['pegawai_id' => $iz[0], 'tanggal' => $iz[2], 'tujuan' => $iz[5]],
                ['atasan_id' => $iz[1], 'perkiraan_jam_pergi' => $iz[3],
                 'perkiraan_jam_kembali' => $iz[4], 'keperluan' => $iz[6],
                 'status' => $iz[7], 'catatan_atasan' => $iz[8]]
            );
        }

        // ── 7. PINDAIAN HARI INI ─────────────────────────────────────────────────
        DB::table('pindaian')->whereDate('jam', $today)->delete();
        DB::table('pasangan_keluar_masuk')->whereDate('jam_keluar', $today)->delete();

        $skenarioHariIni = [
            // [pegawai, jam_keluar, jam_kembali|null, keperluan_jenis, izin_index|null]
            [$pPegawai1,   '07:45', '10:30', 'dinas',         0],
            [$pPegawai2,   '08:15', null,    'dinas',         1],
            [$pAdmin,      '08:30', '09:45', 'keperluan_lain',null],
            [$pEkstra[0],  '09:00', '11:15', 'keperluan_lain',null],
            [$pEkstra[1],  '09:30', null,    'keperluan_lain',null],
            [$pEkstra[2],  '10:00', null,    'dinas',         2],
            [$pEkstra[3],  '10:20', '13:00', 'keperluan_lain',null],
            [$pEkstra[4],  '11:00', null,    'dinas',         3],
            [$pEkstra[5],  '07:50', '08:30', 'keperluan_lain',null],
            [$pEkstra[6],  '13:00', null,    'keperluan_lain',null],
        ];

        $this->seedPindaian($skenarioHariIni, $izinRecords, false);

        // ── 8. PINDAIAN KEMARIN ──────────────────────────────────────────────────
        DB::table('pindaian')->whereDate('jam', $yesterday)->delete();
        DB::table('pasangan_keluar_masuk')->whereDate('jam_keluar', $yesterday)->delete();

        $skenarioKemarin = [
            [$pPegawai1,  '08:00', '12:30', 'dinas',         null],
            [$pPegawai2,  '09:15', '14:00', 'dinas',         null],
            [$pAdmin,     '08:45', '10:00', 'keperluan_lain',null],
            [$pEkstra[0], '10:00', '15:30', 'keperluan_lain',null],
            [$pEkstra[2], '08:30', '11:00', 'dinas',         null],
            [$pEkstra[4], '13:00', null,    'keperluan_lain',null],
        ];

        $this->seedPindaian($skenarioKemarin, [], true);

        // ── OUTPUT ───────────────────────────────────────────────────────────────
        $this->command->info('✓ DatabaseSeeder selesai.');
        $this->command->table(
            ['Role', 'Username', 'Password'],
            [
                ['Admin',    'admin',    'admin123'],
                ['Pimpinan', 'pimpinan', 'pimpinan123'],
                ['Atasan',   'atasan',   'atasan123'],
                ['Pegawai',  'pegawai1', 'pegawai123'],
                ['Pegawai',  'pegawai2', 'pegawai123'],
                ['Pegawai',  'pegawai3', 'pegawai123'],
                ['Pegawai',  'pegawai4~10', 'pegawai123'],
                ['Lobby',    'lobby',    'lobby123'],
                ['Pos',      'pos',      'pos123'],
            ]
        );
    }

    private function seedPindaian(array $skenarios, array $izinRecords, bool $kemarin): void
    {
        foreach ($skenarios as [$pegawai, $jamKeluar, $jamKembali, $keperluanJenis, $izinIdx]) {
            $izinId   = ($izinIdx !== null && isset($izinRecords[$izinIdx])) ? $izinRecords[$izinIdx]->id : null;
            $base     = $kemarin ? now()->subDay() : now();
            $dtKeluar = $base->copy()->setTimeFromTimeString($jamKeluar);

            $pindaianKeluar = Pindaian::create([
                'pegawai_id'      => $pegawai->id,
                'jenis'           => 'keluar',
                'jam'             => $dtKeluar,
                'tempat'          => 'lobby',
                'keperluan_jenis' => $keperluanJenis,
                'izin_dinas_id'   => $izinId,
                'pasangan_id'     => null,
            ]);

            if ($jamKembali) {
                $dtKembali   = $base->copy()->setTimeFromTimeString($jamKembali);
                $durasiMenit = (int) $dtKeluar->diffInMinutes($dtKembali);

                $pindaianMasuk = Pindaian::create([
                    'pegawai_id'  => $pegawai->id,
                    'jenis'       => 'masuk',
                    'jam'         => $dtKembali,
                    'tempat'      => 'pos',
                    'pasangan_id' => null,
                ]);

                $pasangan = PasanganKeluarMasuk::create([
                    'pegawai_id'         => $pegawai->id,
                    'pindaian_keluar_id' => $pindaianKeluar->id,
                    'jam_keluar'         => $dtKeluar,
                    'pindaian_masuk_id'  => $pindaianMasuk->id,
                    'jam_kembali'        => $dtKembali,
                    'durasi_menit'       => $durasiMenit,
                    'status'             => 'kembali',
                ]);

                $pindaianKeluar->update(['pasangan_id' => $pasangan->id]);
                $pindaianMasuk->update(['pasangan_id'  => $pasangan->id]);
            } else {
                $pasangan = PasanganKeluarMasuk::create([
                    'pegawai_id'         => $pegawai->id,
                    'pindaian_keluar_id' => $pindaianKeluar->id,
                    'jam_keluar'         => $dtKeluar,
                    'status'             => $kemarin ? 'belum_kembali' : 'terbuka',
                ]);

                $pindaianKeluar->update(['pasangan_id' => $pasangan->id]);
            }
        }
    }
}
