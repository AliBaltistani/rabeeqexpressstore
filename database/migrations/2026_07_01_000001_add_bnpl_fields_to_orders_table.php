<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('gateway_order_id')->nullable()->after('payment_intent_id')
                  ->comment('Tamara order_id or Tabby payment id');
            $table->string('gateway_status')->nullable()->after('gateway_order_id')
                  ->comment('Raw status from BNPL gateway (approved, AUTHORIZED, etc.)');
            $table->json('gateway_payload')->nullable()->after('gateway_status')
                  ->comment('Last webhook payload for debugging');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['gateway_order_id', 'gateway_status', 'gateway_payload']);
        });
    }
};
