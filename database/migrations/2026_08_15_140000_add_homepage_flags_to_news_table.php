<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * «Показать на главной» для новостей — как у страниц.
 *
 * У страниц такой переключатель есть с самого начала, у новостей его не было:
 * на главную попадали ВСЕ опубликованные материалы, и убрать оттуда один,
 * оставив его в ленте новостей, было нечем.
 *
 * ⚠️ ЗНАЧЕНИЕ ПО УМОЛЧАНИЮ — true, В ОТЛИЧИЕ ОТ СТРАНИЦ.
 *
 * У страниц по умолчанию false: страница живёт по своему адресу, а на главную
 * её выносят осознанно. У новостей наоборот — главная и есть их витрина, и
 * сегодня там показаны все. Поставь я false, при первом же запуске главная
 * опустела бы целиком: правильное поведение поля не должно означать потерю
 * содержимого. Существующие записи проставляются тем же значением явно.
 *
 * homepage_order — необязательное закрепление наверху: чем меньше число, тем
 * выше. Пусто — обычный порядок по дате.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            if (! Schema::hasColumn('news', 'show_on_homepage')) {
                $table->boolean('show_on_homepage')->default(true)->after('published');
            }

            if (! Schema::hasColumn('news', 'homepage_order')) {
                $table->integer('homepage_order')->nullable()->after('show_on_homepage');
            }
        });

        // Явно, а не полагаясь на умолчание: у части драйверов оно не
        // применяется к уже существующим строкам.
        DB::table('news')->whereNull('show_on_homepage')->update(['show_on_homepage' => true]);
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            foreach (['show_on_homepage', 'homepage_order'] as $колонка) {
                if (Schema::hasColumn('news', $колонка)) {
                    $table->dropColumn($колонка);
                }
            }
        });
    }
};
