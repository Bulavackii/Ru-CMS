<?php

namespace Modules\Seo\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Seo\Services\SeoSyncService;

/**
 * SEO-записи для уже существующего содержимого.
 *
 * Записи в `seo_pages` заводятся, когда материал сохраняют ЧЕРЕЗ МОДЕЛЬ.
 * Мастер установки вставляет демо-новости запросом (`DB::table()->insert`)
 * — быстрее и в одной транзакции, — поэтому события модели не срабатывают
 * и раздел SEO после установки оставался пустым: ни заголовков, ни
 * описаний для поисковика, хотя содержимое на сайте есть.
 *
 * Команда проходит по новостям и страницам и заводит недостающее.
 * Идемпотентно: правки владельца не перетираются (`force = false`), а
 * записи с пометкой «заблокировано» пропускаются всегда.
 */
class SeedSeoPagesCommand extends Command
{
    protected $signature = 'seo:seed-pages {--force : Перезаписать заголовки и описания, кроме заблокированных}';

    protected $description = 'Завести SEO-записи для новостей и страниц, у которых их нет';

    public function handle(): int
    {
        $created = self::seed((bool) $this->option('force'));

        $this->info($created > 0
            ? "Заведено SEO-записей: {$created}"
            : 'Все материалы уже описаны — ничего не меняли.');

        return self::SUCCESS;
    }

    /**
     * @return int сколько записей реально заведено
     */
    public static function seed(bool $force = false): int
    {
        if (! class_exists(SeoSyncService::class)) {
            return 0;
        }

        $service = app(SeoSyncService::class);
        $created = 0;

        // Новости
        if (class_exists(\Modules\News\Models\News::class)) {
            foreach (\Modules\News\Models\News::query()->get() as $news) {
                $created += self::sync(fn () => $service->upsertFromNews($news, $force), 'news', $news->id);
            }
        }

        // Страницы
        if (class_exists(\Modules\Menu\Models\Page::class)) {
            foreach (\Modules\Menu\Models\Page::query()->get() as $page) {
                $created += self::sync(fn () => $service->upsertFromMenuPage($page, $force), 'page', $page->id);
            }
        }

        return $created;
    }

    /**
     * Одна запись. Сбой на отдельном материале не должен ронять весь
     * проход: у мастера установки это был бы прерванный шаг.
     */
    private static function sync(callable $work, string $type, int $id): int
    {
        try {
            $page = $work();

            // wasRecentlyCreated отличает «завели» от «уже было».
            return $page->wasRecentlyCreated ? 1 : 0;
        } catch (\Throwable $e) {
            Log::warning('SEO-запись не заведена', [
                'type' => $type,
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }
}
