<?php
/**
 * Pegawai Controller - SIKMA
 * Balai Penjaminan Mutu Pendidikan (BPMP) Gorontalo
 * 
 * Endpoints:
 *   GET    PegawaiController.php?action=get_list
 *   GET    PegawaiController.php?action=get_detail&id=X
 *   POST   PegawaiController.php?action=create
 *   POST   PegawaiController.php?action=update
 *   DELETE/POST PegawaiController.php?action=delete
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

    case 'get_detail':
        handleGetDetail($pdo);
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

    default:
        jsonResponse([
            'success' => false,
            'message' => 'Action tidak valid pada PegawaiController'
        ], 400);
}

/**
 * Daftar pegawai dengan pencarian, filter, dan pagination
 */
function handleGetList(PDO $pdo): void {
    requireRole(['admin', 'pimpinan', 'atasan', 'pegawai']);

    $search = trim($_GET['search'] ?? '');
    $unitKerjaId = isset($_GET['unit_kerja_id']) ? (int)$_GET['unit_kerja_id'] : 0;
    $status = trim($_GET['status'] ?? '');
    $all = isset($_GET['all']) && $_GET['all'] == '1';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 12)));
    $offset = ($page - 1) * $limit;

    $where = ["1=1"];
    $params = [];

    if (!empty($search)) {
        $where[] = "(p.nama_lengkap LIKE :search OR p.nip LIKE :search OR p.jabatan LIKE :search)";
        $params['search'] = "%{$search}%";
    }
    if ($unitKerjaId > 0) {
        $where[] = "p.unit_kerja_id = :unit_kerja_id";
        $params['unit_kerja_id'] = $unitKerjaId;
    }
    if (!empty($status) && in_array($status, ['aktif', 'tidak_aktif'], true)) {
        $where[] = "p.status = :status";
        $params['status'] = $status;
    }

    $whereSql = implode(" AND ", $where);

    // Hitung total
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM pegawai p WHERE {$whereSql}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Query data
    $sql = "
        SELECT p.*, u.nama_unit, u.kode_unit,
               atasan.nama_lengkap AS nama_atasan,
               usr.id AS user_id, usr.username, usr.role AS user_role
        FROM pegawai p
        LEFT JOIN unit_kerja u ON p.unit_kerja_id = u.id
        LEFT JOIN pegawai atasan ON p.atasan_id = atasan.id
        LEFT JOIN users usr ON usr.pegawai_id = p.id
        WHERE {$whereSql}
        ORDER BY p.unit_kerja_id ASC, p.nama_lengkap ASC
    ";

    if (!$all) {
        $sql .= " LIMIT :limit OFFSET :offset";
    }

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    if (!$all) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll();

    jsonResponse([
        'success'    => true,
        'page'       => $all ? 1 : $page,
        'limit'      => $all ? $total : $limit,
        'total'      => $total,
        'total_page' => $all ? 1 : ceil($total / $limit),
        'data'       => $rows
    ]);
}

/**
 * Detail pegawai tunggal
 */
function handleGetDetail(PDO $pdo): void {
    requireAuth();
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID Pegawai tidak valid'], 422);
    }

    $stmt = $pdo->prepare("
        SELECT p.*, u.nama_unit, u.kode_unit,
               atasan.nama_lengkap AS nama_atasan, atasan.nip AS nip_atasan,
               usr.id AS user_id, usr.username, usr.role AS user_role, usr.status AS user_status
        FROM pegawai p
        LEFT JOIN unit_kerja u ON p.unit_kerja_id = u.id
        LEFT JOIN pegawai atasan ON p.atasan_id = atasan.id
        LEFT JOIN users usr ON usr.pegawai_id = p.id
        WHERE p.id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $id]);
    $pegawai = $stmt->fetch();

    if (!$pegawai) {
        jsonResponse(['success' => false, 'message' => 'Pegawai tidak ditemukan'], 404);
    }

    jsonResponse([
        'success' => true,
        'data'    => $pegawai
    ]);
}

/**
 * Tambah pegawai baru (Admin)
 */
