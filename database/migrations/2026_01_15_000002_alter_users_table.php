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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('password');
            $table->string('avatar')->nullable()->after('email_verified_at');
            $table->boolean('is_active')->default(true)->after('avatar');
            $table->boolean('is_banned')->default(false)->after('is_active');
            $table->text('ban_reason')->nullable()->after('is_banned');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['phone', 'avatar', 'is_active', 'is_banned', 'ban_reason']);
        });
    }
};
