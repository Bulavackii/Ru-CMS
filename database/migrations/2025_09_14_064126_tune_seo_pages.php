<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('seo_pages', function (Blueprint $table) {
            // h1
            if (!Schema::hasColumn('seo_pages', 'h1')) {
                $col = $table->string('h1')->nullable();
                if (Schema::hasColumn('seo_pages', 'title')) {
                    $col->after('title');
                }
            }

            // source_type/source_id (+ индекс)
            if (!Schema::hasColumn('seo_pages', 'source_type')) {
                $col = $table->string('source_type')->nullable();
                if (Schema::hasColumn('seo_pages', 'slug')) {
                    $col->after('slug');
                }
            }
            if (!Schema::hasColumn('seo_pages', 'source_id')) {
                $col = $table->unsignedBigInteger('source_id')->nullable();
                if (Schema::hasColumn('seo_pages', 'source_type')) {
                    $col->after('source_type');
                }
            }
            // индекс безопасно добавим, только если обе колонки есть и индекса нет
            if (Schema::hasColumn('seo_pages', 'source_type') && Schema::hasColumn('seo_pages', 'source_id')) {
                // имя индекса фиксированное, чтобы не дублить
                $table->index(['source_type', 'source_id'], 'seo_pages_source_type_source_id_idx');
            }

            // manual_fields (JSON) — ставим after(jsonld), только если jsonld есть
            if (!Schema::hasColumn('seo_pages', 'manual_fields')) {
                $col = $table->json('manual_fields')->nullable();
                if (Schema::hasColumn('seo_pages', 'jsonld')) {
                    $col->after('jsonld');
                }
            }

            // locked
            if (!Schema::hasColumn('seo_pages', 'locked')) {
                $col = $table->boolean('locked')->default(false);
                if (Schema::hasColumn('seo_pages', 'manual_fields')) {
                    $col->after('manual_fields');
                }
            }

            // sync_hash
            if (!Schema::hasColumn('seo_pages', 'sync_hash')) {
                $col = $table->string('sync_hash')->nullable();
                if (Schema::hasColumn('seo_pages', 'locked')) {
                    $col->after('locked');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('seo_pages', function (Blueprint $table) {
            if (Schema::hasColumn('seo_pages', 'h1'))            $table->dropColumn('h1');
            if (Schema::hasColumn('seo_pages', 'sync_hash'))     $table->dropColumn('sync_hash');
            if (Schema::hasColumn('seo_pages', 'locked'))        $table->dropColumn('locked');
            if (Schema::hasColumn('seo_pages', 'manual_fields')) $table->dropColumn('manual_fields');

            if (Schema::hasColumn('seo_pages', 'source_type') && Schema::hasColumn('seo_pages', 'source_id')) {
                // снимем индекс, если он есть
                try { $table->dropIndex('seo_pages_source_type_source_id_idx'); } catch (\Throwable $e) {}
            }
            if (Schema::hasColumn('seo_pages', 'source_id'))   $table->dropColumn('source_id');
            if (Schema::hasColumn('seo_pages', 'source_type')) $table->dropColumn('source_type');
        });
    }
};
