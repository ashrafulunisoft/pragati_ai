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
        Schema::table('rfids', function (Blueprint $table) {
            $table->foreignId('visit_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('generated_by')->nullable()->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rfids', function (Blueprint $table) {
            $table->dropForeign(['visit_id']);
            $table->dropForeign(['generated_by']);
            $table->dropColumn(['visit_id', 'generated_by']);
        });
    }
};
