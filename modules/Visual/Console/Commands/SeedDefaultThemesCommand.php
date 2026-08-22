<?php

namespace Modules\Visual\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Visual\Models\Theme;

/**
 * Пять готовых тем оформления сразу после установки.
 *
 * Вызывается мастером установки (InstallController::finish → self::seed(false)).
 * Раньше таблица visual_themes оставалась пустой: раздел «Темы» встречал пустой
 * таблицей, Theme::getActive() возвращал null, и весь сайт жил на значениях,
 * зашитых в лейаутах, — сменить оформление было нечем.
 *
 *   php artisan themes:seed-default            # добавить недостающие темы
 *   php artisan themes:seed-default --reset    # перезаписать наборы токенов
 *
 * Токены заполняются ровно те, что читает фронт (layouts/frontend.blade.php):
 * colors.bg/text/primary/accent/header/footer, radius.md, font.base — плюс
 * config.icon_mode и шрифт. Их же использует админка.
 */
class SeedDefaultThemesCommand extends Command
{
    protected $signature = 'themes:seed-default {--reset : Перезаписать токены дефолтных тем}';

    protected $description = 'Пять готовых тем оформления (одна назначается активной)';

    /** Slug темы, которая включается сразу после установки */
    public const DEFAULT_SLUG = 'indigo';

