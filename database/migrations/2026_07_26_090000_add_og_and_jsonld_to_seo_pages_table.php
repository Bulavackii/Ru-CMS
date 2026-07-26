<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OpenGraph/Twitter и JSON-LD у SEO-страниц.
 *
 * Форма редактирования уже собирала эти поля, фильтры списка по ним фильтровали,
 * а бейджи «og» и «json-ld» отрисовывались — но колонок в таблице не было, и
 * PagesController::filterColumns() молча выбрасывал данные при сохранении.
 * То есть заполнить их было невозможно, а фильтр всегда давал пустой результат.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('seo_pages', 'og')) {
                // json — драйвер-нейтрально (Postgres на проде, SQLite в тестах)
                $table->json('og')->nullable()->after('canonical');
            }
            if (!Schema::hasColumn('seo_pages', 'jsonld')) {
                $table->json('jsonld')->nullable()->after('og');
            }
        });
    }

    public function down(): void
    {
        Schema::table('seo_pages', function (Blueprint $table) {
            foreach (['og', 'jsonld'] as $column) {
                if (Schema::hasColumn('seo_pages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
