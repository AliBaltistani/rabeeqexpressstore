<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_status_history', function (Blueprint $table) {
            $table->string('status_from')->nullable()->after('status');
            $table->string('status_to')->nullable()->after('status_from');
            $table->foreignId('changed_by')->nullable()->after('created_by')
                  ->constrained('admins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_status_history', function (Blueprint $table) {
            $table->dropForeign(['changed_by']);
            $table->dropColumn(['status_from', 'status_to', 'changed_by']);
        });
    }
};
