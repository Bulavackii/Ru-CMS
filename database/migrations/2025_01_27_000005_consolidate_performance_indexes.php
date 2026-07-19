<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⚡ Объединенная миграция индексов для оптимизации производительности
 * 
 * Объединяет следующие миграции:
 * - add_performance_indexes (базовые индексы)
 * - add_additional_performance_indexes (составные индексы)
 * 
 * Примечание: Индексы для news перенесены в consolidate_news_table_fields
 */
return new class extends Migration
{
    public function up(): void
    {
        // Индексы для users
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $this->addIndexIfNotExists($table, 'users', 'email', 'idx_users_email');
                $this->addIndexIfNotExists($table, 'users', 'is_admin', 'idx_users_is_admin');
            });
        }

        // Индексы для categories
        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                if (Schema::hasColumn('categories', 'template')) {
                    $this->addIndexIfNotExists($table, 'categories', 'template', 'idx_categories_template');
                }
                if (Schema::hasColumn('categories', 'slug')) {
                    $this->addIndexIfNotExists($table, 'categories', 'slug', 'idx_categories_slug');
                }
            });
        }

        // Индексы для news_category (pivot)
        if (Schema::hasTable('news_category')) {
            Schema::table('news_category', function (Blueprint $table) {
                $this->addIndexIfNotExists($table, 'news_category', 'news_id', 'idx_news_category_news_id');
                $this->addIndexIfNotExists($table, 'news_category', 'category_id', 'idx_news_category_category_id');
            });
        }

        // Индексы для subscriptions
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $this->addIndexIfNotExists($table, 'subscriptions', 'user_id', 'idx_subscriptions_user_id');
                $this->addIndexIfNotExists($table, 'subscriptions', 'expires_at', 'idx_subscriptions_expires_at');
                $this->addIndexIfNotExists($table, 'subscriptions', 'is_active', 'idx_subscriptions_is_active');
            });
        }

        // Индексы для security_logs
        if (Schema::hasTable('security_logs')) {
            Schema::table('security_logs', function (Blueprint $table) {
                $this->addIndexIfNotExists($table, 'security_logs', 'created_at', 'idx_security_logs_created_at');
                $this->addIndexIfNotExists($table, 'security_logs', 'ip_address', 'idx_security_logs_ip_address');
            });
        }

        // Составные индексы для comments (модуль Comments)
        if (Schema::hasTable('comments')) {
            Schema::table('comments', function (Blueprint $table) {
                // Для запросов: model_type + model_id + status
                if (!$this->hasIndex('comments', 'idx_comments_model_status')) {
                    try {
                        $table->index(['model_type', 'model_id', 'status'], 'idx_comments_model_status');
                    } catch (\Throwable $e) {}
                }
                
                // Для запросов: status + created_at (модерация)
                if (!$this->hasIndex('comments', 'idx_comments_status_created')) {
                    try {
                        $table->index(['status', 'created_at'], 'idx_comments_status_created');
                    } catch (\Throwable $e) {}
                }
            });
        }

        // Индексы для orders (модуль Payments)
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                // Для запросов: status + created_at
                if (!$this->hasIndex('orders', 'idx_orders_status_created')) {
                    try {
                        $table->index(['status', 'created_at'], 'idx_orders_status_created');
                    } catch (\Throwable $e) {}
                }
                
                // Для запросов: user_id + status
                if (!$this->hasIndex('orders', 'idx_orders_user_status')) {
                    try {
                        $table->index(['user_id', 'status'], 'idx_orders_user_status');
                    } catch (\Throwable $e) {}
                }
            });
        }

        // Индексы для pages (модуль Menu)
        if (Schema::hasTable('pages')) {
            Schema::table('pages', function (Blueprint $table) {
                // Для запросов: published + show_on_homepage
                if (!$this->hasIndex('pages', 'idx_pages_published_homepage')) {
                    try {
                        $table->index(['published', 'show_on_homepage'], 'idx_pages_published_homepage');
                    } catch (\Throwable $e) {}
                }
                
                // Для запросов: published + homepage_order
                if (!$this->hasIndex('pages', 'idx_pages_published_order')) {
                    try {
                        $table->index(['published', 'homepage_order'], 'idx_pages_published_order');
                    } catch (\Throwable $e) {}
                }
            });
        }

        // Индексы для content_views (аналитика)
        if (Schema::hasTable('content_views')) {
            Schema::table('content_views', function (Blueprint $table) {
                // Для запросов: model_type + model_id + viewed_at
                if (!$this->hasIndex('content_views', 'idx_content_views_model_viewed')) {
                    try {
                        $table->index(['model_type', 'model_id', 'viewed_at'], 'idx_content_views_model_viewed');
                    } catch (\Throwable $e) {}
                }
            });
        }

        // Индексы для notifications
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                // Индексы только для существующих колонок
                // Для запросов: enabled + created_at (если колонки существуют)
                if (Schema::hasColumn('notifications', 'enabled') && Schema::hasColumn('notifications', 'created_at')) {
                    if (!$this->hasIndex('notifications', 'idx_notifications_enabled_created')) {
                        try {
                            $table->index(['enabled', 'created_at'], 'idx_notifications_enabled_created');
                        } catch (\Throwable $e) {}
                    }
                }
            });
        }
        
        // Индексы для admin_notifications (отдельная таблица)
        if (Schema::hasTable('admin_notifications')) {
            Schema::table('admin_notifications', function (Blueprint $table) {
                // Для запросов: user_id + read_at + created_at
                if (!$this->hasIndex('admin_notifications', 'idx_admin_notifications_user_read_created')) {
                    try {
                        if (Schema::hasColumn('admin_notifications', 'user_id') && 
                            Schema::hasColumn('admin_notifications', 'read_at') && 
                            Schema::hasColumn('admin_notifications', 'created_at')) {
                            $table->index(['user_id', 'read_at', 'created_at'], 'idx_admin_notifications_user_read_created');
                        }
                    } catch (\Throwable $e) {}
                }
            });
        }
    }

    public function down(): void
    {
        $indexes = [
            'users' => ['idx_users_email', 'idx_users_is_admin'],
            'categories' => ['idx_categories_template', 'idx_categories_slug'],
            'news_category' => ['idx_news_category_news_id', 'idx_news_category_category_id'],
            'subscriptions' => ['idx_subscriptions_user_id', 'idx_subscriptions_expires_at', 'idx_subscriptions_is_active'],
            'security_logs' => ['idx_security_logs_created_at', 'idx_security_logs_ip_address'],
            'comments' => ['idx_comments_model_status', 'idx_comments_status_created'],
            'orders' => ['idx_orders_status_created', 'idx_orders_user_status'],
            'pages' => ['idx_pages_published_homepage', 'idx_pages_published_order'],
            'content_views' => ['idx_content_views_model_viewed'],
            'notifications' => ['idx_notifications_enabled_created'],
            'admin_notifications' => ['idx_admin_notifications_user_read_created'],
        ];

        foreach ($indexes as $table => $tableIndexes) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($tableIndexes) {
                    foreach ($tableIndexes as $index) {
                        try {
                            $table->dropIndex($index);
                        } catch (\Throwable $e) {
                            // Индекс не существует
                        }
                    }
                });
            }
        }
    }

    /**
     * Добавить индекс если его еще нет
     */
    private function addIndexIfNotExists(Blueprint $table, string $tableName, string $column, string $indexName): void
    {
        if (!$this->hasIndex($tableName, $indexName) && Schema::hasColumn($tableName, $column)) {
            try {
                $table->index($column, $indexName);
            } catch (\Throwable $e) {
                // Индекс уже существует или ошибка
            }
        }
    }

    /**
     * Проверка существования индекса. Schema::hasIndex() — штатный,
     * кросс-СУБД способ (работает одинаково на PostgreSQL/MySQL/SQLite),
     * без самодельных запросов к information_schema, завязанных на MySQL.
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        try {
            return Schema::hasIndex($table, $indexName);
        } catch (\Throwable $e) {
            return false;
        }
    }
};



