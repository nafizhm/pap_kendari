<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->json('detected_variables')->nullable();
            $table->json('selected_variables')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('document_templates', fn (Blueprint $table) => $table->dropColumn(['detected_variables', 'selected_variables']));
    }
};