    /**
     * Канонический набор тем.
     *
     * Шрифты берём из локального набора (@fontsource, см. LOCAL_FONTS в
     * app/helpers.php) — внешние CDN проекту не нужны.
     */
    public static function definitions(): array
    {
        return [
            [
                'slug'  => 'indigo',
                'title' => 'Индиго',
                'note'  => 'Светлая тема с фиолетово-синим акцентом — оформление по умолчанию.',
                'tokens' => [
                    'colors' => [
                        'bg'      => '#f8fafc',
                        'text'    => '#111827',
                        'primary' => '#6366f1',
                        'accent'  => '#8b5cf6',
                        'header'  => '#ffffff',
                        'footer'  => '#ffffff',
                    ],
                    'radius' => ['md' => '12px'],
                    'font'   => ['base' => "Inter, -apple-system, BlinkMacSystemFont, system-ui, sans-serif"],
                ],
                'config' => [
                    'icon_mode'     => 'lucide',
                    'font_provider' => 'local',
                    'font_name'     => 'Inter',
                ],
            ],

            /*
             * Темы ниже переписаны под фоновую картинку по умолчанию — светлую,
             * бело-голубую. Тёмные фоны её глушили, поэтому все четыре светлые,
             * а различаются акцентом.
             *
             * Контраст текста к фону проверен по WCAG: везде выше 15:1, то есть
             * с большим запасом к порогу AA (4.5:1). Акценты подобраны так,
             * чтобы белая надпись на кнопке тоже проходила порог, — поэтому
             * красный и малиновый взяты приглушённее фирменных.
             */

            [
                'slug'  => 'scarlet',
                'title' => 'Алый',
                'note'  => 'Светлая тема с красным акцентом: спокойный фон, насыщенная кнопка.',
                'tokens' => [
                    'colors' => [
                        'bg'      => '#f7f8fa',
                        'text'    => '#14171a',
                        'primary' => '#d32b1a',
                        'accent'  => '#f2542d',
                        'header'  => '#ffffff',
                        'footer'  => '#ffffff',
                    ],
                    'radius' => ['md' => '8px'],
                    'font'   => ['base' => "Inter, -apple-system, BlinkMacSystemFont, system-ui, sans-serif"],
                ],
                'config' => [
                    'icon_mode'     => 'svg',
                    'font_provider' => 'local',
                    'font_name'     => 'Inter',
                ],
            ],

            [
                'slug'  => 'azure',
                'title' => 'Лазурь',
                'note'  => 'Светлая тема с синим акцентом: холодный фон, чистая типографика.',
                'tokens' => [
                    'colors' => [
                        'bg'      => '#f5f8ff',
                        'text'    => '#101828',
                        'primary' => '#0057e7',
                        'accent'  => '#3d8bff',
                        'header'  => '#ffffff',
                        'footer'  => '#ffffff',
                    ],
                    'radius' => ['md' => '12px'],
                    'font'   => ['base' => "Inter, -apple-system, BlinkMacSystemFont, system-ui, sans-serif"],
                ],
                'config' => [
                    'icon_mode'     => 'bootstrap',
                    'font_provider' => 'local',
                    'font_name'     => 'Inter',
                ],
            ],

            [
                'slug'  => 'graphite',
                'title' => 'Графит',
                'note'  => 'Почти монохромная тема: графитовый текст и кнопки, синие ссылки.',
                'tokens' => [
                    'colors' => [
                        'bg'      => '#f7f9f9',
                        'text'    => '#0f1419',
                        'primary' => '#0f1419',
                        'accent'  => '#1274c4',
                        'header'  => '#ffffff',
                        'footer'  => '#ffffff',
                    ],
                    'radius' => ['md' => '16px'],
                    'font'   => ['base' => "Inter, -apple-system, BlinkMacSystemFont, system-ui, sans-serif"],
                ],
                'config' => [
                    'icon_mode'     => 'remix',
                    'font_provider' => 'local',
                    'font_name'     => 'Inter',
                ],
            ],

            [
                'slug'  => 'magenta',
                'title' => 'Пурпур',
                'note'  => 'Светлая тема с малиновым акцентом: мягкий тёплый фон.',
                'tokens' => [
                    'colors' => [
                        'bg'      => '#fdf6fa',
                        'text'    => '#1a1220',
                        'primary' => '#c2185b',
                        'accent'  => '#e0407e',
                        'header'  => '#ffffff',
                        'footer'  => '#ffffff',
                    ],
                    'radius' => ['md' => '10px'],
                    'font'   => ['base' => "Inter, -apple-system, BlinkMacSystemFont, system-ui, sans-serif"],
                ],
                'config' => [
                    'icon_mode'     => 'svg',
                    'font_provider' => 'local',
                    'font_name'     => 'Inter',
                ],
            ],

            /*
             * Неон — единственная ТЁМНАЯ тема набора: почти чёрный фон с
             * фиолетовым отливом, сиреневый акцент и бирюзовая искра.
             *
             * Пришёл на место «Изумруда» по просьбе владельца. Тот завёлся
             * вручную и жил только в его базе, а пояснение у него оставалось
             * от совсем другой темы («песочный фон, терракотовая кнопка») —
             * с цветами не сходилось вовсе.
             *
             * Контраст: текст к фону 16.0, акцент к фону 7.2, бирюза к фону
             * 10.9 — с большим запасом к порогу AA.
             */
            [
                'slug'  => 'neon',
                'title' => 'Неон',
                'note'  => 'Тёмная тема: почти чёрный фон с фиолетовым отливом и бирюзовой искрой.',
                'tokens' => [
                    'colors' => [
                        'bg'      => '#0b0a14',
                        'text'    => '#e9e6f5',
                        'primary' => '#a78bfa',
                        'accent'  => '#22d3ee',
                        'header'  => '#110f1c',
                        'footer'  => '#110f1c',
                    ],
                    'radius' => ['md' => '14px'],
                    'font'   => ['base' => "Inter, -apple-system, BlinkMacSystemFont, system-ui, sans-serif"],
                ],
                'config' => [
                    'icon_mode'     => 'svg',
                    'font_provider' => 'local',
                    'font_name'     => 'Inter',
                ],
            ],

            /*
             * Пять тем ниже добавлены отдельным заходом. У каждой свой фон и
             * свой знак с собственной надписью — прежние пять отличались
             * только цветом. Оттенки специально не пересекаются с уже
             * существующими: бирюза «Океана» не повторяет синий «Лазури»,
             * слива не повторяет малину «Пурпура».
             *
             * Контраст проверен по WCAG до отрисовки: текст к фону не ниже
             * 16:1, белая надпись на кнопке не ниже 5:1 при пороге AA 4.5:1.
             */

            [
                'slug'  => 'mono',
                'title' => 'Монохром',
                'note'  => 'Чёрно-белая: только графика и типографика, ни одного цветного пятна.',
                'tokens' => [
                    'colors' => [
                        'bg'      => '#ffffff',
                        'text'    => '#0a0a0a',
                        'primary' => '#171717',
                        'accent'  => '#525252',
                        'header'  => '#ffffff',
                        'footer'  => '#fafafa',
                    ],
                    'radius' => ['md' => '4px'],
                    'font'   => ['base' => "Inter, -apple-system, BlinkMacSystemFont, system-ui, sans-serif"],
                ],
                'config' => [
                    'icon_mode'     => 'svg',
                    'font_provider' => 'local',
                    'font_name'     => 'Inter',
                ],
            ],

            [
                'slug'  => 'amber',
                'title' => 'Янтарь',
                'note'  => 'Тёплая песочная тема с золотым акцентом — мягкая и «бумажная».',
                'tokens' => [
                    'colors' => [
                        'bg'      => '#fffdf7',
                        'text'    => '#1c1917',
                        'primary' => '#b45309',
                        'accent'  => '#d97706',
                        'header'  => '#ffffff',
                        'footer'  => '#fffbeb',
                    ],
                    'radius' => ['md' => '14px'],
                    'font'   => ['base' => "Inter, -apple-system, BlinkMacSystemFont, system-ui, sans-serif"],
                ],
                'config' => [
                    'icon_mode'     => 'bootstrap',
                    'font_provider' => 'local',
                    'font_name'     => 'Inter',
                ],
            ],

            [
                'slug'  => 'pine',
                'title' => 'Хвоя',
                'note'  => 'Глубокий хвойный зелёный: спокойная тема для текста и каталогов.',
                'tokens' => [
                    'colors' => [
                        'bg'      => '#f6faf7',
                        'text'    => '#0f1f14',
                        'primary' => '#166534',
                        'accent'  => '#15803d',
                        'header'  => '#ffffff',
                        'footer'  => '#f0fdf4',
                    ],
                    'radius' => ['md' => '10px'],
                    'font'   => ['base' => "Inter, -apple-system, BlinkMacSystemFont, system-ui, sans-serif"],
                ],
                'config' => [
                    'icon_mode'     => 'remix',
                    'font_provider' => 'local',
                    'font_name'     => 'Inter',
                ],
            ],

            [
                'slug'  => 'ocean',
                'title' => 'Океан',
                'note'  => 'Морская бирюза — прохладная и чистая, без синевы «Лазури».',
                'tokens' => [
                    'colors' => [
                        'bg'      => '#f4fafb',
                        'text'    => '#0c1c1f',
                        'primary' => '#0e7490',
                        'accent'  => '#0891b2',
                        'header'  => '#ffffff',
                        'footer'  => '#ecfeff',
                    ],
                    'radius' => ['md' => '12px'],
                    'font'   => ['base' => "Inter, -apple-system, BlinkMacSystemFont, system-ui, sans-serif"],
                ],
                'config' => [
                    'icon_mode'     => 'lucide',
                    'font_provider' => 'local',
                    'font_name'     => 'Inter',
                ],
            ],

            [
                'slug'  => 'plum',
                'title' => 'Слива',
                'note'  => 'Насыщенный сливовый — глубже и спокойнее «Пурпура».',
                'tokens' => [
                    'colors' => [
                        'bg'      => '#faf7fc',
                        'text'    => '#1a1020',
                        'primary' => '#6b21a8',
                        'accent'  => '#7e22ce',
                        'header'  => '#ffffff',
                        'footer'  => '#f5f3ff',
                    ],
                    'radius' => ['md' => '12px'],
                    'font'   => ['base' => "Inter, -apple-system, BlinkMacSystemFont, system-ui, sans-serif"],
                ],
                'config' => [
                    'icon_mode'     => 'svg',
                    'font_provider' => 'local',
                    'font_name'     => 'Inter',
                ],
            ],
        ];
    }

