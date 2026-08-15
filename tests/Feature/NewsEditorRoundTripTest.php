<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Controllers\Admin\NewsController;
use Modules\News\Models\News;
use Tests\TestCase;

/**
 * Материал КАЖДОГО шаблона открывается в форме и сохраняется без потерь.
 *
 * Проверка появилась по прямому вопросу владельца: «все ли материалы всех
 * шаблонов корректно правятся в графическом редакторе». Отдельного теста на
 * это не было ни одного, хотя ловушек здесь сразу три:
 *
 *   1. Список TEMPLATES в контроллере и правило `in:` в NewsRequest уже
 *      однажды разошлись — сохранение «Игр» и «Клиники» отбивалось с
 *      «Выбран недопустимый шаблон». Здесь это ловится на каждом шаблоне
 *      сразу.
 *   2. Форма правки одна на все шаблоны, но поля у них разные (оценка,
 *      цена, остаток). Пропавшее поле молча обнуляет значение при
 *      сохранении — материал открыли, ничего не трогали, нажали
 *      «Сохранить», и цена исчезла.
 *   3. Содержимое уходит в базу как есть (`content` — `nullable|string`,
 *      без чистки на сервере), поэтому испортить его может только
 *      редактор в браузере. Разметку, на которой держатся шаблоны
 *      (списки в услугах, картинки в товарах), проверяем отдельно.
 *
 * ⚠️ Круговой прогон через САМ редактор здесь не воспроизвести: он живёт в
 * браузере. Он проверен вживую отдельным стендом — 18 материалов девяти
 * шаблонов, поднялись все, разметка вернулась без потерь. Здесь закреплена
 * серверная половина, которая ломается тихо и в тестах видна.
 */
class NewsEditorRoundTripTest extends TestCase
{
    use RefreshDatabase;

    /** Разметка, на которой реально держатся шаблоны. */
    private const СОДЕРЖИМОЕ = '<p>Первый абзац с <strong>выделением</strong> и <em>наклоном</em>.</p>'
        . '<p><img src="/images/demo.svg" alt="Схема" style="width:100%;max-width:560px;height:auto"></p>'
        . '<ul><li>Первый пункт</li><li>Второй пункт</li><li>Третий пункт</li></ul>'
        . '<blockquote>Врезка</blockquote><p>Код: <code>php artisan view:clear</code></p>';

    private function админ(): \App\Models\User
    {
        $админ = \App\Models\User::factory()->create(['is_admin' => true]);

        return $админ;
    }

    public static function шаблоны(): array
    {
        return array_map(
            fn ($ключ) => [$ключ],
            array_keys(NewsController::TEMPLATES)
        );
    }

    /**
     * @dataProvider шаблоны
     */
    public function test_material_of_every_template_opens_and_saves_intact(string $шаблон): void
    {
        $this->actingAs($this->админ());

        $материал = News::create([
            'title'     => 'Материал шаблона ' . $шаблон,
            'slug'      => 'material-' . str_replace('_', '-', $шаблон),
            'content'   => self::СОДЕРЖИМОЕ,
            'template'  => $шаблон,
            'published' => true,
            // Поля, которые есть не у всех шаблонов: если форма их теряет,
            // сохранение без правок молча обнулит значение.
            'price'     => 1490,
            'rating'    => 8.5,
            'stock'     => 7,
        ]);

        // 1. Форма правки открывается и отдаёт содержимое в поле редактора.
        $форма = $this->get(route('admin.news.edit', $материал->id));
        $форма->assertOk();
        $форма->assertSee('Первый пункт', false);

        // Редактор действительно поднимается на этом поле: без разметки
        // компонента владелец увидел бы голую textarea.
        $форма->assertSee('RuEditor', false);

        // 2. Сохранение без единой правки — самый частый сценарий.
        $ответ = $this->put(route('admin.news.update', $материал->id), [
            'title'    => $материал->title,
            'slug'     => $материал->slug,
            'content'  => self::СОДЕРЖИМОЕ,
            'template' => $шаблон,
            'published' => 1,
            'price'    => 1490,
            'rating'   => 8.5,
            'stock'    => 7,
        ]);

        $ответ->assertSessionHasNoErrors();
        $ответ->assertRedirect();

        $материал->refresh();

        // 3. Содержимое дошло до базы БАЙТ В БАЙТ. Сервер его не чистит, и
        //    любое расхождение здесь означало бы, что чистка появилась.
        $this->assertSame(self::СОДЕРЖИМОЕ, $материал->content,
            "Шаблон {$шаблон}: содержимое изменилось при сохранении");

        // 4. Шаблон не сбросился (проверка `in:` пропустила его).
        $this->assertSame($шаблон, $материал->template);
    }

