<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change carrier_type from enum to string to support dynamic carrier codes
        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->string('carrier_type', 50)->default('standard')->change();
        });
    }

    public function down(): void
    {
        // Revert to enum (only safe if all values are one of the three original)
        DB::statement("ALTER TABLE shipping_methods MODIFY carrier_type ENUM('smsa','standard','local') DEFAULT 'standard'");
    }
};
