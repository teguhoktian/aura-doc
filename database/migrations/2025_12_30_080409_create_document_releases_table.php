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
        Schema::create('document_releases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ba_number')->unique(); // Nomor Berita Acara
            $table->date('release_date');
            $table->string('receiver_name'); // Nama penerima (Nasabah/Ahli Waris)
            $table->string('receiver_id_number')->nullable(); // No KTP Penerima
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained(); // Petugas Bank yang menyerahkan
            $table->timestamps();
        });

        // Tambahkan kolom release_id di tabel documents agar kita tahu dokumen ini keluar lewat BA yang mana
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignUuid('document_release_id')->nullable()->constrained('document_releases')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_releases');
    }
};
