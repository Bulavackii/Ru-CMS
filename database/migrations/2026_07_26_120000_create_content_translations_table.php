<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Переводы контента из базы: меню, страницы, новости, категории, фрагменты,
 * слайды.
 *
 * Словарями (resources/lang) это не решается — там строки интерфейса, а
 * заголовок новости лежит в БД. До этой таблицы переводов контента не было
 * вовсе: при переключении языка меню оставалось «Главная / О нас», а слайд —
 * «Контент без кода».
 *
 * Таблица общая и полиморфная: одна строка = одно поле одной записи на одном
 * языке. Так перевод подключается к любой модели одинаково (трейт
 * HasContentTranslations), без отдельных колонок в каждой таблице модуля.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_translations', function (Blueprint $table) {
            $table->id();

            // Полиморфная связь с переводимой записью
            $table->string('translatable_type');
            $table->unsignedBigInteger('translatable_id');

            $table->string('locale', 12);
            $table->string('field', 64);
            $table->text('value')->nullable();

            $table->timestamps();

            // Одно значение на «запись + язык + поле»
            $table->unique(
                ['translatable_type', 'translatable_id', 'locale', 'field'],
                'content_translations_unique'
            );

            // Выборка всех переводов записи на текущем языке
            $table->index(['translatable_type', 'translatable_id', 'locale'], 'content_translations_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_translations');
    }
};
