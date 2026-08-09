<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Своя картинка у пункта меню.
 *
 * До сих пор значок задавался ТОЛЬКО именем из набора темы (lucide, FA и
 * прочие). Для соцсетей этого мало: фирменных глифов у нас четыре, а сетей
 * бывает сколько угодно, и своего логотипа было не поставить.
 *
 * Поле хранит путь к загруженному файлу и имеет приоритет над именем значка:
 * загрузили картинку — показывается она, убрали — снова работает имя.
 *
 * Schema Builder, а не сырой SQL: боевая база PostgreSQL, тесты идут на
 * SQLite, и миграция обязана работать на обеих.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('menu_items', 'icon_image')) {
            return;
        }

        Schema::table('menu_items', function (Blueprint $table) {
            $table->string('icon_image')->nullable()->after('icon');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('menu_items', 'icon_image')) {
            return;
        }

        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn('icon_image');
        });
    }
};
