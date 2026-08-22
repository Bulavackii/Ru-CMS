<?php

namespace Tests\Feature;

use App\Providers\ThemeServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Ни одного пункта без значка — ни при каком оформлении.
 *
 * 🔴 Набор значков меняется вместе с темой, и у каждого набора СВОИ имена
 * глифов. Пока карты соответствий были неполными, это выглядело так, будто
 * значки «не дорисовались»: у Boxicons не находилось 26 имён из 47, у
 * Phosphor — 25, у Bootstrap и Remix — по три, у Font Awesome — четыре.
 * Промах молчаливый: имя уходит в набор как есть, глифа нет, и на месте
 * значка не рисуется ничего. Заметно это только на той странице, куда
 * случайно зайдёшь.
 *
 * Тест сверяет КАЖДОЕ имя, которое проект реально использует, с самим
 * набором — списком имён, вынутым из его же CSS или из каталога файлов.
 */
class IconSetsCoverageTest extends TestCase
{
    use RefreshDatabase;

    /** Откуда берётся список имён, которые набор действительно умеет. */
    private const НАБОРЫ = [
        'bootstrap' => ['public/assets/css/bootstrap-icons.css',   '~\.bi-([a-z0-9-]+)::?before~'],
        'remix'     => ['public/assets/css/remixicon.css',         '~\.ri-([a-z0-9-]+):before~'],
        'boxicons'  => ['public/assets/css/boxicons.css',          '~\.bx-([a-z0-9-]+):before~'],
        'phosphor'  => ['public/assets/css/phosphor-icons.css',    '~\.ph-([a-z0-9-]+):before~'],
    ];

    /**
     * Свой набор — обычные файлы, список имён это список файлов.
     *
     * Начертание у «Нексума» ОДНО. «Плотное» (обводка 2.7) убрано: при показе
     * в 14–16 пикселей просветы между линиями смыкались, и значки читались
     * глухими пятнами.
     *
     * 🔴 Tabler здесь ТОЖЕ файловый, а не шрифтовой. Шрифтовая сборка рисуется
     * силуэтами: доля заливки при 16 пикселях 0.81 против 0.51 у Bootstrap, и
     * середина закрашена у всех значков подряд. Файл при этом официальный, и
     * коды в CSS совпали с официальными до единого — чинится только заменой
     * шрифта на файлы SVG.
     */
    private const СВОИ = ['nexum-line', 'tabler'];

    /**
     * Имена, которые проект просит у набора.
     *
     * Собираются из шаблонов, а не перечисляются руками: рукописный список
     * устарел бы с первым новым значком, и ломались бы как раз непроверенные.
     */
    private function именаВХоду(): array
    {
        $имена = [];

        foreach (['resources/views', 'modules'] as $каталог) {
            $обход = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(base_path($каталог))
            );

            foreach ($обход as $файл) {
                if (! $файл->isFile() || ! str_ends_with($файл->getFilename(), '.blade.php')) {
                    continue;
                }

                preg_match_all("~@themeIcon\('([a-z0-9-]+)'~", file_get_contents($файл->getPathname()), $m);

                foreach ($m[1] as $имя) {
                    $имена[$имя] = true;
                }
            }
        }

