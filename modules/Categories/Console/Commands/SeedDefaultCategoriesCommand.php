<?php

namespace Modules\Categories\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Категории по умолчанию — и раскладка по ним уже существующих материалов.
 *
 * Раньше категории заводились прямо в `InstallController::installDemoData()`
 * тремя штуками (Новости, Товары, Услуги), и привязывались только к новостям
 * шаблонов `default` и `products`. Материалы шаблонов magazine, clinic и
 * gaming, все десять страниц и вся медиатека оставались без категории вовсе:
 * раздел «Категории» после установки выглядел живым, а фильтры по нему —
 * пустыми.
 *
 *   php artisan categories:seed-default            # дозаполнить недостающее
 *   php artisan categories:seed-default --reset    # переписать названия и переприкрепить
 *
 * Набор построен по тому, что в CMS реально есть после установки:
 *
 *  • новости демонстрируют пять шаблонов — по категории на каждый;
 *  • страницы разложены так же, как меню подвала («Информация», «Участие»,
 *    «Помощь»): навигация и таксономия сайта совпадают, а не расходятся;
 *  • медиатека делится так, чтобы у каждого файла был дом: заготовки
 *    оформления, фотографии, видео и аудио.
 *
 * Иконка — эмодзи: в форме категории так и написано («Эмодзи / HTML»), и
 * значение выводится через `{!! !!}`. Эмодзи не зависит от подключённого
 * набора иконок и не ломается при смене темы.
 */
class SeedDefaultCategoriesCommand extends Command
{
    protected $signature = 'categories:seed-default {--reset : Переписать названия категорий и переприкрепить материалы}';

    protected $description = 'Категории по умолчанию и раскладка по ним новостей, страниц и файлов';

