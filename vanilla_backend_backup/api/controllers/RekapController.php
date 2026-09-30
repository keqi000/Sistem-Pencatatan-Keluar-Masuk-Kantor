<?php
/**
 * Rekap Controller - SIKMA
 * Balai Penjaminan Mutu Pendidikan (BPMP) Gorontalo
 * 
 * Endpoints:
 *   GET RekapController.php?action=harian&tanggal=YYYY-MM-DD
 *   GET RekapController.php?action=bulanan&bulan=YYYY-MM
 *   GET RekapController.php?action=export_pdf
 *   GET RekapController.php?action=export_excel
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$pdo = getDB();

switch ($action) {
    case 'harian':
        handleRekapHarian($pdo);
        break;

    case 'bulanan':
        handleRekapBulanan($pdo);
        break;

    case 'export_pdf':
        handleExportPdf($pdo);
        break;

    case 'export_excel':
        handleExportExcel($pdo);
        break;

    default:
        header('Content-Type: application/json; charset=utf-8');
        jsonResponse([
            'success' => false,
            'message' => 'Action tidak valid pada RekapController'
        ], 400);
}

/**
 * Rekap aktivitas harian per tanggal
 */
function handleRekapHarian(PDO $pdo): void {
    header('Content-Type: application/json; charset=utf-8');
    requireRole(['admin', 'pimpinan']);
    $user = getCurrentUser();

    $tanggal = trim($_GET['tanggal'] ?? date('Y-m-d'));
    $unitKerjaId = isset($_GET['unit_kerja_id']) ? (int)$_GET['unit_kerja_id'] : 0;

    // Filter otomatis pimpinan berdasarkan unitnya
    if ($user['role'] === 'pimpinan' && !empty($user['unit_kerja_id'])) {
        $unitKerjaId = (int)$user['unit_kerja_id'];
    }

    $where = ["DATE(pk.jam_keluar) = :tanggal"];
    $params = ['tanggal' => $tanggal];

    if ($unitKerjaId > 0) {
        $where[] = "peg.unit_kerja_id = :unit_kerja_id";
        $params['unit_kerja_id'] = $unitKerjaId;
    }

    $whereSql = implode(" AND ", $where);

    $sql = "
        SELECT pk.id AS pasangan_id, pk.jam_keluar, pk.jam_kembali, pk.status,
               CASE 
                   WHEN pk.status = 'terbuka' THEN TIMESTAMPDIFF(MINUTE, pk.jam_keluar, NOW())
                   ELSE pk.durasi_menit
               END AS durasi_menit,
               peg.id AS pegawai_id, peg.nama_lengkap, peg.nip, peg.jabatan,
               u.nama_unit, u.kode_unit,
               p_keluar.keperluan_jenis,
               iz.tujuan, iz.keperluan
        FROM pasangan_keluar_masuk pk
        JOIN pegawai peg ON pk.pegawai_id = peg.id
        LEFT JOIN unit_kerja u ON peg.unit_kerja_id = u.id
        LEFT JOIN pindaian p_keluar ON pk.pindaian_keluar_id = p_keluar.id
        LEFT JOIN izin_dinas iz ON p_keluar.izin_dinas_id = iz.id
        WHERE {$whereSql}
        ORDER BY pk.jam_keluar ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Hitung ringkasan
    $summary = [
        'total_keluar'         => count($rows),
        'sedang_diluar'        => 0,
        'sudah_kembali'        => 0,
        'belum_kembali'        => 0,
        'total_dinas'          => 0,
        'total_keperluan_lain' => 0,
        'total_durasi_menit'   => 0
    ];

    foreach ($rows as &$row) {
        $menit = (int)$row['durasi_menit'];
        $row['durasi_format'] = formatDurasiMenitRekap($menit);

        if ($row['status'] === 'terbuka') $summary['sedang_diluar']++;
        if ($row['status'] === 'kembali') $summary['sudah_kembali']++;
        if ($row['status'] === 'belum_kembali') $summary['belum_kembali']++;

        if ($row['keperluan_jenis'] === 'dinas') $summary['total_dinas']++;
        if ($row['keperluan_jenis'] === 'keperluan_lain') $summary['total_keperluan_lain']++;

        $summary['total_durasi_menit'] += $menit;
    }
    $summary['total_durasi_format'] = formatDurasiMenitRekap($summary['total_durasi_menit']);

    jsonResponse([
        'success' => true,
        'tanggal' => $tanggal,
        'summary' => $summary,
        'data'    => $rows
    ]);
}

