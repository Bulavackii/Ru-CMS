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

            [
                'slug'  => 'graphite',
                'title' => 'Графит',
                'note'  => 'Тёмная тема: глубокий серый фон, светлый текст, холодный синий акцент.',
                'tokens' => [
                    'colors' => [
                        'bg'      => '#0f172a',
                        'text'    => '#e2e8f0',
                        'primary' => '#38bdf8',
                        'accent'  => '#818cf8',
                        'header'  => '#111c33',
                        'footer'  => '#111c33',
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

            [
                'slug'  => 'terracotta',
                'title' => 'Терракота',
                'note'  => 'Тёплая тема: песочный фон, кирпичный акцент, мягкие скругления.',
                'tokens' => [
                    'colors' => [
                        'bg'      => '#fdf6ef',
                        'text'    => '#3f2d23',
                        'primary' => '#c2410c',
                        'accent'  => '#d97706',
                        'header'  => '#fffaf4',
                        'footer'  => '#f7ece1',
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
                'slug'  => 'mint',
                'title' => 'Мята',
                'note'  => 'Холодная светлая тема: прохладный фон и зелёно-бирюзовый акцент.',
                'tokens' => [
                    'colors' => [
                        'bg'      => '#f2fbf8',
                        'text'    => '#0f2e26',
                        'primary' => '#0f766e',
                        'accent'  => '#0ea5e9',
                        'header'  => '#ffffff',
                        'footer'  => '#e8f6f2',
                    ],
                    'radius' => ['md' => '14px'],
                    'font'   => ['base' => "Inter, -apple-system, BlinkMacSystemFont, system-ui, sans-serif"],
                ],
                'config' => [
                    'icon_mode'     => 'lucide',
                    'font_provider' => 'local',
                    'font_name'     => 'Inter',
                ],
            ],

            [
                'slug'  => 'contrast',
                'title' => 'Контраст',
                'note'  => 'Строгая тема повышенной читаемости: чистый белый фон, чёрный текст, прямые углы.',
                'tokens' => [
                    'colors' => [
                        'bg'      => '#ffffff',
                        'text'    => '#000000',
                        'primary' => '#1d4ed8',
                        'accent'  => '#b91c1c',
                        'header'  => '#ffffff',
                        'footer'  => '#f3f4f6',
                    ],
                    'radius' => ['md' => '0px'],
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
                    ]),
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
                Cache::forget('active_theme');
                Cache::forget('active_theme_id');
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
