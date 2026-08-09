<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Visual\Models\Theme;
use Tests\TestCase;

/**
 * Фоновая картинка темы: принадлежит ЕЙ ОДНОЙ и ограничена по размеру.
 *
 * Прежде фон намеренно копировался во все темы разом — так чинилась жалоба
 * «картинка пропадает при переключении оформления». Появлялась она оттого,
 * что фон был всего у одной темы. Теперь свой узор есть у каждой из коробки,
 * и владелец попросил обратного: чтобы темы отличались и картинкой тоже, а
 * чужую можно было забрать осознанно — скачать в форме и загрузить себе.
 *
 * Тесты переписаны вместе с этой переменой, а не подогнаны под новый ответ:
 * раньше они закрепляли ровно то поведение, от которого отказались.
 */
class ThemeBackgroundTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function makeTheme(string $slug): Theme
    {
        return Theme::create([
            'slug' => $slug,
            'title' => ucfirst($slug),
            'tokens' => ['colors' => ['primary' => '#6366f1']],
            'config' => [],
        ]);
    }

    public function test_background_belongs_to_the_edited_theme_only(): void
    {
        Storage::fake('public');

        $edited = $this->makeTheme('indigo');
        $other = $this->makeTheme('mint');
        $third = $this->makeTheme('graphite');

        $other->config = ['background_url' => '/storage/themes/2/mint.png'];
        $other->save();

        $this->actingAs($this->admin())->put(
            route('admin.visual.themes.update', $edited),
            [
                'slug' => $edited->slug,
                'title' => $edited->title,
                'bg_image' => UploadedFile::fake()->image('pattern.png', 200, 200),
            ]
        )->assertSessionHasNoErrors();

        $url = data_get($edited->fresh()->config, 'background_url');

        $this->assertNotNull($url, 'Фон не сохранился у редактируемой темы');

        $this->assertSame(
            '/storage/themes/2/mint.png',
            data_get($other->fresh()->config, 'background_url'),
            'Чужой фон трогать нельзя: темы должны отличаться картинкой.'
        );
        $this->assertNull(
            data_get($third->fresh()->config, 'background_url'),
            'Теме без фона он не должен появиться сам собой.'
        );
    }

    public function test_removing_background_touches_only_its_own_theme(): void
    {
        Storage::fake('public');

        $edited = $this->makeTheme('indigo');
        $other = $this->makeTheme('mint');

        foreach ([$edited, $other] as $theme) {
            $theme->config = ['background_url' => '/storage/themes/1/old.png'];
            $theme->save();
        }

        $this->actingAs($this->admin())->put(
            route('admin.visual.themes.update', $edited),
            ['slug' => $edited->slug, 'title' => $edited->title, 'remove_bg' => '1']
        )->assertSessionHasNoErrors();

        $this->assertNull(data_get($edited->fresh()->config, 'background_url'));

        $this->assertSame(
            '/storage/themes/1/old.png',
            data_get($other->fresh()->config, 'background_url'),
            'Снятие фона у одной темы не должно раздевать остальные.'
        );
    }

    public function test_legacy_background_keys_are_cleared_on_removal(): void
    {
        Storage::fake('public');

        $edited = $this->makeTheme('indigo');

        // bg_url и pattern_url — прежние имена того же поля. Лейаут читает их
        // по очереди, поэтому старое значение перебило бы новое.
        $edited->config = [
            'background_url' => '/storage/new.png',
            'bg_url'         => '/storage/old.png',
            'pattern_url'    => '/storage/older.png',
        ];
        $edited->save();

        $this->actingAs($this->admin())->put(
            route('admin.visual.themes.update', $edited),
            ['slug' => $edited->slug, 'title' => $edited->title, 'remove_bg' => '1']
        )->assertSessionHasNoErrors();

        $config = $edited->fresh()->config;

        $this->assertArrayNotHasKey('bg_url', $config);
        $this->assertArrayNotHasKey('pattern_url', $config);
        $this->assertNull(data_get($config, 'background_url'));
    }

    public function test_upload_limit_follows_php_settings(): void
    {
        // Правило max: не должно обещать больше, чем принимает сам PHP:
        // файл сверх upload_max_filesize отбрасывается до валидации, и
        // остаётся только глухое «Не удалось загрузить файл».
        $limit = max_upload_kb(10240);

        $this->assertGreaterThan(0, $limit);
        $this->assertLessThanOrEqual(10240, $limit);
        $this->assertStringContainsString('Б', max_upload_label(10240));
    }

    /**
     * У каждой темы из коробки свой узор и свой знак.
     *
     * Раньше сидер выдавал всем один и тот же фон, а логотипа не давал вовсе —
     * темы отличались только цветами.
     */
    public function test_seeder_gives_every_theme_its_own_assets(): void
    {
        \Modules\Visual\Console\Commands\SeedDefaultThemesCommand::seed(true);

        $themes = Theme::whereIn('slug', ['indigo', 'scarlet', 'azure', 'graphite', 'magenta'])->get();

        $this->assertCount(5, $themes, 'Ожидались все пять базовых тем.');

        $backgrounds = [];
        $logos = [];

        foreach ($themes as $theme) {
            $bg = data_get($theme->config, 'background_url');

            $this->assertNotNull($bg, "У темы «{$theme->slug}» нет узора.");
            $backgrounds[] = $bg;

            if ($theme->slug !== 'indigo') {
                $logo = data_get($theme->config, 'logo_url');
                $this->assertNotNull($logo, "У темы «{$theme->slug}» нет знака.");
                $logos[] = $logo;
            }
        }

        $this->assertCount(5, array_unique($backgrounds), 'Узоры тем обязаны отличаться.');
        $this->assertCount(4, array_unique($logos), 'Знаки тем обязаны отличаться.');

        $indigo = Theme::where('slug', 'indigo')->first();

        // Индиго остаётся на прежней картинке: после обновления сайт должен
        // выглядеть ровно так же, как до него.
        $this->assertSame('/images/theme-default-bg.png', data_get($indigo->config, 'background_url'));

        // И БЕЗ картинки-знака: это тема по умолчанию после установки, в её
        // шапке остаётся текстовая марка «RU CMS». Задать logo_url — значит
        // подменить марку картинкой.
        $this->assertNull(
            data_get($indigo->config, 'logo_url'),
            'У темы по умолчанию знак остаётся текстовой маркой.'
        );
    }

    public function test_seeder_reset_drops_a_logo_it_no_longer_defines(): void
    {
        \Modules\Visual\Console\Commands\SeedDefaultThemesCommand::seed(true);

        $indigo = Theme::where('slug', 'indigo')->first();
        $indigo->config = array_merge($indigo->config ?? [], ['logo_url' => '/images/old-logo.svg']);
        $indigo->save();

        \Modules\Visual\Console\Commands\SeedDefaultThemesCommand::seed(true);

        // Сброс сливает конфиг ПОВЕРХ прежнего, поэтому «просто не задать
        // ключ» мало — старое значение пережило бы сброс. Отсутствие знака
        // объявлено явным null и ключ вычищается.
        $this->assertNull(
            data_get($indigo->fresh()->config, 'logo_url'),
            'Сброс обязан снять знак, которого сидер больше не задаёт.'
        );
    }

    public function test_theme_form_offers_assets_of_other_themes(): void
    {
        $edited = $this->makeTheme('indigo');

        $donor = $this->makeTheme('mint');
        $donor->config = [
            'background_url' => '/images/themes/backgrounds/mint.svg',
            'logo_url'       => '/images/themes/logos/mint.svg',
        ];
        $donor->save();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.visual.themes.edit', $edited))
            ->assertOk();

        $library = $response->viewData('assetLibrary');

        $this->assertCount(1, $library, 'В списке должна быть только чужая тема.');
        $this->assertSame('mint', $library->first()->slug);
        $this->assertSame('/images/themes/logos/mint.svg', $library->first()->logo);

        // Ссылки на скачивание действительно попадают в разметку.
        $response->assertSee('/images/themes/backgrounds/mint.svg', false);
        $response->assertSee('download', false);
    }
}
