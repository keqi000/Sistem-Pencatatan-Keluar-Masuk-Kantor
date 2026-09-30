<?php
/**
 * Izin Dinas Controller - SIKMA
 * Balai Penjaminan Mutu Pendidikan (BPMP) Gorontalo
 * 
 * Endpoints:
 *   GET  IzinDinasController.php?action=get_list_saya
 *   POST IzinDinasController.php?action=ajukan
 *   GET  IzinDinasController.php?action=get_list_bawahan
 *   POST IzinDinasController.php?action=putuskan
 *   GET  IzinDinasController.php?action=get_aktif_hari_ini
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$pdo = getDB();

switch ($action) {
    case 'get_list_saya':
        handleGetListSaya($pdo);
        break;

    case 'ajukan':
        handleAjukan($pdo);
        break;

    case 'get_list_bawahan':
        handleGetListBawahan($pdo);
        break;

    case 'putuskan':
        handlePutuskan($pdo);
        break;

    case 'get_aktif_hari_ini':
        handleGetAktifHariIni($pdo);
        break;

    default:
        jsonResponse([
            'success' => false,
            'message' => 'Action tidak valid pada IzinDinasController'
        ], 400);
}

/**
 * Mendapatkan daftar izin dinas milik pegawai yang sedang login
 */
function handleGetListSaya(PDO $pdo): void {
    requireAuth();
    $pegawai = getCurrentPegawai();
    if (!$pegawai) {
        jsonResponse(['success' => false, 'message' => 'Data pegawai tidak ditemukan'], 404);
    }
    $pegawaiId = (int)$pegawai['id'];

    $stmt = $pdo->prepare("
        SELECT iz.*, u.full_name AS nama_atasan
        FROM izin_dinas iz
        LEFT JOIN users u ON iz.atasan_id = u.id
        WHERE iz.pegawai_id = :pegawai_id
        ORDER BY iz.tanggal DESC, iz.id DESC
    ");
    $stmt->execute(['pegawai_id' => $pegawaiId]);
    $list = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'count'   => count($list),
        'data'    => $list
    ]);
}

/**
 * Ajukan Izin Dinas oleh Pegawai
 */
