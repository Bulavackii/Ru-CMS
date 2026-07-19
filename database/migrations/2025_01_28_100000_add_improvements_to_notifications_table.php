<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Добавление улучшений в таблицу notifications:
 * - Soft Deletes (deleted_at)
 * - Поля created_by и updated_by для отслеживания авторов
 * - Поля priority, starts_at, ends_at, views_count
 * - Дополнительные индексы для производительности
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            // Soft Deletes
            if (!Schema::hasColumn('notifications', 'deleted_at')) {
                $table->softDeletes();
            }

            // Поля для отслеживания авторов
            if (!Schema::hasColumn('notifications', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('created_at');
            }
            if (!Schema::hasColumn('notifications', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('updated_at');
            }

            // Новые поля
            if (!Schema::hasColumn('notifications', 'priority')) {
                $table->integer('priority')->default(0)->after('enabled');
            }
            if (!Schema::hasColumn('notifications', 'starts_at')) {
                $table->timestamp('starts_at')->nullable()->after('priority');
            }
            if (!Schema::hasColumn('notifications', 'ends_at')) {
                $table->timestamp('ends_at')->nullable()->after('starts_at');
            }
            if (!Schema::hasColumn('notifications', 'views_count')) {
                $table->integer('views_count')->default(0)->after('ends_at');
            }

            // Внешние ключи для created_by и updated_by
            if (Schema::hasTable('users')) {
                try {
                    if (!$this->hasForeignKey('notifications', 'notifications_created_by_foreign')) {
                        $table->foreign('created_by', 'notifications_created_by_foreign')
                              ->references('id')->on('users')
                              ->nullOnDelete();
                    }
                } catch (\Throwable $e) {
                    // FK уже существует или ошибка
                }

                try {
                    if (!$this->hasForeignKey('notifications', 'notifications_updated_by_foreign')) {
                        $table->foreign('updated_by', 'notifications_updated_by_foreign')
                              ->references('id')->on('users')
                              ->nullOnDelete();
                    }
                } catch (\Throwable $e) {
                    // FK уже существует или ошибка
                }
            }

            // Индексы для производительности
            try {
                if (!$this->hasIndex('notifications', 'notifications_enabled_index')) {
                    $table->index('enabled', 'notifications_enabled_index');
                }
            } catch (\Throwable $e) {}

            try {
                if (!$this->hasIndex('notifications', 'notifications_target_index')) {
                    $table->index('target', 'notifications_target_index');
                }
            } catch (\Throwable $e) {}

            try {
                if (!$this->hasIndex('notifications', 'notifications_type_index')) {
                    $table->index('type', 'notifications_type_index');
                }
            } catch (\Throwable $e) {}

            try {
                if (!$this->hasIndex('notifications', 'notifications_position_index')) {
                    $table->index('position', 'notifications_position_index');
                }
            } catch (\Throwable $e) {}

            try {
                if (!$this->hasIndex('notifications', 'notifications_route_filter_index')) {
                    $table->index('route_filter', 'notifications_route_filter_index');
                }
            } catch (\Throwable $e) {}

            // Составной индекс для часто используемых запросов
            try {
                if (!$this->hasIndex('notifications', 'notifications_enabled_target_index')) {
                    $table->index(['enabled', 'target'], 'notifications_enabled_target_index');
                }
            } catch (\Throwable $e) {}

            try {
                if (!$this->hasIndex('notifications', 'notifications_active_index')) {
                    $table->index(['enabled', 'starts_at', 'ends_at'], 'notifications_active_index');
                }
            } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            // Удаление индексов
            try {
                $table->dropIndex('notifications_active_index');
            } catch (\Throwable $e) {}
            try {
                $table->dropIndex('notifications_enabled_target_index');
            } catch (\Throwable $e) {}
            try {
                $table->dropIndex('notifications_route_filter_index');
            } catch (\Throwable $e) {}
            try {
                $table->dropIndex('notifications_position_index');
            } catch (\Throwable $e) {}
            try {
                $table->dropIndex('notifications_type_index');
            } catch (\Throwable $e) {}
            try {
                $table->dropIndex('notifications_target_index');
            } catch (\Throwable $e) {}
            try {
                $table->dropIndex('notifications_enabled_index');
            } catch (\Throwable $e) {}

            // Удаление внешних ключей
            try {
                $table->dropForeign('notifications_updated_by_foreign');
            } catch (\Throwable $e) {}
            try {
                $table->dropForeign('notifications_created_by_foreign');
            } catch (\Throwable $e) {}

            // Удаление колонок
            if (Schema::hasColumn('notifications', 'views_count')) {
                $table->dropColumn('views_count');
            }
            if (Schema::hasColumn('notifications', 'ends_at')) {
                $table->dropColumn('ends_at');
            }
            if (Schema::hasColumn('notifications', 'starts_at')) {
                $table->dropColumn('starts_at');
            }
            if (Schema::hasColumn('notifications', 'priority')) {
                $table->dropColumn('priority');
            }
            if (Schema::hasColumn('notifications', 'updated_by')) {
                $table->dropColumn('updated_by');
            }
            if (Schema::hasColumn('notifications', 'created_by')) {
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('notifications', 'deleted_at')) {
                $table->dropColumn('deleted_at');
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




