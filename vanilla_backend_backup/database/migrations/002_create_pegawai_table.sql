-- Migration 002: Create pegawai table
-- SIKMA BPMP Gorontalo

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `pegawai` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nip` VARCHAR(30) NOT NULL UNIQUE,
    `nama_lengkap` VARCHAR(150) NOT NULL,
    `jabatan` VARCHAR(150) NOT NULL,
    `unit_kerja_id` INT NOT NULL,
    `atasan_id` INT NULL,
    `nomor_hp` VARCHAR(25) NULL,
    `foto` VARCHAR(255) NULL,
    `status` ENUM('aktif', 'tidak_aktif') NOT NULL DEFAULT 'aktif',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_pegawai_unit` (`unit_kerja_id`),
    INDEX `idx_pegawai_atasan` (`atasan_id`),
    INDEX `idx_pegawai_status` (`status`),
    CONSTRAINT `fk_pegawai_unit_kerja` FOREIGN KEY (`unit_kerja_id`) REFERENCES `unit_kerja` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_pegawai_atasan` FOREIGN KEY (`atasan_id`) REFERENCES `pegawai` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
