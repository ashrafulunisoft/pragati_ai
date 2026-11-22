<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The create migration already creates `insurance_packages`, so only
        // rename if the legacy misspelled table exists on older databases.
        if (Schema::hasTable('insurance_pacages') && !Schema::hasTable('insurance_packages')) {
            Schema::rename('insurance_pacages', 'insurance_packages');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('insurance_packages') && !Schema::hasTable('insurance_pacages')) {
            Schema::rename('insurance_packages', 'insurance_pacages');
        }
    }
};
