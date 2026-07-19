<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('news') && !Schema::hasColumn('news', 'template')) {
            Schema::table('news', function (Blueprint $table) {
                $table->string('template', 100)->nullable()->index()->after('published');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('news') && Schema::hasColumn('news', 'template')) {
            Schema::table('news', function (Blueprint $table) {
                $table->dropIndex(['template']);
                $table->dropColumn('template');
            });
        }
    }
};
