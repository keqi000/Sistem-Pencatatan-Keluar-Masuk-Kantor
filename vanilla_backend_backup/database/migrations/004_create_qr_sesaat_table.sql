-- Migration 004: Create qr_sesaat table
-- SIKMA BPMP Gorontalo

CREATE TABLE IF NOT EXISTS `qr_sesaat` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `token` VARCHAR(64) NOT NULL UNIQUE,
    `jenis` ENUM('keluar', 'masuk') NOT NULL,
    `expired_at` DATETIME NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_qr_jenis_expired` (`jenis`, `expired_at`),
    INDEX `idx_qr_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
