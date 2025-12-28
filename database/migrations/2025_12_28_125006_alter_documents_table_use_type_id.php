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
        Schema::table('documents', function (Blueprint $table) {
            // Hapus kolom string lama jika ada
            $table->dropColumn('document_type');

            // Tambahkan relasi ke master document types
            $table->foreignUuid('document_type_id')->nullable()->constrained('document_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
