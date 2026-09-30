-- Migration 007: Create pasangan_keluar_masuk table
-- SIKMA BPMP Gorontalo

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `pasangan_keluar_masuk` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pegawai_id` INT NOT NULL,
    `pindaian_keluar_id` INT NOT NULL,
    `jam_keluar` DATETIME NOT NULL,
    `pindaian_masuk_id` INT NULL,
    `jam_kembali` DATETIME NULL,
    `durasi_menit` INT NULL,
    `status` ENUM('terbuka', 'kembali', 'belum_kembali') NOT NULL DEFAULT 'terbuka',
    `catatan` VARCHAR(255) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_pasangan_pegawai_status` (`pegawai_id`, `status`),
    INDEX `idx_pasangan_jam_keluar` (`jam_keluar`),
    INDEX `idx_pasangan_status` (`status`),
    CONSTRAINT `fk_pasangan_pegawai` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_pasangan_pindaian_keluar` FOREIGN KEY (`pindaian_keluar_id`) REFERENCES `pindaian` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_pasangan_pindaian_masuk` FOREIGN KEY (`pindaian_masuk_id`) REFERENCES `pindaian` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
