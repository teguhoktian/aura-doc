<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE documents ADD CONSTRAINT documents_document_number_unique UNIQUE(document_number)");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE documents DROP CONSTRAINT IF EXISTS documents_document_number_unique");
    }
};
