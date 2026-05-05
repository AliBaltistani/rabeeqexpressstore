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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique(); // ORD-YYYYMMDD-XXXXX
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('guest_email')->nullable();
            $table->string('guest_name')->nullable();
            $table->string('guest_phone')->nullable();
            $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'paid', 'refunded', 'partially_refunded'])->default('unpaid');
            $table->enum('payment_method', ['stripe', 'paypal', 'cod', 'bank_transfer']);
            $table->string('transaction_id')->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('shipping_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('currency_code', 3)->default('SAR');
            $table->decimal('currency_rate', 10, 6);
            $table->foreignId('coupon_id')->nullable();
            $table->string('coupon_code')->nullable();
            $table->text('notes')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
