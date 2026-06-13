<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignId('loyalty_reward_id')->nullable()->after('user_id')->constrained('loyalty_rewards')->nullOnDelete();
            $table->enum('source', ['admin', 'loyalty', 'referral', 'system'])->default('admin')->after('is_active');
            $table->json('applicable_shipping_method_ids')->nullable()->after('source');

            $table->index('user_id');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['loyalty_reward_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['source']);
            $table->dropColumn(['user_id', 'loyalty_reward_id', 'source', 'applicable_shipping_method_ids']);
        });
    }
};