/**
 * Rekap bulanan per pegawai (Jumlah hari keluar, tanpa izin, total menit, belum kembali)
 */
function handleRekapBulanan(PDO $pdo): void {
    header('Content-Type: application/json; charset=utf-8');
    requireRole(['admin', 'pimpinan']);
    $user = getCurrentUser();

    $bulan = trim($_GET['bulan'] ?? date('Y-m')); // Format YYYY-MM
    $unitKerjaId = isset($_GET['unit_kerja_id']) ? (int)$_GET['unit_kerja_id'] : 0;

    if ($user['role'] === 'pimpinan' && !empty($user['unit_kerja_id'])) {
        $unitKerjaId = (int)$user['unit_kerja_id'];
    }

    $where = ["peg.status = 'aktif'"];
    $params = ['bulan' => $bulan];

    if ($unitKerjaId > 0) {
        $where[] = "peg.unit_kerja_id = :unit_kerja_id";
        $params['unit_kerja_id'] = $unitKerjaId;
    }

    $whereSql = implode(" AND ", $where);

    $sql = "
        SELECT 
            peg.id AS pegawai_id, peg.nama_lengkap, peg.nip, peg.jabatan,
            u.nama_unit, u.kode_unit,
            COUNT(DISTINCT CASE WHEN DATE_FORMAT(pk.jam_keluar, '%Y-%m') = :bulan THEN DATE(pk.jam_keluar) END) AS jumlah_hari_keluar,
            COUNT(CASE WHEN DATE_FORMAT(pk.jam_keluar, '%Y-%m') = :bulan THEN pk.id END) AS total_kali_keluar,
            SUM(CASE WHEN DATE_FORMAT(pk.jam_keluar, '%Y-%m') = :bulan AND p_keluar.keperluan_jenis = 'keperluan_lain' THEN 1 ELSE 0 END) AS jumlah_tanpa_izin,
            SUM(CASE WHEN DATE_FORMAT(pk.jam_keluar, '%Y-%m') = :bulan AND p_keluar.keperluan_jenis = 'dinas' THEN 1 ELSE 0 END) AS jumlah_dinas,
            SUM(CASE WHEN DATE_FORMAT(pk.jam_keluar, '%Y-%m') = :bulan AND pk.status = 'belum_kembali' THEN 1 ELSE 0 END) AS jumlah_belum_kembali,
            COALESCE(SUM(CASE WHEN DATE_FORMAT(pk.jam_keluar, '%Y-%m') = :bulan THEN pk.durasi_menit ELSE 0 END), 0) AS total_menit_keluar
        FROM pegawai peg
        LEFT JOIN unit_kerja u ON peg.unit_kerja_id = u.id
        LEFT JOIN pasangan_keluar_masuk pk ON peg.id = pk.pegawai_id AND DATE_FORMAT(pk.jam_keluar, '%Y-%m') = :bulan
        LEFT JOIN pindaian p_keluar ON pk.pindaian_keluar_id = p_keluar.id
        WHERE {$whereSql}
        GROUP BY peg.id
        ORDER BY total_kali_keluar DESC, peg.nama_lengkap ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        $totalMenit = (int)$row['total_menit_keluar'];
        $row['total_durasi_format'] = formatDurasiMenitRekap($totalMenit);
    }

    jsonResponse([
        'success' => true,
        'bulan'   => $bulan,
        'count'   => count($rows),
        'data'    => $rows
    ]);
}

/**
 * Export Rekap ke Tampilan Cetak Resmi PDF / Printable HTML
 */
