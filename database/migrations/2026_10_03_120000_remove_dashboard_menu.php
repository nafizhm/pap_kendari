<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $ids = DB::table('menu')->where('route_name', 'dashboard.index')->pluck('id');
            DB::table('hak_akses')->whereIn('id_menu', $ids)->delete();
            DB::table('role_user')->whereIn('id_menu', $ids)->delete();
            DB::table('menu')->whereIn('id', $ids)->delete();
        });
    }

    public function down(): void
    {
        // Dashboard has been replaced by Beranda; obsolete permissions are not restored.
    }
};
