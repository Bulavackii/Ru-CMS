<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // 1) добавляем колонку (пока nullable, чтобы заполнить для старых записей)
        Schema::table('news', function (Blueprint $table) {
            if (!Schema::hasColumn('news', 'slug')) {
                $table->string('slug')->nullable()->after('title');
                $table->unique('slug'); // уникальный индекс
            }
        });

        // 2) заполняем slug для существующих строк (если таблица не пустая)
        try {
            $rows = DB::table('news')->select('id','title','slug')->get();
            foreach ($rows as $r) {
                if (!$r->slug) {
                    // делаем уникальный слаг; добавляем id в конец чтобы не конфликтовало
                    $slug = Str::slug((string)$r->title);
                    if ($slug === '') $slug = 'post';
                    $slug = $slug.'-'.$r->id;
                    DB::table('news')->where('id', $r->id)->update(['slug' => $slug]);
                }
            }
        } catch (\Throwable $e) {
            // молча, чтобы миграция не упала, если таблица пустая
        }

        // 3) (опционально) сделать NOT NULL — только если стоит doctrine/dbal.
        // Если dbal не подключён, оставь nullable.
        /*
        Schema::table('news', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
        */
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            if (Schema::hasColumn('news', 'slug')) {
                $table->dropUnique(['slug']);
                $table->dropColumn('slug');
            }
        });
    }
};
