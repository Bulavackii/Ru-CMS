<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Кто создал и кто последним менял SEO-запись.
 *
 * Контроллер писал created_by/updated_by с самого начала, но колонок в таблице
 * не было: при создании и обновлении их молча отбрасывал filterColumns(), а вот
 * «Заблокировать»/«Разблокировать» и массовые действия шли мимо этого фильтра
 * и падали с QueryException — то есть кнопка блокировки отдавала 500.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('seo_pages', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('sync_hash');
            }
            if (!Schema::hasColumn('seo_pages', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('seo_pages', function (Blueprint $table) {
            foreach (['created_by', 'updated_by'] as $column) {
                if (Schema::hasColumn('seo_pages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
