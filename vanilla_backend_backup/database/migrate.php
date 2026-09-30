<?php
/**
 * SIKMA - Database Migration & Seeder Runner
 * Balai Penjaminan Mutu Pendidikan (BPMP) Gorontalo
 * 
 * Usage:
 *   CLI: php database/migrate.php [--fresh] [--seed]
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$isCli = (php_sapi_name() === 'cli');
$nl = $isCli ? PHP_EOL : "<br>";

echo "=== SIKMA BPMP GORONTALO - DATABASE MIGRATION ===" . $nl;

try {
    $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
    $dbName = defined('DB_NAME') ? DB_NAME : 'sikma_db';
    $dbUser = defined('DB_USER') ? DB_USER : 'root';
    $dbPass = defined('DB_PASS') ? DB_PASS : '';
    $dbPort = defined('DB_PORT') ? DB_PORT : '3306';

    // 1. Connect without db to ensure database exists
    $pdoRoot = new PDO("mysql:host={$dbHost};port={$dbPort};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "✓ Database `{$dbName}` siap." . $nl;

    // 2. Connect to the specific database
    $pdo = new PDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // 3. Scan and execute migrations
    $migrationsDir = __DIR__ . '/migrations';
    $files = glob($migrationsDir . '/*.sql');
    sort($files);

    echo "Menjalankan migration files..." . $nl;
    foreach ($files as $file) {
        $filename = basename($file);
        $sql = file_get_contents($file);
        
        $pdo->exec($sql);
        echo "  [OK] Migration: {$filename}" . $nl;
    }

    // 4. Seed initial data
    $seedFile = __DIR__ . '/seed_initial_data.sql';
    if (file_exists($seedFile)) {
        echo "Menjalankan seeder data awal..." . $nl;
        $seedSql = file_get_contents($seedFile);
        $pdo->exec($seedSql);
        echo "  [OK] Seeder initial data berhasil dieksekusi!" . $nl;
    }

    echo $nl . "✓ SEMUA MIGRATION & SEEDER BERHASIL DILAKUKAN!" . $nl;
    echo "Akun Default (Password: password123):" . $nl;
    echo "- admin (Admin Kepegawaian)" . $nl;
    echo "- pimpinan (Kepala Balai)" . $nl;
    echo "- atasan (Kasubbag Umum)" . $nl;
    echo "- pegawai (Staf Andi Saputra)" . $nl;
    echo "- lobby (Layar Lobby QR Keluar)" . $nl;
    echo "- pos (Layar Pos Satpam QR Masuk)" . $nl;

} catch (PDOException $e) {
    echo "❌ Terjadi kesalahan Database: " . $e->getMessage() . $nl;
    exit(1);
} catch (Exception $e) {
    echo "❌ Terjadi kesalahan: " . $e->getMessage() . $nl;
    exit(1);
}
