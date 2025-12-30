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
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignUuid('notary_id')->nullable()->constrained('notaries')->nullOnDelete();
            $table->date('sent_to_notary_at')->nullable(); // Kapan dikirim ke notaris
            $table->date('expected_return_at')->nullable(); // SLA: Kapan seharusnya kembali
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['notary_id']);
            $table->dropColumn(['notary_id', 'sent_to_notary_at', 'expected_return_at']);
        });
    }
};
