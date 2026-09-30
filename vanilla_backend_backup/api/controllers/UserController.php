<?php
/**
 * User Controller - SIKMA
 * Balai Penjaminan Mutu Pendidikan (BPMP) Gorontalo
 * 
 * Endpoints:
 *   GET    UserController.php?action=get_list
 *   POST   UserController.php?action=create
 *   POST   UserController.php?action=update
 *   DELETE/POST UserController.php?action=delete
 *   POST   UserController.php?action=reset_password
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$pdo = getDB();

switch ($action) {
    case 'get_list':
        handleGetList($pdo);
        break;

    case 'create':
        handleCreate($pdo);
        break;

    case 'update':
        handleUpdate($pdo);
        break;

    case 'delete':
        handleDelete($pdo);
        break;

    case 'reset_password':
        handleResetPassword($pdo);
        break;

    default:
        jsonResponse([
            'success' => false,
            'message' => 'Action tidak valid pada UserController'
        ], 400);
}

/**
 * Daftar Akun User (Admin)
 */
function handleGetList(PDO $pdo): void {
    requireRole('admin');

    $role = trim($_GET['role'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $search = trim($_GET['search'] ?? '');

    $where = ["1=1"];
    $params = [];

    if (!empty($role)) {
        $where[] = "u.role = :role";
        $params['role'] = $role;
    }
    if (!empty($status)) {
        $where[] = "u.status = :status";
        $params['status'] = $status;
    }
    if (!empty($search)) {
        $where[] = "(u.username LIKE :search OR u.full_name LIKE :search OR p.nama_lengkap LIKE :search OR p.nip LIKE :search)";
        $params['search'] = "%{$search}%";
    }

    $whereSql = implode(" AND ", $where);

    $sql = "
        SELECT u.id, u.username, u.full_name, u.role, u.status, u.pegawai_id, u.created_at, u.updated_at,
               p.nip, p.nama_lengkap AS nama_pegawai, p.jabatan,
               uk.nama_unit
        FROM users u
        LEFT JOIN pegawai p ON u.pegawai_id = p.id
        LEFT JOIN unit_kerja uk ON p.unit_kerja_id = uk.id
        WHERE {$whereSql}
        ORDER BY u.id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'count'   => count($rows),
        'data'    => $rows
    ]);
}

/**
 * Buat Akun User Baru (Admin)
 */
