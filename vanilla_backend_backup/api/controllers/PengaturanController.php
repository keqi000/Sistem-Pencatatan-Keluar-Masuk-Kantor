<?php
/**
 * Pengaturan Controller - SIKMA
 * Balai Penjaminan Mutu Pendidikan (BPMP) Gorontalo
 * 
 * Endpoints:
 *   GET  PengaturanController.php?action=get_jam_kerja
 *   POST PengaturanController.php?action=update_jam_kerja
 *   GET  PengaturanController.php?action=get_unit_kerja
 *   POST PengaturanController.php?action=crud_unit_kerja
 *   GET  PengaturanController.php?action=get_settings
 *   POST PengaturanController.php?action=update_settings
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$pdo = getDB();

switch ($action) {
    case 'get_jam_kerja':
    case 'get_settings':
        handleGetSettings($pdo);
        break;

    case 'update_jam_kerja':
    case 'update_settings':
        handleUpdateSettings($pdo);
        break;

    case 'get_unit_kerja':
        handleGetUnitKerja($pdo);
        break;

    case 'crud_unit_kerja':
        handleCrudUnitKerja($pdo);
        break;

    default:
        jsonResponse([
            'success' => false,
            'message' => 'Action tidak valid pada PengaturanController'
        ], 400);
}

/**
 * Mendapatkan seluruh konfigurasi sistem (jam kerja, qr interval, instansi)
 */
function handleGetSettings(PDO $pdo): void {
    requireRole(['admin', 'pimpinan']);

    $stmt = $pdo->query("SELECT setting_key, setting_value, keterangan FROM pengaturan");
    $rows = $stmt->fetchAll();

    $settings = [];
    foreach ($rows as $r) {
        $settings[$r['setting_key']] = $r['setting_value'];
    }

    jsonResponse([
        'success'  => true,
        'settings' => [
            'nama_instansi'          => $settings['nama_instansi'] ?? 'Balai Penjaminan Mutu Pendidikan Provinsi Gorontalo',
            'kementerian'            => $settings['kementerian'] ?? 'Kementerian Pendidikan Dasar dan Menengah',
            'alamat_instansi'        => $settings['alamat_instansi'] ?? 'Jl. Kasmat Lahay, Gorontalo',
            'jam_masuk'              => $settings['jam_masuk'] ?? '07:30',
            'jam_pulang'             => $settings['jam_pulang'] ?? '16:00',
            'jam_istirahat_mulai'    => $settings['jam_istirahat_mulai'] ?? '12:00',
            'jam_istirahat_selesai'  => $settings['jam_istirahat_selesai'] ?? '13:00',
            'hitung_jam_istirahat'   => (int)($settings['hitung_jam_istirahat'] ?? 0),
            'qr_interval'            => (int)($settings['qr_interval'] ?? 30),
            'ambang_terlambat_menit' => (int)($settings['ambang_terlambat_menit'] ?? 120),
        ]
    ]);
}

/**
 * Update pengaturan sistem (Admin)
 */
function handleUpdateSettings(PDO $pdo): void {
    requireRole('admin');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Metode request harus POST'], 405);
    }

    $input = getJsonInput();

    $allowedKeys = [
        'nama_instansi', 'kementerian', 'alamat_instansi',
        'jam_masuk', 'jam_pulang', 'jam_istirahat_mulai', 'jam_istirahat_selesai',
        'hitung_jam_istirahat', 'qr_interval', 'ambang_terlambat_menit'
    ];

    $updated = [];
    foreach ($allowedKeys as $key) {
        if (isset($input[$key])) {
            $val = trim((string)$input[$key]);
            updateSetting($key, $val);
            $updated[$key] = $val;
        }
    }

    logActivity('update_pengaturan', 'pengaturan', null, 'Admin memperbarui konfigurasi sistem');

    jsonResponse([
        'success' => true,
        'message' => 'Pengaturan sistem berhasil diperbarui',
        'updated' => $updated
    ]);
}

/**
 * Daftar Unit Kerja beserta nama pimpinan dan jumlah pegawai
 */
function handleGetUnitKerja(PDO $pdo): void {
    requireRole(['admin', 'pimpinan', 'pegawai', 'atasan']);

    $stmt = $pdo->query("
        SELECT u.*, 
               pmp.full_name AS nama_pimpinan,
               COUNT(p.id) AS jumlah_pegawai
        FROM unit_kerja u
        LEFT JOIN users pmp ON u.pimpinan_id = pmp.id
        LEFT JOIN pegawai p ON p.unit_kerja_id = u.id AND p.status = 'aktif'
        GROUP BY u.id
        ORDER BY u.id ASC
    ");
    $rows = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'count'   => count($rows),
        'data'    => $rows
    ]);
}

