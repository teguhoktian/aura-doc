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
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('loan_id')->constrained()->cascadeOnDelete();
            $table->string('document_type'); // PK, APHT, SHT, SHM, dll
            $table->string('document_number'); // No Sertifikat/SK
            $table->json('legal_metadata')->nullable(); // Simpan Luas, Notaris, dll di sini
            $table->enum('status', ['in_vault', 'borrowed', 'released'])->default('in_vault');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
