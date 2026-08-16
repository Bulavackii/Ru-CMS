<?php

namespace Modules\Seo\Console\Commands;

use Illuminate\Console\Command;
use Modules\Seo\Models\SeoPage;

/**
 * Осиротевшие SEO-записи — описания материалов, которых больше нет.
 *
 * Откуда берутся: массовые действия раньше шли мимо модели
 * (`News::whereIn('id', $ids)->delete()` — это запрос построителя, событий
 * модели он НЕ поднимает), поэтому удалённый пачкой материал уносил с сайта
 * страницу, но не своё SEO-описание. Сам источник дефекта закрыт
 * (`BulkActionsEventsTest`), а записи, накопленные до починки, остались:
 * в базе владельца их нашлось три, и одну поймал обход ссылок —
 * `/news/proba-sobytiy` вёл в 404.
 *
 * Чем это вредно: раздел SEO показывает адреса, которых нет, а карта сайта
 * зовёт поисковик на несуществующие страницы.
 *
 * ⚠️ Команда УДАЛЯЕТ данные, поэтому по умолчанию она только показывает
 * список. Удаление — явным `--force`, и оно мягкое (`SoftDeletes`): запись
 * остаётся в таблице с отметкой и восстановима.
 */
class CleanOrphanSeoPagesCommand extends Command
{
    protected $signature = 'seo:clean-orphans
                            {--force : Действительно удалить (по умолчанию — только показать)}';

    protected $description = 'Найти SEO-записи, ссылающиеся на удалённые материалы';

    /** Какой источник какой моделью проверяется. */
    private const ИСТОЧНИКИ = [
        'news' => \Modules\News\Models\News::class,
        'page' => \Modules\Menu\Models\Page::class,
    ];

    public function handle(): int
    {
        $сироты = self::найти();

        if ($сироты->isEmpty()) {
            $this->info('Осиротевших SEO-записей нет.');

            return self::SUCCESS;
        }

        $this->warn('Найдено записей без материала: ' . $сироты->count());

        $this->table(
            ['ID', 'Адрес', 'Источник'],
            $сироты->map(fn (SeoPage $з) => [
                $з->id,
                $з->slug,
                $з->source_type . '#' . $з->source_id,
            ])->all()
        );

        if (! $this->option('force')) {
            $this->line('');
            $this->comment('Это только показ. Чтобы удалить: php artisan seo:clean-orphans --force');

            return self::SUCCESS;
        }

        // Удаляем ПОШТУЧНО через модель: массовое удаление построителем —
        // ровно тот приём, который эти записи и породил.
        $удалено = 0;

        foreach ($сироты as $запись) {
            $запись->delete();
            $удалено++;
        }

        $this->info("Удалено записей: {$удалено} (мягко, восстановимо).");

        return self::SUCCESS;
    }

    /**
     * Записи, у которых источник указан, но материала по нему нет.
     *
     * ⚠️ Записи БЕЗ источника (`source_type` пуст) не трогаем: их заводят
     * руками под произвольный адрес — раздел, фильтр, лендинг, — и материала
     * за ними не стоит по замыслу.
     *
     * @return \Illuminate\Support\Collection<int, SeoPage>
     */
    public static function найти(): \Illuminate\Support\Collection
    {
        $сироты = collect();

        foreach (self::ИСТОЧНИКИ as $тип => $модель) {
            if (! class_exists($модель)) {
                continue;
            }

            $живые = $модель::query()->pluck('id')->all();

            $сироты = $сироты->merge(
                SeoPage::query()
                    ->where('source_type', $тип)
                    ->whereNotNull('source_id')
                    ->when($живые !== [], fn ($q) => $q->whereNotIn('source_id', $живые))
                    ->get()
            );
        }

        return $сироты->sortBy('id')->values();
    }
}
