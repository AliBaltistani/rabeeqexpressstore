<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `orders` MODIFY `payment_method` ENUM('stripe','paypal','cod','bank_transfer','tamara','tabby','wallet') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `orders` MODIFY `payment_method` ENUM('stripe','paypal','cod','bank_transfer') NOT NULL");
    }
};
