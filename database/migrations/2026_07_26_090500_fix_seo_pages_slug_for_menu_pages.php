<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Починка адресов SEO-записей, привязанных к страницам модуля Menu.
 *
 * Синхронизация записывала slug как /{slug}, тогда как страница открывается по
 * /page/{slug} (маршрут frontend.pages.show). Следствия: в sitemap.xml уезжали
 * ссылки, отдающие 404, «Просмотр» из админки вёл в никуда, а сопоставить
 * SEO-запись с реальной страницей было нельзя.
 *
 * Здесь переносим существующие записи на правильный адрес. Если запись с новым
 * адресом уже появилась (после исправления кода синхронизация могла создать
 * дубль), оставляем более раннюю — в ней могли быть правки руками.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('seo_pages') || ! Schema::hasColumn('seo_pages', 'source_type')) {
            return;
        }

        $rows = DB::table('seo_pages')
            ->where('source_type', 'page')
            ->whereNotNull('slug')
            ->get(['id', 'slug', 'source_id', 'created_at']);

        foreach ($rows as $row) {
            $slug = '/' . ltrim((string) $row->slug, '/');

            if (str_starts_with($slug, '/page/')) {
                continue;
            }

            $newSlug = '/page/' . ltrim($slug, '/');

            $duplicate = DB::table('seo_pages')
                ->where('slug', $newSlug)
                ->where('id', '!=', $row->id)
                ->first(['id', 'created_at']);

            if ($duplicate) {
                // Оставляем ту запись, что появилась раньше: у неё больше шансов
                // содержать ручные правки
                $olderIsCurrent = $row->created_at !== null
                    && ($duplicate->created_at === null || $row->created_at <= $duplicate->created_at);

                if ($olderIsCurrent) {
                    DB::table('seo_pages')->where('id', $duplicate->id)->delete();
                    DB::table('seo_pages')->where('id', $row->id)->update(['slug' => $newSlug]);
                } else {
                    DB::table('seo_pages')->where('id', $row->id)->delete();
                }

                continue;
            }

            DB::table('seo_pages')->where('id', $row->id)->update(['slug' => $newSlug]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('seo_pages') || ! Schema::hasColumn('seo_pages', 'source_type')) {
            return;
        }

        foreach (DB::table('seo_pages')->where('source_type', 'page')->get(['id', 'slug']) as $row) {
            $slug = (string) $row->slug;

            if (! str_starts_with($slug, '/page/')) {
                continue;
            }

            DB::table('seo_pages')
                ->where('id', $row->id)
                ->update(['slug' => '/' . ltrim(substr($slug, strlen('/page/')), '/')]);
        }
    }
};