function handleAjukan(PDO $pdo): void {
    requireAuth();
    $pegawai = getCurrentPegawai();
    if (!$pegawai) {
        jsonResponse(['success' => false, 'message' => 'Hanya akun terhubung data pegawai yang dapat mengajukan izin'], 403);
    }
    $pegawaiId = (int)$pegawai['id'];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Metode request harus POST'], 405);
    }

    $input = getJsonInput();
    $tanggal = trim($input['tanggal'] ?? '');
    $jamPergi = trim($input['perkiraan_jam_pergi'] ?? '');
    $jamKembali = trim($input['perkiraan_jam_kembali'] ?? '');
    $tujuan = trim($input['tujuan'] ?? '');
    $keperluan = trim($input['keperluan'] ?? '');

    // Validasi input
    if (empty($tanggal) || empty($jamPergi) || empty($jamKembali) || empty($tujuan) || empty($keperluan)) {
        jsonResponse([
            'success' => false,
            'message' => 'Semua kolom (tanggal, perkiraan jam pergi, jam kembali, tujuan, keperluan) wajib diisi'
        ], 422);
    }

    // Tanggal tidak boleh di masa lalu
    $today = date('Y-m-d');
    if ($tanggal < $today) {
        jsonResponse([
            'success' => false,
            'message' => 'Tanggal izin tidak boleh tanggal di masa lalu'
        ], 422);
    }

    // Jam kembali harus setelah jam pergi
    if (strtotime($jamKembali) <= strtotime($jamPergi)) {
        jsonResponse([
            'success' => false,
            'message' => 'Perkiraan jam kembali harus lebih akhir dari jam pergi'
        ], 422);
    }

    // Cari user id atasan
    $atasanUserId = null;
    if (!empty($input['atasan_id'])) {
        $atasanUserId = (int)$input['atasan_id'];
    } else if (!empty($pegawai['atasan_id'])) {
        // Cari user yang pegawai_id nya = atasan_id pegawai ini
        $stmtAtasan = $pdo->prepare("SELECT id FROM users WHERE pegawai_id = :atasan_id AND status = 'aktif' LIMIT 1");
        $stmtAtasan->execute(['atasan_id' => $pegawai['atasan_id']]);
        $atasanUserId = $stmtAtasan->fetchColumn();
    }

    // Fallback: atasan default (role 'atasan' atau 'admin')
    if (!$atasanUserId) {
        $stmtDefaultAtasan = $pdo->query("SELECT id FROM users WHERE role IN ('atasan', 'admin') AND status = 'aktif' ORDER BY id ASC LIMIT 1");
        $atasanUserId = $stmtDefaultAtasan->fetchColumn();
    }

    if (!$atasanUserId) {
        jsonResponse([
            'success' => false,
            'message' => 'Tidak ditemukan atasan aktif untuk memverifikasi izin dinas Anda'
        ], 422);
    }

    $stmtInsert = $pdo->prepare("
        INSERT INTO izin_dinas (pegawai_id, atasan_id, tanggal, perkiraan_jam_pergi, perkiraan_jam_kembali, tujuan, keperluan, status, created_at)
        VALUES (:pegawai_id, :atasan_id, :tanggal, :jam_pergi, :jam_kembali, :tujuan, :keperluan, 'menunggu', NOW())
    ");
    $stmtInsert->execute([
        'pegawai_id'  => $pegawaiId,
        'atasan_id'   => $atasanUserId,
        'tanggal'     => $tanggal,
        'jam_pergi'   => $jamPergi,
        'jam_kembali' => $jamKembali,
        'tujuan'      => $tujuan,
        'keperluan'   => $keperluan
    ]);
    $izinId = (int)$pdo->lastInsertId();

    logActivity('ajukan_izin_dinas', 'izin_dinas', $izinId, "Pengajuan izin dinas ke {$tujuan} pada {$tanggal}");

    jsonResponse([
        'success' => true,
        'message' => 'Pengajuan izin dinas berhasil dikirim dan menunggu persetujuan atasan.',
        'data'    => [
            'id'      => $izinId,
            'status'  => 'menunggu',
            'tanggal' => $tanggal,
            'tujuan'  => $tujuan
        ]
    ], 201);
}

/**
 * Daftar pengajuan izin dinas bawahan (untuk role atasan / admin)
 */
function handleGetListBawahan(PDO $pdo): void {
    requireRole(['atasan', 'admin', 'pimpinan']);
    $user = getCurrentUser();
    $userId = (int)$user['id'];
    $role = $user['role'];

    $status = trim($_GET['status'] ?? ''); // 'menunggu', 'disetujui', 'ditolak'

    $sql = "
        SELECT iz.*, 
               p.nama_lengkap AS nama_pegawai, p.nip AS nip_pegawai, p.jabatan AS jabatan_pegawai,
               u.nama_unit, u.kode_unit
        FROM izin_dinas iz
        JOIN pegawai p ON iz.pegawai_id = p.id
        LEFT JOIN unit_kerja u ON p.unit_kerja_id = u.id
        WHERE 1=1
    ";
    $params = [];

    // Jika bukan admin / pimpinan, hanya tampilkan bawahan yang ditugaskan ke atasan ini
    if ($role === 'atasan') {
        $sql .= " AND (iz.atasan_id = :user_id OR p.atasan_id = :pegawai_id)";
        $params['user_id'] = $userId;
        $params['pegawai_id'] = $user['pegawai_id'] ?? 0;
    }

    if (!empty($status) && in_array($status, ['menunggu', 'disetujui', 'ditolak'], true)) {
        $sql .= " AND iz.status = :status";
        $params['status'] = $status;
    }

    $sql .= " ORDER BY CASE WHEN iz.status = 'menunggu' THEN 0 ELSE 1 END, iz.tanggal DESC, iz.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $list = $stmt->fetchAll();

    // Hitung jumlah pending (menunggu)
    $countSql = "SELECT COUNT(*) FROM izin_dinas iz JOIN pegawai p ON iz.pegawai_id = p.id WHERE iz.status = 'menunggu'";
    $countParams = [];
    if ($role === 'atasan') {
        $countSql .= " AND (iz.atasan_id = :user_id OR p.atasan_id = :pegawai_id)";
        $countParams['user_id'] = $userId;
        $countParams['pegawai_id'] = $user['pegawai_id'] ?? 0;
    }
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($countParams);
    $pendingCount = (int)$countStmt->fetchColumn();

    jsonResponse([
        'success'       => true,
        'count'         => count($list),
        'pending_count' => $pendingCount,
        'data'          => $list
    ]);
}