/**
 * CRUD Unit Kerja (Admin)
 */
function handleCrudUnitKerja(PDO $pdo): void {
    requireRole('admin');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Metode request harus POST'], 405);
    }

    $input = getJsonInput();
    $subaction = trim($input['subaction'] ?? ($input['action_type'] ?? ''));

    switch ($subaction) {
        case 'create':
            $namaUnit = trim($input['nama_unit'] ?? '');
            $kodeUnit = strtoupper(trim($input['kode_unit'] ?? ''));
            $pimpinanId = !empty($input['pimpinan_id']) ? (int)$input['pimpinan_id'] : null;

            if (empty($namaUnit) || empty($kodeUnit)) {
                jsonResponse(['success' => false, 'message' => 'Nama Unit dan Kode Unit wajib diisi'], 422);
            }

            // Cek keunikan kode_unit
            $chk = $pdo->prepare("SELECT id FROM unit_kerja WHERE kode_unit = :kode LIMIT 1");
            $chk->execute(['kode' => $kodeUnit]);
            if ($chk->fetch()) {
                jsonResponse(['success' => false, 'message' => "Kode unit {$kodeUnit} sudah terdaftar"], 422);
            }

            $stmt = $pdo->prepare("INSERT INTO unit_kerja (nama_unit, kode_unit, pimpinan_id, created_at) VALUES (:nama, :kode, :pimpinan, NOW())");
            $stmt->execute(['nama' => $namaUnit, 'kode' => $kodeUnit, 'pimpinan' => $pimpinanId]);
            $newId = (int)$pdo->lastInsertId();

            logActivity('create_unit_kerja', 'unit_kerja', $newId, "Menambah unit kerja: {$namaUnit}");

            jsonResponse(['success' => true, 'message' => 'Unit kerja berhasil ditambahkan', 'id' => $newId]);
            break;

        case 'update':
            $id = (int)($input['id'] ?? 0);
            $namaUnit = trim($input['nama_unit'] ?? '');
            $kodeUnit = strtoupper(trim($input['kode_unit'] ?? ''));
            $pimpinanId = !empty($input['pimpinan_id']) ? (int)$input['pimpinan_id'] : null;

            if ($id <= 0 || empty($namaUnit) || empty($kodeUnit)) {
                jsonResponse(['success' => false, 'message' => 'ID, Nama Unit, dan Kode Unit wajib diisi'], 422);
            }

            $chk = $pdo->prepare("SELECT id FROM unit_kerja WHERE kode_unit = :kode AND id != :id LIMIT 1");
            $chk->execute(['kode' => $kodeUnit, 'id' => $id]);
            if ($chk->fetch()) {
                jsonResponse(['success' => false, 'message' => "Kode unit {$kodeUnit} sudah digunakan unit lain"], 422);
            }

            $stmt = $pdo->prepare("UPDATE unit_kerja SET nama_unit = :nama, kode_unit = :kode, pimpinan_id = :pimpinan WHERE id = :id");
            $stmt->execute(['nama' => $namaUnit, 'kode' => $kodeUnit, 'pimpinan' => $pimpinanId, 'id' => $id]);

            logActivity('update_unit_kerja', 'unit_kerja', $id, "Memperbarui unit kerja: {$namaUnit}");

            jsonResponse(['success' => true, 'message' => 'Unit kerja berhasil diperbarui']);
            break;

        case 'delete':
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                jsonResponse(['success' => false, 'message' => 'ID Unit kerja tidak valid'], 422);
            }

            // Cek apakah ada pegawai yang bertugas di unit ini
            $chk = $pdo->prepare("SELECT COUNT(*) FROM pegawai WHERE unit_kerja_id = :id");
            $chk->execute(['id' => $id]);
            $count = (int)$chk->fetchColumn();

            if ($count > 0) {
                jsonResponse(['success' => false, 'message' => "Unit kerja tidak dapat dihapus karena masih menaungi {$count} pegawai."], 400);
            }

            $stmt = $pdo->prepare("DELETE FROM unit_kerja WHERE id = :id");
            $stmt->execute(['id' => $id]);

            logActivity('delete_unit_kerja', 'unit_kerja', $id, "Menghapus unit kerja #{$id}");

            jsonResponse(['success' => true, 'message' => 'Unit kerja berhasil dihapus']);
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Subaction tidak valid (pilih: create, update, delete)'], 400);
    }
}
