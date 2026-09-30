-- Migration 005: Create izin_dinas table
-- SIKMA BPMP Gorontalo

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `izin_dinas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pegawai_id` INT NOT NULL,
    `atasan_id` INT NOT NULL,
    `tanggal` DATE NOT NULL,
    `perkiraan_jam_pergi` TIME NOT NULL,
    `perkiraan_jam_kembali` TIME NOT NULL,
    `tujuan` VARCHAR(255) NOT NULL,
    `keperluan` TEXT NOT NULL,
    `status` ENUM('menunggu', 'disetujui', 'ditolak') NOT NULL DEFAULT 'menunggu',
    `catatan_atasan` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_izin_pegawai_tanggal` (`pegawai_id`, `tanggal`),
    INDEX `idx_izin_atasan_status` (`atasan_id`, `status`),
    INDEX `idx_izin_status` (`status`),
    CONSTRAINT `fk_izin_dinas_pegawai` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_izin_dinas_atasan` FOREIGN KEY (`atasan_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
