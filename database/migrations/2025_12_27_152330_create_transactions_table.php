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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(); // Staf yang memproses
            $table->string('borrower_name'); // Orang yang meminjam (bisa eksternal)
            $table->enum('type', ['borrow', 'return', 'release']);
            $table->date('transaction_date');
            $table->date('due_date')->nullable(); // Tanggal estimasi kembali
            $table->date('returned_at')->nullable(); // Tanggal realisasi kembali
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
