-- Jalankan sekali di database server yang belum memakai migrasi berkas booking.
-- Pilih SQL ini ATAU migrasi Laravel, jangan jalankan keduanya di database yang sama.
CREATE TABLE `berkas_booking` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `kode` VARCHAR(100) NOT NULL,
    `nama` VARCHAR(255) NOT NULL,
    `urutan` INT UNSIGNED NOT NULL DEFAULT 0,
    `wajib` TINYINT(1) NOT NULL DEFAULT 0,
    `aktif` TINYINT(1) NOT NULL DEFAULT 1,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `berkas_booking_kode_unique` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `pengajuan_hold` ADD COLUMN `berkas_booking` JSON NULL;
ALTER TABLE `pengajuan_hold_tempo` ADD COLUMN `berkas_booking` JSON NULL;

-- Jenis berkas awal tetap memakai kolom lama agar file yang sudah ada tetap terbaca.
INSERT INTO `berkas_booking` (`kode`, `nama`, `urutan`, `wajib`, `aktif`) VALUES
('foto_pemohon', 'Foto Pemohon', 1, 0, 1),
('foto_ktp', 'Foto KTP', 2, 1, 1),
('foto_npwp', 'Foto NPWP', 3, 0, 1),
('foto_kk', 'Foto KK', 4, 0, 1),
('foto_bpjs', 'Foto BPJS', 5, 0, 1),
('foto_ktp_p', 'Foto KTP Pasangan', 6, 0, 1),
('file_bukti', 'Bukti Transfer Booking', 7, 0, 1),
('file_sppr', 'File SPPR', 8, 0, 1);

SET @master_data_id = (SELECT `id` FROM `menu` WHERE `title` = 'Master Data' ORDER BY `id` LIMIT 1);
SET @urutan_berkas_booking = (SELECT COALESCE(MAX(`urutan`), 0) + 1 FROM `menu` WHERE `id_parent` = @master_data_id);
INSERT INTO `menu` (`id_parent`, `title`, `route_name`, `icon`, `urutan`, `lihat`, `tambah`, `edit`, `hapus`)
SELECT @master_data_id, 'Berkas Booking', 'berkas-booking.index', 'far fa-circle', @urutan_berkas_booking, 1, 1, 1, 1
WHERE @master_data_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `menu` WHERE `route_name` = 'berkas-booking.index');

SET @berkas_booking_menu_id = (SELECT `id` FROM `menu` WHERE `route_name` = 'berkas-booking.index' ORDER BY `id` LIMIT 1);
-- Ikuti hak akses menu Jenis Berkas yang sudah ada.
SET @sumber_akses_id = COALESCE((SELECT `id` FROM `menu` WHERE `route_name` = 'jenis-berkas.index' ORDER BY `id` LIMIT 1), @master_data_id);
INSERT INTO `hak_akses` (`id_user`, `id_menu`, `lihat`, `beranda`, `tambah`, `edit`, `hapus`)
SELECT a.`id_user`, @berkas_booking_menu_id, a.`lihat`, 0, a.`tambah`, a.`edit`, a.`hapus`
FROM `hak_akses` a WHERE a.`id_menu` = @sumber_akses_id AND @berkas_booking_menu_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM `hak_akses` b WHERE b.`id_user` = a.`id_user` AND b.`id_menu` = @berkas_booking_menu_id);

INSERT INTO `role_user` (`id_role`, `id_menu`, `lihat`, `beranda`, `tambah`, `edit`, `hapus`)
SELECT a.`id_role`, @berkas_booking_menu_id, a.`lihat`, 0, a.`tambah`, a.`edit`, a.`hapus`
FROM `role_user` a WHERE a.`id_menu` = @sumber_akses_id AND @berkas_booking_menu_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM `role_user` b WHERE b.`id_role` = a.`id_role` AND b.`id_menu` = @berkas_booking_menu_id);
