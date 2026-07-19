<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🎨 Визуальные темы и фрагменты - объединенная миграция
 */
return new class extends Migration
{
    public function up(): void
    {
        // Визуальные темы
        Schema::create('visual_themes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('tokens')->nullable(); // Цвета, шрифты и т.д.
            $table->boolean('is_default')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Визуальные фрагменты
        Schema::create('visual_fragments', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->json('meta')->nullable();
            $table->string('zone')->nullable(); // header, footer, sidebar и т.д.
            $table->string('type')->default('html'); // html, css, js
            $table->integer('order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Ревизии фрагментов (если еще не создана в модуле Visual)
        if (!Schema::hasTable('visual_revisions')) {
            Schema::create('visual_revisions', function (Blueprint $table) {
                $table->id();
                $table->morphs('target');
                $table->json('snapshot');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('visual_revisions');
        Schema::dropIfExists('visual_fragments');
        Schema::dropIfExists('visual_themes');
    }
};
