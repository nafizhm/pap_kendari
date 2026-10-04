<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Remove Siteplan BPHTB & SSP (duplicate of Legal > BPHTB & SSP)
        $siteplanBphtbSsp = DB::table('menu')->where('id', 84)->first();
        if ($siteplanBphtbSsp) {
            // Remove hak_akses entries for this menu
            DB::table('hak_akses')->where('id_menu', 84)->delete();
            DB::table('role_user')->where('id_menu', 84)->delete();
            DB::table('menu')->where('id', 84)->delete();
        }

        // 2. Remove Pembelian parent menu (id=15) and its remaining children
        $pembelianChildren = DB::table('menu')->where('id_parent', 15)->pluck('id');
        foreach ($pembelianChildren as $childId) {
            DB::table('hak_akses')->where('id_menu', $childId)->delete();
            DB::table('role_user')->where('id_menu', $childId)->delete();
        }
        DB::table('menu')->where('id_parent', 15)->delete();

        $pembelian = DB::table('menu')->where('id', 15)->first();
        if ($pembelian) {
            DB::table('hak_akses')->where('id_menu', 15)->delete();
            DB::table('role_user')->where('id_menu', 15)->delete();
            DB::table('menu')->where('id', 15)->delete();
        }

        // 3. Remove Role User menu (id=70)
        $roleUser = DB::table('menu')->where('id', 70)->first();
        if ($roleUser) {
            DB::table('hak_akses')->where('id_menu', 70)->delete();
            DB::table('role_user')->where('id_menu', 70)->delete();
            DB::table('menu')->where('id', 70)->delete();
        }
    }

    public function down(): void
    {
        // Re-add Role User menu
        DB::table('menu')->insert([
            'id' => 70,
            'id_parent' => 19,
            'title' => 'Role User',
            'route_name' => 'role-user.index',
            'icon' => 'far fa-circle',
            'urutan' => 4,
            'lihat' => 1,
            'tambah' => 0,
            'edit' => 1,
            'hapus' => 0,
        ]);

        // Re-add Pembelian parent menu
        DB::table('menu')->insert([
            'id' => 15,
            'id_parent' => 0,
            'title' => 'Pembelian',
            'route_name' => '#',
            'icon' => 'fas fa-shopping-cart',
            'urutan' => 14,
            'lihat' => 1,
            'tambah' => 0,
            'edit' => 0,
            'hapus' => 0,
        ]);

        // Re-add Siteplan BPHTB & SSP menu
        DB::table('menu')->insert([
            'id' => 84,
            'id_parent' => 3,
            'title' => 'Siteplan BPHTB & SSP',
            'route_name' => 'st-bphtb-ssp.index',
            'icon' => 'fas fa-circle',
            'urutan' => 5,
            'lihat' => 1,
            'tambah' => 0,
            'edit' => 0,
            'hapus' => 0,
        ]);
    }
};
