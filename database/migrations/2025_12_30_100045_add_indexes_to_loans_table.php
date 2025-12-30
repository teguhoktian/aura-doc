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
        Schema::table('loans', function (Blueprint $table) {
            // 1. Tambahkan index pada debtor_name & status jika belum ada
            // Kita bungkus dalam try-catch atau cek manual agar tidak error saat retry
            $table->index('debtor_name');
            $table->index('status');

            // 2. Tambahkan foreignUuid jika belum ada
            if (!Schema::hasColumn('loans', 'branch_id')) {
                $table->foreignUuid('branch_id')->nullable()->after('id')->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('loans', 'loan_type_id')) {
                $table->foreignUuid('loan_type_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex(['debtor_name']);
            $table->dropIndex(['status']);

            // Opsional: Drop foreign jika ingin bersih total saat rollback
            // $table->dropForeign(['branch_id']);
            // $table->dropForeign(['loan_type_id']);
        });
    }
};
