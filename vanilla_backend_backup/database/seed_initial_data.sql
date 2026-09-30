-- Seed Data: Initial default data for SIKMA BPMP Gorontalo
-- Default password for all users: password123

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `pengaturan`;
TRUNCATE TABLE `unit_kerja`;
TRUNCATE TABLE `pegawai`;
TRUNCATE TABLE `users`;
TRUNCATE TABLE `qr_sesaat`;
TRUNCATE TABLE `izin_dinas`;
TRUNCATE TABLE `pindaian`;
TRUNCATE TABLE `pasangan_keluar_masuk`;
TRUNCATE TABLE `activity_log`;

-- 1. Pengaturan Default
INSERT INTO `pengaturan` (`setting_key`, `setting_value`, `keterangan`) VALUES
('nama_instansi', 'Balai Penjaminan Mutu Pendidikan (BPMP) Provinsi Gorontalo', 'Nama resmi instansi'),
('kementerian', 'Kementerian Pendidikan Dasar dan Menengah', 'Nama kementerian penaung'),
('alamat_instansi', 'Jl. Kasmat Lahay, Desa Bulila, Kec. Telaga, Kab. Gorontalo', 'Alamat kantor balai'),
('jam_masuk', '07:30', 'Jam mulai dinas'),
('jam_pulang', '16:00', 'Jam selesai dinas'),
('jam_istirahat_mulai', '12:00', 'Jam mulai istirahat siang'),
('jam_istirahat_selesai', '13:00', 'Jam selesai istirahat siang'),
('hitung_jam_istirahat', '0', '1 = pindaian istirahat dihitung di rekap, 0 = tidak dihitung'),
('qr_interval', '30', 'Durasi masa berlaku kode QR dalam detik (default 30s)'),
('ambang_terlambat_menit', '120', 'Ambang batas menit berada di luar kantor untuk highlight peringatan');

-- 2. Unit Kerja
INSERT INTO `unit_kerja` (`id`, `nama_unit`, `kode_unit`, `pimpinan_id`, `created_at`) VALUES
(1, 'Subbagian Umum', 'SU', NULL, NOW()),
(2, 'Pokja Penjaminan Mutu & Supervisi', 'PMS', NULL, NOW()),
(3, 'Pokja Transformasi Pembelajaran', 'TP', NULL, NOW()),
(4, 'Pokja Tata Kelola & Kemitraan', 'TKK', NULL, NOW());

-- 3. Pegawai
INSERT INTO `pegawai` (`id`, `nip`, `nama_lengkap`, `jabatan`, `unit_kerja_id`, `atasan_id`, `nomor_hp`, `foto`, `status`, `created_at`) VALUES
(1, '197505102000031001', 'Dr. H. Rusdianto, M.Pd.', 'Kepala Balai BPMP Gorontalo', 1, NULL, '081234567801', NULL, 'aktif', NOW()),
(2, '198003152005011002', 'Drs. Ramdan Wartabone, M.Si.', 'Kepala Subbagian Umum', 1, 1, '081234567802', NULL, 'aktif', NOW()),
(3, '198508202010011003', 'Andi Saputra, S.Kom.', 'Pranata Komputer Ahli Pertama', 1, 2, '081234567803', NULL, 'aktif', NOW()),
(4, '199012012015022001', 'Siti Rahmawati, S.Pd.', 'Pengembang Teknologi Pembelajaran', 3, 2, '081234567804', NULL, 'aktif', NOW()),
(5, '199204182019031005', 'Budi Santoso, S.AP.', 'Pengadministrasi Kepegawaian', 1, 2, '081234567805', NULL, 'aktif', NOW());

-- 4. Users (Password: password123)
-- Hash: $2y$10$TLEyRFMBWvfD8DwE/M3AK.z0mHuOsNVsBAkGaPeCTN3NBfu7hQxcG
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `status`, `pegawai_id`, `created_at`) VALUES
(1, 'admin', '$2y$10$TLEyRFMBWvfD8DwE/M3AK.z0mHuOsNVsBAkGaPeCTN3NBfu7hQxcG', 'Admin Kepegawaian BPMP', 'admin', 'aktif', 5, NOW()),
(2, 'pimpinan', '$2y$10$TLEyRFMBWvfD8DwE/M3AK.z0mHuOsNVsBAkGaPeCTN3NBfu7hQxcG', 'Dr. H. Rusdianto, M.Pd.', 'pimpinan', 'aktif', 1, NOW()),
(3, 'atasan', '$2y$10$TLEyRFMBWvfD8DwE/M3AK.z0mHuOsNVsBAkGaPeCTN3NBfu7hQxcG', 'Drs. Ramdan Wartabone, M.Si.', 'atasan', 'aktif', 2, NOW()),
(4, 'pegawai', '$2y$10$TLEyRFMBWvfD8DwE/M3AK.z0mHuOsNVsBAkGaPeCTN3NBfu7hQxcG', 'Andi Saputra, S.Kom.', 'pegawai', 'aktif', 3, NOW()),
(5, 'pegawai2', '$2y$10$TLEyRFMBWvfD8DwE/M3AK.z0mHuOsNVsBAkGaPeCTN3NBfu7hQxcG', 'Siti Rahmawati, S.Pd.', 'pegawai', 'aktif', 4, NOW()),
(6, 'lobby', '$2y$10$TLEyRFMBWvfD8DwE/M3AK.z0mHuOsNVsBAkGaPeCTN3NBfu7hQxcG', 'Layar Lobby BPMP (QR Keluar)', 'lobby', 'aktif', NULL, NOW()),
(7, 'pos', '$2y$10$TLEyRFMBWvfD8DwE/M3AK.z0mHuOsNVsBAkGaPeCTN3NBfu7hQxcG', 'Layar Pos Satpam (QR Masuk)', 'pos', 'aktif', NULL, NOW());

-- Update pimpinan_id di unit kerja
UPDATE `unit_kerja` SET `pimpinan_id` = 2 WHERE `id` = 1;
UPDATE `unit_kerja` SET `pimpinan_id` = 2 WHERE `id` = 2;
UPDATE `unit_kerja` SET `pimpinan_id` = 2 WHERE `id` = 3;
UPDATE `unit_kerja` SET `pimpinan_id` = 2 WHERE `id` = 4;

SET FOREIGN_KEY_CHECKS = 1;
