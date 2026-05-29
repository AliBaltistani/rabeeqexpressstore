<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->decimal('min_order_for_free', 10, 2)->nullable()->after('base_cost')
                  ->comment('Minimum order amount for free shipping (null = never free)');
            $table->decimal('min_weight', 10, 2)->nullable()->after('min_order_for_free');
            $table->decimal('max_weight', 10, 2)->nullable()->after('min_weight');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->dropColumn(['min_order_for_free', 'min_weight', 'max_weight']);
        });
    }
};
