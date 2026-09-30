<?php
/**
 * Entry Point - SIKMA
 * Balai Penjaminan Mutu Pendidikan (BPMP) Gorontalo
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/pages/auth/login.func.php';

if (isLoggedIn()) {
    $user = getCurrentUser();
    $redirectUrl = getRedirectUrlForRole($user['role'] ?? '');
    header("Location: {$redirectUrl}");
    exit;
} else {
    header("Location: " . BASE_URL . "/pages/auth/login.php");
    exit;
}
