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
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('shipping_zones')->onDelete('cascade');
            $table->json('name'); // {"en": "...", "ar": "..."}
            $table->enum('method', ['flat', 'free', 'weight_based']);
            $table->decimal('price', 10, 2);
            $table->decimal('min_order_for_free', 10, 2)->nullable();
            $table->decimal('min_weight', 8, 2)->default(0);
            $table->decimal('max_weight', 8, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
