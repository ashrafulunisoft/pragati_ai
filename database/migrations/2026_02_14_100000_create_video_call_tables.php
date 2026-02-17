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
        // Agents table - customer care representatives
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('department')->nullable();
            $table->enum('status', ['free', 'busy', 'offline'])->default('offline');
            $table->timestamp('last_seen')->nullable();
            $table->integer('total_calls')->default(0);
            $table->integer('total_duration')->default(0); // in seconds
            $table->float('average_rating')->default(0);
            $table->timestamps();
        });

        // Call queue for waiting customers
        Schema::create('call_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->enum('status', ['waiting', 'connected', 'cancelled', 'timeout'])->default('waiting');
            $table->integer('position')->default(0);
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        // Call sessions - tracking actual calls
        Schema::create('call_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('channel_name')->unique();
            $table->string('agora_uid')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained('agents')->onDelete('set null');
            $table->foreignId('call_queue_id')->nullable()->constrained('call_queue')->onDelete('set null');
            $table->enum('status', ['ringing', 'connected', 'ended'])->default('ringing');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration')->default(0); // in seconds
            $table->timestamps();
        });

        // Call feedback and ratings
        Schema::create('call_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained('agents')->onDelete('set null');
            $table->integer('rating')->comment('1-5 stars');
            $table->text('comment')->nullable();
            $table->text('customer_name')->nullable();
            $table->timestamps();
        });

        // Call metrics for admin dashboard
        Schema::create('call_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('date')->useCurrent();
            $table->integer('total_calls')->default(0);
            $table->integer('connected_calls')->default(0);
            $table->integer('missed_calls')->default(0);
            $table->integer('total_duration')->default(0);
            $table->integer('total_wait_time')->default(0);
            $table->float('average_wait_time')->default(0);
            $table->float('average_rating')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_feedback');
        Schema::dropIfExists('call_sessions');
        Schema::dropIfExists('call_queue');
        Schema::dropIfExists('call_metrics');
        Schema::dropIfExists('agents');
    }
};