    /**
     * Фон Индиго.
     *
     * Прежде здесь лежал растр `/images/theme-default-bg.png` (1,4 МБ). Он
     * ушёл вместе со светящимися узлами: владелец попросил убрать блики во
     * ВСЕХ темах, а из готовой картинки их не вынуть. Индиго собран тем же
     * генератором, что и остальные, — язык рисунка сохранён (сеть, грани,
     * соты), пропали только засветки.
     */
    public const DEFAULT_BACKGROUND = '/images/themes/backgrounds/indigo.svg';

    /**
     * Свой фон и свой знак у каждой темы.
     *
     * Раньше сидер выдавал ВСЕМ темам одну и ту же картинку, а логотипа не
     * давал вовсе — темы отличались только цветами. Потом появились свои
     * узоры, но все десять были ОДНОЙ россыпью точек при 8% непрозрачности:
     * от темы к теме сайт по-прежнему не менялся.
     *
     * Сейчас у каждой темы фон в языке Индиго — низкополигональная сеть с
     * гранями, сотами и мягкими пятнами, — но в своей палитре и со своим
     * рисунком сети. Светящихся узлов нет ни у одной: блики читались как
     * засветка от вспышки и спорили с текстом карточек.
     *
     * Формат SVG: рисунок не мылится на любом экране, весит ~42 КБ вместо
     * 1,4 МБ и правится текстом. Файлы СОБРАНЫ генератором — руками их не
     * правят, меняют генератор и пересобирают.
     *
     * Ключ отсутствует — тема получает только то, что задано; ничего не
     * подставляется молча.
     */
    /**
     * Ширина знака в шапке.
     *
     * Знаки-надписи вытянутые, и предел по умолчанию — 120px — зажимал их до
     * 25px в высоту: мельче прежней текстовой марки, и шапка съезжала со
     * 124px до 116.
     *
     * Значение с запасом, чтобы размер держал ограничитель ВЫСОТЫ (2.4rem в
     * layouts/partials/header), а не ширины. Иначе высота зависела бы от
     * пропорций знака: у Индиго надпись длиннее («Nexum Core» с расшифровкой),
     * его знак шире, и при пределе 200px он не дотягивал до общей высоты —
     * шапка выходила на 4px ниже, чем у остальных тем.
     */
    private const LOGO_WIDTH = '240px';

