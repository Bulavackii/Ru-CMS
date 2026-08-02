<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Статистика по сохранённым сборкам каптчи.
 *
 * Свёртка по дням, а не строка на каждый показ: смысл таблицы — увидеть,
 * что каптча слишком сложная, а для этого хватает дневных счётчиков.
 * Построчный лог рос бы линейно от трафика и требовал бы чистки.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('captcha_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preset_id')->constrained('captcha_presets')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('shown')->default(0);
            $table->unsignedInteger('passed')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->timestamps();

            $table->unique(['preset_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('captcha_stats');
    }
};
