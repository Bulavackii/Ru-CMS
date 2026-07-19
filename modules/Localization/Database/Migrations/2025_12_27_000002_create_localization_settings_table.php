<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Эта миграция создавала более раннюю, per-country схему таблицы
        // (date_format/time_format/currency_code/...), которую заменили на
        // общую key-value схему из 2025_12_27_100001_create_localization_settings_table.php
        // (см. Modules\Localization\Models\LocalizationSetting — использует
        // именно key/value/type/group). Оставлена как no-op, чтобы не терять
        // историю миграций для баз, где она уже применена.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op — см. комментарий в up(). Таблицу создаёт и удаляет
        // 2025_12_27_100001_create_localization_settings_table.php.
    }
};
