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
        Schema::table('visits', function (Blueprint $table) {
            // Add columns if they don't exist
            if (!Schema::hasColumn('visits', 'otp')) {
                $table->string('otp', 6)->nullable()->after('rejected_reason');
            }

            if (!Schema::hasColumn('visits', 'otp_verified_at')) {
                $table->timestamp('otp_verified_at')->nullable()->after('approved_at');
            }

            if (!Schema::hasColumn('visits', 'rfid')) {
                $table->string('rfid', 20)->nullable()->after('otp_verified_at');
            }

            if (!Schema::hasColumn('visits', 'checkin_time')) {
                $table->timestamp('checkin_time')->nullable()->after('approved_at');
            }

            if (!Schema::hasColumn('visits', 'checkout_time')) {
                $table->timestamp('checkout_time')->nullable()->after('checkin_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $columns = ['otp', 'otp_verified_at', 'rfid', 'checkin_time', 'checkout_time'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('visits', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
