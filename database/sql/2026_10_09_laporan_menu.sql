-- Jalankan sekali di server yang belum memakai migrasi menu Laporan.
START TRANSACTION;
SET @keuangan_id = (SELECT id FROM menu WHERE id_parent = 0 AND title = 'Keuangan' LIMIT 1);
SET @keuangan_urutan = (SELECT urutan FROM menu WHERE id = @keuangan_id);
SET @laporan_exists = (SELECT COUNT(*) FROM menu WHERE route_name = 'laporan.index');
UPDATE menu SET urutan = urutan + 1 WHERE id_parent = 0 AND urutan > @keuangan_urutan AND @laporan_exists = 0;
INSERT INTO menu (id_parent, title, route_name, icon, urutan, lihat, tambah, edit, hapus)
SELECT 0, 'Laporan', 'laporan.index', 'fas fa-chart-bar', @keuangan_urutan + 1, 1, 0, 0, 0
WHERE @keuangan_id IS NOT NULL AND @laporan_exists = 0;
SET @laporan_id = (SELECT id FROM menu WHERE route_name = 'laporan.index' LIMIT 1);
INSERT INTO hak_akses (id_user, id_menu, lihat, beranda, tambah, edit, hapus)
SELECT DISTINCT a.id_user, @laporan_id, 1, 0, 0, 0, 0 FROM hak_akses a JOIN menu m ON m.id = a.id_menu
WHERE (m.id = @keuangan_id OR m.id_parent = @keuangan_id) AND a.lihat = 1 AND @laporan_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM hak_akses b WHERE b.id_user = a.id_user AND b.id_menu = @laporan_id);
INSERT INTO role_user (id_role, id_menu, lihat, beranda, tambah, edit, hapus)
SELECT DISTINCT a.id_role, @laporan_id, 1, 0, 0, 0, 0 FROM role_user a JOIN menu m ON m.id = a.id_menu
WHERE (m.id = @keuangan_id OR m.id_parent = @keuangan_id) AND a.lihat = 1 AND @laporan_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM role_user b WHERE b.id_role = a.id_role AND b.id_menu = @laporan_id);
COMMIT;
