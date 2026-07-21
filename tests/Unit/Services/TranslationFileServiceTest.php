<?php

namespace Tests\Unit\Services;

use Modules\Localization\Services\TranslationFileService;
use RuntimeException;
use Tests\TestCase;

/**
 * Тесты редактора переводов.
 *
 * Работают на временном каталоге языков (useLangPath), а не на реальном
 * resources/lang — тест не должен править словари проекта.
 */
class TranslationFileServiceTest extends TestCase
{
    private string $langPath;
    private TranslationFileService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->langPath = sys_get_temp_dir() . '/rucms-lang-' . uniqid();
        mkdir($this->langPath . '/ru', 0775, true);
        mkdir($this->langPath . '/en', 0775, true);

        // Эталон: вложенность + список + значение, совпадающее с переводом
        file_put_contents($this->langPath . '/ru/demo.php', <<<'PHP'
<?php

/* Комментарий-шапка, который редактор обязан сохранить */

return [
    'title' => 'Заголовок',
    'nested' => [
        'deep' => [
            'key' => 'Глубокое значение',
        ],
    ],
    'levels' => ['ноль', 'один', 'два'],
    'email' => 'Email',
];
PHP);

        // Перевод: одного ключа нет, один совпадает с эталоном
        file_put_contents($this->langPath . '/en/demo.php', <<<'PHP'
<?php

return [
    'title' => 'Title',
    'nested' => [
        'deep' => [
            'key' => 'Deep value',
        ],
    ],
    'levels' => ['zero', 'one', 'two'],
];
PHP);

        $this->app->useLangPath($this->langPath);

        $this->service = new TranslationFileService();
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->langPath);

        parent::tearDown();
    }

    private function deleteTree(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            is_dir($path) ? $this->deleteTree($path) : @unlink($path);
        }

        @rmdir($dir);
    }

    public function test_it_lists_locales_and_groups(): void
    {
        $this->assertSame(['en', 'ru'], $this->service->locales());
        $this->assertSame(['demo'], $this->service->groups());
    }

    public function test_it_flattens_nested_keys_and_lists(): void
    {
        $flat = $this->service->loadFlat('ru', 'demo');

        $this->assertSame('Заголовок', $flat['title']);
        $this->assertSame('Глубокое значение', $flat['nested.deep.key']);
        $this->assertSame('ноль', $flat['levels.0']);
        $this->assertSame('два', $flat['levels.2']);
    }

    public function test_it_saves_and_restores_structure(): void
    {
        $flat = $this->service->loadFlat('en', 'demo');
        $flat['title'] = 'New title';
        $flat['nested.deep.key'] = 'New deep';

        $this->service->save('en', 'demo', $flat);

        $nested = $this->service->load('en', 'demo');

        $this->assertSame('New title', $nested['title']);
        $this->assertSame('New deep', $nested['nested']['deep']['key']);

        // Числовые ключи должны снова стать списком, а не картой
        $this->assertTrue(array_is_list($nested['levels']));
        $this->assertSame(['zero', 'one', 'two'], $nested['levels']);
    }

    public function test_it_escapes_quotes_and_backslashes(): void
    {
        $tricky = "Значение с ' кавычкой, \\ слешем и :placeholder";

        $flat = $this->service->loadFlat('en', 'demo');
        $flat['title'] = $tricky;
        $this->service->save('en', 'demo', $flat);

        $this->assertSame($tricky, $this->service->loadFlat('en', 'demo')['title']);
    }

    public function test_it_preserves_the_file_header_comment(): void
    {
        $flat = $this->service->loadFlat('ru', 'demo');
        $flat['title'] = 'Другой заголовок';
        $this->service->save('ru', 'demo', $flat);

        $raw = file_get_contents($this->langPath . '/ru/demo.php');

        $this->assertStringStartsWith('<?php', $raw);
        $this->assertStringContainsString('Комментарий-шапка', $raw);
    }

    public function test_it_drops_keys_absent_from_the_payload(): void
    {
        $flat = $this->service->loadFlat('en', 'demo');
        unset($flat['nested.deep.key']);

        $this->service->save('en', 'demo', $flat);

        $this->assertArrayNotHasKey('nested.deep.key', $this->service->loadFlat('en', 'demo'));
    }

    public function test_stats_count_missing_and_identical_values(): void
    {
        $stats = $this->service->stats('en');

        // Эталон: title, nested.deep.key, levels.0..2, email = 6 ключей
        $this->assertSame(6, $stats['total']);
        // email в переводе отсутствует
        $this->assertSame(1, $stats['missing']);
        $this->assertSame(5, $stats['translated']);
    }

    public function test_stats_treat_reference_locale_as_complete(): void
    {
        $stats = $this->service->stats('ru');

        $this->assertTrue($stats['reference']);
        $this->assertSame(100, $stats['percent']);
        $this->assertSame(0, $stats['missing']);
    }

    public function test_identical_translation_counts_as_untranslated(): void
    {
        $flat = $this->service->loadFlat('en', 'demo');
        $flat['email'] = 'Email'; // дословно как в эталоне
        $this->service->save('en', 'demo', $flat);

        $stats = $this->service->stats('en');

        $this->assertSame(0, $stats['missing']);
        $this->assertSame(1, $stats['same']);
    }

    public function test_it_creates_a_new_locale_by_copying(): void
    {
        $this->service->createLocale('kk', 'ru');

        $this->assertContains('kk', $this->service->locales());
        $this->assertSame(
            $this->service->loadFlat('ru', 'demo'),
            $this->service->loadFlat('kk', 'demo')
        );
    }

    public function test_it_refuses_to_create_an_existing_locale(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->createLocale('en', 'ru');
    }

    public function test_it_deletes_a_locale(): void
    {
        $this->service->createLocale('pl', 'ru');
        $this->service->deleteLocale('pl');

        $this->assertNotContains('pl', $this->service->locales());
    }

    public function test_it_protects_reference_and_fallback_locales(): void
    {
        // ru — эталон, en — fallback_locale приложения
        foreach (['ru', 'en'] as $protected) {
            try {
                $this->service->deleteLocale($protected);
                $this->fail("Локаль «{$protected}» не должна удаляться.");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('защищена', $e->getMessage());
            }
        }

        $this->assertSame(['en', 'ru'], $this->service->locales());
    }

    public function test_it_rejects_path_traversal_in_locale_and_group(): void
    {
        $this->assertFalse($this->service->isValidLocale('../../etc'));
        $this->assertFalse($this->service->isValidLocale('ru/../..'));
        $this->assertFalse($this->service->isValidGroup('../secrets'));
        $this->assertFalse($this->service->isValidGroup('a/b'));

        $this->expectException(RuntimeException::class);
        $this->service->load('../../etc', 'passwd');
    }

    public function test_it_accepts_regional_locale_codes(): void
    {
        $this->assertTrue($this->service->isValidLocale('pt_BR'));
        $this->assertTrue($this->service->isValidLocale('zh-Hans'));
        $this->assertTrue($this->service->isValidLocale('be'));
    }
}
