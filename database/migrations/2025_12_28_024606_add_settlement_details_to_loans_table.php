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
        Schema::table('loans', function (Blueprint $table) {
            // Tanggal Pelunasan/Hapus Buku
            $table->date('settled_at')->nullable();

            // Detail Keuangan (Snapshot saat Close/WO)
            $table->decimal('settlement_principal', 15, 2)->default(0); // Pokok
            $table->decimal('settlement_interest', 15, 2)->default(0);  // Bunga
            $table->decimal('settlement_penalty_principal', 15, 2)->default(0); // Denda Pokok
            $table->decimal('settlement_penalty_interest', 15, 2)->default(0);  // Denda Bunga

            // Catatan Landasan Hapus Buku (Nomor SK/Memo)
            $table->string('write_off_basis_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn([
                'settled_at',
                'settlement_principal',
                'settlement_interest',
                'settlement_penalty_principal',
                'settlement_penalty_interest',
                'write_off_basis_number',
            ]);
        });
    }
};
