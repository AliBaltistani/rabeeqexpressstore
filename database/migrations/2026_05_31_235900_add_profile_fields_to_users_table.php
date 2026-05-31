<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->date('birth_date')->nullable()->after('last_name');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('birth_date');
            $table->boolean('profile_completed')->default(false)->after('gender');
            $table->boolean('promotional_messages')->default(true)->after('profile_completed');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'birth_date', 'gender', 'profile_completed', 'promotional_messages']);
        });
    }
};
