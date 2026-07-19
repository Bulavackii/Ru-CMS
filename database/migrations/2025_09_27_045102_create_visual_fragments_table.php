<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('visual_fragments')) {
            Schema::create('visual_fragments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('slug')->unique();        // уникальный ключ, напр. site-header / site-footer
                $table->string('title')->nullable();     // человекочитаемое имя
                $table->longText('content')->nullable(); // HTML/Blade/Markdown — чем вы пользуетесь
                $table->json('meta')->nullable();        // произвольные поля (например, позиция, видимость)
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('visual_fragments');
    }
};
