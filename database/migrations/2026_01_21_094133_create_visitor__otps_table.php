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
        Schema::create('visitor__otps', function (Blueprint $table) {
            $table->id();

            $table->foreignId('visitor_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->string('otp_hash'); // hashed OTP
            $table->enum('channel', ['email', 'sms', 'both'])->default('both');

            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();

            $table->unsignedTinyInteger('attempts')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['visitor_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor__otps');
    }
};
