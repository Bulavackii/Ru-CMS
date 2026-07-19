<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $t) {
            $t->id();
            $t->string('code', 2)->unique()->comment('ISO 3166-1 alpha-2 код страны (RU, US, DE и т.д.)');
            $t->string('name')->comment('Название страны');
            $t->string('native_name')->nullable()->comment('Название страны на родном языке');
            $t->string('flag')->nullable()->comment('Эмодзи флаг страны');
            $t->string('currency_code', 3)->comment('Код валюты (RUB, USD, EUR)');
            $t->string('currency_symbol')->nullable()->comment('Символ валюты (₽, $, €)');
            $t->string('locale')->comment('Основной локаль (ru_RU, en_US, de_DE)');
            $t->string('timezone')->default('UTC')->comment('Часовой пояс');
            $t->string('date_format')->default('d.m.Y')->comment('Формат даты (d.m.Y, Y-m-d, d/m/Y)');
            $t->string('time_format')->default('H:i')->comment('Формат времени (H:i, h:i A)');
            $t->string('decimal_separator')->default('.')->comment('Разделитель дробной части');
            $t->string('thousands_separator')->default(' ')->comment('Разделитель тысяч');
            $t->integer('decimal_places')->default(2)->comment('Количество знаков после запятой');
            $t->boolean('active')->default(true)->comment('Активна ли страна');
            $t->json('translations')->nullable()->comment('Дополнительные переводы для этой страны');
            $t->timestamps();
            $t->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
