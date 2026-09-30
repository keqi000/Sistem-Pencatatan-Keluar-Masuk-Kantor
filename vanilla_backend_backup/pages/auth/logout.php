<?php
/**
 * Logout Handler - SIKMA
 * Balai Penjaminan Mutu Pendidikan (BPMP) Gorontalo
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';

if (isLoggedIn()) {
    logActivity('logout', 'users', getCurrentUser()['id'] ?? null, 'Pengguna berhasil keluar (logout)');
}

// Hancurkan session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

if (isApiRequest()) {
    jsonResponse([
        'success' => true,
        'message' => 'Berhasil logout'
    ]);
} else {
    header('Location: ' . BASE_URL . '/pages/auth/login.php');
    exit;
}
