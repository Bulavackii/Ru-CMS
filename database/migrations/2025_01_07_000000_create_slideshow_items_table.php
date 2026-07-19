<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // 2025_01_06_000000_create_slideshows_table.php уже создаёт эту таблицу
        // (была превращена в консолидированную "полную" миграцию) — на свежей
        // установке она приходит первой по времени и эта миграция тут лишняя.
        if (Schema::hasTable('slideshow_items')) {
            return;
        }

        Schema::create('slideshow_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slideshow_id')->constrained('slideshows')->onDelete('cascade');
            $table->string('file_path');
            $table->enum('media_type', ['image', 'video']);
            $table->text('caption')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('slideshow_items');
    }
};
