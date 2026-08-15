<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\News;
use Tests\TestCase;

/**
 * «Показать на главной» у новостей — как у страниц.
 *
 * Раньше на главную попадали ВСЕ опубликованные материалы: убрать оттуда
 * один, оставив его в ленте новостей, было нечем. У страниц такой
 * переключатель есть с самого начала.
 *
 * ⚠️ Умолчание здесь ОБРАТНОЕ страничному — включено. Главная и есть витрина
 * новостей, там показаны все; выключенное умолчание означало бы, что при
 * первом же запуске главная опустела, а каждая новая новость нигде не
 * появляется, пока автор не догадается щёлкнуть ещё один тумблер.
 */
class NewsHomepageFlagTest extends TestCase
{
    use RefreshDatabase;

    private function админ(): \App\Models\User
    {
        return \App\Models\User::factory()->create(['is_admin' => true]);
    }

    /**
     * ⚠️ Дату ставим ОТДЕЛЬНО, а не через create().
     *
     * `created_at` нет в списке массово заполняемых полей, поэтому в массиве
     * создания она молча игнорируется. Первая версия теста на этом и
     * попалась: обе новости получали текущее время, «старая» оказывалась
     * новее по идентификатору и вставала первой сама — проверка закрепления
     * проходила и на заведомо сломанном коде.
     */
    private function материал(array $поля = []): News
    {
        $дата = $поля['created_at'] ?? null;
        unset($поля['created_at']);

        $материал = News::create(array_merge([
            'title' => 'Новость', 'slug' => 'novost-' . uniqid(),
            'content' => '<p>Текст</p>', 'template' => 'default', 'published' => true,
        ], $поля));

        if ($дата !== null) {
            $материал->created_at = $дата;
            $материал->save();
        }

        return $материал;
    }

    /** По умолчанию материал показан на главной — как было до появления поля. */
    public function test_new_material_is_shown_on_home_by_default(): void
    {
        $this->assertTrue($this->материал()->fresh()->show_on_homepage);
    }

    /** Снятый переключатель убирает материал с главной, но не из ленты. */
    public function test_unchecked_material_disappears_from_home_only(): void
    {
        $видимый = $this->материал(['title' => 'Виден везде', 'slug' => 'viden-vezde']);
        $скрытый = $this->материал([
            'title' => 'Только в ленте', 'slug' => 'tolko-v-lente',
            'show_on_homepage' => false,
        ]);

        $главная = $this->get('/');
        $главная->assertOk();
        $главная->assertSee('Виден везде', false);
        $главная->assertDontSee('Только в ленте', false);

        // В ленте новостей он остаётся: флаг про главную, а не про публикацию.
        $лента = $this->get('/news');
        $лента->assertOk();
        $лента->assertSee('Только в ленте', false);

        // И по своему адресу открывается.
        $this->get('/news/' . $скрытый->slug)->assertOk();
        $this->assertNotNull($видимый->id);
    }

    /**
     * Форма правки отдаёт переключатель и поле порядка.
     *
     * ⚠️ Проверять одно лишь сохранение недостаточно — на этом уже обжигались
     * с ценой у услуг: тест слал значение запросом и проходил, а поля в
     * панели не было, и в жизни оно уходило пустым.
     */
    public function test_form_has_the_toggle_and_the_order_field(): void
    {
        $this->actingAs($this->админ());
        $материал = $this->материал(['slug' => 'dlya-formy', 'homepage_order' => 3]);

        foreach ([route('admin.news.edit', $материал->id), route('admin.news.create')] as $адрес) {
            $форма = $this->get($адрес);
            $форма->assertOk();
            $форма->assertSee('name="show_on_homepage"', false);
            $форма->assertSee('name="homepage_order"', false);
        }

        $this->get(route('admin.news.edit', $материал->id))->assertSee('value="3"', false);
    }

    /** Сохранение из панели переносит оба поля в базу. */
    public function test_saving_stores_both_fields(): void
    {
        $this->actingAs($this->админ());
        $материал = $this->материал(['slug' => 'sohranenie']);

        $this->put(route('admin.news.update', $материал->id), [
            'title' => $материал->title, 'slug' => $материал->slug,
            'content' => '<p>Текст</p>', 'template' => 'default', 'published' => 1,
            'homepage_order' => 7,
            // show_on_homepage НЕ шлём — снятый переключатель не приходит вовсе
        ])->assertSessionHasNoErrors();

        $материал->refresh();
        $this->assertFalse($материал->show_on_homepage, 'Снятый переключатель не сохранился');
        $this->assertSame(7, $материал->homepage_order);

        $this->put(route('admin.news.update', $материал->id), [
            'title' => $материал->title, 'slug' => $материал->slug,
            'content' => '<p>Текст</p>', 'template' => 'default', 'published' => 1,
            'show_on_homepage' => 1, 'homepage_order' => '',
        ])->assertSessionHasNoErrors();

        $материал->refresh();
        $this->assertTrue($материал->show_on_homepage);
        $this->assertNull($материал->homepage_order, 'Пустой порядок должен стать NULL, а не нулём');
    }

    /**
     * Закреплённый материал идёт первым, даже если он старше.
     *
     * ⚠️ Пустой порядок обязан уходить в КОНЕЦ. На PostgreSQL NULL при
     * сортировке по убыванию всплывает наверх, и закрепление работало бы
     * наоборот — незакреплённые материалы оказались бы выше закреплённых.
     */
    public function test_pinned_material_goes_first(): void
    {
        $this->материал(['title' => 'Свежая', 'slug' => 'svezhaya',
            'created_at' => now()]);
        $this->материал(['title' => 'Старая закреплённая', 'slug' => 'zakreplennaya',
            'created_at' => now()->subYear(), 'homepage_order' => 1]);

        $ответ = $this->get('/');
        $ответ->assertOk();

        $html = $ответ->getContent();
        $свежая = strpos($html, 'Свежая');
        $закреплённая = strpos($html, 'Старая закреплённая');

        // ⚠️ Сначала убеждаемся, что ОБА материала на странице. Первая версия
        // теста сравнивала позиции сразу — и проходила на заведомо сломанном
        // коде: strpos не нашёл один из заголовков и вернул false, а false в
        // сравнении становится нулём, то есть «самой первой позицией».
        $this->assertNotFalse($свежая, 'Свежего материала нет на главной');
        $this->assertNotFalse($закреплённая, 'Закреплённого материала нет на главной');

        $this->assertLessThan($свежая, $закреплённая, 'Закреплённый материал не поднялся наверх');
    }

    /** Ноль — законный порядок («самый верх»), а не «пусто». */
    public function test_zero_order_is_a_real_value(): void
    {
        $this->actingAs($this->админ());
        $материал = $this->материал(['slug' => 'nol']);

        $this->put(route('admin.news.update', $материал->id), [
            'title' => $материал->title, 'slug' => $материал->slug,
            'content' => '<p>Текст</p>', 'template' => 'default', 'published' => 1,
            'show_on_homepage' => 1, 'homepage_order' => 0,
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, $материал->fresh()->homepage_order);
    }
}
