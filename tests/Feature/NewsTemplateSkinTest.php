<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\News;
use Tests\TestCase;

/**
 * Оформление страницы новости под шаблон материала.
 *
 * Раньше поле «Шаблон» влияло только на списки: карточки на главной
 * выглядели по-разному, а открытая новость — одинаково у всех. Теперь
 * статья получает класс `news--<шаблон>`, а само оформление живёт в
 * необязательной «шкурке» (frontend/templates/skins/<шаблон>.blade.php).
 *
 * Главное, что здесь проверяется: механизм НЕ ЛОМАЕТ материалы без шкурки.
 * Копировать show.blade.php под каждый шаблон было нельзя — крошки, плашка
 * покупки и «поделиться» разъехались бы по восьми копиям.
 */
class NewsTemplateSkinTest extends TestCase
{
    use RefreshDatabase;

    private function материал(string $slug, ?string $шаблон): News
    {
        return News::create([
            'title'     => 'Патч 1.4',
            'slug'      => $slug,
            'content'   => '<p>Крупное обновление баланса.</p>',
            'template'  => $шаблон,
            'published' => true,
        ]);
    }

    /**
     * Комментарии шкурок не должны попадать на страницу.
     *
     * ⚠️ Файлы создавались скриптом через str.format, а он трактует {{ и }}
     * как экранирование: «{{--» превратилось в «{--», «--}}» в «--}». Blade
     * такой комментарий не распознаёт и печатает его на странице как обычный
     * текст — владелец увидел абзац с пояснением прямо над крошками
     * журнального материала.
     *
     * Проверяем ИСТОЧНИК, а не отрисовку: так ошибка ловится сразу во всех
     * шкурках, включая те, что заведут потом.
     */
    public function test_skins_use_real_blade_comments(): void
    {
        $каталог = resource_path('views/frontend/templates/skins');
        $файлы = glob($каталог . '/*.blade.php');

        $this->assertNotEmpty($файлы, 'Шкурок не найдено — проверять нечего');

        foreach ($файлы as $файл) {
            $текст = file_get_contents($файл);
            $имя = basename($файл);

            // Одиночная скобка вместо двойной — это НЕ комментарий Blade.
            $this->assertDoesNotMatchRegularExpression(
                '~(^|\n)\s*\{--(?!-)~',
                $текст,
                "В шкурке {$имя} комментарий открыт одной скобкой — он выведется на страницу текстом"
            );
            $this->assertDoesNotMatchRegularExpression(
                '~(?<!-)--\}(?!\})~',
                $текст,
                "В шкурке {$имя} комментарий закрыт одной скобкой"
            );
        }
    }

    public function test_article_carries_its_template_class(): void
    {
        $this->материал('igry-patch', 'gaming');

        $this->get('/news/igry-patch')
            ->assertOk()
            ->assertSee('news--gaming', false);
    }

    /** Шкурка подключается сама, регистрировать её нигде не нужно. */
    public function test_skin_is_applied_for_gaming(): void
    {
        $this->материал('igry-patch-2', 'gaming');

        $ответ = $this->get('/news/igry-patch-2');

        $ответ->assertOk();
        // Правило из шкурки: карточки статьи становятся тёмными.
        $ответ->assertSee('.news--gaming .fx-card', false);
    }

    /**
     * Материал БЕЗ шкурки открывается как раньше: класс есть, чужого
     * оформления нет. Это и есть страховка от поломки остальных шаблонов.
     */
    public function test_material_without_skin_is_untouched(): void
    {
        $this->материал('obychnaya', 'clinic');

        $ответ = $this->get('/news/obychnaya');

        $ответ->assertOk();
        $ответ->assertSee('news--clinic', false);
        $ответ->assertDontSee('.news--gaming', false);
    }

    /**
     * Пустой шаблон и NULL — то же самое, что «default». Три состояния одной
     * группы: та же ловушка уже ловилась в постраничном выводе ленты и в
     * категориях по умолчанию.
     */
    public function test_empty_and_null_template_fall_back_to_default(): void
    {
        $this->материал('bez-shablona', null);
        $this->материал('pustoy-shablon', '');

        $this->get('/news/bez-shablona')->assertOk()->assertSee('news--default', false);
        $this->get('/news/pustoy-shablon')->assertOk()->assertSee('news--default', false);
    }
}
