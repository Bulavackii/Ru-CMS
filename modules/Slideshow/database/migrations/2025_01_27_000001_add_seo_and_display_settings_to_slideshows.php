<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🎞️ Добавление SEO и настроек отображения для слайдшоу
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slideshows', function (Blueprint $table) {
            // Настройки отображения
            $table->integer('autoplay_delay')->default(5000)->after('position');
            $table->string('transition_effect')->default('slide')->after('autoplay_delay');
            $table->string('height')->nullable()->after('transition_effect');
            $table->boolean('show_pagination')->default(true)->after('height');
            $table->boolean('show_navigation')->default(true)->after('show_pagination');
        });

        Schema::table('slideshow_items', function (Blueprint $table) {
            // SEO поля
            $table->string('alt_text')->nullable()->after('caption');
            
            // Настройки текста
            $table->string('text_position')->default('bottom-right')->after('alt_text');
            $table->string('text_color')->nullable()->after('text_position');
            $table->string('background_color')->nullable()->after('text_color');
        });
    }

    public function down(): void
    {
        Schema::table('slideshows', function (Blueprint $table) {
            $table->dropColumn([
                'autoplay_delay',
                'transition_effect',
                'height',
                'show_pagination',
                'show_navigation',
            ]);
        });

        Schema::table('slideshow_items', function (Blueprint $table) {
            $table->dropColumn([
                'alt_text',
                'text_position',
                'text_color',
                'background_color',
            ]);
        });
    }
};




