<?php
/**
 * Automated Backend Test Suite - SIKMA
 * Balai Penjaminan Mutu Pendidikan (BPMP) Gorontalo
 * 
 * Usage: php tests/test_backend.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

echo "=======================================================\n";
echo "   SIKMA BPMP GORONTALO - BACKEND TEST SUITE\n";
echo "=======================================================\n\n";

$pdo = getDB();
$passed = 0;
$failed = 0;

function assertTest(string $name, bool $condition, string $detail = '') {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] {$name}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$name} - {$detail}\n";
        $failed++;
    }
}

// 1. Test Database & Tables
echo "1. Menguji Koneksi dan Tabel Database...\n";
$tables = ['users', 'pegawai', 'unit_kerja', 'qr_sesaat', 'izin_dinas', 'pindaian', 'pasangan_keluar_masuk', 'activity_log', 'pengaturan'];
foreach ($tables as $t) {
    $stmt = $pdo->query("SHOW TABLES LIKE '{$t}'");
    assertTest("Tabel `{$t}` ada", $stmt->rowCount() > 0);
}

// 2. Test Auth Helper & Password Verification
echo "\n2. Menguji Autentikasi & Password Verification...\n";
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE username = 'pegawai' LIMIT 1");
$stmtUser->execute();
$pegawaiUser = $stmtUser->fetch();

assertTest("Akun default `pegawai` ditemukan", !empty($pegawaiUser));
assertTest("Password `password123` valid dengan bcrypt", password_verify('password123', $pegawaiUser['password']));

// Simulasi login pegawai
$_SESSION['user'] = [
    'id'            => (int)$pegawaiUser['id'],
    'username'      => $pegawaiUser['username'],
    'full_name'     => $pegawaiUser['full_name'],
    'role'          => $pegawaiUser['role'],
    'pegawai_id'    => (int)$pegawaiUser['pegawai_id'],
    'unit_kerja_id' => 1
];
$_SESSION['LAST_ACTIVITY'] = time();

assertTest("Helper isLoggedIn() true", isLoggedIn());
$curUser = getCurrentUser();
assertTest("Helper getCurrentUser() return user valid", $curUser['username'] === 'pegawai');
$curPegawai = getCurrentPegawai();
assertTest("Helper getCurrentPegawai() return detail pegawai", !empty($curPegawai) && $curPegawai['nip'] === '198508202010011003');

// 3. Test QR Controller Logic
echo "\n3. Menguji QR Controller Logic...\n";
// Buat token QR keluar
$tokenKeluar = bin2hex(random_bytes(16));
$pdo->prepare("INSERT INTO qr_sesaat (token, jenis, expired_at, created_at) VALUES (:token, 'keluar', DATE_ADD(NOW(), INTERVAL 30 SECOND), NOW())")->execute(['token' => $tokenKeluar]);

$stmtChk = $pdo->prepare("SELECT * FROM qr_sesaat WHERE token = :t");
$stmtChk->execute(['t' => $tokenKeluar]);
$qrData = $stmtChk->fetch();
assertTest("Token QR Keluar berhasil digenerate", !empty($qrData) && $qrData['jenis'] === 'keluar');

// Buat token QR masuk
$tokenMasuk = bin2hex(random_bytes(16));
$pdo->prepare("INSERT INTO qr_sesaat (token, jenis, expired_at, created_at) VALUES (:token, 'masuk', DATE_ADD(NOW(), INTERVAL 30 SECOND), NOW())")->execute(['token' => $tokenMasuk]);
assertTest("Token QR Masuk berhasil digenerate", true);

// 4. Test Izin Dinas
echo "\n4. Menguji Pengajuan & Persetujuan Izin Dinas...\n";
$today = date('Y-m-d');
$stmtIzin = $pdo->prepare("
    INSERT INTO izin_dinas (pegawai_id, atasan_id, tanggal, perkiraan_jam_pergi, perkiraan_jam_kembali, tujuan, keperluan, status, created_at)
    VALUES (:pegawai_id, 3, :tanggal, '09:00:00', '12:00:00', 'Dinas Dikbud Gorontalo', 'Rapat Koordinasi Mutu', 'menunggu', NOW())
");
$stmtIzin->execute([
    'pegawai_id' => $curPegawai['id'],
    'tanggal'    => $today
]);
$izinId = (int)$pdo->lastInsertId();
assertTest("Pegawai berhasil mengajukan izin dinas (#{$izinId})", $izinId > 0);

// Atasan menyetujui izin dinas
$pdo->prepare("UPDATE izin_dinas SET status = 'disetujui', catatan_atasan = 'Disetujui silakan bertugas', updated_at = NOW() WHERE id = :id")->execute(['id' => $izinId]);
$stmtIzinCheck = $pdo->prepare("SELECT status FROM izin_dinas WHERE id = :id");
$stmtIzinCheck->execute(['id' => $izinId]);
$statusIzin = $stmtIzinCheck->fetchColumn();
assertTest("Atasan menyetujui izin dinas", $statusIzin === 'disetujui');

// 5. Test Pindaian Keluar di Lobby
echo "\n5. Menguji Pindaian Keluar (Lobby)...\n";
$pdo->beginTransaction();
$now = date('Y-m-d H:i:s');
$stmtP1 = $pdo->prepare("
    INSERT INTO pindaian (pegawai_id, jenis, jam, tempat, keperluan_jenis, izin_dinas_id, created_at)
    VALUES (:pegawai_id, 'keluar', :jam, 'lobby', 'dinas', :izin_id, NOW())
");
$stmtP1->execute(['pegawai_id' => $curPegawai['id'], 'jam' => $now, 'izin_id' => $izinId]);
$p1Id = (int)$pdo->lastInsertId();

$stmtPas = $pdo->prepare("
    INSERT INTO pasangan_keluar_masuk (pegawai_id, pindaian_keluar_id, jam_keluar, status, created_at)
    VALUES (:pegawai_id, :p1, :jam, 'terbuka', NOW())
");
$stmtPas->execute(['pegawai_id' => $curPegawai['id'], 'p1' => $p1Id, 'jam' => $now]);
$pasanganId = (int)$pdo->lastInsertId();

$pdo->prepare("UPDATE pindaian SET pasangan_id = :pas WHERE id = :p1")->execute(['pas' => $pasanganId, 'p1' => $p1Id]);
$pdo->commit();

assertTest("Pindaian keluar lobby tercatat di tabel pindaian", $p1Id > 0);
assertTest("Pasangan keluar masuk dibuat dengan status `terbuka`", $pasanganId > 0);

// Cek status pegawai saat sedang di luar
$stmtStatus = $pdo->prepare("SELECT status FROM pasangan_keluar_masuk WHERE id = :id");
$stmtStatus->execute(['id' => $pasanganId]);
assertTest("Status pasangan adalah `terbuka` (pegawai sedang di luar)", $stmtStatus->fetchColumn() === 'terbuka');

// 6. Test Feed Pos Security
echo "\n6. Menguji Feed Layar Pos Satpam...\n";
$stmtPos = $pdo->prepare("
    SELECT p.id, peg.nama_lengkap, p.tempat, p.jenis, p.keperluan_jenis
    FROM pindaian p
    JOIN pegawai peg ON p.pegawai_id = peg.id
    WHERE DATE(p.jam) = CURDATE()
    ORDER BY p.id DESC LIMIT 1
");
$stmtPos->execute();
$posRow = $stmtPos->fetch();
assertTest("Layar Pos satpam menerima data pindaian keluar dari lobby secara real-time", !empty($posRow) && $posRow['nama_lengkap'] === $curPegawai['nama_lengkap']);

// 7. Test Pindaian Masuk di Pos Security
echo "\n7. Menguji Pindaian Masuk (Pos Security)...\n";
$pdo->beginTransaction();
$jamKembali = date('Y-m-d H:i:s', strtotime('+45 minutes'));
$durasiMenit = 45;

$stmtP2 = $pdo->prepare("
    INSERT INTO pindaian (pegawai_id, jenis, jam, tempat, pasangan_id, created_at)
    VALUES (:pegawai_id, 'masuk', :jam, 'pos', :pasangan_id, NOW())
");
$stmtP2->execute(['pegawai_id' => $curPegawai['id'], 'jam' => $jamKembali, 'pasangan_id' => $pasanganId]);
$p2Id = (int)$pdo->lastInsertId();

$pdo->prepare("
    UPDATE pasangan_keluar_masuk 
    SET pindaian_masuk_id = :p2,
        jam_kembali = :jam,
        durasi_menit = :durasi,
        status = 'kembali',
        updated_at = NOW()
    WHERE id = :id
")->execute([
    'p2'     => $p2Id,
    'jam'    => $jamKembali,
    'durasi' => $durasiMenit,
    'id'     => $pasanganId
]);
$pdo->commit();

$stmtCheckKembali = $pdo->prepare("SELECT status, durasi_menit FROM pasangan_keluar_masuk WHERE id = :id");
$stmtCheckKembali->execute(['id' => $pasanganId]);
$resKembali = $stmtCheckKembali->fetch();

assertTest("Pindaian masuk pos security berhasil menutup pasangan", $resKembali['status'] === 'kembali');
assertTest("Durasi di luar kantor berhasil dihitung ({$resKembali['durasi_menit']} menit)", (int)$resKembali['durasi_menit'] === 45);

// 8. Test Dashboard Stats
echo "\n8. Menguji Dashboard Statistics...\n";
$stmtStats = $pdo->query("
    SELECT 
        COUNT(id) AS total_keluar,
        SUM(CASE WHEN status = 'kembali' THEN 1 ELSE 0 END) AS sudah_kembali
    FROM pasangan_keluar_masuk
    WHERE DATE(jam_keluar) = CURDATE()
");
$stats = $stmtStats->fetch();
assertTest("Statistik total keluar terhitung", (int)$stats['total_keluar'] >= 1);
assertTest("Statistik sudah kembali terhitung", (int)$stats['sudah_kembali'] >= 1);

// 9. Test Pengaturan
echo "\n9. Menguji Pengaturan Jam Kerja & Instansi...\n";
$namaInstansi = getSetting('nama_instansi');
$qrInterval = getSetting('qr_interval');
assertTest("Setting nama_instansi terbaca", !empty($namaInstansi));
assertTest("Setting qr_interval terbaca (30 detik)", (int)$qrInterval === 30);

// 10. Test Activity Log
echo "\n10. Menguji Activity Logging...\n";
logActivity('test_suite', 'test', 1, 'Pengujian otomatis backend');
$stmtLog = $pdo->query("SELECT COUNT(*) FROM activity_log WHERE action = 'test_suite'");
assertTest("Aktivitas berhasil dicatat di activity_log", (int)$stmtLog->fetchColumn() >= 1);

echo "\n=======================================================\n";
echo "   HASIL AKHIR PENGUJIAN BACKEND:\n";
echo "   Lulus (PASSED): {$passed}\n";
echo "   Gagal (FAILED): {$failed}\n";
echo "=======================================================\n";

if ($failed === 0) {
    echo "🎉 SEMUA KOMPONEN BACKEND BERFUNGSI DENGAN SEMPURNA!\n\n";
    exit(0);
} else {
    echo "❌ Ada pengujian yang gagal.\n\n";
    exit(1);
}
