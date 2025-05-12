<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSlideshowItemsTable extends Migration
{
    public function up(): void
    {
        // Создание таблицы slideshow_items
        Schema::create('slideshow_items', function (Blueprint $table) {
            $table->id();
            // Связь с таблицей slideshows (один слайд может принадлежать только одному слайдшоу)
            $table->foreignId('slideshow_id')->constrained('slideshows')->onDelete('cascade'); // Связь с таблицей slideshows
            $table->string('file_path'); // Путь к медиафайлу (например, изображению или видео)
            $table->enum('media_type', ['image', 'video']); // Тип медиа (картинка или видео)
            $table->text('caption')->nullable(); // Подпись для слайда
            $table->integer('order')->default(0); // Порядок слайда
            $table->timestamps(); // Столбцы created_at и updated_at
        });
    }

    public function down(): void
    {
        // Удаление таблицы slideshow_items
        Schema::dropIfExists('slideshow_items');
    }
}