    /**
     * Канонический набор категорий.
     *
     * `news` — по одной на каждый демо-шаблон ленты, `pages` — по слагам
     * страниц, `files` — по расширениям. Пустой список привязок означает
     * «категория заводится как заготовка, материалов под неё пока нет».
     */
    public static function definitions(): array
    {
        return [
            // ── Лента ────────────────────────────────────────────────
            ['title' => 'Новости',  'slug' => 'news',     'type' => 'news', 'icon' => '📰', 'sort_order' => 10,
             'templates' => ['default', '']],
            ['title' => 'Журнал',   'slug' => 'magazine', 'type' => 'news', 'icon' => '📖', 'sort_order' => 20,
             'templates' => ['magazine']],
            ['title' => 'Клиника',  'slug' => 'clinic',   'type' => 'news', 'icon' => '🩺', 'sort_order' => 30,
             'templates' => ['clinic']],
            ['title' => 'Игры',     'slug' => 'gaming',   'type' => 'news', 'icon' => '🎮', 'sort_order' => 40,
             'templates' => ['gaming']],

            // ── Каталог ──────────────────────────────────────────────
            ['title' => 'Товары',   'slug' => 'products', 'type' => 'product', 'icon' => '🛍️', 'sort_order' => 50,
             'templates' => ['products']],

            // ── Страницы: те же три группы, что в меню подвала ────────
            ['title' => 'Информация', 'slug' => 'info',          'type' => 'page', 'icon' => 'ℹ️', 'sort_order' => 60,
             'pages' => ['o-proekte', 'vozmozhnosti', 'soglashenie', 'konfidencialnost', 'karta-sayta']],
            ['title' => 'Участие',    'slug' => 'participation', 'type' => 'page', 'icon' => '🤝', 'sort_order' => 70,
             'pages' => ['razrabotchikam', 'sotrudnichestvo', 'podderzhat-proekt']],
            ['title' => 'Помощь',     'slug' => 'help',          'type' => 'page', 'icon' => '💬', 'sort_order' => 80,
             'pages' => ['chastye-voprosy', 'kontakty']],

            // ── Медиатека ────────────────────────────────────────────
            // Набор покрывает всё, что библиотека реально держит, — иначе
            // часть файлов осталась бы без категории и фильтр по ним врал бы
            // о полноте. Документы отдельной категорией не заводим: их в
            // демо-содержимом нет, а заготовка под пустоту только засоряет список.
            ['title' => 'Оформление сайта', 'slug' => 'site-design', 'type' => 'file', 'icon' => '🎨', 'sort_order' => 90,
             'extensions' => ['svg', 'ico']],
            ['title' => 'Фотографии',       'slug' => 'photos',      'type' => 'file', 'icon' => '🖼️', 'sort_order' => 100,
             'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'avif', 'heic', 'gif']],
            ['title' => 'Видео и аудио',    'slug' => 'media',       'type' => 'file', 'icon' => '🎬', 'sort_order' => 110,
             'extensions' => ['mp4', 'webm', 'mov', 'mkv', 'avi', 'mp3', 'wav', 'ogg', 'm4a', 'flac']],
        ];
    }

    public function handle(): int
    {
        $result = self::seed((bool) $this->option('reset'));

        $this->info(sprintf(
            'Категорий: %d. Привязано — новостей: %d, страниц: %d, файлов: %d.',
            $result['categories'],
            $result['news'],
            $result['pages'],
            $result['files']
        ));

        return self::SUCCESS;
    }

    /**
     * @return array{categories:int, news:int, pages:int, files:int}
     */
    public static function seed(bool $reset = false): array
    {
        if (! Schema::hasTable('categories')) {
            return ['categories' => 0, 'news' => 0, 'pages' => 0, 'files' => 0];
        }

        $ids = self::ensure($reset);

        return [
            'categories' => count($ids),
            'news'       => self::attachNews($ids, $reset),
            'pages'      => self::attachPages($ids, $reset),
            'files'      => self::attachFiles($ids),
        ];
    }

    /**
     * Завести недостающие категории, вернуть карту «слаг → id».
     *
     * Идемпотентно по слагу: он уникален, и повторный вызов без --reset ничего
     * не переписывает — владелец мог переименовать категорию под себя.
     *
     * @return array<string,int>
     */
    public static function ensure(bool $reset = false): array
    {
        $ids = [];

        foreach (self::definitions() as $definition) {
            $existing = DB::table('categories')->where('slug', $definition['slug'])->first();

            $fields = [
                'title'      => $definition['title'],
                'type'       => $definition['type'],
                'icon'       => $definition['icon'],
                'sort_order' => $definition['sort_order'],
                'is_active'  => true,
                'updated_at' => now(),
            ];

            if ($existing) {
                $ids[$definition['slug']] = $existing->id;

                if ($reset) {
                    DB::table('categories')->where('id', $existing->id)->update($fields);
                    continue;
                }

                // Без --reset заданное владельцем не трогаем, но ПУСТОЕ
                // дозаполняем: категории «Новости» и «Товары» существуют с
                // самых первых установок и остались без иконки и порядка —
                // молча оставить их серыми рядом с новыми значило бы сделать
                // раздел разнородным на ровном месте.
                $gaps = [];

                if (($existing->icon ?? '') === '') {
                    $gaps['icon'] = $definition['icon'];
                }

                if ((int) ($existing->sort_order ?? 0) === 0) {
                    $gaps['sort_order'] = $definition['sort_order'];
                }

                if ($gaps !== []) {
                    DB::table('categories')->where('id', $existing->id)
                        ->update($gaps + ['updated_at' => now()]);
                }

                continue;
            }

            $ids[$definition['slug']] = DB::table('categories')->insertGetId($fields + [
                'slug'       => $definition['slug'],
                'created_at' => now(),
            ]);
        }

        return $ids;
    }

    /**
     * Новости раскладываются по шаблону: демо-лента как раз и построена так,
     * что каждый шаблон — отдельная витрина.
     *
     * Пустая строка в списке шаблонов ловит материалы без шаблона: у формы это
     * значение записывается то как `default`, то как пустая строка, то как NULL
     * (три состояния одной группы — та же ловушка, что в постраничном выводе
     * ленты, см. CLAUDE.md).
     */
    private static function attachNews(array $ids, bool $reset): int
    {
        if (! Schema::hasTable('news') || ! Schema::hasTable('news_category')) {
            return 0;
        }

        $linked = 0;

        foreach (self::definitions() as $definition) {
            if (empty($definition['templates']) || ! isset($ids[$definition['slug']])) {
                continue;
            }

            $categoryId = $ids[$definition['slug']];

            $query = DB::table('news')->select('id');

            $query->where(function ($inner) use ($definition) {
                foreach ($definition['templates'] as $template) {
                    if ($template === '') {
                        $inner->orWhereNull('template')->orWhere('template', '');
                    } else {
                        $inner->orWhere('template', $template);
                    }
                }
            });

            // Без --reset трогаем только те материалы, у которых категории нет
            // вовсе: расставленное руками переписывать нельзя.
            if (! $reset) {
                $query->whereNotExists(fn ($sub) => $sub->from('news_category')
                    ->whereColumn('news_category.news_id', 'news.id'));
            }

            foreach ($query->pluck('id') as $newsId) {
                $exists = DB::table('news_category')
                    ->where('news_id', $newsId)
                    ->where('category_id', $categoryId)
                    ->exists();

                if (! $exists) {
                    DB::table('news_category')->insert([
                        'news_id'     => $newsId,
                        'category_id' => $categoryId,
                    ]);
                    $linked++;
                }
            }
        }

        return $linked;
    }

    /** Страницы — по слагу: он у демо-страниц канонический и задан сидером. */
    private static function attachPages(array $ids, bool $reset): int
    {
        if (! Schema::hasTable('pages') || ! Schema::hasTable('page_category')) {
            return 0;
        }

        $linked = 0;

        foreach (self::definitions() as $definition) {
            if (empty($definition['pages']) || ! isset($ids[$definition['slug']])) {
                continue;
            }

            $categoryId = $ids[$definition['slug']];

            $query = DB::table('pages')->select('id')->whereIn('slug', $definition['pages']);

            if (! $reset) {
                $query->whereNotExists(fn ($sub) => $sub->from('page_category')
                    ->whereColumn('page_category.page_id', 'pages.id'));
            }

            foreach ($query->pluck('id') as $pageId) {
                $exists = DB::table('page_category')
                    ->where('page_id', $pageId)
                    ->where('category_id', $categoryId)
                    ->exists();

                if (! $exists) {
                    DB::table('page_category')->insert([
                        'page_id'     => $pageId,
                        'category_id' => $categoryId,
                    ]);
                    $linked++;
                }
            }
        }

        return $linked;
    }

    /**
     * Файлы — по расширению имени, и ТОЛЬКО те, у которых категории нет.
     *
     * Выбранное вручную не перетирается даже с --reset: у файла категория одна,
     * и перезапись стёрла бы решение владельца без следа. Расширение берётся из
     * имени, а не из mime: у части загрузок он приезжает как
     * application/octet-stream.
     */
    private static function attachFiles(array $ids): int
    {
        if (! Schema::hasTable('files')) {
            return 0;
        }

        $linked = 0;

        foreach (self::definitions() as $definition) {
            if (empty($definition['extensions']) || ! isset($ids[$definition['slug']])) {
                continue;
            }

            $query = DB::table('files')->whereNull('category_id');

            $query->where(function ($inner) use ($definition) {
                foreach ($definition['extensions'] as $extension) {
                    $inner->orWhere('original_name', 'like', '%.' . $extension);
                }
            });

            $linked += $query->update(['category_id' => $ids[$definition['slug']]]);
        }

        return $linked;
    }
}
