<?php

namespace Modules\Localization\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Переводы демо-контента, который создаётся при установке.
 *
 * Вызывается мастером установки (InstallController::finish → self::seed()).
 * Без этого переключение языка выглядело недоделкой: интерфейс менялся, а
 * меню, страницы, новости и слайды оставались русскими — их тексты лежат в
 * базе, словари resources/lang к ним отношения не имеют.
 *
 *   php artisan content:seed-translations
 *
 * Переводим только записи, созданные сидерами (ищем по slug и заголовку).
 * Пользовательский контент не трогаем, существующие переводы не перезаписываем.
 */
class SeedContentTranslationsCommand extends Command
{
    protected $signature = 'content:seed-translations {--force : Перезаписать существующие переводы}';

    protected $description = 'Переводы демо-контента (меню, страницы, новости, фрагменты) на английский';

    /**
     * Переводы демо-контента. Ключ — идентификатор записи в её модуле.
     */
    public static function definitions(): array
    {
        return [
            'menu' => [
                'Главная'   => ['en' => 'Home', 'de' => 'Startseite', 'fr' => 'Accueil', 'it' => 'Home', 'be' => 'Галоўная', 'kk' => 'Басты бет'],
                'О нас'     => ['en' => 'About', 'de' => 'Über uns', 'fr' => 'À propos', 'it' => 'Chi siamo', 'be' => 'Пра нас', 'kk' => 'Біз туралы'],
                'Вопросы'   => ['en' => 'FAQ', 'de' => 'Fragen', 'fr' => 'Questions', 'it' => 'Domande', 'be' => 'Пытанні', 'kk' => 'Сұрақтар'],
                'Контакты'  => ['en' => 'Contacts', 'de' => 'Kontakt', 'fr' => 'Contacts', 'it' => 'Contatti', 'be' => 'Кантакты', 'kk' => 'Байланыс'],
                'О проекте' => ['en' => 'About the project', 'de' => 'Über das Projekt', 'fr' => 'À propos du projet', 'it' => 'Il progetto', 'be' => 'Пра праект', 'kk' => 'Жоба туралы'],
                'Возможности' => ['en' => 'Features', 'de' => 'Funktionen', 'fr' => 'Fonctionnalités', 'it' => 'Funzionalità', 'be' => 'Магчымасці', 'kk' => 'Мүмкіндіктер'],
                'Частые вопросы' => ['en' => 'FAQ', 'de' => 'Häufige Fragen', 'fr' => 'Questions fréquentes', 'it' => 'Domande frequenti', 'be' => 'Частыя пытанні', 'kk' => 'Жиі қойылатын сұрақтар'],
                'Соглашение' => ['en' => 'Terms', 'de' => 'Nutzungsbedingungen', 'fr' => 'Conditions', 'it' => 'Termini', 'be' => 'Пагадненне', 'kk' => 'Келісім'],
                'Карта сайта' => ['en' => 'Sitemap', 'de' => 'Sitemap', 'fr' => 'Plan du site', 'it' => 'Mappa del sito', 'be' => 'Карта сайта', 'kk' => 'Сайт картасы'],
                'Разработчикам' => ['en' => 'For developers', 'de' => 'Für Entwickler', 'fr' => 'Pour les développeurs', 'it' => 'Per sviluppatori', 'be' => 'Распрацоўшчыкам', 'kk' => 'Әзірлеушілерге'],
                'Сотрудничество' => ['en' => 'Partnership', 'de' => 'Zusammenarbeit', 'fr' => 'Partenariat', 'it' => 'Collaborazione', 'be' => 'Супрацоўніцтва', 'kk' => 'Ынтымақтастық'],
                'Поддержать проект' => ['en' => 'Support the project', 'de' => 'Projekt unterstützen', 'fr' => 'Soutenir le projet', 'it' => 'Sostieni il progetto', 'be' => 'Падтрымаць праект', 'kk' => 'Жобаны қолдау'],
                'Информация' => ['en' => 'Information', 'de' => 'Informationen', 'fr' => 'Informations', 'it' => 'Informazioni', 'be' => 'Інфармацыя', 'kk' => 'Ақпарат'],
                'Участие' => ['en' => 'Get involved', 'de' => 'Mitmachen', 'fr' => 'Participer', 'it' => 'Partecipa', 'be' => 'Удзел', 'kk' => 'Қатысу'],
            ],

            'pages' => [
                'o-proekte' => [
                    'en' => ['title' => 'About the project', 'content' => '<p><strong>RU CMS</strong> is a modular content management system built on Laravel. It is designed so that everyday editorial tasks — publishing news, building menus, managing pages — can be done without a developer.</p>'],
                    'de' => ['title' => 'Über das Projekt'],
                    'fr' => ['title' => 'À propos du projet'],
                    'it' => ['title' => 'Il progetto'],
                    'be' => ['title' => 'Пра праект'],
                    'kk' => ['title' => 'Жоба туралы'],
                ],
                'vozmozhnosti' => [
                    'en' => ['title' => 'Features', 'content' => '<p>A short overview of what is available right after installation. All sections live in the left-hand menu of the admin panel.</p>'],
                    'de' => ['title' => 'Funktionen'],
                    'fr' => ['title' => 'Fonctionnalités'],
                    'it' => ['title' => 'Funzionalità'],
                    'be' => ['title' => 'Магчымасці'],
                    'kk' => ['title' => 'Мүмкіндіктер'],
                ],
                'chastye-voprosy' => [
                    'en' => ['title' => 'Frequently asked questions', 'content' => '<p>Short answers to the questions that come up most often when you start working with the system.</p>'],
                    'de' => ['title' => 'Häufige Fragen'],
                    'fr' => ['title' => 'Questions fréquentes'],
                    'it' => ['title' => 'Domande frequenti'],
                    'be' => ['title' => 'Частыя пытанні'],
                    'kk' => ['title' => 'Жиі қойылатын сұрақтар'],
                ],
            ],

            'news' => [
                'welcome-to-ru-cms' => [
                    'en' => ['title' => 'Welcome to RU CMS!', 'content' => '<p>This is your first news item. You can edit it in the admin panel.</p>'],
                    'de' => ['title' => 'Willkommen bei RU CMS!'],
                    'fr' => ['title' => 'Bienvenue sur RU CMS !'],
                    'it' => ['title' => 'Benvenuto in RU CMS!'],
                    'be' => ['title' => 'Сардэчна запрашаем у RU CMS!'],
                    'kk' => ['title' => 'RU CMS-ке қош келдіңіз!'],
                ],
                'modular-architecture' => [
                    'en' => ['title' => 'Modular architecture', 'content' => '<p>RU CMS is built on a modular architecture. Modules can be enabled and disabled in one click.</p>'],
                    'de' => ['title' => 'Modulare Architektur'],
                    'fr' => ['title' => 'Architecture modulaire'],
                    'it' => ['title' => 'Architettura modulare'],
                    'be' => ['title' => 'Модульная архітэктура'],
                    'kk' => ['title' => 'Модульдік архитектура'],
                ],
            ],

            'fragments' => [
                'frontend-topbar' => [
                    'en' => ['title' => 'Announcement above the header', 'html_cached' => '<div class="frg-topbar"><span class="frg-topbar__dot"></span><span>Open daily from 9:00 to 20:00</span><a href="/contacts" class="frg-topbar__link">Contact us</a></div>'],
                ],
                'frontend-content-bottom' => [
                    'en' => ['title' => 'Block below page content', 'html_cached' => '<div class="frg-cta"><div class="frg-cta__text"><strong class="frg-cta__title">Still have questions?</strong><span class="frg-cta__sub">We reply within one business day — drop us a line.</span></div><a href="/contacts" class="frg-cta__btn">Write to us</a></div>'],
                ],
            ],

            'categories' => [
                'Новости' => ['en' => 'News', 'de' => 'Neuigkeiten', 'fr' => 'Actualités', 'it' => 'Notizie', 'be' => 'Навіны', 'kk' => 'Жаңалықтар'],
            ],
        ];
    }

