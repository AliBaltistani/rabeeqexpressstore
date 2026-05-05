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
        Schema::create('cms_pages', function (Blueprint $table) {
            $table->id();
            $table->json('title'); // {"en": "...", "ar": "..."}
            $table->string('slug')->unique();
            $table->json('content'); // {"en": "...", "ar": "..."}
            $table->json('excerpt'); // {"en": "...", "ar": "..."}
            $table->enum('template', ['default', 'legal', 'landing'])->default('default');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('featured_image')->nullable();
            $table->text('custom_css')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_pages');
    }
};
