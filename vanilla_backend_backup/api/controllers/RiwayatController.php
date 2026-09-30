<?php
/**
 * Riwayat Controller - SIKMA
 * Balai Penjaminan Mutu Pendidikan (BPMP) Gorontalo
 * 
 * Endpoints:
 *   GET RiwayatController.php?action=get_saya
 *   GET RiwayatController.php?action=get_all
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$pdo = getDB();

switch ($action) {
    case 'get_saya':
        handleGetSaya($pdo);
        break;

    case 'get_all':
        handleGetAll($pdo);
        break;

    default:
        jsonResponse([
            'success' => false,
            'message' => 'Action tidak valid pada RiwayatController'
        ], 400);
}

/**
 * Mendapatkan riwayat pindaian keluar masuk pegawai yang sedang login (paginated & filterable)
 */
function handleGetSaya(PDO $pdo): void {
    requireAuth();
    $pegawai = getCurrentPegawai();
    if (!$pegawai) {
        jsonResponse(['success' => false, 'message' => 'Data pegawai tidak ditemukan'], 404);
    }
    $pegawaiId = (int)$pegawai['id'];

    $startDate = trim($_GET['start_date'] ?? '');
    $endDate = trim($_GET['end_date'] ?? '');
    $keperluanJenis = trim($_GET['keperluan_jenis'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 15)));
    $offset = ($page - 1) * $limit;

    $where = ["pk.pegawai_id = :pegawai_id"];
    $params = ['pegawai_id' => $pegawaiId];

    if (!empty($startDate)) {
        $where[] = "DATE(pk.jam_keluar) >= :start_date";
        $params['start_date'] = $startDate;
    }
    if (!empty($endDate)) {
        $where[] = "DATE(pk.jam_keluar) <= :end_date";
        $params['end_date'] = $endDate;
    }
    if (!empty($keperluanJenis) && in_array($keperluanJenis, ['dinas', 'keperluan_lain'], true)) {
        $where[] = "p_keluar.keperluan_jenis = :keperluan_jenis";
        $params['keperluan_jenis'] = $keperluanJenis;
    }
    if (!empty($status) && in_array($status, ['terbuka', 'kembali', 'belum_kembali'], true)) {
        $where[] = "pk.status = :status";
        $params['status'] = $status;
    }

    $whereSql = implode(" AND ", $where);

    // Hitung total data
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM pasangan_keluar_masuk pk
        LEFT JOIN pindaian p_keluar ON pk.pindaian_keluar_id = p_keluar.id
        WHERE {$whereSql}
    ");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Ambil data
    $sql = "
        SELECT pk.id AS pasangan_id, pk.jam_keluar, pk.jam_kembali, pk.durasi_menit, pk.status, pk.catatan,
               p_keluar.keperluan_jenis, p_keluar.tempat AS tempat_keluar,
               p_masuk.tempat AS tempat_masuk,
               iz.tujuan, iz.keperluan,
               CASE 
                   WHEN pk.status = 'terbuka' THEN TIMESTAMPDIFF(MINUTE, pk.jam_keluar, NOW())
                   ELSE pk.durasi_menit
               END AS durasi_hitung_menit
        FROM pasangan_keluar_masuk pk
        LEFT JOIN pindaian p_keluar ON pk.pindaian_keluar_id = p_keluar.id
        LEFT JOIN pindaian p_masuk ON pk.pindaian_masuk_id = p_masuk.id
        LEFT JOIN izin_dinas iz ON p_keluar.izin_dinas_id = iz.id
        WHERE {$whereSql}
        ORDER BY pk.jam_keluar DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    // Format output
    foreach ($rows as &$row) {
        $durasi = (int)$row['durasi_hitung_menit'];
        $row['durasi_format'] = formatDurasiTeks($durasi);
    }

    jsonResponse([
        'success'    => true,
        'page'       => $page,
        'limit'      => $limit,
        'total'      => $total,
        'total_page' => ceil($total / $limit),
        'data'       => $rows
    ]);
}

/**
 * Mendapatkan seluruh catatan keluar masuk untuk Admin dan Pimpinan
 */
