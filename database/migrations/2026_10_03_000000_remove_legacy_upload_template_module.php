<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $menuIds = DB::table('menu')
                ->where('route_name', 'upload-template.index')
                ->pluck('id');

            DB::table('hak_akses')->whereIn('id_menu', $menuIds)->delete();
            DB::table('role_user')->whereIn('id_menu', $menuIds)->delete();
            DB::table('menu')->whereIn('id', $menuIds)->delete();
        });

        // document_templates is shared by Cetak Berkas and customer document printing.
    }

    public function down(): void
    {
        // The retired module and its per-user permissions cannot be restored here.
    }
};
