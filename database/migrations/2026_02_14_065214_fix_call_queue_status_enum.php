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
        // Update status enum to include 'ended' status
        Schema::table('call_queue', function (Blueprint $table) {
            $table->enum('status', ['waiting', 'connected', 'cancelled', 'timeout', 'ended'])->default('waiting')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_queue', function (Blueprint $table) {
            $table->enum('status', ['waiting', 'connected', 'cancelled', 'timeout'])->default('waiting')->change();
        });
    }
};
