<?php
/**
 * Pindaian Controller - SIKMA
 * Balai Penjaminan Mutu Pendidikan (BPMP) Gorontalo
 * 
 * Endpoints:
 *   POST PindaianController.php?action=catat_keluar
 *   POST PindaianController.php?action=catat_masuk
 *   GET  PindaianController.php?action=get_status_saya
 *   GET  PindaianController.php?action=get_hari_ini_pos
 *   POST PindaianController.php?action=tutup_manual
 *   POST PindaianController.php?action=update
 *   POST PindaianController.php?action=delete
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$pdo = getDB();

switch ($action) {
    case 'catat_keluar':
        handleCatatKeluar($pdo);
        break;

    case 'catat_masuk':
        handleCatatMasuk($pdo);
        break;

    case 'get_status_saya':
        handleGetStatusSaya($pdo);
        break;

    case 'get_hari_ini_pos':
        handleGetHariIniPos($pdo);
        break;

    case 'tutup_manual':
        handleTutupManual($pdo);
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
            'message' => 'Action tidak valid pada PindaianController'
        ], 400);
}

/**
 * Validasi Token QR dari tabel qr_sesaat
 */
function verifyQRToken(PDO $pdo, string $token, string $expectedJenis): void {
    if (empty($token)) {
        jsonResponse([
            'success' => false,
            'message' => 'Token QR wajib disertakan'
        ], 422);
    }

    $stmt = $pdo->prepare("
        SELECT id, token, jenis, expired_at,
               TIMESTAMPDIFF(SECOND, NOW(), expired_at) AS remaining_seconds
        FROM qr_sesaat
        WHERE token = :token
        LIMIT 1
    ");
    $stmt->execute(['token' => $token]);
    $qr = $stmt->fetch();

    if (!$qr) {
        jsonResponse([
            'success' => false,
            'message' => 'Kode QR tidak dikenali atau salah tempat.'
        ], 404);
    }

    if ($qr['jenis'] !== $expectedJenis) {
        $tempat = ($expectedJenis === 'keluar') ? 'Lobby (QR Keluar)' : 'Pos Satpam (QR Masuk)';
        jsonResponse([
            'success' => false,
            'message' => "Kode QR ini bukan untuk pindaian {$expectedJenis}. Silakan scan kode di {$tempat}."
        ], 400);
    }

    if ($qr['remaining_seconds'] <= 0) {
        jsonResponse([
            'success' => false,
            'message' => 'Kode QR sudah kedaluwarsa. Silakan scan kode QR terbaru di layar.'
        ], 400);
    }
}

/**
 * Catat pindaian keluar di Lobby
 */
function handleCatatKeluar(PDO $pdo): void {
    requireAuth();
    $user = getCurrentUser();

    $pegawai = getCurrentPegawai();
    if (!$pegawai) {
        jsonResponse([
            'success' => false,
            'message' => 'Akun Anda tidak terhubung dengan data pegawai yang aktif'
        ], 403);
    }
    $pegawaiId = (int)$pegawai['id'];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Metode request harus POST'], 405);
    }

    $input = getJsonInput();
    $token = trim($input['token'] ?? '');
    $keperluanJenis = trim($input['keperluan_jenis'] ?? ''); // 'dinas' | 'keperluan_lain'
    $izinDinasId = !empty($input['izin_dinas_id']) ? (int)$input['izin_dinas_id'] : null;

    // 1. Verifikasi QR Token Keluar
    verifyQRToken($pdo, $token, 'keluar');

    // 2. Cek apakah ada sesi keluar yang masih terbuka (mencegah pindaian keluar berturut-turut)
    $stmtOpen = $pdo->prepare("
        SELECT id, jam_keluar 
        FROM pasangan_keluar_masuk 
        WHERE pegawai_id = :pegawai_id AND status = 'terbuka'
        ORDER BY id DESC LIMIT 1
    ");
    $stmtOpen->execute(['pegawai_id' => $pegawaiId]);
    $openSession = $stmtOpen->fetch();

    if ($openSession) {
        jsonResponse([
            'success' => false,
            'message' => 'Anda masih memiliki catatan keluar yang belum ditutup. Harap pindai QR Masuk di pos security saat kembali.'
        ], 400);
    }

    // 3. Validasi Keperluan
    if (!in_array($keperluanJenis, ['dinas', 'keperluan_lain'], true)) {
        $keperluanJenis = 'keperluan_lain';
    }

    // Jika memilih keperluan dinas, cek keabsahan izin dinas
    if ($keperluanJenis === 'dinas') {
        if ($izinDinasId) {
            $stmtIzin = $pdo->prepare("
                SELECT id, status, tanggal FROM izin_dinas 
                WHERE id = :id AND pegawai_id = :pegawai_id
                LIMIT 1
            ");
            $stmtIzin->execute(['id' => $izinDinasId, 'pegawai_id' => $pegawaiId]);
            $izin = $stmtIzin->fetch();

            if (!$izin || $izin['status'] !== 'disetujui') {
                jsonResponse([
                    'success' => false,
                    'message' => 'Izin dinas yang dipilih belum disetujui atasan. Pilih keperluan lain atau tunggu persetujuan.'
                ], 422);
            }
        } else {
            // Cek apakah ada izin dinas disetujui hari ini
            $stmtTodayIzin = $pdo->prepare("
                SELECT id FROM izin_dinas 
                WHERE pegawai_id = :pegawai_id AND tanggal = CURDATE() AND status = 'disetujui'
                ORDER BY id DESC LIMIT 1
            ");
            $stmtTodayIzin->execute(['pegawai_id' => $pegawaiId]);
            $todayIzin = $stmtTodayIzin->fetch();
            if ($todayIzin) {
                $izinDinasId = (int)$todayIzin['id'];
            } else {
                jsonResponse([
                    'success' => false,
                    'message' => 'Tidak ditemukan izin dinas yang sudah disetujui untuk hari ini. Silakan ajukan izin atau pilih keperluan lain.'
                ], 422);
            }
        }
    } else {
        $izinDinasId = null;
    }

    // 4. Catat transaksi ke DB
    $pdo->beginTransaction();
    try {
        $now = date('Y-m-d H:i:s');

        // A. Insert pindaian keluar
        $stmtPindaian = $pdo->prepare("
            INSERT INTO pindaian (pegawai_id, jenis, jam, tempat, keperluan_jenis, izin_dinas_id, created_at)
            VALUES (:pegawai_id, 'keluar', :jam, 'lobby', :keperluan_jenis, :izin_dinas_id, NOW())
        ");
        $stmtPindaian->execute([
            'pegawai_id'      => $pegawaiId,
            'jam'             => $now,
            'keperluan_jenis' => $keperluanJenis,
            'izin_dinas_id'   => $izinDinasId
        ]);
        $pindaianId = (int)$pdo->lastInsertId();

        // B. Insert pasangan keluar masuk (status terbuka)
        $stmtPasangan = $pdo->prepare("
            INSERT INTO pasangan_keluar_masuk (pegawai_id, pindaian_keluar_id, jam_keluar, status, created_at)
            VALUES (:pegawai_id, :pindaian_keluar_id, :jam_keluar, 'terbuka', NOW())
        ");
        $stmtPasangan->execute([
            'pegawai_id'          => $pegawaiId,
            'pindaian_keluar_id'  => $pindaianId,
            'jam_keluar'          => $now
        ]);
        $pasanganId = (int)$pdo->lastInsertId();

        // C. Kaitkan pasangan_id di tabel pindaian
        $stmtUpdatePindaian = $pdo->prepare("UPDATE pindaian SET pasangan_id = :pasangan_id WHERE id = :id");
        $stmtUpdatePindaian->execute(['pasangan_id' => $pasanganId, 'id' => $pindaianId]);

        $pdo->commit();

        logActivity('catat_keluar', 'pindaian', $pindaianId, "Pegawai {$pegawai['nama_lengkap']} keluar dari lobby ({$keperluanJenis})");

        jsonResponse([
            'success'         => true,
            'message'         => 'Pindaian keluar berhasil dicatat di Lobby.',
            'data'            => [
                'pindaian_id'     => $pindaianId,
                'pasangan_id'     => $pasanganId,
                'pegawai_nama'    => $pegawai['nama_lengkap'],
                'jam_keluar'      => $now,
                'keperluan_jenis' => $keperluanJenis,
                'status'          => 'sedang_diluar'
            ]
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse([
            'success' => false,
            'message' => 'Gagal mencatat pindaian keluar: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Catat pindaian masuk di Pos Security
 */
function handleCatatMasuk(PDO $pdo): void {
    requireAuth();
    $user = getCurrentUser();

    $pegawai = getCurrentPegawai();
    if (!$pegawai) {
        jsonResponse([
            'success' => false,
            'message' => 'Akun Anda tidak terhubung dengan data pegawai yang aktif'
        ], 403);
    }
    $pegawaiId = (int)$pegawai['id'];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Metode request harus POST'], 405);
    }

    $input = getJsonInput();
    $token = trim($input['token'] ?? '');

    // 1. Verifikasi QR Token Masuk
    verifyQRToken($pdo, $token, 'masuk');

    // 2. Cari sesi keluar yang masih terbuka untuk pegawai ini
    $stmtOpen = $pdo->prepare("
        SELECT id, jam_keluar, pindaian_keluar_id 
        FROM pasangan_keluar_masuk 
        WHERE pegawai_id = :pegawai_id AND status = 'terbuka'
        ORDER BY id DESC LIMIT 1
    ");
    $stmtOpen->execute(['pegawai_id' => $pegawaiId]);
    $openSession = $stmtOpen->fetch();

    if (!$openSession) {
        jsonResponse([
            'success' => false,
            'message' => 'Pindaian masuk ditolak: Anda tidak memiliki catatan keluar dari lobby yang masih aktif.'
        ], 400);
    }

    $pasanganId = (int)$openSession['id'];
    $jamKeluar = $openSession['jam_keluar'];
    $now = date('Y-m-d H:i:s');

    // Hitung durasi dalam menit
    $diffMinutes = max(0, (int)round((strtotime($now) - strtotime($jamKeluar)) / 60));

    // 3. Catat transaksi ke DB
    $pdo->beginTransaction();
    try {
        // A. Insert pindaian masuk di pos
        $stmtPindaian = $pdo->prepare("
            INSERT INTO pindaian (pegawai_id, jenis, jam, tempat, pasangan_id, created_at)
            VALUES (:pegawai_id, 'masuk', :jam, 'pos', :pasangan_id, NOW())
        ");
        $stmtPindaian->execute([
            'pegawai_id'  => $pegawaiId,
            'jam'         => $now,
            'pasangan_id' => $pasanganId
        ]);
        $pindaianMasukId = (int)$pdo->lastInsertId();

        // B. Update pasangan_keluar_masuk
        $stmtPasangan = $pdo->prepare("
            UPDATE pasangan_keluar_masuk 
            SET pindaian_masuk_id = :pindaian_masuk_id,
                jam_kembali = :jam_kembali,
                durasi_menit = :durasi_menit,
                status = 'kembali',
                updated_at = NOW()
            WHERE id = :id
        ");
        $stmtPasangan->execute([
            'pindaian_masuk_id' => $pindaianMasukId,
            'jam_kembali'       => $now,
            'durasi_menit'      => $diffMinutes,
            'id'                => $pasanganId
        ]);

        $pdo->commit();

        logActivity('catat_masuk', 'pindaian', $pindaianMasukId, "Pegawai {$pegawai['nama_lengkap']} kembali di pos satpam (durasi: {$diffMinutes} menit)");

        jsonResponse([
            'success'      => true,
            'message'      => 'Pindaian masuk di pos security berhasil dicatat. Selamat datang kembali di kantor!',
            'data'         => [
                'pindaian_id'   => $pindaianMasukId,
                'pasangan_id'   => $pasanganId,
                'pegawai_nama'  => $pegawai['nama_lengkap'],
                'jam_keluar'    => $jamKeluar,
                'jam_kembali'   => $now,
                'durasi_menit'  => $diffMinutes,
                'durasi_format' => formatMenitKeJam($diffMinutes),
                'status'        => 'di_kantor'
            ]
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse([
            'success' => false,
            'message' => 'Gagal mencatat pindaian masuk: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Mendapatkan status terkini pegawai yang sedang login
 */
function handleGetStatusSaya(PDO $pdo): void {
    requireAuth();
    $pegawai = getCurrentPegawai();
    if (!$pegawai) {
        jsonResponse([
            'success' => false,
            'message' => 'Data pegawai tidak ditemukan untuk user ini'
        ], 404);
    }
    $pegawaiId = (int)$pegawai['id'];

    // 1. Cek sesi keluar terbuka
    $stmtOpen = $pdo->prepare("
        SELECT pk.*, pk.jam_keluar, p.keperluan_jenis, p.izin_dinas_id,
               TIMESTAMPDIFF(MINUTE, pk.jam_keluar, NOW()) AS durasi_berjalan_menit,
               iz.tujuan, iz.keperluan
        FROM pasangan_keluar_masuk pk
        LEFT JOIN pindaian p ON pk.pindaian_keluar_id = p.id
        LEFT JOIN izin_dinas iz ON p.izin_dinas_id = iz.id
        WHERE pk.pegawai_id = :pegawai_id AND pk.status = 'terbuka'
        ORDER BY pk.id DESC LIMIT 1
    ");
    $stmtOpen->execute(['pegawai_id' => $pegawaiId]);
    $openSession = $stmtOpen->fetch();

    $statusPegawai = $openSession ? 'sedang_diluar' : 'di_kantor';

    // 2. Cek Izin Dinas aktif hari ini
    $stmtIzin = $pdo->prepare("
        SELECT id, tanggal, perkiraan_jam_pergi, perkiraan_jam_kembali, tujuan, keperluan, status
        FROM izin_dinas
        WHERE pegawai_id = :pegawai_id AND tanggal = CURDATE()
        ORDER BY id DESC LIMIT 1
    ");
    $stmtIzin->execute(['pegawai_id' => $pegawaiId]);
    $izinHariIni = $stmtIzin->fetch() ?: null;

    // 3. Ambil 5 riwayat pindaian terakhir hari ini
    $stmtRiwayat = $pdo->prepare("
        SELECT p.id, p.jenis, p.jam, p.tempat, p.keperluan_jenis,
               pk.durasi_menit, pk.status AS status_pasangan
        FROM pindaian p
        LEFT JOIN pasangan_keluar_masuk pk ON p.pasangan_id = pk.id
        WHERE p.pegawai_id = :pegawai_id AND DATE(p.jam) = CURDATE()
        ORDER BY p.jam DESC
        LIMIT 5
    ");
    $stmtRiwayat->execute(['pegawai_id' => $pegawaiId]);
    $recentScans = $stmtRiwayat->fetchAll();

    jsonResponse([
        'success'        => true,
        'pegawai'        => [
            'id'           => $pegawai['id'],
            'nip'          => $pegawai['nip'],
            'nama_lengkap' => $pegawai['nama_lengkap'],
            'jabatan'      => $pegawai['jabatan'],
            'nama_unit'    => $pegawai['nama_unit']
        ],
        'status'         => $statusPegawai,
        'active_session' => $openSession ? [
            'pasangan_id'            => (int)$openSession['id'],
            'jam_keluar'             => $openSession['jam_keluar'],
            'durasi_berjalan_menit'  => (int)$openSession['durasi_berjalan_menit'],
            'durasi_berjalan_format' => formatMenitKeJam((int)$openSession['durasi_berjalan_menit']),
            'keperluan_jenis'        => $openSession['keperluan_jenis'],
            'tujuan'                 => $openSession['tujuan'],
            'keperluan'              => $openSession['keperluan']
        ] : null,
        'izin_hari_ini'  => $izinHariIni,
        'recent_scans'   => $recentScans,
        'server_time'    => date('Y-m-d H:i:s')
    ]);
}

/**
 * Realtime feed pindaian hari ini untuk layar Pos Security
 */
function handleGetHariIniPos(PDO $pdo): void {
    // Akun pos, admin, pimpinan, atasan dapat mengakses
    $sinceId = isset($_GET['since_id']) ? (int)$_GET['since_id'] : 0;

    $sql = "
        SELECT p.id AS pindaian_id, p.jenis, p.jam, p.tempat, p.keperluan_jenis,
               peg.nama_lengkap, peg.nip, peg.jabatan, peg.foto,
               u.nama_unit, u.kode_unit,
               pk.id AS pasangan_id, pk.jam_keluar, pk.jam_kembali, pk.durasi_menit, pk.status AS status_pasangan,
               TIMESTAMPDIFF(MINUTE, pk.jam_keluar, NOW()) AS durasi_berjalan_menit
        FROM pindaian p
        JOIN pegawai peg ON p.pegawai_id = peg.id
        LEFT JOIN unit_kerja u ON peg.unit_kerja_id = u.id
        LEFT JOIN pasangan_keluar_masuk pk ON p.pasangan_id = pk.id
        WHERE DATE(p.jam) = CURDATE()
    ";

    $params = [];
    if ($sinceId > 0) {
        $sql .= " AND p.id > :since_id";
        $params['since_id'] = $sinceId;
    }

    $sql .= " ORDER BY p.jam DESC, p.id DESC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    jsonResponse([
        'success'     => true,
        'count'       => count($rows),
        'server_time' => date('Y-m-d H:i:s'),
        'data'        => $rows
    ]);
}

/**
 * Tutup manual catatan keluar yang masih terbuka oleh Admin
 */
function handleTutupManual(PDO $pdo): void {
    requireRole('admin');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Metode request harus POST'], 405);
    }

    $input = getJsonInput();
    $pasanganId = (int)($input['pasangan_id'] ?? 0);
    $jamKembali = trim($input['jam_kembali'] ?? date('Y-m-d H:i:s'));
    $catatan = trim($input['catatan'] ?? 'Ditutup manual oleh admin kepegawaian');
    $status = in_array($input['status'] ?? '', ['kembali', 'belum_kembali'], true) ? $input['status'] : 'kembali';

    if ($pasanganId <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID pasangan catatan keluar masuk wajib diisi'], 422);
    }

    $stmt = $pdo->prepare("SELECT * FROM pasangan_keluar_masuk WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $pasanganId]);
    $pasangan = $stmt->fetch();

    if (!$pasangan) {
        jsonResponse(['success' => false, 'message' => 'Catatan tidak ditemukan'], 404);
    }

    $diffMinutes = max(0, (int)round((strtotime($jamKembali) - strtotime($pasangan['jam_keluar'])) / 60));

    $updateStmt = $pdo->prepare("
        UPDATE pasangan_keluar_masuk 
        SET jam_kembali = :jam_kembali,
            durasi_menit = :durasi_menit,
            status = :status,
            catatan = :catatan,
            updated_at = NOW()
        WHERE id = :id
    ");
    $updateStmt->execute([
        'jam_kembali'  => $jamKembali,
        'durasi_menit' => $diffMinutes,
        'status'       => $status,
        'catatan'      => $catatan,
        'id'           => $pasanganId
    ]);

    logActivity('tutup_manual', 'pasangan_keluar_masuk', $pasanganId, "Catatan keluar ditutup manual status {$status}");

    jsonResponse([
        'success' => true,
        'message' => 'Catatan berhasil ditutup manual',
        'data'    => [
            'id'           => $pasanganId,
            'status'       => $status,
            'jam_kembali'  => $jamKembali,
            'durasi_menit' => $diffMinutes
        ]
    ]);
}

/**
 * Update data catatan pindaian / pasangan oleh Admin
 */
function handleUpdate(PDO $pdo): void {
    requireRole('admin');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Metode request harus POST'], 405);
    }

    $input = getJsonInput();
    $pasanganId = (int)($input['pasangan_id'] ?? 0);
    $jamKeluar = trim($input['jam_keluar'] ?? '');
    $jamKembali = trim($input['jam_kembali'] ?? '');
    $keperluanJenis = trim($input['keperluan_jenis'] ?? '');
    $catatan = trim($input['catatan'] ?? '');

    if ($pasanganId <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID catatan wajib disertakan'], 422);
    }

    $stmt = $pdo->prepare("SELECT * FROM pasangan_keluar_masuk WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $pasanganId]);
    $item = $stmt->fetch();

    if (!$item) {
        jsonResponse(['success' => false, 'message' => 'Catatan tidak ditemukan'], 404);
    }

    $jamKeluarVal = !empty($jamKeluar) ? $jamKeluar : $item['jam_keluar'];
    $jamKembaliVal = !empty($jamKembali) ? $jamKembali : $item['jam_kembali'];

    $durasi = null;
    $status = $item['status'];
    if (!empty($jamKembaliVal)) {
        $durasi = max(0, (int)round((strtotime($jamKembaliVal) - strtotime($jamKeluarVal)) / 60));
        $status = 'kembali';
    }

    $updateStmt = $pdo->prepare("
        UPDATE pasangan_keluar_masuk 
        SET jam_keluar = :jam_keluar,
            jam_kembali = :jam_kembali,
            durasi_menit = :durasi,
            status = :status,
            catatan = :catatan,
            updated_at = NOW()
        WHERE id = :id
    ");
    $updateStmt->execute([
        'jam_keluar'  => $jamKeluarVal,
        'jam_kembali' => $jamKembaliVal,
        'durasi'      => $durasi,
        'status'      => $status,
        'catatan'     => $catatan,
        'id'          => $pasanganId
    ]);

    // Update keperluan_jenis di pindaian keluar
    if (!empty($keperluanJenis) && in_array($keperluanJenis, ['dinas', 'keperluan_lain'], true)) {
        $stmtP = $pdo->prepare("UPDATE pindaian SET keperluan_jenis = :k WHERE id = :id");
        $stmtP->execute(['k' => $keperluanJenis, 'id' => $item['pindaian_keluar_id']]);
    }

    logActivity('update_catatan', 'pasangan_keluar_masuk', $pasanganId, "Admin mengoreksi waktu/keperluan catatan #{$pasanganId}");

    jsonResponse([
        'success' => true,
        'message' => 'Data catatan berhasil diperbarui'
    ]);
}

/**
 * Hapus catatan oleh Admin
 */
function handleDelete(PDO $pdo): void {
    requireRole('admin');

    $input = getJsonInput();
    $pasanganId = (int)($input['pasangan_id'] ?? ($_GET['pasangan_id'] ?? 0));

    if ($pasanganId <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID catatan tidak valid'], 422);
    }

    $stmt = $pdo->prepare("SELECT * FROM pasangan_keluar_masuk WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $pasanganId]);
    $item = $stmt->fetch();

    if (!$item) {
        jsonResponse(['success' => false, 'message' => 'Data catatan tidak ditemukan'], 404);
    }

    // Hapus pindaian terkait
    $pdo->beginTransaction();
    try {
        if (!empty($item['pindaian_masuk_id'])) {
            $pdo->prepare("DELETE FROM pindaian WHERE id = :id")->execute(['id' => $item['pindaian_masuk_id']]);
        }
        if (!empty($item['pindaian_keluar_id'])) {
            $pdo->prepare("DELETE FROM pindaian WHERE id = :id")->execute(['id' => $item['pindaian_keluar_id']]);
        }
        $pdo->prepare("DELETE FROM pasangan_keluar_masuk WHERE id = :id")->execute(['id' => $pasanganId]);
        $pdo->commit();

        logActivity('delete_catatan', 'pasangan_keluar_masuk', $pasanganId, "Admin menghapus catatan #{$pasanganId}");

        jsonResponse([
            'success' => true,
            'message' => 'Catatan berhasil dihapus dari sistem'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Gagal menghapus data: ' . $e->getMessage()], 500);
    }
}

/**
 * Helper format durasi menit ke jam dan menit
 */
function formatMenitKeJam(int $menit): string {
    if ($menit < 60) {
        return "{$menit} menit";
    }
    $jam = floor($menit / 60);
    $sisaMenit = $menit % 60;
    return ($sisaMenit > 0) ? "{$jam} jam {$sisaMenit} menit" : "{$jam} jam";
}
