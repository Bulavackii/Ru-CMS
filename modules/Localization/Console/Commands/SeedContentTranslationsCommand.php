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
                'Новости'   => ['en' => 'News', 'de' => 'Neuigkeiten', 'fr' => 'Actualités', 'it' => 'Notizie', 'be' => 'Навіны', 'kk' => 'Жаңалықтар'],
                'Меню подвала' => ['en' => 'Footer menu', 'de' => 'Fußzeilenmenü', 'fr' => 'Menu du pied de page', 'it' => 'Menu a piè di pagina', 'be' => 'Меню падвала', 'kk' => 'Төменгі мәзір'],
                'Боковое меню' => ['en' => 'Side menu', 'de' => 'Seitenmenü', 'fr' => 'Menu latéral', 'it' => 'Menu laterale', 'be' => 'Бакавое меню', 'kk' => 'Бүйірлік мәзір'],
                'Главное меню' => ['en' => 'Main menu', 'de' => 'Hauptmenü', 'fr' => 'Menu principal', 'it' => 'Menu principale', 'be' => 'Галоўнае меню', 'kk' => 'Негізгі мәзір'],
                'Информация' => ['en' => 'Information', 'de' => 'Informationen', 'fr' => 'Informations', 'it' => 'Informazioni', 'be' => 'Інфармацыя', 'kk' => 'Ақпарат'],
                'Участие' => ['en' => 'Get involved', 'de' => 'Mitmachen', 'fr' => 'Participer', 'it' => 'Partecipa', 'be' => 'Удзел', 'kk' => 'Қатысу'],
            ],

            'pages' => [
                'o-proekte' => [
                    'en' => ['title' => 'About the project', 'content' => '<p><strong>Nexum Core</strong> is a modular content management system built on Laravel. It is designed so that everyday editorial tasks — publishing news, building menus, managing pages — can be done without a developer.</p>'],
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
                'welcome-to-nexum-core' => [
                    'en' => ['title' => 'Welcome to Nexum Core!', 'content' => '<p>This is your first news item. You can edit it in the admin panel.</p>'],
                    'de' => ['title' => 'Willkommen bei Nexum Core!'],
                    'fr' => ['title' => 'Bienvenue sur Nexum Core !'],
                    'it' => ['title' => 'Benvenuto in Nexum Core!'],
                    'be' => ['title' => 'Сардэчна запрашаем у Nexum Core!'],
                    'kk' => ['title' => 'Nexum Core-ке қош келдіңіз!'],
                ],
                'modular-architecture' => [
                    'en' => ['title' => 'Modular architecture', 'content' => '<p>Nexum Core is built on a modular architecture. Modules can be enabled and disabled in one click.</p>'],
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
                    'de' => ['title' => 'Ankündigung über der Kopfzeile', 'html_cached' => '<div class="frg-topbar"><span class="frg-topbar__dot"></span><span>Täglich von 9:00 bis 20:00 Uhr</span><a href="/contacts" class="frg-topbar__link">Kontakt</a></div>'],
                    'fr' => ['title' => "Annonce au-dessus de l'en-tête", 'html_cached' => '<div class="frg-topbar"><span class="frg-topbar__dot"></span><span>Ouvert tous les jours de 9h00 à 20h00</span><a href="/contacts" class="frg-topbar__link">Nous contacter</a></div>'],
                    'it' => ['title' => "Annuncio sopra l'intestazione", 'html_cached' => '<div class="frg-topbar"><span class="frg-topbar__dot"></span><span>Aperti tutti i giorni dalle 9:00 alle 20:00</span><a href="/contacts" class="frg-topbar__link">Contattaci</a></div>'],
                    'be' => ['title' => 'Аб’ява над шапкай', 'html_cached' => '<div class="frg-topbar"><span class="frg-topbar__dot"></span><span>Працуем штодня з 9:00 да 20:00</span><a href="/contacts" class="frg-topbar__link">Звязацца</a></div>'],
                    'kk' => ['title' => 'Тақырып үстіндегі хабарландыру', 'html_cached' => '<div class="frg-topbar"><span class="frg-topbar__dot"></span><span>Күн сайын 9:00-ден 20:00-ге дейін жұмыс істейміз</span><a href="/contacts" class="frg-topbar__link">Байланысу</a></div>'],
                ],
                'frontend-header' => [
                    'en' => ['title' => 'Block below the header', 'html_cached' => '<div class="frg-underhead"><div class="frg-underhead__inner"><span class="frg-underhead__badge">New</span><span class="frg-underhead__text">We collected answers to the most common questions about the system</span><a href="/page/chastye-voprosy" class="frg-underhead__link">Open section →</a></div></div>'],
                    'de' => ['title' => 'Block unter der Kopfzeile', 'html_cached' => '<div class="frg-underhead"><div class="frg-underhead__inner"><span class="frg-underhead__badge">Neu</span><span class="frg-underhead__text">Wir haben Antworten auf die häufigsten Fragen zum System gesammelt</span><a href="/page/chastye-voprosy" class="frg-underhead__link">Zum Bereich →</a></div></div>'],
                    'fr' => ['title' => "Bloc sous l'en-tête", 'html_cached' => '<div class="frg-underhead"><div class="frg-underhead__inner"><span class="frg-underhead__badge">Nouveau</span><span class="frg-underhead__text">Nous avons rassemblé les réponses aux questions les plus fréquentes</span><a href="/page/chastye-voprosy" class="frg-underhead__link">Ouvrir la section →</a></div></div>'],
                    'it' => ['title' => "Blocco sotto l'intestazione", 'html_cached' => '<div class="frg-underhead"><div class="frg-underhead__inner"><span class="frg-underhead__badge">Novità</span><span class="frg-underhead__text">Abbiamo raccolto le risposte alle domande più frequenti</span><a href="/page/chastye-voprosy" class="frg-underhead__link">Apri la sezione →</a></div></div>'],
                    'be' => ['title' => 'Блок пад шапкай', 'html_cached' => '<div class="frg-underhead"><div class="frg-underhead__inner"><span class="frg-underhead__badge">Новае</span><span class="frg-underhead__text">Сабралі адказы на частыя пытанні пра працу з сістэмай</span><a href="/page/chastye-voprosy" class="frg-underhead__link">Адкрыць раздзел →</a></div></div>'],
                    'kk' => ['title' => 'Тақырып астындағы блок', 'html_cached' => '<div class="frg-underhead"><div class="frg-underhead__inner"><span class="frg-underhead__badge">Жаңа</span><span class="frg-underhead__text">Жүйемен жұмыс туралы жиі қойылатын сұрақтарға жауап жинадық</span><a href="/page/chastye-voprosy" class="frg-underhead__link">Бөлімді ашу →</a></div></div>'],
                ],
                'frontend-content-bottom' => [
                    'en' => ['title' => 'Block below page content', 'html_cached' => '<div class="frg-cta"><div class="frg-cta__text"><strong class="frg-cta__title">Still have questions?</strong><span class="frg-cta__sub">We reply within one business day — drop us a line.</span></div><a href="/contacts" class="frg-cta__btn">Write to us</a></div>'],
                    'de' => ['title' => 'Block unter dem Seiteninhalt', 'html_cached' => '<div class="frg-cta"><div class="frg-cta__text"><strong class="frg-cta__title">Noch Fragen?</strong><span class="frg-cta__sub">Wir antworten innerhalb eines Werktages — schreiben Sie uns.</span></div><a href="/contacts" class="frg-cta__btn">Schreiben Sie uns</a></div>'],
                    'fr' => ['title' => 'Bloc sous le contenu', 'html_cached' => '<div class="frg-cta"><div class="frg-cta__text"><strong class="frg-cta__title">Des questions ?</strong><span class="frg-cta__sub">Nous répondons sous un jour ouvré — écrivez-nous.</span></div><a href="/contacts" class="frg-cta__btn">Nous écrire</a></div>'],
                    'it' => ['title' => 'Blocco sotto il contenuto', 'html_cached' => '<div class="frg-cta"><div class="frg-cta__text"><strong class="frg-cta__title">Hai ancora domande?</strong><span class="frg-cta__sub">Rispondiamo entro un giorno lavorativo — scrivici.</span></div><a href="/contacts" class="frg-cta__btn">Scrivici</a></div>'],
                    'be' => ['title' => 'Блок пад змесцівам', 'html_cached' => '<div class="frg-cta"><div class="frg-cta__text"><strong class="frg-cta__title">Засталіся пытанні?</strong><span class="frg-cta__sub">Адкажам на працягу рабочага дня — напішыце нам.</span></div><a href="/contacts" class="frg-cta__btn">Напісаць нам</a></div>'],
                    'kk' => ['title' => 'Мазмұн астындағы блок', 'html_cached' => '<div class="frg-cta"><div class="frg-cta__text"><strong class="frg-cta__title">Сұрақтар қалды ма?</strong><span class="frg-cta__sub">Бір жұмыс күні ішінде жауап береміз — бізге жазыңыз.</span></div><a href="/contacts" class="frg-cta__btn">Бізге жазу</a></div>'],
                ],
                'frontend-footer' => [
                    'en' => ['title' => 'Line above the site footer', 'html_cached' => '<div class="frg-preftr"><span>The information on this site is for reference only</span><span class="frg-preftr__sep">·</span><a href="/page/o-proekte">About the project</a><span class="frg-preftr__sep">·</span><a href="/page/chastye-voprosy">Questions and answers</a></div>'],
                    'de' => ['title' => 'Zeile über der Fußzeile', 'html_cached' => '<div class="frg-preftr"><span>Die Informationen auf dieser Website dienen nur zur Orientierung</span><span class="frg-preftr__sep">·</span><a href="/page/o-proekte">Über das Projekt</a><span class="frg-preftr__sep">·</span><a href="/page/chastye-voprosy">Fragen und Antworten</a></div>'],
                    'fr' => ['title' => 'Ligne au-dessus du pied de page', 'html_cached' => '<div class="frg-preftr"><span>Les informations de ce site sont fournies à titre indicatif</span><span class="frg-preftr__sep">·</span><a href="/page/o-proekte">À propos du projet</a><span class="frg-preftr__sep">·</span><a href="/page/chastye-voprosy">Questions et réponses</a></div>'],
                    'it' => ['title' => 'Riga sopra il piè di pagina', 'html_cached' => '<div class="frg-preftr"><span>Le informazioni sul sito hanno carattere puramente indicativo</span><span class="frg-preftr__sep">·</span><a href="/page/o-proekte">Il progetto</a><span class="frg-preftr__sep">·</span><a href="/page/chastye-voprosy">Domande e risposte</a></div>'],
                    'be' => ['title' => 'Радок над падвалам', 'html_cached' => '<div class="frg-preftr"><span>Інфармацыя на сайце мае даведачны характар</span><span class="frg-preftr__sep">·</span><a href="/page/o-proekte">Пра праект</a><span class="frg-preftr__sep">·</span><a href="/page/chastye-voprosy">Пытанні і адказы</a></div>'],
                    'kk' => ['title' => 'Төменгі бөлік үстіндегі жол', 'html_cached' => '<div class="frg-preftr"><span>Сайттағы ақпарат анықтамалық сипатта</span><span class="frg-preftr__sep">·</span><a href="/page/o-proekte">Жоба туралы</a><span class="frg-preftr__sep">·</span><a href="/page/chastye-voprosy">Сұрақтар мен жауаптар</a></div>'],
                ],
            ],

            'themes' => [
                'indigo'     => ['en' => 'Indigo', 'de' => 'Indigo', 'fr' => 'Indigo', 'it' => 'Indaco', 'be' => 'Індыга', 'kk' => 'Индиго'],
                'graphite'   => ['en' => 'Graphite', 'de' => 'Graphit', 'fr' => 'Graphite', 'it' => 'Grafite', 'be' => 'Графіт', 'kk' => 'Графит'],
                'terracotta' => ['en' => 'Terracotta', 'de' => 'Terrakotta', 'fr' => 'Terre cuite', 'it' => 'Terracotta', 'be' => 'Тэракота', 'kk' => 'Терракота'],
                'mint'       => ['en' => 'Mint', 'de' => 'Minze', 'fr' => 'Menthe', 'it' => 'Menta', 'be' => 'Мята', 'kk' => 'Жалбыз'],
                'contrast'   => ['en' => 'Contrast', 'de' => 'Kontrast', 'fr' => 'Contraste', 'it' => 'Contrasto', 'be' => 'Кантраст', 'kk' => 'Контраст'],
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
            self::translateThemes($definitions['themes'] ?? [], $force);
        });
    }

    /** Пункты меню: ищем по названию, переводим только title */
    private static function translateMenu(array $map, bool $force): void
    {
        if (! class_exists(\Modules\Menu\Models\MenuItem::class)) {
            return;
        }

        foreach ($map as $title => $translations) {
            $payload = array_map(fn ($value) => ['title' => $value], $translations);

            foreach (\Modules\Menu\Models\MenuItem::where('title', $title)->get() as $item) {
                self::apply($item, $payload, $force);
            }

            // Названия самих меню — отдельная модель: в подвале они выводятся
            // заголовками колонок («Информация», «Участие»)
            if (class_exists(\Modules\Menu\Models\Menu::class)) {
                foreach (\Modules\Menu\Models\Menu::where('title', $title)->get() as $menu) {
                    self::apply($menu, $payload, $force);
                }
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


    /** Названия тем оформления: видны посетителю в переключателе шапки */
    private static function translateThemes(array $map, bool $force): void
    {
        if (! class_exists(\Modules\Visual\Models\Theme::class)) {
            return;
        }

        foreach ($map as $slug => $translations) {
            $theme = \Modules\Visual\Models\Theme::where('slug', $slug)->first();

            if ($theme) {
                self::apply($theme, array_map(fn ($value) => ['title' => $value], $translations), $force);
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
