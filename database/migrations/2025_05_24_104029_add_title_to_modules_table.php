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
        Schema::table('modules', function (Blueprint $table) {
            // 🏷️ Добавляем поле title, если его ещё нет
            if (!Schema::hasColumn('modules', 'title')) {
                $table->string('title')->nullable()->after('name');
            }

            // 🔢 Добавляем поле priority, если его нет
            if (!Schema::hasColumn('modules', 'priority')) {
                $table->integer('priority')->default(0)->after('installed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            if (Schema::hasColumn('modules', 'title')) {
                $table->dropColumn('title');
            }

            if (Schema::hasColumn('modules', 'priority')) {
                $table->dropColumn('priority');
            }
        });
    }
};
