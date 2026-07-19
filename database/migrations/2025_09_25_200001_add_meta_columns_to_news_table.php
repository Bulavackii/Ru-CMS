<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('news')) {
            Schema::create('news', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('title');
                $table->text('content')->nullable();
                $table->string('template')->nullable()->index();
                $table->boolean('published')->default(false)->index();

                // сразу мета
                $table->string('meta_title')->nullable();
                $table->string('meta_keywords')->nullable();
                $table->text('meta_description')->nullable();
                $table->text('meta_header')->nullable();

                $table->timestamps();
            });
            return;
        }

        Schema::table('news', function (Blueprint $table) {
            if (!Schema::hasColumn('news', 'meta_title'))       $table->string('meta_title')->nullable()->after('title');
            if (!Schema::hasColumn('news', 'meta_keywords'))    $table->string('meta_keywords')->nullable()->after('meta_title');
            if (!Schema::hasColumn('news', 'meta_description')) $table->text('meta_description')->nullable()->after('meta_keywords');
            if (!Schema::hasColumn('news', 'meta_header'))      $table->text('meta_header')->nullable()->after('meta_description');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('news')) return;

        Schema::table('news', function (Blueprint $table) {
            foreach (['meta_header','meta_description','meta_keywords','meta_title'] as $col) {
                if (Schema::hasColumn('news', $col)) $table->dropColumn($col);
            }
        });
    }
};
