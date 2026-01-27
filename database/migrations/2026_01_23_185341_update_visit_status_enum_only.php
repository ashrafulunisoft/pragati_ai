<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Add new enum values temporarily
        DB::statement("ALTER TABLE visits MODIFY COLUMN status ENUM('pending_otp', 'pending_host', 'approved', 'rejected', 'checked_in', 'completed', 'pending', 'cancelled') NOT NULL");

        // Step 2: Update existing status values
        DB::statement("UPDATE visits SET status = 'pending_host' WHERE status = 'pending'");
        DB::statement("UPDATE visits SET status = 'checked_in' WHERE status = 'cancelled'");

        // Step 3: Remove old enum values
        DB::statement("ALTER TABLE visits MODIFY COLUMN status ENUM('pending_otp', 'pending_host', 'approved', 'rejected', 'checked_in', 'completed') NOT NULL DEFAULT 'pending_otp'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Add back old enum values temporarily
        DB::statement("ALTER TABLE visits MODIFY COLUMN status ENUM('pending_otp', 'pending_host', 'approved', 'rejected', 'checked_in', 'completed', 'pending', 'cancelled') NOT NULL");

        // Step 2: Revert status values
        DB::statement("UPDATE visits SET status = 'pending' WHERE status = 'pending_host'");
        DB::statement("UPDATE visits SET status = 'cancelled' WHERE status = 'checked_in'");

        // Step 3: Remove new enum values
        DB::statement("ALTER TABLE visits MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'cancelled', 'completed') NOT NULL DEFAULT 'pending'");
    }
};
