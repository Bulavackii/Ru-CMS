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
        // Консолидированная 2025_01_06_000000_create_slideshows_table.php уже
        // создаёт колонку link — на свежей установке добавлять её второй раз не нужно.
        if (Schema::hasColumn('slideshow_items', 'link')) {
            return;
        }

        Schema::table('slideshow_items', function (Blueprint $table) {
            $table->string('link')->nullable()->after('caption');
        });
    }

    public function down(): void
    {
        Schema::table('slideshow_items', function (Blueprint $table) {
            $table->dropColumn('link');
        });
    }
};
