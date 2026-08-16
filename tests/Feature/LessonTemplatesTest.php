<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Controllers\Admin\NewsController;
use Modules\News\Models\News;
use Tests\TestCase;

/**
 * Шаблоны уроков: реестр, файл шаблона и подпись раздела должны совпадать.
 *
 * Эти три места уже расходились: файлы четырёх шаблонов уроков лежали в
 * проекте, а в реестре их не было — выбрать шаблон в форме было НЕЛЬЗЯ, то
 * есть готовые шаблоны для владельца просто не существовали. Тест ловит
 * ровно это: добавили шаблон в один список и забыли про два других.
 */
class LessonTemplatesTest extends TestCase
{
    use RefreshDatabase;

    /** Ключи всех шаблонов уроков — по общему префиксу. */
    private function шаблоныУроков(): array
    {
        return array_values(array_filter(
            array_keys(NewsController::TEMPLATES),
            fn (string $ключ) => str_starts_with($ключ, 'base-')
        ));
    }

    /**
     * У каждого шаблона из реестра есть файл разметки.
     *
     * ⚠️ Имена переменных в сообщениях берём в фигурные скобки. PHP
     * разрешает многобайтовые символы в именах, и «$ключ» без скобок
     * разбирается как переменная с кавычкой-ёлочкой в имени.
     */
    public function test_every_lesson_template_has_a_view(): void
    {
        $шаблоны = $this->шаблоныУроков();
        $this->assertNotEmpty($шаблоны, 'В реестре нет ни одного шаблона уроков');

        foreach ($шаблоны as $ключ) {
            $this->assertTrue(
                view()->exists("frontend.templates.$ключ"),
                "Шаблон «{$ключ}» есть в реестре, но файла разметки нет — выбрать его можно, а показать нечем"
            );
        }
    }

    /**
     * Каждый шаблон уроков подписан на обоих языках.
     *
     * Подпись берётся по короткому ключу из самого файла шаблона
     * (`base-git` → `git`), поэтому проверяем именно её.
     */
    public function test_every_lesson_template_has_a_label(): void
    {
        foreach ($this->шаблоныУроков() as $ключ) {
            $короткий = str_replace('base-', '', $ключ);

            foreach (['ru', 'en'] as $язык) {
                $подпись = __('frontend.templates.' . $короткий, [], $язык);

                $this->assertNotSame(
                    'frontend.templates.' . $короткий,
                    $подпись,
                    "У шаблона «{$ключ}» нет подписи в словаре $язык — на сайте покажется сырой ключ"
                );
            }
        }
    }

    /** Материал шаблона уроков открывается и показывает свой раздел. */
    public function test_lesson_material_opens(): void
    {
        $материал = News::create([
            'title'     => 'Ветки: почему здесь работают прямо в master',
            'slug'      => 'urok-git-vetki-proverka',
            'content'   => '<p>Текст урока</p><pre><code>git branch</code></pre>',
            'template'  => 'base-git',
            'published' => true,
        ]);

        $ответ = $this->get('/news/' . $материал->slug);
        $ответ->assertOk();
        $ответ->assertSee('Ветки: почему здесь работают прямо в master', false);
    }

    /**
     * Демо-уроки шаблона заведены в мастере установки.
     *
     * Правило проекта: всё, что мы добавляем в содержимое, дублируется в
     * сидер — иначе чистая установка выглядит иначе, чем боевой сайт.
     */
    public function test_git_lessons_are_in_the_installer(): void
    {
        $исходник = file_get_contents(base_path('modules/Install/Controllers/InstallController.php'));

        $this->assertGreaterThanOrEqual(
            3,
            substr_count($исходник, "'template' => 'base-git'"),
            'Уроков Git в мастере установки меньше трёх — на чистой установке раздел будет почти пуст'
        );
    }
}
