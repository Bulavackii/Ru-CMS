<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Модель Modules\Localization\Models\LocalizationSetting использует трейт
 * SoftDeletes, но колонки deleted_at в таблице не было: любой запрос к
 * настройкам локализации падал с «столбец localization_settings.deleted_at
 * не существует». Из-за этого не открывалась страница /admin/localization.
 *
 * У соседней таблицы countries колонка есть — расхождение появилось при
 * добавлении SoftDeletes в модель без сопутствующей миграции.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('localization_settings')) {
            return;
        }

        if (!Schema::hasColumn('localization_settings', 'deleted_at')) {
            Schema::table('localization_settings', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('localization_settings') && Schema::hasColumn('localization_settings', 'deleted_at')) {
            Schema::table('localization_settings', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
