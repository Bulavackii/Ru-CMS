<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Сохранённые сборки каптчи («пресеты») из конструктора в /admin/captcha.
 *
 * Лежит в database/migrations, а НЕ в modules/Captcha/Migrations: модульные
 * миграции в этом проекте молча не выполняются, их никто не подхватывает
 * (см. CLAUDE.md). Schema Builder без сырого SQL — миграции обязаны быть
 * драйвер-нейтральными: боевая база PostgreSQL, тесты гоняются на SQLite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('captcha_presets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // По слагу пресет вставляется в материалы: [captcha preset="…"]
            $table->string('slug')->unique();
            $table->string('type', 32)->default('image');
            // Параметры сборки: длина кода, размеры, диапазон чисел и т.п.
            $table->json('options')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('captcha_presets');
    }
};
