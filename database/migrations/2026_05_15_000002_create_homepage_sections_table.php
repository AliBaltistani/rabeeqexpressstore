<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop old table if it exists
        Schema::dropIfExists('home_sections');

        Schema::create('homepage_sections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // hero_slider, banner, products, custom_html
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_sections');
    }
};
