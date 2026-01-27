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
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurance_package_id')->constrained('insurance_packages');
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('claim_number')->unique();
            $table->decimal('claim_amount', 12, 2);
            $table->text('reason')->nullable();
            $table->enum('status', ['submitted', 'under_review', 'approved', 'rejected'])
              ->default('submitted');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