    private const ASSETS = [
        // Индиго: знак в том же строении, что у остальных тем, но надпись
        // прежняя — «Nexum Core» с расшифровкой. Это тема по умолчанию, её марка
        // должна остаться узнаваемой: менялось исполнение, не смысл.
        'indigo' => [
            'background_url' => self::DEFAULT_BACKGROUND,
            // Тема не на своём наборе: путь к нему должен исчезнуть из
            // настроек. Явный null нужен потому, что сброс сливает конфиг
            // ПОВЕРХ прежнего и чужие ключи сохраняет — «не задать» мало.
            'icons_path'     => null,
            'logo_url'       => '/images/themes/logos/indigo.svg',
        ],
        'scarlet' => [
            'background_url' => '/images/themes/backgrounds/scarlet.svg',
            'icons_path'     => '/assets/icons/nexum-line',
            'logo_url'       => '/images/themes/logos/scarlet.svg',
        ],
        'azure' => [
            'background_url' => '/images/themes/backgrounds/azure.svg',
            'logo_url'       => '/images/themes/logos/azure.svg',
        ],
        'graphite' => [
            'background_url' => '/images/themes/backgrounds/graphite.svg',
            // Тема не на своём наборе: путь к нему должен исчезнуть из
            // настроек. Явный null нужен потому, что сброс сливает конфиг
            // ПОВЕРХ прежнего и чужие ключи сохраняет — «не задать» мало.
            'icons_path'     => null,
            'logo_url'       => '/images/themes/logos/graphite.svg',
        ],
        'magenta' => [
            'background_url' => '/images/themes/backgrounds/magenta.svg',
            'icons_path'     => '/assets/icons/nexum-line',
            'logo_url'       => '/images/themes/logos/magenta.svg',
        ],
        'mono' => [
            'background_url' => '/images/themes/backgrounds/mono.svg',
            'icons_path'     => '/assets/icons/nexum-line',
            'logo_url'       => '/images/themes/logos/mono.svg',
        ],
        'amber' => [
            'background_url' => '/images/themes/backgrounds/amber.svg',
            'logo_url'       => '/images/themes/logos/amber.svg',
        ],
        'pine' => [
            'background_url' => '/images/themes/backgrounds/pine.svg',
            // Тема не на своём наборе: путь к нему должен исчезнуть из
            // настроек. Явный null нужен потому, что сброс сливает конфиг
            // ПОВЕРХ прежнего и чужие ключи сохраняет — «не задать» мало.
            'icons_path'     => null,
            'logo_url'       => '/images/themes/logos/pine.svg',
        ],
        'ocean' => [
            'background_url' => '/images/themes/backgrounds/ocean.svg',
            // Тема не на своём наборе: путь к нему должен исчезнуть из
            // настроек. Явный null нужен потому, что сброс сливает конфиг
            // ПОВЕРХ прежнего и чужие ключи сохраняет — «не задать» мало.
            'icons_path'     => null,
            'logo_url'       => '/images/themes/logos/ocean.svg',
        ],
        'plum' => [
            'background_url' => '/images/themes/backgrounds/plum.svg',
            'icons_path'     => '/assets/icons/nexum-line',
            'logo_url'       => '/images/themes/logos/plum.svg',
        ],
        // Неон единственный ТЁМНЫЙ, поэтому и узор у него тёмный: светлая
        // плитка остальных на его фоне была бы белым пятном.
        'neon' => [
            'background_url' => '/images/themes/backgrounds/neon.svg',
            'icons_path'     => '/assets/icons/nexum-line',
            'logo_url'       => '/images/themes/logos/neon.svg',
        ],
    ];