function handleCreate(PDO $pdo): void {
    requireRole('admin');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Metode request harus POST'], 405);
    }

    $input = getJsonInput();
    $nip = trim($input['nip'] ?? '');
    $namaLengkap = trim($input['nama_lengkap'] ?? '');
    $jabatan = trim($input['jabatan'] ?? '');
    $unitKerjaId = (int)($input['unit_kerja_id'] ?? 0);
    $atasanId = !empty($input['atasan_id']) ? (int)$input['atasan_id'] : null;
    $nomorHp = trim($input['nomor_hp'] ?? '');
    $status = in_array($input['status'] ?? '', ['aktif', 'tidak_aktif'], true) ? $input['status'] : 'aktif';

    if (empty($nip) || empty($namaLengkap) || empty($jabatan) || $unitKerjaId <= 0) {
        jsonResponse([
            'success' => false,
            'message' => 'NIP, Nama Lengkap, Jabatan, dan Unit Kerja wajib diisi'
        ], 422);
    }

    // Cek keunikan NIP
    $checkNip = $pdo->prepare("SELECT id FROM pegawai WHERE nip = :nip LIMIT 1");
    $checkNip->execute(['nip' => $nip]);
    if ($checkNip->fetch()) {
        jsonResponse(['success' => false, 'message' => "NIP {$nip} sudah terdaftar dalam sistem"], 422);
    }

    // Handle upload foto jika ada
    $fotoFilename = handleUploadFoto();

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO pegawai (nip, nama_lengkap, jabatan, unit_kerja_id, atasan_id, nomor_hp, foto, status, created_at)
            VALUES (:nip, :nama, :jabatan, :unit_id, :atasan_id, :hp, :foto, :status, NOW())
        ");
        $stmt->execute([
            'nip'       => $nip,
            'nama'      => $namaLengkap,
            'jabatan'   => $jabatan,
            'unit_id'   => $unitKerjaId,
            'atasan_id' => $atasanId,
            'hp'        => $nomorHp,
            'foto'      => $fotoFilename,
            'status'    => $status
        ]);
        $pegawaiId = (int)$pdo->lastInsertId();

        // Buat akun user otomatis jika disertakan username & password
        $username = trim($input['username'] ?? '');
        $password = (string)($input['password'] ?? '');
        $userRole = in_array($input['role'] ?? '', ['pegawai', 'atasan', 'admin', 'pimpinan'], true) ? $input['role'] : 'pegawai';

        if (!empty($username) && !empty($password)) {
            $hashedPass = password_hash($password, PASSWORD_BCRYPT);
            $stmtUser = $pdo->prepare("
                INSERT INTO users (username, password, full_name, role, status, pegawai_id, created_at)
                VALUES (:username, :password, :full_name, :role, 'aktif', :pegawai_id, NOW())
            ");
            $stmtUser->execute([
                'username'   => $username,
                'password'   => $hashedPass,
                'full_name'  => $namaLengkap,
                'role'       => $userRole,
                'pegawai_id' => $pegawaiId
            ]);
        }

        $pdo->commit();

        logActivity('create_pegawai', 'pegawai', $pegawaiId, "Menambahkan pegawai baru: {$namaLengkap} ({$nip})");

        jsonResponse([
            'success' => true,
            'message' => 'Data pegawai berhasil ditambahkan',
            'data'    => [
                'id'           => $pegawaiId,
                'nip'          => $nip,
                'nama_lengkap' => $namaLengkap
            ]
        ], 201);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Gagal menambahkan pegawai: ' . $e->getMessage()], 500);
    }
}

/**
 * Update data pegawai (Admin)
 */
