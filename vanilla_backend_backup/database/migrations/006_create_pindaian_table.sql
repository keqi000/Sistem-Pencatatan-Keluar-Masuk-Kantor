-- Migration 006: Create pindaian table
-- SIKMA BPMP Gorontalo

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `pindaian` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pegawai_id` INT NOT NULL,
    `jenis` ENUM('keluar', 'masuk') NOT NULL,
    `jam` DATETIME NOT NULL,
    `tempat` ENUM('lobby', 'pos') NOT NULL,
    `keperluan_jenis` ENUM('dinas', 'keperluan_lain') NULL,
    `izin_dinas_id` INT NULL,
    `pasangan_id` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_pindaian_pegawai_jam` (`pegawai_id`, `jam`),
    INDEX `idx_pindaian_jam` (`jam`),
    INDEX `idx_pindaian_jenis` (`jenis`),
    INDEX `idx_pindaian_tempat` (`tempat`),
    INDEX `idx_pindaian_pasangan` (`pasangan_id`),
    CONSTRAINT `fk_pindaian_pegawai` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_pindaian_izin_dinas` FOREIGN KEY (`izin_dinas_id`) REFERENCES `izin_dinas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_pindaian_pasangan` FOREIGN KEY (`pasangan_id`) REFERENCES `pasangan_keluar_masuk` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
