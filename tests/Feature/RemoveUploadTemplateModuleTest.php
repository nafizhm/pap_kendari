<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RemoveUploadTemplateModuleTest extends TestCase
{
    public function test_cleanup_removes_only_legacy_menu_permissions_and_preserves_shared_templates(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));

        Schema::create('menu', function (Blueprint $table) {
            $table->id();
            $table->string('route_name');
        });
        foreach (['hak_akses', 'role_user'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->foreignId('id_menu')->constrained('menu');
            });
            DB::table('menu')->insertOrIgnore([
                ['id' => 1, 'route_name' => 'upload-template.index'],
                ['id' => 2, 'route_name' => 'pengaturan-template.index'],
            ]);
            DB::table($name)->insert([['id_menu' => 1], ['id_menu' => 2]]);
        }
        (require database_path('migrations/2026_07_05_000001_create_document_templates_table.php'))->up();
        DB::table('document_templates')->insert(['nama' => 'Surat', 'kode' => 'surat', 'engine' => 'docx']);

        $migration = require database_path('migrations/2026_10_03_000000_remove_legacy_upload_template_module.php');
        $migration->up();
        $migration->up();

        $this->assertDatabaseMissing('menu', ['id' => 1]);
        $this->assertDatabaseHas('menu', ['id' => 2]);
        foreach (['hak_akses', 'role_user'] as $name) {
            $this->assertDatabaseMissing($name, ['id_menu' => 1]);
            $this->assertDatabaseHas($name, ['id_menu' => 2]);
        }
        $this->assertDatabaseHas('document_templates', ['kode' => 'surat']);
    }

    public function test_legacy_routes_are_removed_and_current_template_routes_remain(): void
    {
        foreach (['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'] as $action) {
            $this->assertFalse(Route::has('upload-template.'.$action));
        }
        $this->assertTrue(Route::has('pengaturan-template.index'));
        $this->assertTrue(Route::has('customer.print-document'));
        $this->get('/admin/master/upload-template')->assertNotFound();
    }
}
