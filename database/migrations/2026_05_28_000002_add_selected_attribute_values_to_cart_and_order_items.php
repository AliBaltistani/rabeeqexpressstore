<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->json('selected_attribute_values')->nullable()->after('variant_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->json('selected_attribute_values')->nullable()->after('variant_id');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn('selected_attribute_values');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('selected_attribute_values');
        });
    }
};
