<?php
/**
 * Auth Helper Functions - SIKMA
 * Balai Penjaminan Mutu Pendidikan (BPMP) Gorontalo
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';

/**
 * Mendapatkan URL redirect yang sesuai berdasarkan peran (role) pengguna
 * @param string $role
 * @return string
 */
function getRedirectUrlForRole(string $role): string {
    switch ($role) {
        case 'pegawai':
            return BASE_URL . '/pages/pegawai/dashboard/';
        case 'atasan':
            return BASE_URL . '/pages/atasan/izin-dinas/';
        case 'lobby':
            return BASE_URL . '/pages/lobby/layar/';
        case 'pos':
            return BASE_URL . '/pages/pos/layar/';
        case 'admin':
            return BASE_URL . '/pages/admin/dashboard/';
        case 'pimpinan':
            return BASE_URL . '/pages/pimpinan/rekap/';
        default:
            return BASE_URL . '/';
    }
}
