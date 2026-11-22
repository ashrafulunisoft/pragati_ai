<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // `orders` is already created with this FK via
        // `->constrained('insurance_packages')`, so guard against duplicates.
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('CONSTRAINT_NAME', 'orders_insurance_package_id_foreign')
            ->exists();

        if (!$exists) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('insurance_package_id')->references('id')->on('insurance_packages')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        // The FK is owned by create_orders_table; nothing to do here.
    }
};