    public static function seed(bool $reset = false): void
    {
        DB::transaction(function () use ($reset) {
            foreach (self::definitions() as $definition) {
                $theme = Theme::where('slug', $definition['slug'])->first();

                $attributes = [
                    'title'  => $definition['title'],
                    'tokens' => $definition['tokens'],
                    'config' => array_merge($definition['config'], [
                        'note' => $definition['note'],
                        // CSS-переменные пересобираем из токенов — тем же способом,
                        // что и контроллер при сохранении темы
                        'css'  => self::buildCss($definition['tokens']),
                    ], self::ASSETS[$definition['slug']] ?? [], array_filter([
                        'logo_width' => isset(self::ASSETS[$definition['slug']]['logo_url'])
                            ? self::LOGO_WIDTH
                            : null,
                    ])),
                ];

                if (! $theme) {
                    $attributes['config'] = array_filter(
                        $attributes['config'],
                        static fn ($value) => $value !== null
                    );

                    Theme::create($attributes + ['slug' => $definition['slug'], 'is_default' => false]);
                    continue;
                }

                if ($reset) {
                    // config темы мог обрасти своими ключами — их сохраняем
                    $merged = array_merge($theme->config ?? [], $attributes['config']);

                    // null означает «этого у темы быть не должно»: ключ
                    // убираем, иначе прежнее значение пережило бы сброс.
                    $attributes['config'] = array_filter(
                        $merged,
                        static fn ($value) => $value !== null
                    );

                    $theme->update($attributes);
                }
            }

            // Активной делаем тему по умолчанию, но только если пользователь
            // ещё не выбрал свою — иначе установка перетёрла бы его выбор
            if (! Theme::where('is_default', true)->exists()) {
                Theme::where('slug', self::DEFAULT_SLUG)->update(['is_default' => true]);
                Theme::flushActiveCache();
            }
        });
    }

    /**
     * CSS-переменные темы (то же, что делает ThemesController::regenerateCss).
     */
    public static function buildCss(array $tokens): string
    {
        $css = ':root{';

        foreach ((array) data_get($tokens, 'colors', []) as $key => $value) {
            if ($value !== null && $value !== '') {
                $css .= "--color-{$key}: {$value};";
            }
        }

        $css .= '--radius-md: ' . (string) data_get($tokens, 'radius.md', '12px') . ';';
        $css .= '--font-base: ' . (string) data_get($tokens, 'font.base', 'Inter, sans-serif') . ';';

        return $css . '}';
    }

    public function handle(): int
    {
        $reset = (bool) $this->option('reset');
        self::seed($reset);

        $titles = collect(self::definitions())->pluck('title')->implode(', ');
        $this->info(($reset ? 'Темы перезаписаны' : 'Темы проверены/созданы') . ": {$titles}.");

        return self::SUCCESS;
    }
}
