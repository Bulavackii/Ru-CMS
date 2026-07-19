<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('seo_pages') && !Schema::hasColumn('seo_pages', 'keywords')) {
            Schema::table('seo_pages', function (Blueprint $t) {
                $t->string('keywords', 255)->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('seo_pages') && Schema::hasColumn('seo_pages', 'keywords')) {
            Schema::table('seo_pages', function (Blueprint $t) {
                $t->dropColumn('keywords');
            });
        }
    }
};
