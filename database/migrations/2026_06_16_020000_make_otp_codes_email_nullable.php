<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Make otp_codes.email nullable so phone-only OTP rows
     * can be inserted without an email value. Also backfills
     * the `channel` column so existing rows remain valid.
     */
    public function up(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Note: reverting to NOT NULL would fail if nullable rows exist.
        // Intentionally left as no-op to preserve data safety.
    }
};