        return array_keys($имена);
    }

    private function карта(string $набор): array
    {
        $метод = new ReflectionMethod(ThemeServiceProvider::class, 'getAliases');

        return $метод->invoke(null)[$набор] ?? [];
    }

    public function test_every_webfont_set_has_a_glyph_for_every_name_in_use(): void
    {
        $имена = $this->именаВХоду();
        $this->assertNotEmpty($имена, 'Имена значков не собрались — сломался обход шаблонов.');

        foreach (self::НАБОРЫ as $набор => [$путь, $правило]) {
            $css = @file_get_contents(base_path($путь));
            $this->assertNotFalse($css, "Набор «{$набор}»: файл {$путь} не читается.");

            preg_match_all($правило, $css, $m);
            $есть = array_flip($m[1]);
            $this->assertNotEmpty($есть, "Набор «{$набор}»: имена из CSS не вынулись — изменился формат файла.");

            $карта = $this->карта($набор);
            $нет = [];

            foreach ($имена as $имя) {
                $цель = $карта[$имя] ?? $имя;

                if (! isset($есть[$цель])) {
                    $нет[] = "{$имя} → {$цель}";
                }
            }

            $this->assertSame([], $нет, "Набор «{$набор}»: нет глифа для " . implode(', ', $нет));
        }
    }

    /**
     * Lucide — набор темы по умолчанию, и он единственный не шрифт, а
     * сборка JavaScript: имена в ней записаны ВерблюжьимРегистром.
     *
     * ⚠️ Промах здесь виден только в консоли браузера («icon name was not
     * found») на той странице, куда случайно зайдёшь. Владелец однажды уже
     * присылал снимок с девятью такими предупреждениями подряд.
     */
    public function test_lucide_has_a_glyph_for_every_name_in_use(): void
    {
        $js = @file_get_contents(base_path('public/assets/js/lucide.min.js'));
        $this->assertNotFalse($js, 'Lucide: сборка не читается.');

        // ⚠️ `*`, а не `+`: в наборе есть односимвольные имена — «X»
        // (крестик). С `+` проверка объявляла его отсутствующим, хотя глиф
        // на месте. Изъян был в самой проверке, а не в наборе.
        preg_match_all('~[,{"\']([A-Z][A-Za-z0-9]*)\s*:~', $js, $m);
        $есть = array_flip($m[1]);
        $this->assertNotEmpty($есть, 'Lucide: имена из сборки не вынулись — изменился её формат.');

        $карта = $this->карта('lucide');
        $нет = [];

        foreach ($this->именаВХоду() as $имя) {
            $цель = $карта[$имя] ?? $имя;
            // kebab-case → ВерблюжийРегистр, как внутри самой сборки
            $верблюд = str_replace(' ', '', ucwords(str_replace('-', ' ', $цель)));

            if (! isset($есть[$верблюд])) {
                $нет[] = "{$имя} → {$цель}";
            }
        }

        $this->assertSame([], $нет, 'Lucide: нет глифа для ' . implode(', ', $нет));
    }

    /**
     * Font Awesome — запасной набор, и он же самостоятельный режим.
     * У него своя карта, поэтому проверяется отдельно.
     */
    public function test_font_awesome_has_a_glyph_for_every_name_in_use(): void
    {
        $css = @file_get_contents(base_path('public/assets/css/font-awesome/all.min.css'));
        $this->assertNotFalse($css, 'Font Awesome: файл стилей не читается.');

        preg_match_all('~\.fa-([a-z0-9-]+):before~', $css, $m);
        $есть = array_flip($m[1]);

        $метод = new ReflectionMethod(ThemeServiceProvider::class, 'getFaMap');
        $карта = $метод->invoke(null);

        $нет = [];

        foreach ($this->именаВХоду() as $имя) {
            $цель = $карта[$имя] ?? $имя;

            if (! isset($есть[$цель])) {
                $нет[] = "{$имя} → {$цель}";
            }
        }

        $this->assertSame([], $нет, 'Font Awesome: нет глифа для ' . implode(', ', $нет));
    }

    /**
     * Свои наборы: имя — это имя файла, промах виден сразу.
     *
     * ⚠️ У режима `svg` есть запасной путь — не нашёлся файл, берётся
     * случайный из того же каталога. То есть пустого места не будет, но
     * значок окажется НЕ ТОТ, и заметить это по внешнему виду страницы
     * почти невозможно. Поэтому проверяем наличие файла, а не картинку.
     */
    public function test_own_sets_have_a_file_for_every_name_in_use(): void
    {
        foreach (self::СВОИ as $набор) {
            $каталог = public_path('assets/icons/' . $набор);
            $this->assertDirectoryExists($каталог, "Свой набор «{$набор}» не найден.");

            $нет = [];

            foreach ($this->именаВХоду() as $имя) {
                if (! is_file($каталог . '/' . $имя . '.svg')) {
                    $нет[] = $имя;
                }
            }

            $this->assertSame([], $нет, "Свой набор «{$набор}»: нет файла для " . implode(', ', $нет));
        }
    }

    /**
     * Значок обязан принимать цвет темы и идти со строкой текста.
     *
     * Зашитый цвет сделал бы набор непригодным на тёмных оформлениях, а
     * размер в пикселях — не дал бы значку следовать кеглю рядом стоящего
     * текста.
     */
    public function test_own_sets_follow_the_theme_colour_and_text_size(): void
    {
        foreach (self::СВОИ as $набор) {
            foreach (glob(public_path('assets/icons/' . $набор . '/*.svg')) as $файл) {
                $svg = file_get_contents($файл);
                $имя = basename($файл);

                $this->assertStringContainsString('currentColor', $svg, "{$набор}/{$имя}: цвет не наследуется от темы.");
                $this->assertStringContainsString('width="1em"', $svg, "{$набор}/{$имя}: размер не следует кеглю текста.");
                $this->assertMatchesRegularExpression(
                    '~fill="(?:none|currentColor)"~', $svg,
                    "{$набор}/{$имя}: в заливке чужой цвет."
                );
            }
        }
    }
}
