<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Pegawai;
use App\Models\UnitKerja;
use App\Models\IzinDinas;
use App\Models\PasanganKeluarMasuk;
use App\Models\Pindaian;
use App\Models\QrSesaat;

class SikmaBackendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure initial data seeded
        $this->artisan('db:seed');
    }

    /**
     * Test Login
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'username' => 'pegawai',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login berhasil',
            ])
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'username', 'role', 'pegawai_id'],
                'redirect_url',
            ]);
    }

    /**
     * Test QR Current & Validate
     */
    public function test_qr_code_generation_and_validation(): void
    {
        $response = $this->getJson('/api/qr/current?jenis=keluar');
        $response->assertStatus(200)
            ->assertJson(['success' => true, 'jenis' => 'keluar']);

        $token = $response->json('token');
        $this->assertNotEmpty($token);

        $valResponse = $this->postJson('/api/qr/validate', [
            'token' => $token,
            'jenis' => 'keluar',
        ]);
        $valResponse->assertStatus(200)
            ->assertJson(['success' => true, 'valid' => true]);
    }

    /**
     * Test Izin Dinas Flow
     */
    public function test_izin_dinas_flow(): void
    {
        $pegawaiUser = User::where('username', 'pegawai')->first();
        $atasanUser = User::where('username', 'atasan')->first();

        // 1. Pegawai ajukan izin
        $ajukanResponse = $this->actingAs($pegawaiUser)
            ->postJson('/api/izin-dinas/ajukan', [
                'tanggal'               => today()->toDateString(),
                'perkiraan_jam_pergi'   => '08:30',
                'perkiraan_jam_kembali' => '11:30',
                'tujuan'                => 'Dinas Pendidikan Bone Bolango',
                'keperluan'             => 'Supervisi Mutu Sekolah',
            ]);

        $ajukanResponse->assertStatus(201)
            ->assertJson(['success' => true]);

        $izinId = $ajukanResponse->json('data.id');

        // 2. Atasan setujui izin
        $putuskanResponse = $this->actingAs($atasanUser)
            ->postJson('/api/izin-dinas/putuskan', [
                'izin_id'        => $izinId,
                'status'         => 'disetujui',
                'catatan_atasan' => 'Disetujui. Bertugas dengan amanah.',
            ]);

        $putuskanResponse->assertStatus(200)
            ->assertJson(['success' => true, 'data' => ['status' => 'disetujui']]);
    }

    /**
     * Test Full Pindaian Lifecycle (Keluar di Lobby -> Status di Luar -> Masuk di Pos)
     */
    public function test_full_pindaian_lifecycle(): void
    {
        $pegawaiUser = User::where('username', 'pegawai')->first();

        // Generate QR keluar
        $qrKeluar = $this->getJson('/api/qr/current?jenis=keluar')->json('token');

        // 1. Catat keluar di Lobby
        $keluarResponse = $this->actingAs($pegawaiUser)
            ->postJson('/api/pindaian/catat-keluar', [
                'token'           => $qrKeluar,
                'keperluan_jenis' => 'keperluan_lain',
            ]);

        $keluarResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => ['status' => 'sedang_diluar'],
            ]);

        // 2. Cek status saya
        $statusResponse = $this->actingAs($pegawaiUser)
            ->getJson('/api/pindaian/status-saya');

        $statusResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status'  => 'sedang_diluar',
            ]);

        // 3. Layar Pos Satpam memuat nama pegawai
        $posResponse = $this->getJson('/api/pindaian/pos-hari-ini');
        $posResponse->assertStatus(200)
            ->assertJson(['success' => true]);
        $this->assertGreaterThanOrEqual(1, $posResponse->json('count'));

        // 4. Catat masuk di Pos Satpam
        $qrMasuk = $this->getJson('/api/qr/current?jenis=masuk')->json('token');

        $masukResponse = $this->actingAs($pegawaiUser)
            ->postJson('/api/pindaian/catat-masuk', [
                'token' => $qrMasuk,
            ]);

        $masukResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => ['status' => 'di_kantor'],
            ]);

        // 5. Cek status saya kembali di_kantor
        $statusAfter = $this->actingAs($pegawaiUser)
            ->getJson('/api/pindaian/status-saya');

        $statusAfter->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status'  => 'di_kantor',
            ]);
    }

    /**
     * Test Dashboard Stats & Pengaturan
     */
    public function test_dashboard_and_pengaturan(): void
    {
        $adminUser = User::where('username', 'admin')->first();

        $stats = $this->actingAs($adminUser)->getJson('/api/dashboard/stats');
        $stats->assertStatus(200)->assertJson(['success' => true]);

        $chart = $this->actingAs($adminUser)->getJson('/api/dashboard/chart');
        $chart->assertStatus(200)->assertJson(['success' => true]);

        $pengaturan = $this->actingAs($adminUser)->getJson('/api/pengaturan/jam-kerja');
        $pengaturan->assertStatus(200)->assertJson(['success' => true]);
    }
}
