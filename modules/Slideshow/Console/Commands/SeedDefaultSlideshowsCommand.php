<?php

namespace Modules\Slideshow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Modules\Slideshow\Models\Slideshow;
use Modules\Slideshow\Models\SlideshowItem;

/**
 * Дефолтные слайдшоу после установки: одно сверху главной, одно снизу.
 *
 * Картинки-баннеры лежат в репозитории (modules/Slideshow/Resources/defaults/*.svg)
 * и при сидировании копируются в storage/app/public/slideshow/defaults — публичная
 * вьюха отдаёт их как asset('storage/'.$item->file_path), поэтому нужен storage:link
 * (мастер установки делает его сам).
 *
 *   php artisan slideshow:seed-default            # создать недостающее (идемпотентно)
 *   php artisan slideshow:seed-default --reset    # пересоздать слайды дефолтных слайдшоу
 */
class SeedDefaultSlideshowsCommand extends Command
{
    protected $signature = 'slideshow:seed-default {--reset : Пересоздать слайды дефолтных слайдшоу}';

    protected $description = 'Создать демо-слайдшоу для главной (верх и низ) с баннерами';

    /** Куда копируем баннеры внутри диска public. */
    private const TARGET_DIR = 'slideshow/defaults';

    /** Канонический набор: два слайдшоу с их слайдами. */
    public static function definitions(): array
    {
        return [
            [
                'title'       => 'Верхний баннер',
                // Слаг намеренно оставлен прежним, хотя название сменилось:
                // сидер ищет запись именно по нему (firstOrCreate ниже). Новый
                // слаг на уже установленной базе не переименовал бы слайдшоу,
                // а завёл второе такое же.
                'slug'        => 'glavnyy-banner',
                'position'    => 'top',
                'description' => 'Верхний баннер главной страницы. Демо-слайды можно заменить своими.',
                'height'      => 'clamp(260px, 40vh, 520px)',
                // 🔴 `caption` — это ПОДПИСЬ-ПЕРЕХОД, а не пересказ слайда.
                //
                // Она рисуется плашкой поверх картинки в углу. Пока в ней
                // лежал заголовок, тот же текст читался дважды: крупно на
                // самом слайде и мелко на плашке, — и плашка вдобавок
                // закрывала угол рисунка. Теперь это короткое действие.
                //
                // Описание для чтения с экрана живёт отдельно в `alt_text`
                // и от укорачивания подписи не пострадало.
                'slides' => [
                    [
                        'file' => 'top-1.svg',
                        'caption' => 'Почему это выгодно',
                        'alt_text' => 'Nexum Core — одна покупка, работа без интернета, магазин в поставке',
                        'link' => '/page/pochemu-nexum-core',
                    ],
                    [
                        'file' => 'top-2.svg',
                        'caption' => 'Открыть сравнение',
                        'alt_text' => 'Сравнение WordPress, 1С-Битрикс и Nexum Core',
                        'link' => '/page/sravnenie',
                    ],
                ],
            ],
            [
                'title'       => 'Нижний баннер',
                'slug'        => 'nizhniy-banner',
                'position'    => 'bottom',
                'description' => 'Нижний баннер главной страницы. Демо-слайды можно заменить своими.',
                'height'      => 'clamp(220px, 32vh, 420px)',
                'slides' => [
                    [
                        'file' => 'bottom-1.svg',
                        'caption' => 'Как это проверить',
                        'alt_text' => 'Автономность: все ресурсы лежат на вашем сервере',
                        'link' => '/page/sravnenie',
                    ],
                    [
                        'file' => 'bottom-2.svg',
                        'caption' => 'Начать установку',
                        'alt_text' => 'Мастер установки: проверка сервера, база, администратор',
                        'link' => '/page/kak-nachat',
                    ],
                ],
            ],
        ];
    }

    /**
     * Копирует баннеры в storage/app/public и создаёт слайдшоу со слайдами.
     * Идемпотентно: слайдшоу ищется по slug, слайды — по паре slideshow_id + file_path.
     * $reset=true — пересоздаёт слайды дефолтных слайдшоу заново.
     */
    public static function seed(bool $reset = false): void
    {
        $source = base_path('modules/Slideshow/Resources/defaults');

        DB::transaction(function () use ($reset, $source) {
            foreach (self::definitions() as $def) {
                $slideshow = Slideshow::firstOrCreate(
                    ['slug' => $def['slug']],
                    [
                        'title'             => $def['title'],
                        'position'          => $def['position'],
                        'description'       => $def['description'],
                        'published'         => true,
                        'autoplay_delay'    => 5000,
                        'transition_effect' => 'slide',
                        'height'            => $def['height'],
                        'show_pagination'   => true,
                        'show_navigation'   => true,
                    ]
                );

                if ($reset) {
                    SlideshowItem::where('slideshow_id', $slideshow->id)->delete();
                }

                foreach ($def['slides'] as $i => $slide) {
                    $relative = self::TARGET_DIR . '/' . $slide['file'];

                    // Копируем баннер на публичный диск, если его там ещё нет,
                    // принудительно при --reset — и когда содержимое разошлось
                    // с тем, что лежит в модуле.
                    //
                    // Последнее нужно, чтобы правка демо-баннера доезжала до
                    // уже установленных сайтов: каталог slideshow/defaults —
                    // это ровно то, что кладёт сюда сидер, свои картинки
                    // владелец загружает отдельными файлами через раздел.
                    $file = $source . DIRECTORY_SEPARATOR . $slide['file'];

                    if (File::exists($file)) {
                        $contents = File::get($file);
                        $stale = ! Storage::disk('public')->exists($relative)
                            || Storage::disk('public')->get($relative) !== $contents;

                        if ($reset || $stale) {
                            Storage::disk('public')->put($relative, $contents);
                        }
                    }

                    SlideshowItem::firstOrCreate(
                        ['slideshow_id' => $slideshow->id, 'file_path' => $relative],
                        [
                            'media_type'       => 'image',
                            'caption'          => $slide['caption'],
                            'alt_text'         => $slide['alt_text'],
                            'link'             => $slide['link'],
                            'order'            => $i + 1,
                            'text_position'    => 'bottom-left',
                            'text_color'       => '#ffffff',
                            'background_color' => '#4f46e5',
                        ]
                    );
                }
            }
        });
    }

    public function handle(): int
    {
        $reset = (bool) $this->option('reset');
        self::seed($reset);

        $titles = collect(self::definitions())
            ->map(fn($d) => "{$d['title']} ({$d['position']})")
            ->implode(', ');

        $this->info(($reset ? 'Демо-слайдшоу пересозданы' : 'Демо-слайдшоу проверены/созданы') . ": {$titles}.");

        return self::SUCCESS;
    }
}
