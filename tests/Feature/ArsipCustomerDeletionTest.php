<?php

namespace Tests\Feature;

use App\Http\Controllers\Customer\ArsipCustomerController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ArsipCustomerDeletionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('customer', function (Blueprint $table) {
            $table->id();
            $table->integer('stt_arsip');
        });
        Schema::create('arsip_customer', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('kavling_peta', function (Blueprint $table) {
            $table->id();
            $table->integer('id_customer')->nullable();
            $table->timestamps();
        });
        DB::table('customer')->insert([['id' => 1, 'stt_arsip' => 1], ['id' => 2, 'stt_arsip' => 0]]);
        DB::table('arsip_customer')->insert(['id' => 1]);
        DB::table('kavling_peta')->insert([
            ['id' => 1, 'id_customer' => 1],
            ['id' => 2, 'id_customer' => 2],
        ]);
        Route::delete('/_test/arsip-customer/{id}', [ArsipCustomerController::class, 'destroy']);
    }

    public function test_permanently_deletes_selected_archived_customer_from_the_correct_table(): void
    {
        $this->deleteJson('/_test/arsip-customer/1')->assertOk()->assertJson(['status' => 'success']);
        $this->assertDatabaseMissing('customer', ['id' => 1]);
        $this->assertDatabaseHas('arsip_customer', ['id' => 1]);
        $this->assertDatabaseHas('kavling_peta', ['id' => 1, 'id_customer' => null]);
        $this->assertDatabaseHas('customer', ['id' => 2, 'stt_arsip' => 0]);
        $this->assertDatabaseHas('kavling_peta', ['id' => 2, 'id_customer' => 2]);
    }

    public function test_active_customer_cannot_be_deleted_through_archive_endpoint(): void
    {
        $this->deleteJson('/_test/arsip-customer/2')->assertNotFound();
        $this->assertDatabaseHas('customer', ['id' => 2, 'stt_arsip' => 0]);
        $this->assertDatabaseHas('kavling_peta', ['id' => 2, 'id_customer' => 2]);
    }

    public function test_repeat_delete_does_not_delete_an_unrelated_legacy_archive(): void
    {
        $this->deleteJson('/_test/arsip-customer/1')->assertOk();
        $this->deleteJson('/_test/arsip-customer/1')->assertNotFound();
        $this->assertDatabaseHas('arsip_customer', ['id' => 1]);
    }
}
