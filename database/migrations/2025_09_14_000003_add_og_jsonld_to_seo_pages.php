<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('seo_pages')) {
            return;
        }

        Schema::table('seo_pages', function (Blueprint $t) {
            // og
            if (!Schema::hasColumn('seo_pages', 'og')) {
                $col = $t->json('og')->nullable();
                // Добавим "после robots_follow" только если такая колонка существует
                if (Schema::hasColumn('seo_pages', 'robots_follow')) {
                    $col->after('robots_follow');
                }
            }

            // jsonld
            if (!Schema::hasColumn('seo_pages', 'jsonld')) {
                $col = $t->json('jsonld')->nullable();
                if (Schema::hasColumn('seo_pages', 'og')) {
                    $col->after('og');
                }
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('seo_pages')) {
            return;
        }

        Schema::table('seo_pages', function (Blueprint $t) {
            if (Schema::hasColumn('seo_pages', 'jsonld')) {
                $t->dropColumn('jsonld');
            }
            if (Schema::hasColumn('seo_pages', 'og')) {
                $t->dropColumn('og');
            }
        });
    }
};
