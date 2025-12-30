<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Hapus check constraint yang lama (biasanya Laravel memberi nama tabel_kolom_check)
        // Jika Anda tidak tahu namanya, kita gunakan pendekatan DROP and RE-CREATE
        DB::statement("ALTER TABLE documents DROP CONSTRAINT IF EXISTS documents_status_check");

        // 2. Tambahkan kembali check constraint dengan nilai yang lebih lengkap
        DB::statement("ALTER TABLE documents ADD CONSTRAINT documents_status_check 
            CHECK (status IN ('in_vault', 'at_notary', 'borrowed', 'released'))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE documents DROP CONSTRAINT IF EXISTS documents_status_check");

        DB::statement("ALTER TABLE documents ADD CONSTRAINT documents_status_check 
            CHECK (status IN ('in_vault', 'borrowed', 'released'))");
    }
};
