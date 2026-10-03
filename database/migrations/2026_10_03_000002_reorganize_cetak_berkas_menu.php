<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $transaksi = DB::table('menu')->where('id_parent', 0)->where('title', 'Transaksi')->first();
            $cetak = DB::table('menu')->where('route_name', 'cetak-berkas')->first();

            if (! $transaksi || ! $cetak) {
                throw new RuntimeException('Menu Transaksi dan Cetak Berkas harus tersedia terlebih dahulu.');
            }

            $dashboardIds = DB::table('menu')->where('id_parent', 0)
                ->where(function ($query) {
                    $query->whereIn('route_name', ['dashboard', 'dashboard.index'])
                        ->orWhere('title', 'Dashboard');
                })->pluck('id');

            foreach (['hak_akses', 'role_user'] as $table) {
                DB::table($table)->whereIn('id_menu', $dashboardIds)->delete();
            }
            DB::table('menu')->whereIn('id', $dashboardIds)->delete();

            DB::table('menu')->where('id', $cetak->id)->update(['title' => 'Cetak berkas']);

            $parents = DB::table('menu')->where('id_parent', 0)->where('id', '!=', $cetak->id)
                ->orderBy('urutan')->orderBy('id')->pluck('id');
            $position = 0;
            foreach ($parents as $parentId) {
                DB::table('menu')->where('id', $parentId)->update(['urutan' => $position++]);
                if ($parentId == $transaksi->id) {
                    DB::table('menu')->where('id', $cetak->id)->update(['urutan' => $position++]);
                }
            }

            $documentRoutes = ['bast.index', 'sppr.index'];
            $otherChildren = DB::table('menu')->where('id_parent', $cetak->id)
                ->whereNotIn('route_name', $documentRoutes)->orderBy('urutan')->orderBy('id')->pluck('id');
            foreach ($documentRoutes as $index => $route) {
                DB::table('menu')->where('route_name', $route)
                    ->update(['id_parent' => $cetak->id, 'urutan' => $index + 1]);
            }
            foreach ($otherChildren as $index => $childId) {
                DB::table('menu')->where('id', $childId)->update(['urutan' => $index + 3]);
            }

            // Parent visibility must include everyone who can view any of its children.
            $childIds = DB::table('menu')->where('id_parent', $cetak->id)->pluck('id');
            foreach (['hak_akses' => 'id_user', 'role_user' => 'id_role'] as $table => $key) {
                $owners = DB::table($table)->whereIn('id_menu', $childIds)->where('lihat', 1)
                    ->distinct()->pluck($key);
                foreach ($owners as $ownerId) {
                    $query = DB::table($table)->where($key, $ownerId)->where('id_menu', $cetak->id);
                    if ($query->exists()) {
                        $query->update(['lihat' => 1]);
                    } else {
                        DB::table($table)->insert([
                            $key => $ownerId, 'id_menu' => $cetak->id,
                            'lihat' => 1, 'beranda' => 0, 'tambah' => 0, 'edit' => 0, 'hapus' => 0,
                        ]);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        // Removed Dashboard permissions cannot be reconstructed safely.
    }
};
