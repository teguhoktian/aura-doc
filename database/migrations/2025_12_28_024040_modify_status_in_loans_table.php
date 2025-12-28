<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Untuk PostgreSQL, kita perlu mengubah kolom enum ke string secara eksplisit
        // Kita gunakan DB::statement agar kompatibel dengan perilaku Type di Postgres
        Schema::table('loans', function (Blueprint $table) {
            $table->string('status')->default('active')->change();
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            // Jika rollback, kembalikan ke enum (opsional)
            $table->enum('status', ['active', 'closed', 'liquidated'])->default('active')->change();
        });
    }
};
