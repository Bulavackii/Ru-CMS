<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    public function up(): void
    {
        // Создание таблицы notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');             // Столбец title
            $table->text('message');             // Столбец message
            $table->string('type');              // Столбец type
            $table->string('target');            // Столбец target
            $table->string('position');          // Столбец position
            $table->integer('duration');         // Столбец duration
            $table->string('icon');              // Столбец icon
            $table->string('color');             // Столбец color
            $table->string('route_filter')->nullable(); // Столбец route_filter
            $table->string('cookie_key')->nullable();   // Столбец cookie_key
            $table->boolean('enabled')->default(true);   // Столбец enabled
            $table->string('bg_color', 20)->nullable();  // Столбец bg_color
            $table->string('text_color', 20)->nullable(); // Столбец text_color
            $table->timestamps();               // Столбцы created_at и updated_at
        });
    }

    public function down(): void
    {
        // Удаление таблицы notifications
        Schema::dropIfExists('notifications');
    }
}
