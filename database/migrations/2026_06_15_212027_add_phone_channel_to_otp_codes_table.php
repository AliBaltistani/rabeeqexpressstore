<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            // Nullable — email OTPs don't need a phone number
            $table->string('phone', 20)->nullable()->after('email');
            // Channel: 'email' (default, preserves existing rows) or 'sms'
            $table->enum('channel', ['email', 'sms'])->default('email')->after('phone');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->dropIndex(['phone']);
            $table->dropColumn(['phone', 'channel']);
        });
    }
};
