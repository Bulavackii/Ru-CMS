<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 📊 Аналитика - таблицы для статистики
 */
return new class extends Migration
{
    public function up(): void
    {
        // Просмотры контента
        if (!Schema::hasTable('content_views')) {
            Schema::create('content_views', function (Blueprint $table) {
            $table->id();
            $table->string('model_type'); // App\Models\News, App\Models\Page
            $table->unsignedBigInteger('model_id');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referer')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('viewed_at');
            $table->timestamps();

                $table->index(['model_type', 'model_id']);
                $table->index('viewed_at');
                $table->index('user_id');
            });
        }

        // Уникальные посетители
        if (!Schema::hasTable('unique_visitors')) {
            Schema::create('unique_visitors', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('user_agent')->nullable();
            $table->date('visit_date');
            $table->integer('page_views')->default(1);
            $table->integer('session_duration')->default(0); // в секундах
            $table->timestamps();

                $table->unique(['ip_address', 'visit_date']);
                $table->index('visit_date');
            });
        }

        // Популярный контент (кешированная статистика)
        if (!Schema::hasTable('content_statistics')) {
            Schema::create('content_statistics', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->integer('views_count')->default(0);
            $table->integer('unique_views')->default(0);
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamps();

                $table->unique(['model_type', 'model_id', 'period_start', 'period_end']);
                $table->index(['model_type', 'model_id']);
            });
        }

        // Интеграция с Яндекс.Метрикой
        if (!Schema::hasTable('analytics_settings')) {
            Schema::create('analytics_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('yandex'); // yandex, google
            $table->string('api_key')->nullable();
            $table->string('counter_id')->nullable(); // ID счетчика Яндекс.Метрики
            $table->boolean('enabled')->default(false);
                $table->json('settings')->nullable(); // Дополнительные настройки
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_settings');
        Schema::dropIfExists('content_statistics');
        Schema::dropIfExists('unique_visitors');
        Schema::dropIfExists('content_views');
    }
};