/**
 * Putuskan persetujuan izin dinas (Setujui / Tolak) oleh Atasan atau Admin
 */
function handlePutuskan(PDO $pdo): void {
    requireRole(['atasan', 'admin', 'pimpinan']);
    $user = getCurrentUser();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Metode request harus POST'], 405);
    }

    $input = getJsonInput();
    $izinId = (int)($input['izin_id'] ?? ($input['id'] ?? 0));
    $keputusan = trim($input['status'] ?? ($input['keputusan'] ?? '')); // 'disetujui' | 'ditolak'
    $catatan = trim($input['catatan_atasan'] ?? ($input['catatan'] ?? ''));

    if ($izinId <= 0 || !in_array($keputusan, ['disetujui', 'ditolak'], true)) {
        jsonResponse([
            'success' => false,
            'message' => 'ID izin dinas dan keputusan (disetujui/ditolak) wajib disertakan'
        ], 422);
    }

    // Jika ditolak, catatan alasan wajib diisi (sesuai dokumen konsep halaman 4)
    if ($keputusan === 'ditolak' && empty($catatan)) {
        jsonResponse([
            'success' => false,
            'message' => 'Alasan penolakan izin dinas wajib diisi'
        ], 422);
    }

    $stmtCheck = $pdo->prepare("SELECT id, pegawai_id, status, tujuan, tanggal FROM izin_dinas WHERE id = :id LIMIT 1");
    $stmtCheck->execute(['id' => $izinId]);
    $izin = $stmtCheck->fetch();

    if (!$izin) {
        jsonResponse(['success' => false, 'message' => 'Pengajuan izin dinas tidak ditemukan'], 404);
    }

    $stmtUpdate = $pdo->prepare("
        UPDATE izin_dinas 
        SET status = :status,
            catatan_atasan = :catatan,
            updated_at = NOW()
        WHERE id = :id
    ");
    $stmtUpdate->execute([
        'status'  => $keputusan,
        'catatan' => $catatan,
        'id'      => $izinId
    ]);

    logActivity('putuskan_izin_dinas', 'izin_dinas', $izinId, "Atasan {$user['full_name']} memutuskan {$keputusan} izin dinas #{$izinId}");

    jsonResponse([
        'success' => true,
        'message' => "Izin dinas berhasil " . ($keputusan === 'disetujui' ? 'disetujui' : 'ditolak'),
        'data'    => [
            'id'             => $izinId,
            'status'         => $keputusan,
            'catatan_atasan' => $catatan
        ]
    ]);
}

/**
 * Mendapatkan izin dinas yang sudah disetujui untuk hari ini (untuk opsi saat scan keluar)
 */
function handleGetAktifHariIni(PDO $pdo): void {
    requireAuth();
    $pegawai = getCurrentPegawai();
    if (!$pegawai) {
        jsonResponse(['success' => false, 'message' => 'Pegawai tidak ditemukan'], 404);
    }

    $stmt = $pdo->prepare("
        SELECT id, tanggal, perkiraan_jam_pergi, perkiraan_jam_kembali, tujuan, keperluan, status
        FROM izin_dinas
        WHERE pegawai_id = :pegawai_id AND tanggal = CURDATE() AND status = 'disetujui'
        ORDER BY perkiraan_jam_pergi ASC
    ");
    $stmt->execute(['pegawai_id' => $pegawai['id']]);
    $list = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'count'   => count($list),
        'data'    => $list
    ]);
}
