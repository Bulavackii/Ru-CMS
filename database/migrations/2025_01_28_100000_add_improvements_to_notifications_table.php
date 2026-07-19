<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
     * Проверка существования индекса. Schema::hasIndex() — штатный,
     * кросс-СУБД способ (PostgreSQL/MySQL/SQLite), без самодельных
     * запросов к information_schema, завязанных на MySQL.
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        try {
            return Schema::hasIndex($table, $indexName);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Проверка существования внешнего ключа через Schema::getForeignKeys()
     * (кросс-СУБД, доступно с Laravel 11).
     */
    private function hasForeignKey(string $table, string $fkName): bool
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
};




