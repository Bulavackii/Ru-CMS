<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Если таблицы news нет — создаём
        if (!Schema::hasTable('news')) {
            Schema::create('news', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('content')->nullable();
                $table->boolean('published')->default(false);
                $table->unsignedBigInteger('category_id')->nullable();
                $table->timestamps();

                $table->index('published');
                $table->index('category_id');
            });
        } else {
            // 2) Если таблица есть — аккуратно добавим недостающие поля/индексы
            Schema::table('news', function (Blueprint $table) {
                if (!Schema::hasColumn('news', 'published')) {
                    $table->boolean('published')->default(false)->after('content');
                }
                if (!Schema::hasColumn('news', 'category_id')) {
                    $table->unsignedBigInteger('category_id')->nullable()->after('published');
                    $table->index('category_id');
                }
                // Индекс published (на случай отсутствия)
                try { $table->index('published'); } catch (\Throwable $e) {}
            });
        }

        // 3) Внешний ключ только если таблица categories существует и FK ещё не добавлен
        if (Schema::hasTable('categories') && Schema::hasColumn('news', 'category_id')) {
            // Проверяем, нет ли уже внешнего ключа (простая попытка добавить с try/catch)
            try {
                Schema::table('news', function (Blueprint $table) {
                    // имя констрейнта будет news_category_id_foreign по умолчанию
                    $table->foreign('category_id')
                          ->references('id')->on('categories')
                          ->nullOnDelete();
                });
            } catch (\Throwable $e) {
                // FK уже есть или несовместим — просто пропускаем
            }
        }
    }

    public function down(): void
    {
        // Откатываем безопасно
        if (Schema::hasTable('news')) {
            try {
                Schema::table('news', function (Blueprint $table) {
                    // Снимем FK, если он есть
                    try { $table->dropForeign(['category_id']); } catch (\Throwable $e) {}
                });
            } catch (\Throwable $e) {}
            Schema::dropIfExists('news');
        }
    }
};
