<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Добавление улучшений в таблицы SEO модуля:
 * - Поля created_by и updated_by для отслеживания авторов
 * - Дополнительные индексы для производительности
 */
return new class extends Migration
{
    public function up(): void
    {
        // ========== seo_pages ==========
        if (Schema::hasTable('seo_pages')) {
            Schema::table('seo_pages', function (Blueprint $table) {
                // Поля для отслеживания авторов
                if (!Schema::hasColumn('seo_pages', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('created_at');
                }
                if (!Schema::hasColumn('seo_pages', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->after('updated_at');
                }

                // Внешние ключи для created_by и updated_by
                if (Schema::hasTable('users')) {
                    try {
                        if (!$this->hasForeignKey('seo_pages', 'seo_pages_created_by_foreign')) {
                            $table->foreign('created_by', 'seo_pages_created_by_foreign')
                                  ->references('id')->on('users')
                                  ->nullOnDelete();
                        }
                    } catch (\Throwable $e) {}

                    try {
                        if (!$this->hasForeignKey('seo_pages', 'seo_pages_updated_by_foreign')) {
                            $table->foreign('updated_by', 'seo_pages_updated_by_foreign')
                                  ->references('id')->on('users')
                                  ->nullOnDelete();
                        }
                    } catch (\Throwable $e) {}
                }

                // Индексы для производительности
                try {
                    if (!$this->hasIndex('seo_pages', 'seo_pages_source_type_index')) {
                        $table->index('source_type', 'seo_pages_source_type_index');
                    }
                } catch (\Throwable $e) {}

                try {
                    if (!$this->hasIndex('seo_pages', 'seo_pages_source_id_index')) {
                        $table->index('source_id', 'seo_pages_source_id_index');
                    }
                } catch (\Throwable $e) {}

                try {
                    if (!$this->hasIndex('seo_pages', 'seo_pages_locked_index')) {
                        $table->index('locked', 'seo_pages_locked_index');
                    }
                } catch (\Throwable $e) {}

                try {
                    if (!$this->hasIndex('seo_pages', 'seo_pages_robots_index')) {
                        $table->index('robots_index', 'seo_pages_robots_index');
                    }
                } catch (\Throwable $e) {}

                // Составной индекс для часто используемых запросов
                try {
                    if (!$this->hasIndex('seo_pages', 'seo_pages_source_type_id_index')) {
                        $table->index(['source_type', 'source_id'], 'seo_pages_source_type_id_index');
                    }
                } catch (\Throwable $e) {}
            });
        }

        // ========== redirect_rules ==========
        if (Schema::hasTable('redirect_rules')) {
            Schema::table('redirect_rules', function (Blueprint $table) {
                // Индексы уже есть в базовой миграции, но добавим составной для оптимизации
                try {
                    if (!$this->hasIndex('redirect_rules', 'redirect_rules_code_index')) {
                        $table->index('code', 'redirect_rules_code_index');
                    }
                } catch (\Throwable $e) {}
            });
        }
    }

    public function down(): void
    {
        // ========== seo_pages ==========
        if (Schema::hasTable('seo_pages')) {
            Schema::table('seo_pages', function (Blueprint $table) {
                // Удаление индексов
                try {
                    $table->dropIndex('seo_pages_source_type_id_index');
                } catch (\Throwable $e) {}
                try {
                    $table->dropIndex('seo_pages_robots_index');
                } catch (\Throwable $e) {}
                try {
                    $table->dropIndex('seo_pages_locked_index');
                } catch (\Throwable $e) {}
                try {
                    $table->dropIndex('seo_pages_source_id_index');
                } catch (\Throwable $e) {}
                try {
                    $table->dropIndex('seo_pages_source_type_index');
                } catch (\Throwable $e) {}

                // Удаление внешних ключей
                try {
                    $table->dropForeign('seo_pages_updated_by_foreign');
                } catch (\Throwable $e) {}
                try {
                    $table->dropForeign('seo_pages_created_by_foreign');
                } catch (\Throwable $e) {}

                // Удаление колонок
                if (Schema::hasColumn('seo_pages', 'updated_by')) {
                    $table->dropColumn('updated_by');
                }
                if (Schema::hasColumn('seo_pages', 'created_by')) {
                    $table->dropColumn('created_by');
                }
            });
        }

        // ========== redirect_rules ==========
        if (Schema::hasTable('redirect_rules')) {
            Schema::table('redirect_rules', function (Blueprint $table) {
                try {
                    $table->dropIndex('redirect_rules_code_index');
                } catch (\Throwable $e) {}
            });
        }
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