function handleGetAll(PDO $pdo): void {
    requireRole(['admin', 'pimpinan', 'pos']);
    $user = getCurrentUser();

    $pegawaiId = isset($_GET['pegawai_id']) ? (int)$_GET['pegawai_id'] : 0;
    $unitKerjaId = isset($_GET['unit_kerja_id']) ? (int)$_GET['unit_kerja_id'] : 0;
    $startDate = trim($_GET['start_date'] ?? '');
    $endDate = trim($_GET['end_date'] ?? '');
    $tanggal = trim($_GET['tanggal'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $keperluanJenis = trim($_GET['keperluan_jenis'] ?? '');
    $search = trim($_GET['search'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    $where = ["1=1"];
    $params = [];

    // Filter otomatis pimpinan berdasarkan unit kerjanya
    if ($user['role'] === 'pimpinan' && !empty($user['unit_kerja_id'])) {
        $where[] = "peg.unit_kerja_id = :pimpinan_unit";
        $params['pimpinan_unit'] = $user['unit_kerja_id'];
    } elseif ($unitKerjaId > 0) {
        $where[] = "peg.unit_kerja_id = :unit_kerja_id";
        $params['unit_kerja_id'] = $unitKerjaId;
    }

    if ($pegawaiId > 0) {
        $where[] = "pk.pegawai_id = :pegawai_id";
        $params['pegawai_id'] = $pegawaiId;
    }
    if (!empty($tanggal)) {
        $where[] = "DATE(pk.jam_keluar) = :tanggal";
        $params['tanggal'] = $tanggal;
    } else {
        if (!empty($startDate)) {
            $where[] = "DATE(pk.jam_keluar) >= :start_date";
            $params['start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(pk.jam_keluar) <= :end_date";
            $params['end_date'] = $endDate;
        }
    }
    if (!empty($status) && in_array($status, ['terbuka', 'kembali', 'belum_kembali'], true)) {
        $where[] = "pk.status = :status";
        $params['status'] = $status;
    }
    if (!empty($keperluanJenis) && in_array($keperluanJenis, ['dinas', 'keperluan_lain'], true)) {
        $where[] = "p_keluar.keperluan_jenis = :keperluan_jenis";
        $params['keperluan_jenis'] = $keperluanJenis;
    }
    if (!empty($search)) {
        $where[] = "(peg.nama_lengkap LIKE :search OR peg.nip LIKE :search)";
        $params['search'] = "%{$search}%";
    }

    $whereSql = implode(" AND ", $where);

    // Hitung total
    $countStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM pasangan_keluar_masuk pk
        JOIN pegawai peg ON pk.pegawai_id = peg.id
        LEFT JOIN pindaian p_keluar ON pk.pindaian_keluar_id = p_keluar.id
        WHERE {$whereSql}
    ");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Query data
    $sql = "
        SELECT pk.id AS pasangan_id, pk.pegawai_id, pk.jam_keluar, pk.jam_kembali, pk.durasi_menit, pk.status, pk.catatan,
               peg.nama_lengkap, peg.nip, peg.jabatan, peg.foto,
               u.nama_unit, u.kode_unit,
               p_keluar.keperluan_jenis, p_keluar.tempat AS tempat_keluar,
               p_masuk.tempat AS tempat_masuk,
               iz.tujuan, iz.keperluan,
               CASE 
                   WHEN pk.status = 'terbuka' THEN TIMESTAMPDIFF(MINUTE, pk.jam_keluar, NOW())
                   ELSE pk.durasi_menit
               END AS durasi_hitung_menit
        FROM pasangan_keluar_masuk pk
        JOIN pegawai peg ON pk.pegawai_id = peg.id
        LEFT JOIN unit_kerja u ON peg.unit_kerja_id = u.id
        LEFT JOIN pindaian p_keluar ON pk.pindaian_keluar_id = p_keluar.id
        LEFT JOIN pindaian p_masuk ON pk.pindaian_masuk_id = p_masuk.id
        LEFT JOIN izin_dinas iz ON p_keluar.izin_dinas_id = iz.id
        WHERE {$whereSql}
        ORDER BY pk.jam_keluar DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        $durasi = (int)$row['durasi_hitung_menit'];
        $row['durasi_format'] = formatDurasiTeks($durasi);
    }

    jsonResponse([
        'success'    => true,
        'page'       => $page,
        'limit'      => $limit,
        'total'      => $total,
        'total_page' => ceil($total / $limit),
        'data'       => $rows
    ]);
}

/**
 * Format teks durasi
 */
function formatDurasiTeks(int $menit): string {
    if ($menit <= 0) return "0 menit";
    if ($menit < 60) return "{$menit} menit";
    $jam = floor($menit / 60);
    $sisa = $menit % 60;
    return ($sisa > 0) ? "{$jam} jam {$sisa} menit" : "{$jam} jam";
}