function handleUpdate(PDO $pdo): void {
    requireRole('admin');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Metode request harus POST'], 405);
    }

    $input = getJsonInput();
    $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));
    $nip = trim($input['nip'] ?? '');
    $namaLengkap = trim($input['nama_lengkap'] ?? '');
    $jabatan = trim($input['jabatan'] ?? '');
    $unitKerjaId = (int)($input['unit_kerja_id'] ?? 0);
    $atasanId = !empty($input['atasan_id']) ? (int)$input['atasan_id'] : null;
    $nomorHp = trim($input['nomor_hp'] ?? '');
    $status = in_array($input['status'] ?? '', ['aktif', 'tidak_aktif'], true) ? $input['status'] : 'aktif';

    if ($id <= 0 || empty($nip) || empty($namaLengkap) || empty($jabatan) || $unitKerjaId <= 0) {
        jsonResponse([
            'success' => false,
            'message' => 'ID Pegawai, NIP, Nama Lengkap, Jabatan, dan Unit Kerja wajib diisi'
        ], 422);
    }

    // Cek keunikan NIP selain pegawai ini
    $checkNip = $pdo->prepare("SELECT id FROM pegawai WHERE nip = :nip AND id != :id LIMIT 1");
    $checkNip->execute(['nip' => $nip, 'id' => $id]);
    if ($checkNip->fetch()) {
        jsonResponse(['success' => false, 'message' => "NIP {$nip} sudah digunakan oleh pegawai lain"], 422);
    }

    // Ambil data eksisting
    $stmtCur = $pdo->prepare("SELECT foto FROM pegawai WHERE id = :id LIMIT 1");
    $stmtCur->execute(['id' => $id]);
    $current = $stmtCur->fetch();
    if (!$current) {
        jsonResponse(['success' => false, 'message' => 'Pegawai tidak ditemukan'], 404);
    }

    // Handle foto baru jika diupload
    $newFoto = handleUploadFoto();
    $fotoFilename = $newFoto ?: $current['foto'];

    $stmtUpdate = $pdo->prepare("
        UPDATE pegawai 
        SET nip = :nip,
            nama_lengkap = :nama,
            jabatan = :jabatan,
            unit_kerja_id = :unit_id,
            atasan_id = :atasan_id,
            nomor_hp = :hp,
            foto = :foto,
            status = :status,
            updated_at = NOW()
        WHERE id = :id
    ");
    $stmtUpdate->execute([
        'nip'       => $nip,
        'nama'      => $namaLengkap,
        'jabatan'   => $jabatan,
        'unit_id'   => $unitKerjaId,
        'atasan_id' => $atasanId,
        'hp'        => $nomorHp,
        'foto'      => $fotoFilename,
        'status'    => $status,
        'id'        => $id
    ]);

    // Update full_name di users jika terhubung
    $pdo->prepare("UPDATE users SET full_name = :nama WHERE pegawai_id = :id")->execute(['nama' => $namaLengkap, 'id' => $id]);

    logActivity('update_pegawai', 'pegawai', $id, "Admin memperbarui data pegawai: {$namaLengkap}");

    jsonResponse([
        'success' => true,
        'message' => 'Data pegawai berhasil diperbarui'
    ]);
}

/**
 * Hapus pegawai (Admin)
 */
function handleDelete(PDO $pdo): void {
    requireRole('admin');

    $input = getJsonInput();
    $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));

    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID Pegawai tidak valid'], 422);
    }

    $stmt = $pdo->prepare("SELECT id, nama_lengkap FROM pegawai WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $pegawai = $stmt->fetch();

    if (!$pegawai) {
        jsonResponse(['success' => false, 'message' => 'Pegawai tidak ditemukan'], 404);
    }

    // Cek apakah pegawai memiliki riwayat pindaian
    $stmtP = $pdo->prepare("SELECT COUNT(*) FROM pindaian WHERE pegawai_id = :id");
    $stmtP->execute(['id' => $id]);
    $hasHistory = (int)$stmtP->fetchColumn() > 0;

    if ($hasHistory) {
        // Soft delete (set status tidak_aktif) agar riwayat tidak rusak
        $pdo->prepare("UPDATE pegawai SET status = 'tidak_aktif' WHERE id = :id")->execute(['id' => $id]);
        $pdo->prepare("UPDATE users SET status = 'tidak_aktif' WHERE pegawai_id = :id")->execute(['id' => $id]);

        logActivity('deactivate_pegawai', 'pegawai', $id, "Menonaktifkan pegawai: {$pegawai['nama_lengkap']}");

        jsonResponse([
            'success' => true,
            'message' => "Pegawai memiliki riwayat pindaian. Status berhasil diubah menjadi tidak aktif."
        ]);
    } else {
        // Hard delete jika belum pernah ada pindaian
        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM users WHERE pegawai_id = :id")->execute(['id' => $id]);
            $pdo->prepare("DELETE FROM izin_dinas WHERE pegawai_id = :id")->execute(['id' => $id]);
            $pdo->prepare("DELETE FROM pegawai WHERE id = :id")->execute(['id' => $id]);
            $pdo->commit();

            logActivity('delete_pegawai', 'pegawai', $id, "Menghapus data pegawai: {$pegawai['nama_lengkap']}");

            jsonResponse([
                'success' => true,
                'message' => 'Data pegawai berhasil dihapus permanen'
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(['success' => false, 'message' => 'Gagal menghapus: ' . $e->getMessage()], 500);
        }
    }
}

/**
 * Helper upload file foto pegawai
 */
function handleUploadFoto(): ?string {
    if (empty($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $file = $_FILES['foto'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, ALLOWED_IMAGE_EXTENSIONS, true)) {
        return null;
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        return null;
    }

    if (!is_dir(UPLOAD_DIR)) {
        @mkdir(UPLOAD_DIR, 0755, true);
    }

    $newFilename = 'pegawai_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = UPLOAD_DIR . $newFilename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return 'assets/img/foto-pegawai/' . $newFilename;
    }

    return null;
}
