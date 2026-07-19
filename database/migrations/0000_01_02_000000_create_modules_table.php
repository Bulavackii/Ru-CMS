<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // создаём таблицу, если её ещё нет
        if (!Schema::hasTable('modules')) {
            Schema::create('modules', function (Blueprint $table) {
                $table->bigIncrements('id');

                // системное имя модуля (уникально)
                $table->string('name')->unique();

                // человекочитаемый заголовок (нужен для syncModuleMetadata)
                $table->string('title')->nullable();

                // версия модуля (может быть неизвестна на старте)
                $table->string('version')->nullable();

                // активен ли модуль (под это условие вы фильтруете при автозагрузке)
                $table->boolean('active')->default(false)->index();

                // приоритет отображения/загрузки
                $table->unsignedInteger('priority')->default(0)->index();
                
                // дата первичной установки (опционально)
                $table->timestamp('installed_at')->nullable();

                $table->timestamps();
            });

            return;
        }

        // если таблица уже была (например, после ручных правок) — мягко добиваем недостающие колонки
        Schema::table('modules', function (Blueprint $table) {
            if (!Schema::hasColumn('modules', 'title')) {
                $table->string('title')->nullable()->after('name');
            }
            if (!Schema::hasColumn('modules', 'version')) {
                $table->string('version')->nullable()->after('title');
            }
            if (!Schema::hasColumn('modules', 'active')) {
                $table->boolean('active')->default(false)->after('version');
                $table->index('active');
            }
            if (!Schema::hasColumn('modules', 'priority')) {
                $table->unsignedInteger('priority')->default(0)->after('active');
                $table->index('priority');
            }
            if (!Schema::hasColumn('modules', 'installed_at')) {
                $table->timestamp('installed_at')->nullable()->after('priority');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
