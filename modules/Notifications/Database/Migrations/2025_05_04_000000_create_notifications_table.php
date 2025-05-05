<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            $table->string('title')->nullable();               // Заголовок уведомления
            $table->text('message');                           // Основное содержимое
            $table->enum('type', ['text', 'html', 'cookie']); // Тип уведомления
            $table->enum('target', ['all', 'admin', 'user']); // Кому показывать
            $table->enum('position', ['top', 'bottom', 'fullscreen']); // Расположение
            $table->integer('duration')->nullable();           // Время показа в секундах
            $table->string('icon')->nullable();                // Иконка (по классу или типу)
            $table->string('color')->nullable();               // Цвет (например: blue, red)
            $table->string('route_filter')->nullable();        // URL/маршрут для фильтра
            $table->string('cookie_key')->nullable();          // Ключ куки, если type == cookie

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
