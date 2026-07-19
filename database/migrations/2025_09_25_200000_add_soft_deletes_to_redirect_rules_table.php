<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Если таблицы нет — создадим её сразу корректно
        if (!Schema::hasTable('redirect_rules')) {
            Schema::create('redirect_rules', function (Blueprint $table) {
                $table->id();
                $table->boolean('is_regex')->default(false);
                $table->string('from');          // исходный путь/паттерн
                $table->string('to');            // куда редиректить
                $table->unsignedSmallInteger('status')->default(302); // 301/302
                $table->integer('priority')->default(0);
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->softDeletes();           // ВАЖНО: добавляем deleted_at

                // Индексы для скорости
                $table->index(['is_regex', 'priority']);
                $table->index('from');
            });

            return;
        }

        // Если таблица уже есть — просто докинем deleted_at при его отсутствии
        if (!Schema::hasColumn('redirect_rules', 'deleted_at')) {
            Schema::table('redirect_rules', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Подстрахуем индексы (необязательно, но полезно)
        Schema::table('redirect_rules', function (Blueprint $table) {
            try { $table->index(['is_regex', 'priority']); } catch (\Throwable $e) {}
            try { $table->index('from'); } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        // Откатим только softDeletes, если мы его добавляли в существующую таблицу
        if (Schema::hasTable('redirect_rules') && Schema::hasColumn('redirect_rules', 'deleted_at')) {
            Schema::table('redirect_rules', function (Blueprint $table) {
                try { $table->dropSoftDeletes(); } catch (\Throwable $e) {}
            });
        }
    }
};
