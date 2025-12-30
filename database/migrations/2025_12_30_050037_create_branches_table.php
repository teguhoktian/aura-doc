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
        Schema::create('branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('branch_code')->unique()->index(); // Contoh: 0031
            $table->string('name'); // Contoh: Cabang Sumber
            $table->string('type'); // KC, KCP, KK

            // Self-relationship untuk Hirarki
            $table->uuid('parent_id')->nullable()->index(); // Buat kolomnya dulu tanpa foreign
            $table->timestamps();
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('branches')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
