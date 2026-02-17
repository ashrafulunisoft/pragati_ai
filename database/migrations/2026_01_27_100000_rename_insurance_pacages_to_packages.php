<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('insurance_pacages', 'insurance_packages');
    }

    public function down(): void
    {
        Schema::rename('insurance_packages', 'insurance_pacages');
    }
};
