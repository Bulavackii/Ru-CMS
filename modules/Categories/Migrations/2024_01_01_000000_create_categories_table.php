<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * 📦 Создание таблицы `categories`
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();                     // 🔑 Первичный ключ (AUTO_INCREMENT ID)
            $table->string('title');         // 🏷️ Название категории (обязательное)
            $table->timestamps();            // 🕒 Временные метки: created_at и updated_at
        });
    }

    /**
     * 🧨 Откат миграции — удаление таблицы
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');  // ❌ Удаляем таблицу, если существует
    }
};
