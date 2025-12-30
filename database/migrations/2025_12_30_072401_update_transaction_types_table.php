<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Hapus constraint lama dan buat baru untuk mendukung notaris
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_type_check");
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_type_check 
        CHECK (type IN ('borrow', 'return', 'release', 'notary_send', 'notary_return'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_type_check");
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_type_check 
        CHECK (type IN ('borrow', 'return', 'release'))");
    }
};
