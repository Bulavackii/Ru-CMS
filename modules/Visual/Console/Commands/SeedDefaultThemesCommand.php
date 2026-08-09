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
                    'icon_mode'     => 'lucide',
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
                    'icon_mode'     => 'lucide',
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
                    'icon_mode'     => 'lucide',
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
                    'icon_mode'     => 'lucide',
                    'font_provider' => 'local',
                    'font_name'     => 'Inter',
                ],
            ],
        ];
    }

    /** Фоновая картинка Индиго — прежняя, отслеживается git. */
    public const DEFAULT_BACKGROUND = '/images/theme-default-bg.png';

    /**
     * Свой фон и свой знак у каждой темы.
     *
     * Раньше сидер выдавал ВСЕМ темам одну и ту же картинку, а логотипа не
     * давал вовсе — темы отличались только цветами. Теперь у каждой свой узор
     * и свой знак в её палитре.
     *
     * Формат SVG: узор повторяется плиткой без потери резкости, весит
     * килобайты вместо мегабайта и правится текстом. Индиго намеренно
     * оставлен на прежнем PNG — после обновления сайт выглядит как и до него.
     *
     * Ключ отсутствует — тема получает только то, что задано; ничего не
     * подставляется молча.
     */
    private const ASSETS = [
        'indigo' => [
            'background_url' => self::DEFAULT_BACKGROUND,
            'logo_url'       => '/images/themes/logos/indigo.svg',
        ],
        'scarlet' => [
            'background_url' => '/images/themes/backgrounds/scarlet.svg',
            'logo_url'       => '/images/themes/logos/scarlet.svg',
        ],
        'azure' => [
            'background_url' => '/images/themes/backgrounds/azure.svg',
            'logo_url'       => '/images/themes/logos/azure.svg',
        ],
        'graphite' => [
            'background_url' => '/images/themes/backgrounds/graphite.svg',
            'logo_url'       => '/images/themes/logos/graphite.svg',
        ],
        'magenta' => [
            'background_url' => '/images/themes/backgrounds/magenta.svg',
            'logo_url'       => '/images/themes/logos/magenta.svg',
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
                    ], self::ASSETS[$definition['slug']] ?? []),
                ];

                if (! $theme) {
                    Theme::create($attributes + ['slug' => $definition['slug'], 'is_default' => false]);
                    continue;
                }

                if ($reset) {
                    // config темы мог обрасти логотипом и фоном — их сохраняем
                    $attributes['config'] = array_merge($theme->config ?? [], $attributes['config']);
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
