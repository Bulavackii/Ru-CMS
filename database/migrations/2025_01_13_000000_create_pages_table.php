<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 📄 Страницы - полная миграция (pages + page_category)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Таблица страниц
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content')->nullable();
            $table->boolean('published')->default(false);
            $table->boolean('show_on_homepage')->default(false);
            $table->integer('homepage_order')->default(0);
            $table->string('template')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();
        });

        // Связь страниц с категориями (pivot)
        Schema::create('page_category', function (Blueprint $table) {
            $table->unsignedBigInteger('page_id');
            $table->unsignedBigInteger('category_id');
            
            $table->primary(['page_id', 'category_id']);
            
            $table->foreign('page_id')
                ->references('id')->on('pages')
                ->onDelete('cascade');
            
            $table->foreign('category_id')
                ->references('id')->on('categories')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_category');
        Schema::dropIfExists('pages');
    }
};
