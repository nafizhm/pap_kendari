<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_berkas', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
        });

        Schema::table('persyaratan_legal', function (Blueprint $table) {
            $table->json('status_jenis_berkas')->nullable()->after('TRILOGI');
        });

        $namaAwal = ['IPH', 'SHGB', 'SSP', 'BPHTB', 'SIKUMBANG', 'DAFTAR SIKASEP', 'FOTO SIKASEP', 'TRILOGI'];
        foreach ($namaAwal as $index => $nama) {
            DB::table('jenis_berkas')->insert([
                'nama' => $nama,
                'urutan' => $index + 1,
                'aktif' => 1,
            ]);
        }

        $jenis = DB::table('jenis_berkas')->get()->keyBy('nama');
        DB::table('persyaratan_legal')->orderBy('id')->chunkById(100, function ($rows) use ($jenis) {
            foreach ($rows as $row) {
                $status = [];
                foreach (['IPH', 'SHGB', 'SSP', 'BPHTB', 'SIKUMBANG', 'DAFTAR_SIKASEP', 'FOTO_SIKASEP', 'TRILOGI'] as $kolom) {
                    $nama = str_replace('_', ' ', $kolom);
                    if (isset($jenis[$nama])) {
                        $status[(string) $jenis[$nama]->id] = (int) ($row->{$kolom} ?? 0);
                    }
                }
                DB::table('persyaratan_legal')->where('id', $row->id)->update([
                    'status_jenis_berkas' => json_encode($status),
                ]);
            }
        });

        $parentId = DB::table('menu')->where('title', 'Master Data')->value('id');
        if ($parentId && ! DB::table('menu')->where('route_name', 'jenis-berkas.index')->exists()) {
            $menuId = DB::table('menu')->insertGetId([
                'id_parent' => $parentId,
                'title' => 'Jenis Berkas',
                'route_name' => 'jenis-berkas.index',
                'icon' => 'far fa-circle',
                'urutan' => ((int) DB::table('menu')->where('id_parent', $parentId)->max('urutan')) + 1,
                'lihat' => 1,
                'tambah' => 1,
                'edit' => 1,
                'hapus' => 1,
            ]);

            $akses = DB::table('users')->pluck('id')->map(fn ($id) => [
                'id_user' => $id,
                'id_menu' => $menuId,
                'lihat' => 1,
                'tambah' => 1,
                'edit' => 1,
                'hapus' => 1,
            ])->all();

            if ($akses) {
                DB::table('hak_akses')->insert($akses);
            }
        }
    }

    public function down(): void
    {
        $menuId = DB::table('menu')->where('route_name', 'jenis-berkas.index')->value('id');
        if ($menuId) {
            DB::table('hak_akses')->where('id_menu', $menuId)->delete();
            DB::table('menu')->where('id', $menuId)->delete();
        }

        Schema::table('persyaratan_legal', function (Blueprint $table) {
            $table->dropColumn('status_jenis_berkas');
        });
        Schema::dropIfExists('jenis_berkas');
    }
};
