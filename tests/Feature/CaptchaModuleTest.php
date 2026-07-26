<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AdminSections;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 🛡️ Модуль «Каптча» в панели.
 *
 * Вьюха modules/Captcha/Views/admin/index.blade.php лежала в проекте с самого
 * начала, но АДМИНСКОГО МАРШРУТА к ней не было вовсе: в Routes/web.php жили
 * только api/captcha/*. Страница была написана и недостижима — ни по ссылке,
 * ни по прямому адресу.
 *
 * Вдобавок примеры кода лежали в обычных <pre>, и Blade их ВЫПОЛНЯЛ: вызов
 * captcha_img() в «памятке» реально генерировал каптчу, а директива CSRF
 * подставляла скрытое поле вместо того, чтобы показать себя текстом.
 */
class CaptchaModuleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_page_opens(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.captcha.index'));

        $response->assertStatus(200);
        $response->assertSee('Каптча', false);
        $response->assertSee('Как встроить', false);
    }

    public function test_admin_page_is_closed_for_guests_and_plain_users(): void
    {
        $this->get(route('admin.captcha.index'))->assertRedirect();

        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user)->get(route('admin.captcha.index'))->assertStatus(403);
    }

    public function test_code_examples_are_shown_not_executed(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.captcha.index'))->getContent();

        // Примеры должны быть видны КАК ТЕКСТ — вместе с директивами Blade
        $this->assertStringContainsString('captcha_img(', $html);
        $this->assertStringContainsString('required|captcha:image', $html);
        $this->assertStringContainsString('@csrf', $html);

        // Внутри самих примеров не должно быть следов компиляции: искать
        // name="_token" по ВСЕЙ странице нельзя — свои формы с CSRF есть и
        // в шапке (выход из аккаунта)
        preg_match_all('~<pre class="cap-pre">(.*?)</pre>~s', $html, $matches);
        $this->assertNotEmpty($matches[1], 'Блоков с примерами кода на странице нет');

        foreach ($matches[1] as $example) {
            $this->assertStringNotContainsString('name="_token"', $example);
        }
    }

    public function test_page_reports_the_real_module_state(): void
    {
        config(['captcha.enabled' => true]);
        $this->actingAs($this->admin())->get(route('admin.captcha.index'))
            ->assertSee('Включена', false);

        config(['captcha.enabled' => false]);
        $this->actingAs($this->admin())->get(route('admin.captcha.index'))
            ->assertSee('Выключена', false)
            ->assertSee('CAPTCHA_ENABLED=false', false);
    }

    public function test_default_type_from_config_is_highlighted(): void
    {
        config(['captcha.default_type' => 'math']);

        $this->actingAs($this->admin())->get(route('admin.captcha.index'))
            ->assertSee('по умолчанию', false);
    }

    public function test_section_is_in_the_shared_list_and_in_navigation(): void
    {
        $labels = array_column(AdminSections::all(), 'label');
        $this->assertContains('Каптча', $labels);

        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertSee('href="' . route('admin.captcha.index') . '"', false);
    }

    public function test_section_is_findable_through_global_search(): void
    {
        $response = $this->actingAs($this->admin())
            ->getJson(route('admin.search.global', ['q' => 'спам']));

        // Ищется и по синониму, а не только по точному названию
        $found = collect($response->json('results'))->firstWhere('title', 'Каптча');

        $this->assertNotNull($found, 'Раздел «Каптча» не находится глобальным поиском');
        $this->assertSame('Раздел', $found['type']);
    }

    public function test_api_endpoint_still_works(): void
    {
        // Страница показывает живые примеры именно с этого эндпоинта
        $response = $this->actingAs($this->admin())
            ->getJson(route('api.captcha.generate', ['type' => 'math']));

        $response->assertStatus(200);
        $response->assertJsonStructure(['html']);
    }
}
