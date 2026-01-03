<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->timestamp('deleted_at')->nullable();
            $table->foreignUuid('deleted_by')->nullable();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->timestamp('deleted_at')->nullable();
            $table->foreignUuid('deleted_by')->nullable();
        });

        Schema::table('notaries', function (Blueprint $table) {
            $table->timestamp('deleted_at')->nullable();
            $table->foreignUuid('deleted_by')->nullable();
        });

        Schema::table('storages', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at')->nullable();
            $table->foreignUuid('deleted_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['deleted_at', 'deleted_by']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['deleted_at', 'deleted_by']);
        });

        Schema::table('notaries', function (Blueprint $table) {
            $table->dropColumn(['deleted_at', 'deleted_by']);
        });

        Schema::table('storages', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'deleted_at', 'deleted_by']);
        });
    }
};
