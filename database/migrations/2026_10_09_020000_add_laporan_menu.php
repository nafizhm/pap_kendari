<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $keuangan = DB::table('menu')->where('id_parent', 0)->where('title', 'Keuangan')->first();
            if (! $keuangan) throw new RuntimeException('Menu Keuangan belum tersedia.');
            if (DB::table('menu')->where('route_name', 'laporan.index')->exists()) return;
            DB::table('menu')->where('id_parent', 0)->where('urutan', '>', $keuangan->urutan)->increment('urutan');
            $id = DB::table('menu')->insertGetId([
                'id_parent' => 0, 'title' => 'Laporan', 'route_name' => 'laporan.index', 'icon' => 'fas fa-chart-bar',
                'urutan' => $keuangan->urutan + 1, 'lihat' => 1, 'tambah' => 0, 'edit' => 0, 'hapus' => 0,
            ]);
            $sourceIds = DB::table('menu')->where('id_parent', $keuangan->id)->pluck('id')->push($keuangan->id);
            foreach (['hak_akses' => 'id_user', 'role_user' => 'id_role'] as $table => $key) {
                $owners = DB::table($table)->whereIn('id_menu', $sourceIds)->where('lihat', 1)->distinct()->pluck($key);
                foreach ($owners as $owner) {
                    DB::table($table)->insert([$key => $owner, 'id_menu' => $id, 'lihat' => 1,
                        'beranda' => 0, 'tambah' => 0, 'edit' => 0, 'hapus' => 0]);
                }
            }
        });
    }

    public function down(): void
    {
        $menu = DB::table('menu')->where('route_name', 'laporan.index')->first();
        if (! $menu) return;
        foreach (['hak_akses', 'role_user'] as $table) DB::table($table)->where('id_menu', $menu->id)->delete();
        DB::table('menu')->where('id', $menu->id)->delete();
        DB::table('menu')->where('id_parent', 0)->where('urutan', '>', $menu->urutan)->decrement('urutan');
    }
};