    public static function seed(bool $force = false): void
    {
        $definitions = self::definitions();

        DB::transaction(function () use ($definitions, $force) {
            self::translateMenu($definitions['menu'] ?? [], $force);
            self::translateBySlug(\Modules\Menu\Models\Page::class, $definitions['pages'] ?? [], $force);
            self::translateBySlug(\Modules\News\Models\News::class, $definitions['news'] ?? [], $force);
            self::translateBySlug(\Modules\Visual\Models\Fragment::class, $definitions['fragments'] ?? [], $force);
            self::translateCategories($definitions['categories'] ?? [], $force);
        });
    }

    /** Пункты меню: ищем по названию, переводим только title */
    private static function translateMenu(array $map, bool $force): void
    {
        if (! class_exists(\Modules\Menu\Models\MenuItem::class)) {
            return;
        }

        foreach ($map as $title => $translations) {
            foreach (\Modules\Menu\Models\MenuItem::where('title', $title)->get() as $item) {
                self::apply($item, array_map(fn ($value) => ['title' => $value], $translations), $force);
            }
        }
    }

    /** Категории: ищем по названию */
    private static function translateCategories(array $map, bool $force): void
    {
        if (! class_exists(\Modules\Categories\Models\Category::class)) {
            return;
        }

        foreach ($map as $title => $translations) {
            foreach (\Modules\Categories\Models\Category::where('title', $title)->get() as $category) {
                self::apply($category, array_map(fn ($value) => ['title' => $value], $translations), $force);
            }
        }
    }

    /** Записи с полем slug: страницы, новости, фрагменты */
    private static function translateBySlug(string $model, array $map, bool $force): void
    {
        if (! class_exists($model)) {
            return;
        }

        foreach ($map as $slug => $translations) {
            $record = $model::where('slug', $slug)->first();

            if ($record) {
                self::apply($record, $translations, $force);
            }
        }
    }

    /** Записываем переводы, не затирая уже введённые вручную */
    private static function apply($model, array $translations, bool $force): void
    {
        foreach ($translations as $locale => $fields) {
            $existing = $model->translationsFor($locale);

            $payload = [];
            foreach ($fields as $field => $value) {
                if ($force || trim((string) ($existing[$field] ?? '')) === '') {
                    $payload[$field] = $value;
                }
            }

            if ($payload !== []) {
                $model->saveTranslations([$locale => $payload]);
            }
        }
    }

    public function handle(): int
    {
        self::seed((bool) $this->option('force'));

        $this->info('Переводы демо-контента записаны (меню, страницы, новости, фрагменты, категории).');

        return self::SUCCESS;
    }
}
