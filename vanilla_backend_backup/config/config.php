<?php
/**
 * Global Configuration - SIKMA
 * Balai Penjaminan Mutu Pendidikan (BPMP) Gorontalo
 * Kementerian Pendidikan Dasar dan Menengah
 */

// Timezone BPMP Gorontalo (WITA = Asia/Makassar, UTC+8)
date_default_timezone_set('Asia/Makassar');

// Application Info
if (!defined('APP_NAME')) {
    define('APP_NAME', 'SIKMA BPMP Gorontalo');
}
if (!defined('APP_VERSION')) {
    define('APP_VERSION', '1.2.0');
}
if (!defined('INSTANSI_NAME')) {
    define('INSTANSI_NAME', 'Balai Penjaminan Mutu Pendidikan Provinsi Gorontalo');
}

// Session Timeout: 8 jam idle (sesuai dokumen konsep & system design)
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 8 * 60 * 60); // 28800 detik
}

// QR Code default validity interval (30 detik)
if (!defined('QR_INTERVAL')) {
    define('QR_INTERVAL', 30);
}

// File Upload
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'foto-pegawai' . DIRECTORY_SEPARATOR);
}
if (!defined('MAX_FILE_SIZE')) {
    define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
}
if (!defined('ALLOWED_IMAGE_EXTENSIONS')) {
    define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);
}

// Base URL Detection
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Auto-detect project subfolder path if running on web server
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    
    // Find project directory relative to docRoot
    $projectDir = str_replace('\\', '/', dirname(__DIR__));
    $relativeDir = '';
    if (!empty($docRoot) && strpos($projectDir, $docRoot) === 0) {
        $relativeDir = substr($projectDir, strlen($docRoot));
    } else {
        // Fallback: detect from script name
        $parts = explode('/', trim($scriptName, '/'));
        if (!empty($parts[0])) {
            $relativeDir = '/' . $parts[0];
        }
    }
    
    $relativeDir = rtrim($relativeDir, '/');
    define('BASE_URL', $protocol . $host . $relativeDir);
}