    /**
     * Поле цены есть в форме у КАЖДОГО шаблона, который её носит.
     *
     * ⚠️ Проверять одно лишь сохранение было недостаточно, и это выяснилось
     * прямо здесь: тест слал цену запросом и проходил, а в живой панели поля
     * у услуги просто не было — цена и остаток показывались одним блоком
     * «Товары». Владелец открывал услугу, сохранял, и цена уходила в NULL,
     * потому что форма её не отправляла.
     */
    public function test_price_field_is_present_for_every_template_that_carries_it(): void
    {
        $this->actingAs($this->админ());

        foreach (NewsController::PRICE_TEMPLATES as $шаблон) {
            $материал = News::create([
                'title'     => 'Цена ' . $шаблон,
                'slug'      => 'cena-' . $шаблон,
                'content'   => '<p>Текст</p>',
                'template'  => $шаблон,
                'published' => true,
                'price'     => 4200,
            ]);

            $форма = $this->get(route('admin.news.edit', $материал->id));
            $форма->assertOk();

            // Само поле и его текущее значение.
            $форма->assertSee('name="price"', false);
            $форма->assertSee('4200', false);

            // Блок цены отделён от товарного: иначе он показывался бы
            // услуге только вместе с остатком и распродажей.
            $форма->assertSee('id="price-fields"', false);
        }
    }

    /**
     * Смена шаблона на «безценовой» цену обнуляет — и это правильно.
     *
     * Иначе значение осталось бы висеть в базе и всплыло бы, когда материал
     * снова станет товаром или услугой. Тот же довод, что у оценки.
     */
    public function test_price_is_cleared_when_template_stops_carrying_it(): void
    {
        $this->actingAs($this->админ());

        $материал = News::create([
            'title' => 'Был товар', 'slug' => 'byl-tovar',
            'content' => '<p>Текст</p>', 'template' => 'products',
            'published' => true, 'price' => 999, 'stock' => 3,
        ]);

        $this->put(route('admin.news.update', $материал->id), [
            'title' => $материал->title, 'slug' => $материал->slug,
            'content' => '<p>Текст</p>', 'template' => 'faq', 'published' => 1,
            'price' => 999, 'stock' => 3,
        ])->assertSessionHasNoErrors();

        $материал->refresh();

        $this->assertNull($материал->price);
        $this->assertNull($материал->stock);
    }

    /**
     * Разметка, на которой держатся новые шаблоны, переживает сохранение.
     *
     * «Наши услуги» показывают первые три <li> как состав работ, «Товары» и
     * «Игры» — картинку из текста. Пропади список или тег img, карточка
     * обеднеет молча: страница по-прежнему откроется, просто станет пустее.
     */
    public function test_markup_the_templates_depend_on_survives(): void
    {
        $this->actingAs($this->админ());

        $материал = News::create([
            'title'     => '🚀 Услуга',
            'slug'      => 'usluga-proverka',
            'content'   => self::СОДЕРЖИМОЕ,
            'template'  => 'ourworks',
            'published' => true,
            'price'     => 9000,
        ]);

        $this->put(route('admin.news.update', $материал->id), [
            'title'    => $материал->title,
            'slug'     => $материал->slug,
            'content'  => self::СОДЕРЖИМОЕ,
            'template' => 'ourworks',
            'published' => 1,
            'price'    => 9000,
        ])->assertSessionHasNoErrors();

        $материал->refresh();

        $this->assertSame(3, substr_count($материал->content, '<li>'),
            'Список пропал — в карточке услуги исчезнет состав работ');
        $this->assertStringContainsString('<img ', $материал->content,
            'Картинка пропала — карточки товаров и игр останутся без обложки');
        $this->assertStringContainsString('style="width:100%', $материал->content,
            'Стиль картинки снят — она развернётся на всю ширину текста');
        $this->assertSame('9000.00', (string) $материал->price);
    }
}
