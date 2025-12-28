<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loan_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique()->index(); // Contoh: A02, G2C
            $table->string('description'); // Contoh: KMK UMUM
            $table->string('division')->index(); // Contoh: Divisi Komersial
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_types');
    }
};
