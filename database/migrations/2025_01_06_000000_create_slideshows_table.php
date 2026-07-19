<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🎞️ Слайдшоу - полная миграция (slideshows + items + link)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Таблица слайдшоу
        Schema::create('slideshows', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('published')->default(false);
            $table->string('position')->default('top');
            $table->timestamps();
        });

        // Элементы слайдшоу
        Schema::create('slideshow_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slideshow_id')->constrained('slideshows')->onDelete('cascade');
            $table->string('file_path');
            $table->enum('media_type', ['image', 'video']);
            $table->text('caption')->nullable();
            $table->string('link')->nullable(); // Сразу добавляем поле link
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slideshow_items');
        Schema::dropIfExists('slideshows');
    }
};

