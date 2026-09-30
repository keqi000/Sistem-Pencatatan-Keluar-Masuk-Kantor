<?php
/**
 * Dashboard Controller - SIKMA
 * Balai Penjaminan Mutu Pendidikan (BPMP) Gorontalo
 * 
 * Endpoints:
 *   GET DashboardController.php?action=get_stats_hari_ini
 *   GET DashboardController.php?action=get_sedang_diluar
 *   GET DashboardController.php?action=get_chart_data
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$pdo = getDB();

switch ($action) {
    case 'get_stats_hari_ini':
        handleGetStatsHariIni($pdo);
        break;

    case 'get_sedang_diluar':
        handleGetSedangDiluar($pdo);
        break;

    case 'get_chart_data':
        handleGetChartData($pdo);
        break;

    default:
        jsonResponse([
            'success' => false,
            'message' => 'Action tidak valid pada DashboardController'
        ], 400);
}

/**
 * Statistik ringkasan aktivitas hari ini
 */
function handleGetStatsHariIni(PDO $pdo): void {
    requireRole(['admin', 'pimpinan', 'atasan', 'pos']);

    $unitKerjaId = isset($_GET['unit_kerja_id']) ? (int)$_GET['unit_kerja_id'] : 0;
    $user = getCurrentUser();
    if ($user['role'] === 'pimpinan' && !empty($user['unit_kerja_id'])) {
        $unitKerjaId = (int)$user['unit_kerja_id'];
    }

    $unitFilter = "";
    $params = [];
    if ($unitKerjaId > 0) {
        $unitFilter = " AND peg.unit_kerja_id = :unit_kerja_id";
        $params['unit_kerja_id'] = $unitKerjaId;
    }

    $sql = "
        SELECT 
            COUNT(pk.id) AS total_keluar,
            SUM(CASE WHEN pk.status = 'terbuka' THEN 1 ELSE 0 END) AS sedang_diluar,
            SUM(CASE WHEN pk.status = 'kembali' THEN 1 ELSE 0 END) AS sudah_kembali,
            SUM(CASE WHEN pk.status = 'belum_kembali' THEN 1 ELSE 0 END) AS belum_kembali,
            SUM(CASE WHEN p_keluar.keperluan_jenis = 'dinas' THEN 1 ELSE 0 END) AS total_dinas,
            SUM(CASE WHEN p_keluar.keperluan_jenis = 'keperluan_lain' THEN 1 ELSE 0 END) AS total_keperluan_lain,
            COALESCE(SUM(pk.durasi_menit), 0) AS total_durasi_menit
        FROM pasangan_keluar_masuk pk
        JOIN pegawai peg ON pk.pegawai_id = peg.id
        LEFT JOIN pindaian p_keluar ON pk.pindaian_keluar_id = p_keluar.id
        WHERE DATE(pk.jam_keluar) = CURDATE() {$unitFilter}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $stats = $stmt->fetch();

    jsonResponse([
        'success'     => true,
        'tanggal'     => date('Y-m-d'),
        'server_time' => date('Y-m-d H:i:s'),
        'stats'       => [
            'sedang_diluar'        => (int)($stats['sedang_diluar'] ?? 0),
            'sudah_kembali'        => (int)($stats['sudah_kembali'] ?? 0),
            'belum_kembali'        => (int)($stats['belum_kembali'] ?? 0),
            'total_keluar'         => (int)($stats['total_keluar'] ?? 0),
            'total_dinas'          => (int)($stats['total_dinas'] ?? 0),
            'total_keperluan_lain' => (int)($stats['total_keperluan_lain'] ?? 0),
            'total_durasi_menit'   => (int)($stats['total_durasi_menit'] ?? 0)
        ]
    ]);
}

/**
 * Daftar pegawai yang saat ini sedang berada di luar kantor (status = 'terbuka')
 */
