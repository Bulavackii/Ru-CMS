<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 🔄 Объединенная миграция для таблицы news
 * 
 * Объединяет следующие миграции:
 * - optimize_news_table (поля, slug, мета-поля, магазин)
 * - add_improvements_to_news_table (soft deletes, created_by, updated_by)
 * - индексы из add_performance_indexes и add_additional_performance_indexes
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('news')) {
            return; // Таблица news создается в модуле News
        }

        Schema::table('news', function (Blueprint $table) {
            // Template
            if (!Schema::hasColumn('news', 'template')) {
                $table->string('template', 100)->nullable()->after('published');
            }

            // Slug
            if (!Schema::hasColumn('news', 'slug')) {
                $table->string('slug')->nullable()->after('title');
            }

            // Мета-поля
            if (!Schema::hasColumn('news', 'meta_title')) {
                $table->string('meta_title')->nullable()->after('title');
            }
            if (!Schema::hasColumn('news', 'meta_keywords')) {
                $table->string('meta_keywords')->nullable()->after('meta_title');
            }
            if (!Schema::hasColumn('news', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('meta_keywords');
            }
            if (!Schema::hasColumn('news', 'meta_header')) {
                $table->text('meta_header')->nullable()->after('meta_description');
            }

            // Поля для магазина
            if (!Schema::hasColumn('news', 'price')) {
                $table->decimal('price', 12, 2)->nullable()->after('meta_header');
            }
            if (!Schema::hasColumn('news', 'stock')) {
                $table->integer('stock')->nullable()->after('price');
            }
            if (!Schema::hasColumn('news', 'is_promo')) {
                $table->boolean('is_promo')->default(false)->after('stock');
            }

            // Soft Deletes
            if (!Schema::hasColumn('news', 'deleted_at')) {
                $table->softDeletes();
            }

            // Поля для отслеживания авторов
            if (!Schema::hasColumn('news', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('created_at');
            }
            if (!Schema::hasColumn('news', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('updated_at');
            }
        });

        // Заполнение slug для существующих записей
        try {
            $rows = DB::table('news')->select('id', 'title', 'slug')->whereNull('slug')->get();
            foreach ($rows as $row) {
                $slug = Str::slug((string)$row->title);
                if ($slug === '') {
                    $slug = 'post';
                }
                $slug = $slug . '-' . $row->id;
                DB::table('news')->where('id', $row->id)->update(['slug' => $slug]);
            }
        } catch (\Throwable $e) {
            // Игнорируем ошибки
        }

        // Внешние ключи для created_by и updated_by
        if (Schema::hasTable('users')) {
            Schema::table('news', function (Blueprint $table) {
                try {
                    if (!$this->hasForeignKey('news', 'news_created_by_foreign')) {
                        $table->foreign('created_by', 'news_created_by_foreign')
                              ->references('id')->on('users')
                              ->nullOnDelete();
                    }
                } catch (\Throwable $e) {
                    // FK уже существует или ошибка
                }

                try {
                    if (!$this->hasForeignKey('news', 'news_updated_by_foreign')) {
                        $table->foreign('updated_by', 'news_updated_by_foreign')
                              ->references('id')->on('users')
                              ->nullOnDelete();
                    }
                } catch (\Throwable $e) {
                    // FK уже существует или ошибка
                }
            });
        }

        // Индексы
        Schema::table('news', function (Blueprint $table) {
            // Уникальный индекс для slug
            try {
                if (!$this->hasIndex('news', 'news_slug_unique')) {
                    $table->unique('slug', 'news_slug_unique');
                }
            } catch (\Throwable $e) {}

            // Базовые индексы
            $indexes = [
                ['published', 'news_published_index'],
                ['template', 'news_template_index'],
                ['created_at', 'news_created_at_index'],
            ];

            foreach ($indexes as [$column, $indexName]) {
                if (Schema::hasColumn('news', $column)) {
                    try {
                        if (!$this->hasIndex('news', $indexName)) {
                            $table->index($column, $indexName);
                        }
                    } catch (\Throwable $e) {}
                }
            }

            // Составные индексы
            // Используем только один индекс для ['published', 'created_at']
            try {
                if (!$this->hasIndex('news', 'idx_news_published_created')) {
                    $table->index(['published', 'created_at'], 'idx_news_published_created');
                }
            } catch (\Throwable $e) {}

            try {
                if (!$this->hasIndex('news', 'idx_news_published_template_created')) {
                    $table->index(['published', 'template', 'created_at'], 'idx_news_published_template_created');
                }
            } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('news')) {
            return;
        }

        Schema::table('news', function (Blueprint $table) {
            // Удаление составных индексов
            try {
                $table->dropIndex('idx_news_published_template_created');
            } catch (\Throwable $e) {}
            try {
                $table->dropIndex('idx_news_published_created');
            } catch (\Throwable $e) {}

            // Удаление базовых индексов
            try {
                $table->dropIndex('news_created_at_index');
            } catch (\Throwable $e) {}
            try {
                $table->dropIndex('news_template_index');
            } catch (\Throwable $e) {}
            try {
                $table->dropIndex('news_published_index');
            } catch (\Throwable $e) {}

            // Удаление уникального индекса
            try {
                $table->dropUnique('news_slug_unique');
            } catch (\Throwable $e) {}

            // Удаление внешних ключей
            try {
                $table->dropForeign('news_updated_by_foreign');
            } catch (\Throwable $e) {}
            try {
                $table->dropForeign('news_created_by_foreign');
            } catch (\Throwable $e) {}

            // Удаление колонок
            $columns = [
                'updated_by', 'created_by', 'deleted_at',
                'is_promo', 'stock', 'price',
                'meta_header', 'meta_description', 'meta_keywords', 'meta_title',
                'slug', 'template'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('news', $column)) {
                    try {
                        $table->dropColumn($column);
                    } catch (\Throwable $e) {}
                }
            }
        });
    }

    /**
     * Проверка существования индекса
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        try {
            $connection = Schema::getConnection();
            $database = $connection->getDatabaseName();
            $driver = $connection->getDriverName();
            
            if ($driver === 'mysql') {
                $result = DB::select(
                    "SELECT COUNT(*) as count FROM information_schema.statistics 
                     WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                    [$database, $table, $indexName]
                );
                return $result[0]->count > 0;
            }
        } catch (\Throwable $e) {}
        
        return false;
    }

    /**
     * Проверка существования внешнего ключа
     */
    private function hasForeignKey(string $table, string $fkName): bool
    {
        try {
            $connection = Schema::getConnection();
            $database = $connection->getDatabaseName();
            
            $result = DB::select(
                "SELECT COUNT(*) as count FROM information_schema.TABLE_CONSTRAINTS 
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
                [$database, $table, $fkName]
            );
            return $result[0]->count > 0;
        } catch (\Throwable $e) {}
        
        return false;
    }
};


