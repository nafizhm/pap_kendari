-- Jalankan sekali di server yang belum menjalankan migrasi ini.
ALTER TABLE `persyaratan_legal` ADD COLUMN `file_jenis_berkas` JSON NULL;
-- Kolom percakapan_wa lama dipertahankan untuk menjaga data lama;
-- fitur upload percakapan WA sudah dihapus dari aplikasi.
