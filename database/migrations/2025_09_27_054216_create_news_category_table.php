<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Создаем pivot-таблицу только если существуют обе основные таблицы
        if (!Schema::hasTable('news_category')) {
            // Проверяем существование основных таблиц
            $newsExists = Schema::hasTable('news');
            $categoriesExists = Schema::hasTable('categories');
            
            if (!$newsExists || !$categoriesExists) {
                // Если таблицы не существуют, пропускаем создание pivot-таблицы
                // Pivot-таблица будет создана позже, когда появятся основные таблицы
                return;
            }
            
            Schema::create('news_category', function (Blueprint $table) use ($newsExists, $categoriesExists) {
                $table->unsignedBigInteger('news_id');
                $table->unsignedBigInteger('category_id');

                $table->primary(['news_id', 'category_id']);

                // Создаем внешние ключи только если таблицы существуют
                if ($newsExists) {
                    $table->foreign('news_id')
                        ->references('id')->on('news')
                        ->onDelete('cascade');
                }

                if ($categoriesExists) {
                    $table->foreign('category_id')
                        ->references('id')->on('categories')
                        ->onDelete('cascade');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('news_category');
    }
};
