<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');           // translatable: {en, ar}
            $table->json('description')->nullable(); // translatable
            $table->decimal('base_cost', 10, 2)->default(0);
            $table->enum('carrier_type', ['smsa', 'standard', 'local'])->default('standard');
            $table->boolean('is_active')->default(true);
            $table->json('supported_countries')->nullable(); // ["SA","BH","AE"]
            $table->integer('estimated_days_min')->nullable();
            $table->integer('estimated_days_max')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('carrier_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
    }
};
