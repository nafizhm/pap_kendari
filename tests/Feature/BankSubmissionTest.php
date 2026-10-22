<?php

namespace Tests\Feature;

use App\Http\Controllers\Transaksi\WawancaraController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BankSubmissionTest extends TestCase
{
    public function test_acc_requires_bank_submission_before_processing_sp3k(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('wawancara', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_masuk_bank')->nullable();
            $table->string('tanda_terima_bank')->nullable();
        });
        DB::table('wawancara')->insert(['id' => 1]);
        Route::post('/_test/bank-acc/{id}', [WawancaraController::class, 'simpanSp3k']);
        $this->postJson('/_test/bank-acc/1')->assertUnprocessable()->assertJsonValidationErrors('tanda_terima_bank');
    }

    public function test_bank_submission_routes_require_authentication(): void
    {
        $this->get(route('wawancara.monitoring-bank'))->assertRedirect(route('login'));
        $this->get(route('wawancara.tanda-terima-bank', 1))->assertRedirect(route('login'));
    }
}
