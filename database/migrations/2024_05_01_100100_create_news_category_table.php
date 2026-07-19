<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsCategoryTable extends Migration
{
    /**
     * Проверка: есть ли уже PK у таблицы. Через Schema::getIndexes() —
     * кросс-СУБД (PostgreSQL/MySQL/SQLite), без запросов к
     * information_schema, завязанных на MySQL (и вообще не работавших на
     * остальных драйверах — DB::table('information_schema...') не
     * существует ни в PostgreSQL, ни тем более в SQLite).
     */
    protected function hasPrimaryKey(string $table): bool
    {
        try {
            foreach (Schema::getIndexes($table) as $index) {
                if ($index['primary'] ?? false) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
        }

        return false;
    }

    /** Проверка: существует ли уже FK по имени (кросс-СУБД, Laravel 11+) */
    protected function hasForeignKey(string $table, string $fkName): bool
    {
        try {
            foreach (Schema::getForeignKeys($table) as $fk) {
                if (($fk['name'] ?? null) === $fkName) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
        }

        return false;
    }

    public function up(): void
    {
        // 1) Создаём таблицу, если её нет
        if (!Schema::hasTable('news_category')) {
            Schema::create('news_category', function (Blueprint $table) {
                $table->unsignedBigInteger('news_id');
                $table->unsignedBigInteger('category_id');

                $table->index('news_id');
                $table->index('category_id');

                // Ставим PK сразу при создании
                $table->primary(['news_id', 'category_id']);
            });
        } else {
            // 2) Таблица есть — убедимся, что нужные колонки и индексы есть
            Schema::table('news_category', function (Blueprint $table) {
                if (!Schema::hasColumn('news_category', 'news_id')) {
                    $table->unsignedBigInteger('news_id');
                    $table->index('news_id');
                }
                if (!Schema::hasColumn('news_category', 'category_id')) {
                    $table->unsignedBigInteger('category_id');
                    $table->index('category_id');
                }
            });

            // 3) Добавим составной PK, если его ещё нет
            if (!$this->hasPrimaryKey('news_category')) {
                Schema::table('news_category', function (Blueprint $table) {
                    $table->primary(['news_id', 'category_id']);
                });
            }
        }

        // 4) Внешние ключи — добавляем, только если их ещё нет
        if (Schema::hasTable('news') && Schema::hasTable('categories')) {
            Schema::table('news_category', function (Blueprint $table) {
                // Имена FK — фиксированные, чтобы можно было проверить/дропать
                if (!(new self)->hasForeignKey('news_category', 'fk_news_category_news')) {
                    try {
                        $table->foreign('news_id', 'fk_news_category_news')
                              ->references('id')->on('news')
                              ->cascadeOnDelete();
                    } catch (\Throwable $e) {
                        // пропускаем, если типы не совпали и т.п.
                    }
                }

                if (!(new self)->hasForeignKey('news_category', 'fk_news_category_category')) {
                    try {
                        $table->foreign('category_id', 'fk_news_category_category')
                              ->references('id')->on('categories')
                              ->cascadeOnDelete();
                    } catch (\Throwable $e) {
                        // пропускаем
                    }
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('news_category')) {
            // Сначала пробуем убрать FK (если существовали с нашими именами)
            try {
                Schema::table('news_category', function (Blueprint $table) {
                    try { $table->dropForeign('fk_news_category_news'); } catch (\Throwable $e) {}
                    try { $table->dropForeign('fk_news_category_category'); } catch (\Throwable $e) {}
                    try { $table->dropForeign(['news_id']); } catch (\Throwable $e) {}
                    try { $table->dropForeign(['category_id']); } catch (\Throwable $e) {}
                });
            } catch (\Throwable $e) {}

            Schema::dropIfExists('news_category');
        }
    }
}
