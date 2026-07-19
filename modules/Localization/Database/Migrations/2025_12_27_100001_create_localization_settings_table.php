<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('localization_settings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $t->string('key')->comment('Ключ настройки (например: date_format, currency_symbol)');
            $t->text('value')->nullable()->comment('Значение настройки');
            $t->string('type')->default('string')->comment('Тип значения: string, number, boolean, json');
            $t->string('group')->default('general')->comment('Группа настроек: general, date, currency, format');
            $t->text('description')->nullable()->comment('Описание настройки');
            $t->boolean('is_system')->default(false)->comment('Системная настройка (не удаляется)');
            $t->timestamps();
        });

        // Индексы для быстрого поиска
        Schema::table('localization_settings', function (Blueprint $t) {
            $t->index(['country_id', 'key']);
            $t->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('localization_settings');
    }
};
