<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Оценка материала — отдельным полем.
 *
 * Шаблон «Игры» показывал оценку обзора, взятую из поля «Цена»: колонки под
 * неё не было. Побочный эффект вылез сразу — материал с ценой считается
 * товаром, и обзор игры получил на странице ценник «8,50 ₽» и кнопку
 * «В корзину». Оценка и цена — разные вещи, и хранятся теперь раздельно.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            if (! Schema::hasColumn('news', 'rating')) {
                // decimal, а не integer: оценки вида 8.5 привычны для обзоров.
                $table->decimal('rating', 3, 1)->nullable()->after('price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            if (Schema::hasColumn('news', 'rating')) {
                $table->dropColumn('rating');
            }
        });
    }
};
