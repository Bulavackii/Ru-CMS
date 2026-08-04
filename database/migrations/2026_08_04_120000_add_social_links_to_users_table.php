<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ссылка на страницу пользователя в MAX.
 *
 * Колонка для ВКонтакте в таблице уже была: её принимает форма профиля и
 * записывает контроллер. Заводить рядом vk_url значило бы получить два поля
 * под одно и то же, поэтому добавляется только недостающее.
 *
 * Хранится ровно то, что ввёл человек — адрес страницы. Разбирать его на
 * идентификатор и собирать обратно незачем: у сетей несколько форм адреса,
 * и попытка «нормализовать» ломала бы редкие случаи вроде коротких имён.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Проверка на существование: на части баз колонку могли завести
            // вручную до появления этой миграции.
            if (! Schema::hasColumn('users', 'max')) {
                $table->string('max')->nullable()->after('vk');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'max')) {
                $table->dropColumn('max');
            }
        });
    }
};
