<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The imported Rundu drawing was assigned to location 1 twice.
        // Match the known drawing and location before repairing the reference.
        DB::transaction(function () {
            $rundu = DB::table('lokasi_kavling')->where('id', 2)
                ->where('nama_kavling', 'RUNDU REGENCY')->exists();

            if ($rundu && ! DB::table('master_svg')->where('id_lokasi', 2)->exists()) {
                DB::table('master_svg')->where('id', 2)->where('id_lokasi', 1)
                    ->where('header_svg', 'like', '%id="svg-image-2"%')
                    ->update(['id_lokasi' => 2]);
            }
        });
    }

    public function down(): void
    {
        // Keep the corrected association; rollback must not break the siteplan.
    }
};