function handleGetSedangDiluar(PDO $pdo): void {
    requireRole(['admin', 'pimpinan', 'atasan', 'pos']);

    $unitKerjaId = isset($_GET['unit_kerja_id']) ? (int)$_GET['unit_kerja_id'] : 0;
    $user = getCurrentUser();
    if ($user['role'] === 'pimpinan' && !empty($user['unit_kerja_id'])) {
        $unitKerjaId = (int)$user['unit_kerja_id'];
    }

    $unitFilter = "";
    $params = [];
    if ($unitKerjaId > 0) {
        $unitFilter = " AND peg.unit_kerja_id = :unit_kerja_id";
        $params['unit_kerja_id'] = $unitKerjaId;
    }

    $threshold = (int)getSetting('ambang_terlambat_menit', 120);

    $sql = "
        SELECT pk.id AS pasangan_id, pk.jam_keluar,
               TIMESTAMPDIFF(MINUTE, pk.jam_keluar, NOW()) AS durasi_berjalan_menit,
               peg.id AS pegawai_id, peg.nama_lengkap, peg.nip, peg.jabatan, peg.foto, peg.nomor_hp,
               u.nama_unit, u.kode_unit,
               p_keluar.keperluan_jenis,
               iz.tujuan, iz.keperluan, iz.perkiraan_jam_kembali
        FROM pasangan_keluar_masuk pk
        JOIN pegawai peg ON pk.pegawai_id = peg.id
        LEFT JOIN unit_kerja u ON peg.unit_kerja_id = u.id
        LEFT JOIN pindaian p_keluar ON pk.pindaian_keluar_id = p_keluar.id
        LEFT JOIN izin_dinas iz ON p_keluar.izin_dinas_id = iz.id
        WHERE pk.status = 'terbuka' AND DATE(pk.jam_keluar) = CURDATE() {$unitFilter}
        ORDER BY pk.jam_keluar ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        $durasi = (int)$row['durasi_berjalan_menit'];
        $row['durasi_format'] = formatDurasiMenit($durasi);
        // Tandai jika durasi melebihi ambang batas atau melewati perkiraan_jam_kembali
        $isOverdue = ($durasi >= $threshold);
        if (!empty($row['perkiraan_jam_kembali'])) {
            if (date('H:i:s') > $row['perkiraan_jam_kembali']) {
                $isOverdue = true;
            }
        }
        $row['is_overdue'] = $isOverdue;
    }

    jsonResponse([
        'success'     => true,
        'count'       => count($rows),
        'server_time' => date('Y-m-d H:i:s'),
        'data'        => $rows
    ]);
}

/**
 * Data per jam (07.00 - 17.00) untuk grafik Bar Chart.js
 */
function handleGetChartData(PDO $pdo): void {
    requireRole(['admin', 'pimpinan', 'atasan']);

    $tanggal = trim($_GET['tanggal'] ?? date('Y-m-d'));
    $hours = ['07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17'];
    $labels = [];
    $dataKeluar = [];
    $dataMasuk = [];

    foreach ($hours as $h) {
        $labels[] = "{$h}:00";
        $dataKeluar[$h] = 0;
        $dataMasuk[$h] = 0;
    }

    // Ambil rekap pindaian per jam pada tanggal tersebut
    $stmt = $pdo->prepare("
        SELECT 
            jenis,
            DATE_FORMAT(jam, '%H') AS jam_group,
            COUNT(*) AS total
        FROM pindaian
        WHERE DATE(jam) = :tanggal
        GROUP BY jenis, jam_group
    ");
    $stmt->execute(['tanggal' => $tanggal]);
    $results = $stmt->fetchAll();

    foreach ($results as $r) {
        $jg = $r['jam_group'];
        $jenis = $r['jenis'];
        $tot = (int)$r['total'];

        if (isset($dataKeluar[$jg]) && $jenis === 'keluar') {
            $dataKeluar[$jg] = $tot;
        }
        if (isset($dataMasuk[$jg]) && $jenis === 'masuk') {
            $dataMasuk[$jg] = $tot;
        }
    }

    jsonResponse([
        'success' => true,
        'tanggal' => $tanggal,
        'chart'   => [
            'labels'   => $labels,
            'datasets' => [
                [
                    'label'           => 'Pegawai Keluar (Lobby)',
                    'backgroundColor' => 'rgba(214, 137, 16, 0.8)',
                    'borderColor'     => '#D68910',
                    'data'            => array_values($dataKeluar)
                ],
                [
                    'label'           => 'Pegawai Kembali (Pos Security)',
                    'backgroundColor' => 'rgba(30, 132, 73, 0.8)',
                    'borderColor'     => '#1E8449',
                    'data'            => array_values($dataMasuk)
                ]
            ]
        ]
    ]);
}

/**
 * Helper format durasi
 */
function formatDurasiMenit(int $menit): string {
    if ($menit <= 0) return "0 menit";
    if ($menit < 60) return "{$menit} menit";
    $jam = floor($menit / 60);
    $sisa = $menit % 60;
    return ($sisa > 0) ? "{$jam} jam {$sisa} menit" : "{$jam} jam";
}
