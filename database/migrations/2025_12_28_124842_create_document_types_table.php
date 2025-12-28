<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // Contoh: SK CPNS, SHM, PK
            $table->string('category'); // Contoh: Kepegawaian, Agunan, Kredit
            $table->boolean('is_mandatory')->default(false); // Apakah wajib ada?
            $table->boolean('has_expiry')->default(false); // Apakah ada tgl kadaluarsa?
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
