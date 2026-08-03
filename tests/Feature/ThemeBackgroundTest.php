<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Visual\Models\Theme;
use Tests\TestCase;

/**
 * Фоновая картинка в темах: общая на весь сайт и ограничение размера.
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

    public function test_background_applies_to_every_theme(): void
    {
        Storage::fake('public');

        $edited = $this->makeTheme('indigo');
        $other = $this->makeTheme('mint');
        $third = $this->makeTheme('graphite');

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

        // Фон один на сайт: раньше он оставался только в той теме, которую
        // редактировали, и при переключении оформления картинка пропадала.
        $this->assertSame($url, data_get($other->fresh()->config, 'background_url'));
        $this->assertSame($url, data_get($third->fresh()->config, 'background_url'));
    }

    public function test_removing_background_clears_it_everywhere(): void
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
        $this->assertNull(data_get($other->fresh()->config, 'background_url'));
    }

    public function test_legacy_background_keys_are_cleared_on_other_themes(): void
    {
        Storage::fake('public');

        $edited = $this->makeTheme('indigo');
        $other = $this->makeTheme('mint');

        // bg_url и pattern_url — прежние имена того же поля. Лейаут читает их
        // по очереди, поэтому старое значение перебило бы новое.
        $other->config = ['bg_url' => '/storage/old.png', 'pattern_url' => '/storage/older.png'];
        $other->save();

        $this->actingAs($this->admin())->put(
            route('admin.visual.themes.update', $edited),
            [
                'slug' => $edited->slug,
                'title' => $edited->title,
                'bg_image' => UploadedFile::fake()->image('pattern.png', 200, 200),
            ]
        )->assertSessionHasNoErrors();

        $config = $other->fresh()->config;

        $this->assertArrayNotHasKey('bg_url', $config);
        $this->assertArrayNotHasKey('pattern_url', $config);
        $this->assertNotNull(data_get($config, 'background_url'));
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
}
