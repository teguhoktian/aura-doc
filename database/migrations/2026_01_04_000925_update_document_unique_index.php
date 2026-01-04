<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // HAPUS UNIQUE LAMA
            $table->dropUnique('documents_document_number_unique');

            // BUAT UNIQUE BARU
            $table->unique(
                ['loan_id', 'document_type_id', 'document_number'],
                'documents_unique_loan_type_number'
            );
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // BALIKKAN JIKA ROLLBACK
            $table->dropUnique('documents_unique_loan_type_number');
            $table->unique('document_number', 'documents_document_number_unique');
        });
    }
};
