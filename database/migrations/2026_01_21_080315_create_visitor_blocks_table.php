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
        Schema::create('visitor_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->constrained()->cascadeOnDelete();
            $table->string('block_type'); // phone, email, rfid, manual
            $table->text('reason');
            $table->foreignId('blocked_by')->constrained('users');
            $table->timestamp('blocked_at');
            $table->foreignId('unblocked_by')->nullable()->constrained('users');
            $table->timestamp('unblocked_at')->nullable();
            $table->enum('status', ['blocked', 'unblocked'])->default('blocked');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_blocks');
    }
};