function handleCreate(PDO $pdo): void {
    requireRole('admin');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Metode request harus POST'], 405);
    }

    $input = getJsonInput();
    $username = trim($input['username'] ?? '');
    $password = (string)($input['password'] ?? '');
    $fullName = trim($input['full_name'] ?? '');
    $role = trim($input['role'] ?? '');
    $pegawaiId = !empty($input['pegawai_id']) ? (int)$input['pegawai_id'] : null;

    $validRoles = ['pegawai', 'atasan', 'lobby', 'pos', 'admin', 'pimpinan'];

    if (empty($username) || empty($password) || empty($fullName) || !in_array($role, $validRoles, true)) {
        jsonResponse([
            'success' => false,
            'message' => 'Username, kata sandi, nama lengkap, dan peran (role) valid wajib diisi'
        ], 422);
    }

    // Cek keunikan username
    $check = $pdo->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
    $check->execute(['u' => $username]);
    if ($check->fetch()) {
        jsonResponse(['success' => false, 'message' => "Username '{$username}' sudah digunakan"], 422);
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("
        INSERT INTO users (username, password, full_name, role, status, pegawai_id, created_at)
        VALUES (:username, :password, :full_name, :role, 'aktif', :pegawai_id, NOW())
    ");
    $stmt->execute([
        'username'   => $username,
        'password'   => $hashedPassword,
        'full_name'  => $fullName,
        'role'       => $role,
        'pegawai_id' => $pegawaiId
    ]);
    $userId = (int)$pdo->lastInsertId();

    logActivity('create_user', 'users', $userId, "Admin membuat akun user: {$username} ({$role})");

    jsonResponse([
        'success' => true,
        'message' => 'Akun pengguna berhasil dibuat',
        'data'    => [
            'id'        => $userId,
            'username'  => $username,
            'full_name' => $fullName,
            'role'      => $role
        ]
    ], 201);
}

/**
 * Update Akun User (Admin)
 */
function handleUpdate(PDO $pdo): void {
    requireRole('admin');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Metode request harus POST'], 405);
    }

    $input = getJsonInput();
    $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));
    $username = trim($input['username'] ?? '');
    $fullName = trim($input['full_name'] ?? '');
    $role = trim($input['role'] ?? '');
    $status = trim($input['status'] ?? 'aktif');
    $pegawaiId = !empty($input['pegawai_id']) ? (int)$input['pegawai_id'] : null;

    $validRoles = ['pegawai', 'atasan', 'lobby', 'pos', 'admin', 'pimpinan'];

    if ($id <= 0 || empty($username) || empty($fullName) || !in_array($role, $validRoles, true)) {
        jsonResponse(['success' => false, 'message' => 'Data input akun user tidak lengkap atau tidak valid'], 422);
    }

    // Cek username unik selain user ini
    $check = $pdo->prepare("SELECT id FROM users WHERE username = :u AND id != :id LIMIT 1");
    $check->execute(['u' => $username, 'id' => $id]);
    if ($check->fetch()) {
        jsonResponse(['success' => false, 'message' => "Username '{$username}' sudah digunakan akun lain"], 422);
    }

    $stmt = $pdo->prepare("
        UPDATE users 
        SET username = :username,
            full_name = :full_name,
            role = :role,
            status = :status,
            pegawai_id = :pegawai_id,
            updated_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        'username'   => $username,
        'full_name'  => $fullName,
        'role'       => $role,
        'status'     => in_array($status, ['aktif', 'tidak_aktif'], true) ? $status : 'aktif',
        'pegawai_id' => $pegawaiId,
        'id'         => $id
    ]);

    logActivity('update_user', 'users', $id, "Admin memperbarui data akun: {$username}");

    jsonResponse(['success' => true, 'message' => 'Akun pengguna berhasil diperbarui']);
}

/**
 * Hapus / Nonaktifkan User (Admin)
 */
function handleDelete(PDO $pdo): void {
    requireRole('admin');

    $input = getJsonInput();
    $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));
    $currentUser = getCurrentUser();

    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID pengguna tidak valid'], 422);
    }

    if ($id === (int)$currentUser['id']) {
        jsonResponse(['success' => false, 'message' => 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif'], 400);
    }

    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'Akun pengguna tidak ditemukan'], 404);
    }

    // Ubah status jadi tidak aktif
    $updateStmt = $pdo->prepare("UPDATE users SET status = 'tidak_aktif' WHERE id = :id");
    $updateStmt->execute(['id' => $id]);

    logActivity('deactivate_user', 'users', $id, "Admin menonaktifkan akun user: {$user['username']}");

    jsonResponse([
        'success' => true,
        'message' => "Akun {$user['username']} berhasil dinonaktifkan"
    ]);
}

/**
 * Reset kata sandi pengguna (Admin)
 */
function handleResetPassword(PDO $pdo): void {
    requireRole('admin');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Metode request harus POST'], 405);
    }

    $input = getJsonInput();
    $id = (int)($input['id'] ?? 0);
    $newPassword = (string)($input['new_password'] ?? 'password123');

    if ($id <= 0 || empty($newPassword)) {
        jsonResponse(['success' => false, 'message' => 'ID pengguna dan kata sandi baru wajib diisi'], 422);
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("UPDATE users SET password = :p, updated_at = NOW() WHERE id = :id");
    $stmt->execute(['p' => $hashedPassword, 'id' => $id]);

    logActivity('reset_password', 'users', $id, "Admin mereset kata sandi akun user #{$id}");

    jsonResponse([
        'success' => true,
        'message' => 'Kata sandi berhasil direset'
    ]);
}
