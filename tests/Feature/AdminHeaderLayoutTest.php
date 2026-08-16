<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Menu\Models\Page;
use Modules\News\Models\News;
use Tests\TestCase;

/**
 * Шапка раздела в два ряда (класс `mh`).
 *
 * Проверяется не внешний вид, а УСТРОЙСТВО: ровно два ряда и ничего
 * постороннего внутри. Тест написан по живой поломке — при переводе списка
 * SEO на общую шапку я не закрыл добавленный ряд, и шапка «проглотила» всю
 * страницу вместе с таблицей. Разметка при этом рендерилась без единой
 * ошибки, страница выглядела почти нормально, а нашлось это только замером
 * геометрии в браузере.
 *
 * ⚠️ Разбор через DOMDocument, а не грепом по строке: непарный `div`
 * подсчётом подстрок не ловится — счёт сходится, вложенность нет.
 */
class AdminHeaderLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function админ(): \App\Models\User
    {
        return \App\Models\User::factory()->create(['is_admin' => true]);
    }

    /**
     * Вырезает разметку шапки — от её открывающего тега до парного закрытия.
     *
     * ⚠️ Считаем теги по сырой разметке, а НЕ разбираем документ.
     * `DOMDocument::loadHTML` чинит непарные теги по своим правилам: он
     * закрывает забытый `div` там, где закрывается родитель, и структура
     * получается верной. Браузер по правилам HTML5 поступает иначе — всё
     * следующее становится потомком шапки. Первая версия теста разбирала
     * документ и проходила на заведомо сломанной вьюхе.
     */
    private function шапка(string $html, string $адрес): string
    {
        $начало = strpos($html, 'class="admin-glass mh ');
        $this->assertNotFalse($начало, "Шапки с классом mh нет: $адрес");

        $начало = strrpos(substr($html, 0, $начало), '<div');
        $глубина = 0;
        $позиция = $начало;

        while (preg_match('~</?div\b~', $html, $m, PREG_OFFSET_CAPTURE, $позиция)) {
            $позиция = $m[0][1] + strlen($m[0][0]);
            $глубина += $m[0][0] === '<div' ? 1 : -1;

            if ($глубина === 0) {
                return substr($html, $начало, $позиция - $начало);
            }
        }

        $this->fail("Шапка не закрыта до конца страницы: $адрес");
    }

    /**
     * У шапки ровно два ряда, и таблица страницы в них не попала.
     *
     * Незакрытый ряд делает шапку родителем всего, что идёт ниже: список,
     * постраничный вывод и подвал оказываются её потомками. Тогда любая
     * широкая таблица выталкивает шапку за край экрана — так и появилась
     * горизонтальная прокрутка, которую заметил владелец.
     */
    public function test_section_header_has_exactly_two_rows(): void
    {
        $this->actingAs($this->админ());

        $материал = News::create([
            'title' => 'Материал для шапки', 'slug' => 'material-dlya-shapki',
            'content' => '<p>Текст</p>', 'template' => 'default', 'published' => true,
        ]);

        $страница = Page::create([
            'title' => 'Страница для шапки', 'slug' => 'stranica-dlya-shapki',
            'content' => '<p>Текст</p>', 'published' => true,
        ]);

        $адреса = [
            route('admin.news.edit', $материал->id),
            route('admin.news.create'),
            route('admin.pages.edit', $страница->id),
            route('admin.pages.create'),
        ];

        foreach ($адреса as $адрес) {
            $ответ = $this->get($адрес);
            $ответ->assertOk();

            $шапка = $this->шапка($ответ->getContent(), $адрес);

            $this->assertSame(
                2,
                substr_count($шапка, 'class="mh-row'),
                "Рядов в шапке не два: $адрес"
            );

            // Таблица, форма материала или подвал внутри шапки означают
            // ровно одно — ряд не закрыт, и шапка поглотила страницу.
            foreach (['<table', '<footer', '<form method="POST" action'] as $чужое) {
                $this->assertStringNotContainsString(
                    $чужое,
                    $шапка,
                    // ⚠️ Скобки обязательны: PHP разрешает многобайтовые
                    // символы в именах, и «$чужое»» без них разбирается как
                    // переменная с кавычкой-ёлочкой в имени.
                    "Внутрь шапки попало «{$чужое}» — где-то не закрыт ряд: {$адрес}"
                );
            }
        }
    }

    /**
     * Состояние помечено плашкой, у которой подпись отделима.
     *
     * Ниже 520 пикселей подпись скрывается общим правилом, и остаётся один
     * значок — зелёный или серый. Без классов `st-chip`/`st-text` правило
     * не за что зацепиться, и слово «Опубликовано» снова займёт четверть
     * ширины экрана.
     */
    public function test_status_chip_keeps_its_label_separable(): void
    {
        $this->actingAs($this->админ());

        $материал = News::create([
            'title' => 'Опубликованный', 'slug' => 'opublikovannyj',
            'content' => '<p>Текст</p>', 'template' => 'default', 'published' => true,
        ]);

        foreach ([route('admin.news.index'), route('admin.news.edit', $материал->id)] as $адрес) {
            $ответ = $this->get($адрес);
            $ответ->assertOk();

            $ответ->assertSee('st-chip', false);
            $ответ->assertSee('st-text', false);
        }
    }

    /**
     * В списке материалов остаётся колонка с названием и ссылкой на правку.
     *
     * Массовая пометка второстепенных колонок однажды задела и ячейку
     * названия — столбец с заголовками пропал целиком, а вместе с ним
     * единственный способ открыть материал. Поймал это владелец, а не тест.
     */
    public function test_news_list_keeps_the_title_column(): void
    {
        $this->actingAs($this->админ());

        $материал = News::create([
            'title' => 'Виден в списке', 'slug' => 'viden-v-spiske',
            'content' => '<p>Текст</p>', 'template' => 'default', 'published' => true,
        ]);

        $html = $this->get(route('admin.news.index'))->assertOk()->getContent();

        $док = new DOMDocument();
        @$док->loadHTML('<?xml encoding="utf-8"?>' . $html);
        $xpath = new DOMXPath($док);

        $ссылка = $xpath->query('//a[contains(@href, "/news/' . $материал->id . '/edit")]');
        $this->assertNotNull($ссылка);
        $this->assertGreaterThan(0, $ссылка->length, 'Ссылки на правку материала в списке нет');

        // Ячейка с названием не помечена как второстепенная: иначе на
        // телефоне она скроется вместе с ссылкой.
        for ($узел = $ссылка->item(0); $узел !== null; $узел = $узел->parentNode) {
            if ($узел instanceof \DOMElement && $узел->tagName === 'td') {
                $this->assertStringNotContainsString(
                    'col-extra',
                    $узел->getAttribute('class'),
                    'Ячейка названия помечена как второстепенная — на телефоне столбец пропадёт'
                );
                break;
            }
        }
    }
}
