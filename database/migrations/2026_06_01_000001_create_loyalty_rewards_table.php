<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_rewards', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->enum('type', ['discount', 'free_shipping']); // reward category
            $table->json('name');           // {"en": "...", "ar": "..."}
            $table->json('description')->nullable(); // {"en": "...", "ar": "..."}
            $table->integer('points_cost'); // cost in loyalty points
            $table->decimal('discount_value', 8, 2)->nullable(); // percentage or fixed amount
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->string('image')->nullable(); // optional image path
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_rewards');
    }
};
