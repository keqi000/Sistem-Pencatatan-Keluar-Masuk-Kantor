<?php
/**
 * QR Controller - SIKMA
 * Balai Penjaminan Mutu Pendidikan (BPMP) Gorontalo
 * 
 * Endpoints:
 *   GET  QRController.php?action=get_current&jenis=keluar
 *   GET  QRController.php?action=get_current&jenis=masuk
 *   POST QRController.php?action=validate
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$pdo = getDB();

switch ($action) {
    case 'get_current':
        handleGetCurrent($pdo);
        break;

    case 'validate':
        handleValidate($pdo);
        break;

    default:
        jsonResponse([
            'success' => false,
            'message' => 'Action tidak valid pada QRController'
        ], 400);
}

/**
 * Generate UUID v4
 */
function generateUuidV4(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // versi 4
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // varian RFC 4122
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Mendapatkan token QR sesaat yang sedang aktif atau membuat baru
 */
function handleGetCurrent(PDO $pdo): void {
    $jenis = trim($_GET['jenis'] ?? '');
    if (!in_array($jenis, ['keluar', 'masuk'], true)) {
        jsonResponse([
            'success' => false,
            'message' => 'Parameter jenis wajib bernilai "keluar" atau "masuk"'
        ], 422);
    }

    // Durasi QR dari tabel pengaturan atau konstanta (default 30 detik)
    $interval = (int)getSetting('qr_interval', QR_INTERVAL);
    if ($interval < 10) $interval = 10;
    if ($interval > 300) $interval = 300;

    // Bersihkan token lama yang sudah expired > 5 menit lalu agar tabel tetap ringkas
    try {
        $pdo->exec("DELETE FROM qr_sesaat WHERE expired_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    } catch (Exception $e) {
        // silent
    }

    // Cek apakah ada token aktif dengan sisa waktu minimal 2 detik
    $stmt = $pdo->prepare("
        SELECT token, jenis, expired_at,
               TIMESTAMPDIFF(SECOND, NOW(), expired_at) AS remaining_seconds
        FROM qr_sesaat
        WHERE jenis = :jenis AND expired_at > DATE_ADD(NOW(), INTERVAL 2 SECOND)
        ORDER BY expired_at DESC
        LIMIT 1
    ");
    $stmt->execute(['jenis' => $jenis]);
    $current = $stmt->fetch();

    if ($current) {
        jsonResponse([
            'success'           => true,
            'token'             => $current['token'],
            'jenis'             => $current['jenis'],
            'expired_at'        => $current['expired_at'],
            'remaining_seconds' => max(0, (int)$current['remaining_seconds']),
            'interval'          => $interval,
            'server_time'       => date('Y-m-d H:i:s')
        ]);
    }

    // Jika belum ada atau hampir habis, buat token baru
    $token = generateUuidV4();
    $insertStmt = $pdo->prepare("
        INSERT INTO qr_sesaat (token, jenis, expired_at, created_at)
        VALUES (:token, :jenis, DATE_ADD(NOW(), INTERVAL :interval SECOND), NOW())
    ");
    $insertStmt->bindValue(':token', $token, PDO::PARAM_STR);
    $insertStmt->bindValue(':jenis', $jenis, PDO::PARAM_STR);
    $insertStmt->bindValue(':interval', $interval, PDO::PARAM_INT);
    $insertStmt->execute();

    // Ambil data token yang baru dibuat
    $getStmt = $pdo->prepare("
        SELECT token, jenis, expired_at,
               TIMESTAMPDIFF(SECOND, NOW(), expired_at) AS remaining_seconds
        FROM qr_sesaat
        WHERE token = :token
        LIMIT 1
    ");
    $getStmt->execute(['token' => $token]);
    $newToken = $getStmt->fetch();

    jsonResponse([
        'success'           => true,
        'token'             => $newToken['token'],
        'jenis'             => $newToken['jenis'],
        'expired_at'        => $newToken['expired_at'],
        'remaining_seconds' => max(0, (int)$newToken['remaining_seconds']),
        'interval'          => $interval,
        'server_time'       => date('Y-m-d H:i:s')
    ]);
}

/**
 * Validasi token QR sesaat
 */
function handleValidate(PDO $pdo): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Metode request harus POST'], 405);
    }

    $input = getJsonInput();
    $token = trim($input['token'] ?? '');
    $jenis = trim($input['jenis'] ?? '');

    if (empty($token)) {
        jsonResponse([
            'success' => false,
            'valid'   => false,
            'message' => 'Token QR tidak boleh kosong'
        ], 422);
    }

    $sql = "SELECT id, token, jenis, expired_at,
                   TIMESTAMPDIFF(SECOND, NOW(), expired_at) AS remaining_seconds
            FROM qr_sesaat
            WHERE token = :token";
    $params = ['token' => $token];

    if (!empty($jenis) && in_array($jenis, ['keluar', 'masuk'], true)) {
        $sql .= " AND jenis = :jenis";
        $params['jenis'] = $jenis;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $qr = $stmt->fetch();

    if (!$qr) {
        jsonResponse([
            'success' => false,
            'valid'   => false,
            'message' => 'Kode QR tidak dikenali atau salah tempat.'
        ], 404);
    }

    if ($qr['remaining_seconds'] <= 0) {
        jsonResponse([
            'success' => false,
            'valid'   => false,
            'message' => 'Kode QR sudah kedaluwarsa. Silakan pindai kode QR yang sedang tampil di layar.'
        ], 400);
    }

    jsonResponse([
        'success'           => true,
        'valid'             => true,
        'token'             => $qr['token'],
        'jenis'             => $qr['jenis'],
        'remaining_seconds' => (int)$qr['remaining_seconds'],
        'message'           => 'Kode QR valid dan aktif'
    ]);
}
