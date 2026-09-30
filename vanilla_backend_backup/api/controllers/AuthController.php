<?php
/**
 * Auth Controller - SIKMA
 * Balai Penjaminan Mutu Pendidikan (BPMP) Gorontalo
 * 
 * Endpoints:
 *   POST AuthController.php?action=login
 *   POST AuthController.php?action=logout
 *   GET  AuthController.php?action=check_session
 *   GET  AuthController.php?action=me
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../pages/auth/login.func.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$pdo = getDB();

switch ($action) {
    case 'login':
        handleLogin($pdo);
        break;

    case 'logout':
        handleLogout();
        break;

    case 'check_session':
        handleCheckSession();
        break;

    case 'me':
        handleMe();
        break;

    default:
        jsonResponse([
            'success' => false,
            'message' => 'Action tidak valid pada AuthController'
        ], 400);
}

/**
 * Handle proses login pengguna
 */
function handleLogin(PDO $pdo): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Metode request harus POST'], 405);
    }

    $input = getJsonInput();
    $username = trim($input['username'] ?? '');
    $password = (string)($input['password'] ?? '');

    if (empty($username) || empty($password)) {
        jsonResponse([
            'success' => false,
            'message' => 'Username dan kata sandi wajib diisi'
        ], 422);
    }

    $stmt = $pdo->prepare("
        SELECT u.*, p.nama_lengkap AS pegawai_nama, p.nip AS pegawai_nip, p.jabatan AS pegawai_jabatan, p.unit_kerja_id
        FROM users u
        LEFT JOIN pegawai p ON u.pegawai_id = p.id
        WHERE u.username = :username
        LIMIT 1
    ");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        jsonResponse([
            'success' => false,
            'message' => 'Username atau kata sandi tidak sesuai'
        ], 401);
    }

    if ($user['status'] !== 'aktif') {
        jsonResponse([
            'success' => false,
            'message' => 'Akun Anda dinonaktifkan. Silakan hubungi admin kepegawaian.'
        ], 403);
    }

    // Hindari session fixation
    session_regenerate_id(true);

    // Siapkan data user di session
    $userData = [
        'id'          => (int)$user['id'],
        'username'    => $user['username'],
        'full_name'   => $user['full_name'],
        'role'        => $user['role'],
        'pegawai_id'  => $user['pegawai_id'] ? (int)$user['pegawai_id'] : null,
        'nip'         => $user['pegawai_nip'],
        'jabatan'     => $user['pegawai_jabatan'],
        'unit_kerja_id' => $user['unit_kerja_id'] ? (int)$user['unit_kerja_id'] : null,
    ];

    $_SESSION['user'] = $userData;
    $_SESSION['LAST_ACTIVITY'] = time();

    // Catat log
    logActivity('login', 'users', $user['id'], "Login berhasil sebagai peran {$user['role']}");

    $redirectUrl = getRedirectUrlForRole($user['role']);

    jsonResponse([
        'success'      => true,
        'message'      => 'Login berhasil',
        'user'         => $userData,
        'redirect_url' => $redirectUrl,
        'csrf_token'   => generateCsrfToken()
    ]);
}

/**
 * Handle logout
 */
function handleLogout(): void {
    if (isLoggedIn()) {
        logActivity('logout', 'users', getCurrentUser()['id'] ?? null, 'Logout akun');
    }

    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();

    jsonResponse([
        'success'      => true,
        'message'      => 'Berhasil logout',
        'redirect_url' => BASE_URL . '/pages/auth/login.php'
    ]);
}

/**
 * Cek status sesi aktif saat ini
 */
function handleCheckSession(): void {
    if (isLoggedIn()) {
        $user = getCurrentUser();
        $pegawai = getCurrentPegawai();
        jsonResponse([
            'success'   => true,
            'logged_in' => true,
            'user'      => $user,
            'pegawai'   => $pegawai,
            'csrf_token' => generateCsrfToken()
        ]);
    } else {
        jsonResponse([
            'success'   => false,
            'logged_in' => false,
            'message'   => 'Belum login'
        ]);
    }
}

/**
 * Mendapatkan detail profile user & pegawai saat ini
 */
function handleMe(): void {
    requireAuth();

    $user = getCurrentUser();
    $pegawai = getCurrentPegawai();

    jsonResponse([
        'success'    => true,
        'user'       => $user,
        'pegawai'    => $pegawai,
        'csrf_token' => generateCsrfToken()
    ]);
}
