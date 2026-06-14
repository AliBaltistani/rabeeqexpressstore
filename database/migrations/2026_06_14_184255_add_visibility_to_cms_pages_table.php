<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->boolean('show_in_header')->default(false)->after('sort_order');
            $table->boolean('show_in_footer')->default(false)->after('show_in_header');
        });
    }

    public function down(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->dropColumn(['show_in_header', 'show_in_footer']);
        });
    }
};
