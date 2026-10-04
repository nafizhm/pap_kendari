<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $userId = 1;
        $menuIds = DB::table('menu')->pluck('id');

        foreach ($menuIds as $menuId) {
            DB::table('hak_akses')->updateOrInsert(
                [
                    'id_user' => $userId,
                    'id_menu' => $menuId,
                ],
                [
                    'lihat'   => 1,
                    'beranda' => 1,
                    'tambah'  => 1,
                    'edit'    => 1,
                    'hapus'   => 1,
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('hak_akses')->where('id_user', 1)->delete();
    }
};
