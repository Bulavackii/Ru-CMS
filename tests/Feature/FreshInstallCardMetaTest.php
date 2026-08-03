<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Даты и время чтения в карточках — после чистой установки.
 *
 * Всё это живёт в шаблонах и выборках контроллеров, то есть в коде, а не в
 * данных. Но выборки перечисляют колонки поимённо, и уже четырежды за
 * сессию новое поле пропадало именно из-за этого: rating, price, stock,
 * updated_at. Тест ловит такой промах на пустой базе.
 */
class FreshInstallCardMetaTest extends TestCase
{
    use RefreshDatabase;

    private function seedAndRender(string $url = '/'): string
    {
        User::factory()->create(['is_admin' => true]);

        $controller = app(\Modules\Install\Controllers\InstallController::class);

        // Настоящая установка зовёт оба сидера: страницы для главной
        // создаёт seedDefaultPages, материалы — installDemoData.
        foreach (['seedDefaultPages', 'installDemoData'] as $method) {
            $seed = new \ReflectionMethod($controller, $method);
            $seed->setAccessible(true);
            $seed->invoke($controller);
        }

        return $this->get($url)->assertOk()->getContent();
    }

    public function test_every_card_block_shows_a_date(): void
    {
        $html = $this->seedAndRender();

        $blocks = [
            'nw-meta__date' => 'Новости',
            'mag-meta__date' => 'Журнал',
            'gm-meta__date' => 'Игры',
            'pg-meta__date' => 'Полезное',
            'clinic-card__date' => 'Клиника',
        ];

        foreach ($blocks as $class => $name) {
            preg_match_all('~' . $class . '">([^<]*)~u', $html, $m);

            $this->assertNotEmpty($m[1], "В блоке «{$name}» нет ни одной даты");

            foreach ($m[1] as $value) {
                // Пустая дата — признак того, что колонка не попала в select().
                $this->assertMatchesRegularExpression(
                    '~^\s*\d{2}\.\d{2}\.\d{4}\s*$~',
                    $value,
                    "В блоке «{$name}» дата пустая или в неверном формате: «{$value}»"
                );
            }
        }
    }

    public function test_reading_time_always_has_a_number(): void
    {
        $html = $this->seedAndRender();

        // Ключ reading_time однажды был без плейсхолдера :min, и на сайте
        // выводилось «мин чтения» вообще без цифры.
        preg_match_all('~(nw|mag|gm)-meta__time">([^<]*)~u', $html, $m);

        $this->assertNotEmpty($m[2], 'Время чтения не выводится ни в одном блоке');

        foreach ($m[2] as $value) {
            $this->assertMatchesRegularExpression(
                '~\d+~',
                $value,
                "Время чтения без числа: «{$value}»"
            );
        }
    }

    public function test_reading_time_counts_cyrillic_words(): void
    {
        // str_word_count не понимает кириллицу и возвращал бы 1 минуту на
        // любом русском тексте — это в проекте уже чинили.
        $text = '<p>' . str_repeat('слово ', 450) . '</p>';

        $this->assertSame(3, reading_time($text), 'Расчёт по русским словам неверен');
        $this->assertSame(1, reading_time('<p>Короткий текст.</p>'));
    }

    public function test_news_page_also_shows_dates_and_reading_time(): void
    {
        $html = $this->seedAndRender('/news');

        $this->assertMatchesRegularExpression('~(nw|mag|gm)-meta__date~', $html);
        $this->assertMatchesRegularExpression('~(nw|mag|gm)-meta__time">[^<]*\d~u', $html);
    }
}