function handleExportPdf(PDO $pdo): void {
    requireRole(['admin', 'pimpinan']);
    $tipe = trim($_GET['tipe'] ?? 'harian'); // 'harian' atau 'bulanan'
    $tanggal = trim($_GET['tanggal'] ?? date('Y-m-d'));
    $bulan = trim($_GET['bulan'] ?? date('Y-m'));

    $namaInstansi = getSetting('nama_instansi', 'Balai Penjaminan Mutu Pendidikan (BPMP) Provinsi Gorontalo');
    $kementerian = getSetting('kementerian', 'Kementerian Pendidikan Dasar dan Menengah');
    $alamatInstansi = getSetting('alamat_instansi', 'Jl. Kasmat Lahay, Gorontalo');

    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Cetak Rekap SIKMA - <?= htmlspecialchars($namaInstansi) ?></title>
        <style>
            body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; margin: 30px; color: #000; }
            .kop { text-align: center; border-bottom: 3px double #000; padding-bottom: 12px; margin-bottom: 20px; }
            .kop h3 { margin: 0; font-size: 13pt; text-transform: uppercase; font-weight: normal; }
            .kop h2 { margin: 4px 0; font-size: 15pt; text-transform: uppercase; font-weight: bold; }
            .kop p { margin: 0; font-size: 10pt; font-style: italic; }
            .judul { text-align: center; margin-bottom: 20px; }
            .judul h4 { margin: 0; text-transform: uppercase; text-decoration: underline; font-size: 13pt; }
            .judul p { margin: 4px 0; font-size: 11pt; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10pt; }
            th, td { border: 1px solid #000; padding: 6px 8px; text-align: left; }
            th { background-color: #f2f2f2; text-align: center; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .footer-sign { margin-top: 40px; float: right; width: 250px; text-align: center; }
            .footer-sign .space { height: 70px; }
            @media print {
                .no-print { display: none; }
                body { margin: 15mm; }
            }
        </style>
    </head>
    <body>
        <div class="no-print" style="margin-bottom: 15px;">
            <button onclick="window.print()" style="padding: 8px 16px; background: #1A5276; color: #fff; border: none; cursor: pointer; border-radius: 4px;">🖨️ Cetak / Simpan PDF</button>
            <button onclick="window.history.back()" style="padding: 8px 16px; background: #555; color: #fff; border: none; cursor: pointer; border-radius: 4px;">Kembali</button>
        </div>

        <div class="kop">
            <h3><?= htmlspecialchars($kementerian) ?></h3>
            <h2><?= htmlspecialchars($namaInstansi) ?></h2>
            <p><?= htmlspecialchars($alamatInstansi) ?></p>
        </div>

        <div class="judul">
            <h4><?= $tipe === 'bulanan' ? 'Laporan Rekapitulasi Bulanan Keluar–Masuk Kantor' : 'Laporan Rekapitulasi Harian Keluar–Masuk Kantor' ?></h4>
            <p>Periode: <strong><?= $tipe === 'bulanan' ? htmlspecialchars($bulan) : date('d F Y', strtotime($tanggal)) ?></strong></p>
        </div>

        <?php if ($tipe === 'bulanan'): 
            $stmt = $pdo->prepare("
                SELECT peg.nama_lengkap, peg.nip, u.nama_unit,
                       COUNT(DISTINCT DATE(pk.jam_keluar)) AS jml_hari,
                       COUNT(pk.id) AS total_keluar,
                       SUM(CASE WHEN p_keluar.keperluan_jenis = 'dinas' THEN 1 ELSE 0 END) AS dinas,
                       SUM(CASE WHEN p_keluar.keperluan_jenis = 'keperluan_lain' THEN 1 ELSE 0 END) AS tanpa_izin,
                       SUM(CASE WHEN pk.status = 'belum_kembali' THEN 1 ELSE 0 END) AS belum_kembali,
                       COALESCE(SUM(pk.durasi_menit), 0) AS total_menit
                FROM pegawai peg
                LEFT JOIN unit_kerja u ON peg.unit_kerja_id = u.id
                LEFT JOIN pasangan_keluar_masuk pk ON peg.id = pk.pegawai_id AND DATE_FORMAT(pk.jam_keluar, '%Y-%m') = :bulan
                LEFT JOIN pindaian p_keluar ON pk.pindaian_keluar_id = p_keluar.id
                WHERE peg.status = 'aktif'
                GROUP BY peg.id
                ORDER BY total_keluar DESC, peg.nama_lengkap ASC
            ");
            $stmt->execute(['bulan' => $bulan]);
            $rows = $stmt->fetchAll();
        ?>
            <table>
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Nama Pegawai / NIP</th>
                        <th>Unit Kerja</th>
                        <th width="10%">Hari Keluar</th>
                        <th width="10%">Total Keluar</th>
                        <th width="10%">Dinas</th>
                        <th width="10%">Tanpa Izin</th>
                        <th width="12%">Belum Kembali</th>
                        <th width="15%">Total Durasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="9" class="text-center">Tidak ada data aktivitas pada bulan ini</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $idx => $r): ?>
                            <tr>
                                <td class="text-center"><?= $idx + 1 ?></td>
                                <td><strong><?= htmlspecialchars($r['nama_lengkap']) ?></strong><br><small><?= htmlspecialchars($r['nip']) ?></small></td>
                                <td><?= htmlspecialchars($r['nama_unit'] ?? '-') ?></td>
                                <td class="text-center"><?= (int)$r['jml_hari'] ?></td>
                                <td class="text-center"><?= (int)$r['total_keluar'] ?></td>
                                <td class="text-center"><?= (int)$r['dinas'] ?></td>
                                <td class="text-center"><?= (int)$r['tanpa_izin'] ?></td>
                                <td class="text-center"><?= (int)$r['belum_kembali'] ?></td>
                                <td class="text-right"><?= formatDurasiMenitRekap((int)$r['total_menit']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php else: 
            $stmt = $pdo->prepare("
                SELECT pk.*, peg.nama_lengkap, peg.nip, u.nama_unit, p_keluar.keperluan_jenis, iz.tujuan
                FROM pasangan_keluar_masuk pk
                JOIN pegawai peg ON pk.pegawai_id = peg.id
                LEFT JOIN unit_kerja u ON peg.unit_kerja_id = u.id
                LEFT JOIN pindaian p_keluar ON pk.pindaian_keluar_id = p_keluar.id
                LEFT JOIN izin_dinas iz ON p_keluar.izin_dinas_id = iz.id
                WHERE DATE(pk.jam_keluar) = :tanggal
                ORDER BY pk.jam_keluar ASC
            ");
            $stmt->execute(['tanggal' => $tanggal]);
            $rows = $stmt->fetchAll();
        ?>
            <table>
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Nama Pegawai / NIP</th>
                        <th>Unit Kerja</th>
                        <th>Jam Keluar</th>
                        <th>Jam Kembali</th>
                        <th>Durasi</th>
                        <th>Keperluan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="8" class="text-center">Tidak ada catatan keluar pada tanggal ini</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $idx => $r): ?>
                            <tr>
                                <td class="text-center"><?= $idx + 1 ?></td>
                                <td><strong><?= htmlspecialchars($r['nama_lengkap']) ?></strong><br><small><?= htmlspecialchars($r['nip']) ?></small></td>
                                <td><?= htmlspecialchars($r['nama_unit'] ?? '-') ?></td>
                                <td class="text-center"><?= date('H:i', strtotime($r['jam_keluar'])) ?></td>
                                <td class="text-center"><?= !empty($r['jam_kembali']) ? date('H:i', strtotime($r['jam_kembali'])) : '-' ?></td>
                                <td class="text-center"><?= !empty($r['durasi_menit']) ? formatDurasiMenitRekap((int)$r['durasi_menit']) : '-' ?></td>
                                <td><?= htmlspecialchars($r['keperluan_jenis'] === 'dinas' ? 'Dinas: ' . ($r['tujuan'] ?? '') : 'Keperluan Lain') ?></td>
                                <td class="text-center"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $r['status']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="footer-sign">
            <p>Gorontalo, <?= date('d F Y') ?><br>Mengetahui,<br>Kepala Subbagian Umum</p>
            <div class="space"></div>
            <p><strong>Drs. Ramdan Wartabone, M.Si.</strong><br>NIP. 198003152005011002</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Export data rekap ke format CSV Excel
 */
function handleExportExcel(PDO $pdo): void {
    requireRole(['admin', 'pimpinan']);
    $tipe = trim($_GET['tipe'] ?? 'harian');
    $tanggal = trim($_GET['tanggal'] ?? date('Y-m-d'));
    $bulan = trim($_GET['bulan'] ?? date('Y-m'));

    $filename = "Rekap_SIKMA_BPMP_{$tipe}_" . ($tipe === 'bulanan' ? $bulan : $tanggal) . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    // Write UTF-8 BOM agar Excel membaca aksen huruf dengan benar
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    if ($tipe === 'bulanan') {
        fputcsv($output, ['No', 'NIP', 'Nama Pegawai', 'Unit Kerja', 'Jumlah Hari Keluar', 'Total Keluar', 'Dinas', 'Tanpa Izin Dinas', 'Belum Kembali', 'Total Menit']);

        $stmt = $pdo->prepare("
            SELECT peg.nip, peg.nama_lengkap, u.nama_unit,
                   COUNT(DISTINCT DATE(pk.jam_keluar)) AS jml_hari,
                   COUNT(pk.id) AS total_keluar,
                   SUM(CASE WHEN p_keluar.keperluan_jenis = 'dinas' THEN 1 ELSE 0 END) AS dinas,
                   SUM(CASE WHEN p_keluar.keperluan_jenis = 'keperluan_lain' THEN 1 ELSE 0 END) AS tanpa_izin,
                   SUM(CASE WHEN pk.status = 'belum_kembali' THEN 1 ELSE 0 END) AS belum_kembali,
                   COALESCE(SUM(pk.durasi_menit), 0) AS total_menit
            FROM pegawai peg
            LEFT JOIN unit_kerja u ON peg.unit_kerja_id = u.id
            LEFT JOIN pasangan_keluar_masuk pk ON peg.id = pk.pegawai_id AND DATE_FORMAT(pk.jam_keluar, '%Y-%m') = :bulan
            LEFT JOIN pindaian p_keluar ON pk.pindaian_keluar_id = p_keluar.id
            WHERE peg.status = 'aktif'
            GROUP BY peg.id
            ORDER BY total_keluar DESC, peg.nama_lengkap ASC
        ");
        $stmt->execute(['bulan' => $bulan]);
        $rows = $stmt->fetchAll();

        foreach ($rows as $i => $r) {
            fputcsv($output, [
                $i + 1,
                "'" . $r['nip'], // prefix apostrophe agar nomor NIP tidak dipotong jadi floating point oleh Excel
                $r['nama_lengkap'],
                $r['nama_unit'] ?? '-',
                (int)$r['jml_hari'],
                (int)$r['total_keluar'],
                (int)$r['dinas'],
                (int)$r['tanpa_izin'],
                (int)$r['belum_kembali'],
                (int)$r['total_menit']
            ]);
        }
    } else {
        fputcsv($output, ['No', 'NIP', 'Nama Pegawai', 'Unit Kerja', 'Jam Keluar', 'Jam Kembali', 'Durasi Menit', 'Keperluan', 'Status']);

        $stmt = $pdo->prepare("
            SELECT pk.*, peg.nip, peg.nama_lengkap, u.nama_unit, p_keluar.keperluan_jenis, iz.tujuan
            FROM pasangan_keluar_masuk pk
            JOIN pegawai peg ON pk.pegawai_id = peg.id
            LEFT JOIN unit_kerja u ON peg.unit_kerja_id = u.id
            LEFT JOIN pindaian p_keluar ON pk.pindaian_keluar_id = p_keluar.id
            LEFT JOIN izin_dinas iz ON p_keluar.izin_dinas_id = iz.id
            WHERE DATE(pk.jam_keluar) = :tanggal
            ORDER BY pk.jam_keluar ASC
        ");
        $stmt->execute(['tanggal' => $tanggal]);
        $rows = $stmt->fetchAll();

        foreach ($rows as $i => $r) {
            fputcsv($output, [
                $i + 1,
                "'" . $r['nip'],
                $r['nama_lengkap'],
                $r['nama_unit'] ?? '-',
                $r['jam_keluar'],
                $r['jam_kembali'] ?? '-',
                $r['durasi_menit'] ?? '-',
                $r['keperluan_jenis'] === 'dinas' ? 'Dinas: ' . ($r['tujuan'] ?? '') : 'Keperluan Lain',
                $r['status']
            ]);
        }
    }

    fclose($output);
    exit;
}

/**
 * Format helper
 */
function formatDurasiMenitRekap(int $menit): string {
    if ($menit <= 0) return "0 menit";
    if ($menit < 60) return "{$menit} menit";
    $jam = floor($menit / 60);
    $sisa = $menit % 60;
    return ($sisa > 0) ? "{$jam} jam {$sisa} menit" : "{$jam} jam";
}
