<?php
/**
 * Authentication & Security Helper - SIKMA
 * Balai Penjaminan Mutu Pendidikan (BPMP) Gorontalo
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

/**
 * Memulai session PHP dengan proteksi keamanan cookie
 */
function startSessionIfNeeded(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $cookieParams = [
            'lifetime' => SESSION_TIMEOUT,
            'path'     => '/',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax'
        ];
        
        session_name('SIKMA_SESSION_ID');
        session_set_cookie_params($cookieParams);
        session_start();
    }

    // Cek idle session timeout (8 jam)
    if (isset($_SESSION['LAST_ACTIVITY'])) {
        if (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            session_start();
        }
    }
    $_SESSION['LAST_ACTIVITY'] = time();
}

// Inisialisasi session otomatis
startSessionIfNeeded();

/**
 * Cek apakah user sedang login
 * @return bool
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

/**
 * Mendapatkan data user yang sedang login
 * @return array|null
 */
function getCurrentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Mendapatkan data pegawai lengkap dari user yang sedang login
 * @return array|null
 */
function getCurrentPegawai(): ?array {
    $user = getCurrentUser();
    if (!$user || empty($user['pegawai_id'])) {
        return null;
    }

    static $cachedPegawai = null;
    if ($cachedPegawai !== null && $cachedPegawai['id'] == $user['pegawai_id']) {
        return $cachedPegawai;
    }

    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT p.*, u.nama_unit, u.kode_unit, 
                   atasan.nama_lengkap AS nama_atasan, atasan.nip AS nip_atasan
            FROM pegawai p
            LEFT JOIN unit_kerja u ON p.unit_kerja_id = u.id
            LEFT JOIN pegawai atasan ON p.atasan_id = atasan.id
            WHERE p.id = :id AND p.status = 'aktif'
            LIMIT 1
        ");
        $stmt->execute(['id' => $user['pegawai_id']]);
        $cachedPegawai = $stmt->fetch() ?: null;
        return $cachedPegawai;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Proteksi otentikasi (wajib login)
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        if (isApiRequest()) {
            jsonResponse([
                'success' => false,
                'message' => 'Autentikasi dibutuhkan. Sesi Anda telah berakhir atau belum login.'
            ], 401);
        } else {
            $redirect = BASE_URL . '/pages/auth/login.php';
            header("Location: {$redirect}");
            exit;
        }
    }
}

/**
 * Proteksi otorisasi berdasarkan role
 * @param string|array $roles Role yang diperbolehkan (misal 'admin' atau ['admin', 'pimpinan'])
 */
function requireRole($roles): void {
    requireAuth();

    $user = getCurrentUser();
    $userRole = $user['role'] ?? '';

    $allowedRoles = is_array($roles) ? $roles : [$roles];

    if (!in_array($userRole, $allowedRoles, true)) {
        if (isApiRequest()) {
            jsonResponse([
                'success' => false,
                'message' => "Akses ditolak. Fitur ini hanya untuk peran: " . implode(', ', $allowedRoles)
            ], 403);
        } else {
            http_response_code(403);
            die("<h1>403 Forbidden</h1><p>Akses ditolak. Anda tidak memiliki hak akses untuk halaman ini.</p><a href='" . BASE_URL . "'>Kembali ke Beranda</a>");
        }
    }
}

/**
 * Cek apakah request saat ini adalah API request
 * @return bool
 */
function isApiRequest(): bool {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    return (
        strpos($uri, '/api/') !== false ||
        strpos($accept, 'application/json') !== false ||
        strpos($contentType, 'application/json') !== false ||
        (isset($_GET['action']) && strpos($uri, 'Controller.php') !== false)
    );
}

/**
 * Kirim response JSON terstandarisasi
 * @param mixed $data
 * @param int $statusCode
 */
function jsonResponse($data, int $statusCode = 200): void {
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Membaca payload JSON input atau request body
 * @return array
 */
function getJsonInput(): array {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            return $json;
        }
    }
    return $_POST ?: [];
}

/**
 * Generate CSRF Token
 * @return string
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validasi CSRF Token
 * @param string|null $token
 * @return bool
 */
function validateCsrfToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Catat aktivitas user ke tabel activity_log
 * @param string $action
 * @param string|null $targetTable
 * @param int|null $targetId
 * @param string|null $keterangan
 */
function logActivity(string $action, ?string $targetTable = null, ?int $targetId = null, ?string $keterangan = null): void {
    try {
        $pdo = getDB();
        $user = getCurrentUser();
        $userId = $user['id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, action, target_table, target_id, keterangan, ip_address, created_at)
            VALUES (:user_id, :action, :target_table, :target_id, :keterangan, :ip_address, NOW())
        ");
        $stmt->execute([
            'user_id'      => $userId,
            'action'       => $action,
            'target_table' => $targetTable,
            'target_id'    => $targetId,
            'keterangan'   => $keterangan,
            'ip_address'   => $ip
        ]);
    } catch (Exception $e) {
        // Silent error logging so main business transaction isn't broken
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Mendapatkan nilai pengaturan dari tabel pengaturan
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function getSetting(string $key, $default = null) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT setting_value FROM pengaturan WHERE setting_key = :key LIMIT 1");
        $stmt->execute(['key' => $key]);
        $val = $stmt->fetchColumn();
        return ($val !== false) ? $val : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Mengupdate nilai pengaturan pada tabel pengaturan
 * @param string $key
 * @param string $value
 * @param string|null $keterangan
 * @return bool
 */
function updateSetting(string $key, string $value, ?string $keterangan = null): bool {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            INSERT INTO pengaturan (setting_key, setting_value, keterangan, updated_at)
            VALUES (:key, :val, :ket, NOW())
            ON DUPLICATE KEY UPDATE setting_value = :val2, keterangan = COALESCE(:ket2, keterangan), updated_at = NOW()
        ");
        return $stmt->execute([
            'key'  => $key,
            'val'  => $value,
            'ket'  => $keterangan,
            'val2' => $value,
            'ket2' => $keterangan
        ]);
    } catch (Exception $e) {
        return false;
    }
}
