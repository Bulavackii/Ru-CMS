<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Accessibility\Models\AccessibilitySetting;
use Tests\TestCase;

/**
 * Модуль «Спецвозможности»: сохранение настроек и вывод виджета на сайте.
 */
class AccessibilityModuleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_page_opens(): void
    {
        $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'ru'])
            ->get('/admin/accessibility')
            ->assertOk()
            ->assertViewHas('settings')
            ->assertViewHas('options');
    }

    public function test_module_can_be_enabled(): void
    {
        // Главный баг: чекбокс без атрибута value браузер отправляет строкой
        // "on", а правило 'boolean' её не принимает. Сохранение падало на
        // валидации всегда, стоило отметить хоть одну галочку, — включить
        // модуль было невозможно в принципе.
        $this->actingAs($this->admin())
            ->post('/admin/accessibility/update', [
                'enabled' => 'on',
                'enable_font_size' => 'on',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        Cache::forget('accessibility_settings');
        $settings = AccessibilitySetting::settings();

        $this->assertTrue((bool) $settings->enabled);
        $this->assertTrue((bool) $settings->enable_font_size);
    }

    public function test_unchecked_options_are_turned_off(): void
    {
        AccessibilitySetting::settings()->update(['enabled' => true, 'enable_speech' => true]);
        Cache::forget('accessibility_settings');

        $this->actingAs($this->admin())
            ->post('/admin/accessibility/update', ['enabled' => '1'])
            ->assertSessionHasNoErrors();

        Cache::forget('accessibility_settings');
        $settings = AccessibilitySetting::settings();

        $this->assertTrue((bool) $settings->enabled);
        $this->assertFalse((bool) $settings->enable_speech);
    }

    public function test_widget_appears_on_the_site_when_enabled(): void
    {
        AccessibilitySetting::settings()->update(['enabled' => true]);
        Cache::forget('accessibility_settings');

        // Настройки раздаются во вьюхи на буте провайдера, поэтому обновляем
        // общий слот вручную — в живом запросе это делает сам провайдер.
        $this->app['view']->share('accessibility', AccessibilitySetting::settings());

        $this->get('/')->assertOk()->assertSee('accessibilityWidget()', false);
    }

    public function test_widget_is_absent_when_disabled(): void
    {
        AccessibilitySetting::settings()->update(['enabled' => false]);
        Cache::forget('accessibility_settings');
        $this->app['view']->share('accessibility', AccessibilitySetting::settings());

        $this->get('/')->assertOk()->assertDontSee('accessibilityWidget()', false);
    }

    public function test_saving_clears_the_cache(): void
    {
        // Настройки живут в кеше час: без сброса изменение доехало бы до
        // сайта в лучшем случае через час.
        AccessibilitySetting::settings();
        $this->assertNotNull(Cache::get('accessibility_settings'));

        $this->actingAs($this->admin())->post('/admin/accessibility/update', ['enabled' => '1']);

        $this->assertNull(Cache::get('accessibility_settings'));
    }

    public function test_page_is_closed_for_guests(): void
    {
        $this->get('/admin/accessibility')->assertRedirect();
        $this->post('/admin/accessibility/update', ['enabled' => '1'])->assertRedirect();
    }
}
