-- Migration 003: Create unit_kerja table
-- SIKMA BPMP Gorontalo

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `unit_kerja` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_unit` VARCHAR(150) NOT NULL,
    `kode_unit` VARCHAR(50) NOT NULL UNIQUE,
    `pimpinan_id` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_unit_pimpinan` (`pimpinan_id`),
    CONSTRAINT `fk_unit_kerja_pimpinan` FOREIGN KEY (`pimpinan_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
