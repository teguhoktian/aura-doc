<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Kita gunakan DB::statement karena ini perintah spesifik PostgreSQL
        DB::statement('ALTER TABLE loans DROP CONSTRAINT IF EXISTS loans_status_check');
    }

    public function down(): void
    {
        // Jika rollback, kita bisa tambahkan lagi (opsional)
        // DB::statement("ALTER TABLE loans ADD CONSTRAINT loans_status_check CHECK (status IN ('active', 'closed', 'liquidated'))");
    }
};
