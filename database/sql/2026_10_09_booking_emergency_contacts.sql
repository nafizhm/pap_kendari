-- Jalankan sekali sebelum menggunakan form kontak darurat yang baru.
ALTER TABLE `pengajuan_hold`
    ADD COLUMN `kontak_darurat_1` VARCHAR(255) NULL,
    ADD COLUMN `nama_pemilik_kontak_darurat_1` VARCHAR(255) NULL,
    ADD COLUMN `keterangan_kontak_darurat_1` VARCHAR(255) NULL,
    ADD COLUMN `kontak_darurat_2` VARCHAR(255) NULL,
    ADD COLUMN `nama_pemilik_kontak_darurat_2` VARCHAR(255) NULL,
    ADD COLUMN `keterangan_kontak_darurat_2` VARCHAR(255) NULL,
    ADD COLUMN `kontak_darurat_3` VARCHAR(255) NULL,
    ADD COLUMN `nama_pemilik_kontak_darurat_3` VARCHAR(255) NULL,
    ADD COLUMN `keterangan_kontak_darurat_3` VARCHAR(255) NULL,
    ADD COLUMN `kontak_darurat_4` VARCHAR(255) NULL,
    ADD COLUMN `nama_pemilik_kontak_darurat_4` VARCHAR(255) NULL,
    ADD COLUMN `keterangan_kontak_darurat_4` VARCHAR(255) NULL,
    ADD COLUMN `kontak_darurat_5` VARCHAR(255) NULL,
    ADD COLUMN `nama_pemilik_kontak_darurat_5` VARCHAR(255) NULL,
    ADD COLUMN `keterangan_kontak_darurat_5` VARCHAR(255) NULL;

UPDATE `pengajuan_hold` SET `kontak_darurat_1` = `no_telp_saudara`,
    `nama_pemilik_kontak_darurat_1` = `nama_saudara`
WHERE `kontak_darurat_1` IS NULL AND `nama_pemilik_kontak_darurat_1` IS NULL;

ALTER TABLE `pengajuan_hold_tempo`
    ADD COLUMN `kontak_darurat_1` VARCHAR(255) NULL,
    ADD COLUMN `nama_pemilik_kontak_darurat_1` VARCHAR(255) NULL,
    ADD COLUMN `keterangan_kontak_darurat_1` VARCHAR(255) NULL,
    ADD COLUMN `kontak_darurat_2` VARCHAR(255) NULL,
    ADD COLUMN `nama_pemilik_kontak_darurat_2` VARCHAR(255) NULL,
    ADD COLUMN `keterangan_kontak_darurat_2` VARCHAR(255) NULL,
    ADD COLUMN `kontak_darurat_3` VARCHAR(255) NULL,
    ADD COLUMN `nama_pemilik_kontak_darurat_3` VARCHAR(255) NULL,
    ADD COLUMN `keterangan_kontak_darurat_3` VARCHAR(255) NULL,
    ADD COLUMN `kontak_darurat_4` VARCHAR(255) NULL,
    ADD COLUMN `nama_pemilik_kontak_darurat_4` VARCHAR(255) NULL,
    ADD COLUMN `keterangan_kontak_darurat_4` VARCHAR(255) NULL,
    ADD COLUMN `kontak_darurat_5` VARCHAR(255) NULL,
    ADD COLUMN `nama_pemilik_kontak_darurat_5` VARCHAR(255) NULL,
    ADD COLUMN `keterangan_kontak_darurat_5` VARCHAR(255) NULL;

UPDATE `pengajuan_hold_tempo` SET `kontak_darurat_1` = `no_telp_saudara`,
    `nama_pemilik_kontak_darurat_1` = `nama_saudara`
WHERE `kontak_darurat_1` IS NULL AND `nama_pemilik_kontak_darurat_1` IS NULL;

ALTER TABLE `customer`
    ADD COLUMN `kontak_darurat_1` VARCHAR(255) NULL,
    ADD COLUMN `nama_pemilik_kontak_darurat_1` VARCHAR(255) NULL,
    ADD COLUMN `keterangan_kontak_darurat_1` VARCHAR(255) NULL,
    ADD COLUMN `kontak_darurat_2` VARCHAR(255) NULL,
    ADD COLUMN `nama_pemilik_kontak_darurat_2` VARCHAR(255) NULL,
    ADD COLUMN `keterangan_kontak_darurat_2` VARCHAR(255) NULL,
    ADD COLUMN `kontak_darurat_3` VARCHAR(255) NULL,
    ADD COLUMN `nama_pemilik_kontak_darurat_3` VARCHAR(255) NULL,
    ADD COLUMN `keterangan_kontak_darurat_3` VARCHAR(255) NULL,
    ADD COLUMN `kontak_darurat_4` VARCHAR(255) NULL,
    ADD COLUMN `nama_pemilik_kontak_darurat_4` VARCHAR(255) NULL,
    ADD COLUMN `keterangan_kontak_darurat_4` VARCHAR(255) NULL,
    ADD COLUMN `kontak_darurat_5` VARCHAR(255) NULL,
    ADD COLUMN `nama_pemilik_kontak_darurat_5` VARCHAR(255) NULL,
    ADD COLUMN `keterangan_kontak_darurat_5` VARCHAR(255) NULL;

UPDATE `customer` SET `kontak_darurat_1` = `no_telp_saudara`,
    `nama_pemilik_kontak_darurat_1` = `nama_saudara`
WHERE `kontak_darurat_1` IS NULL AND `nama_pemilik_kontak_darurat_1` IS NULL;

