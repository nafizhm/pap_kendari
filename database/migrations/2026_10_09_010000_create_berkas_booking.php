<?php

use App\Models\BerkasBooking;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('berkas_booking', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 100)->unique();
            $table->string('nama');
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('wajib')->default(false);
            $table->boolean('aktif')->default(true);
            $table->softDeletes();
        });
        foreach (['pengajuan_hold', 'pengajuan_hold_tempo'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->json('berkas_booking')->nullable());
        }
        foreach (BerkasBooking::LEGACY as $kode => $nama) {
            DB::table('berkas_booking')->insert([
                'kode' => $kode, 'nama' => $nama,
                'urutan' => DB::table('berkas_booking')->count() + 1,
                'wajib' => $kode === 'foto_ktp', 'aktif' => 1,
            ]);
        }
        $parent = DB::table('menu')->where('title', 'Master Data')->value('id');
        if ($parent) {
            $id = DB::table('menu')->insertGetId([
                'id_parent' => $parent, 'title' => 'Berkas Booking', 'route_name' => 'berkas-booking.index',
                'icon' => 'far fa-circle', 'urutan' => DB::table('menu')->where('id_parent', $parent)->max('urutan') + 1,
                'lihat' => 1, 'tambah' => 1, 'edit' => 1, 'hapus' => 1,
            ]);
            $source = DB::table('menu')->where('route_name', 'jenis-berkas.index')->value('id') ?? $parent;
            foreach (['hak_akses' => 'id_user', 'role_user' => 'id_role'] as $table => $key) {
                foreach (DB::table($table)->where('id_menu', $source)->get() as $access) {
                    DB::table($table)->insert([
                        $key => $access->{$key}, 'id_menu' => $id, 'lihat' => $access->lihat,
                        'tambah' => $access->tambah, 'edit' => $access->edit, 'hapus' => $access->hapus,
                        'beranda' => 0,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('menu')->where('route_name', 'berkas-booking.index')->pluck('id');
        foreach (['hak_akses', 'role_user'] as $table) {
            DB::table($table)->whereIn('id_menu', $ids)->delete();
        }
        DB::table('menu')->whereIn('id', $ids)->delete();
        foreach (['pengajuan_hold', 'pengajuan_hold_tempo'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('berkas_booking'));
        }
        Schema::dropIfExists('berkas_booking');
    }
};
