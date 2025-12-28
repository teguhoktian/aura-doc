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
        Schema::create('loans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('loan_number')->unique(); // No Kontrak
            $table->string('debtor_name'); // Nama Nasabah
            $table->decimal('plafond', 15, 2);
            $table->date('disbursement_date');
            $table->enum('status', ['active', 'closed', 'liquidated'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
