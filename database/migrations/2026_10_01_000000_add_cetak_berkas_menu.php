<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $customer = DB::table('menu')->where('id_parent', 0)->where('title', 'Customer')->first();
            $position = $customer ? $customer->urutan + 1 : (int) DB::table('menu')->where('id_parent', 0)->max('urutan') + 1;
            DB::table('menu')->where('id_parent', 0)->where('urutan', '>=', $position)->increment('urutan');
            $parent = DB::table('menu')->insertGetId([
                'id_parent' => 0, 'title' => 'Cetak Berkas', 'route_name' => 'cetak-berkas',
                'icon' => 'fas fa-print', 'urutan' => $position,
                'lihat' => 1, 'tambah' => 0, 'edit' => 0, 'hapus' => 0,
            ]);
            $child = DB::table('menu')->insertGetId([
                'id_parent' => $parent, 'title' => 'Pengaturan Template', 'route_name' => 'pengaturan-template.index',
                'icon' => 'far fa-file-word', 'urutan' => 1,
                'lihat' => 1, 'tambah' => 1, 'edit' => 1, 'hapus' => 1,
            ]);
            $oldMenu = DB::table('menu')->where('route_name', 'upload-template.index')->value('id');
            foreach (['hak_akses' => ['users', 'id_user'], 'role_user' => ['role', 'id_role']] as $table => [$source, $key]) {
                foreach (DB::table($source)->get() as $record) {
                    $old = $oldMenu ? DB::table($table)->where($key, $record->id)->where('id_menu', $oldMenu)->first() : null;
                    $roleName = $source === 'users'
                        ? DB::table('role')->where('id', $record->id_role)->value('role')
                        : $record->role;
                    $admin = in_array(strtolower((string) $roleName), ['admin', 'administrator', 'super admin', 'superadmin', 'developer'])
                        || ($source === 'users' && $record->username === 'dev');
                    $rights = [];
                    foreach (['lihat', 'tambah', 'edit', 'hapus'] as $action) {
                        $rights[$action] = $old ? (int) $old->{$action} : (int) $admin;
                    }
                    DB::table($table)->insert(array_merge([$key => $record->id, 'id_menu' => $child, 'beranda' => 0], $rights));
                    DB::table($table)->insert([
                        $key => $record->id, 'id_menu' => $parent, 'beranda' => 0,
                        'lihat' => $rights['lihat'], 'tambah' => 0, 'edit' => 0, 'hapus' => 0,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $parent = DB::table('menu')->where('route_name', 'cetak-berkas')->first();
            if (! $parent) return;
            $ids = DB::table('menu')->where('id_parent', $parent->id)->pluck('id')->push($parent->id);
            DB::table('hak_akses')->whereIn('id_menu', $ids)->delete();
            DB::table('role_user')->whereIn('id_menu', $ids)->delete();
            DB::table('menu')->whereIn('id', $ids)->delete();
            DB::table('menu')->where('id_parent', 0)->where('urutan', '>', $parent->urutan)->decrement('urutan');
        });
    }
};
