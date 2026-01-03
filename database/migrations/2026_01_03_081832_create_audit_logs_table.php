<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entity_type');     // loans, documents, storages, etc
            $table->uuid('entity_id');
            $table->bigInteger('user_id')->nullable();
            $table->string('action');          // created, updated, deleted
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
