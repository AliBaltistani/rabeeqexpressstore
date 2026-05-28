<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_gateway')->nullable()->after('payment_method');
            $table->string('payment_intent_id')->nullable()->after('payment_gateway');
            $table->string('tracking_number')->nullable()->after('transaction_id');
            $table->string('shipping_status')->default('pending')->after('shipping_amount');

            $table->index('payment_gateway');
            $table->index('payment_intent_id');
            $table->index('tracking_number');
            $table->index('shipping_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['payment_gateway']);
            $table->dropIndex(['payment_intent_id']);
            $table->dropIndex(['tracking_number']);
            $table->dropIndex(['shipping_status']);

            $table->dropColumn([
                'payment_gateway',
                'payment_intent_id',
                'tracking_number',
                'shipping_status',
            ]);
        });
    }
};
