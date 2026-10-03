<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kavling_peta', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        Schema::table('kavling_peta', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(0);
        });
        DB::table('kavling_peta')->whereIn('id', function ($query) {
            $query->select('id_kavling')->from('pengajuan_hold')->where('stt_reg', 1);
        })->update(['status' => 1]);
        DB::table('kavling_peta')->whereIn('id', function ($query) {
            $query->select('id_kavling')->from('customer')
                ->where(fn ($customer) => $customer->where('stt_arsip', 0)->orWhereNull('stt_arsip'));
        })->update(['status' => 2]);
    }
};
