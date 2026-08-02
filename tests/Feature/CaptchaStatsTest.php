<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Captcha\Models\CaptchaPreset;
use Modules\Captcha\Models\CaptchaStat;
use Modules\Captcha\Services\CaptchaService;
use Tests\TestCase;

/**
 * Статистика сборок каптчи: показы, пройденные и проваленные попытки.
 *
 * Смысл счётчиков — увидеть, что каптча слишком сложная для живого
 * человека, поэтому проверяем не только сам факт записи, но и то, что
 * сбой статистики не ломает саму каптчу.
 */
class CaptchaStatsTest extends TestCase
{
    use RefreshDatabase;

    private function preset(array $attributes = []): CaptchaPreset
    {
        return CaptchaPreset::create(array_merge([
            'name' => 'Форма обратной связи',
            'type' => 'math',
            'options' => ['min' => 1, 'max' => 5, 'operations' => ['+']],
            'is_active' => true,
        ], $attributes));
    }

    public function test_render_through_a_preset_counts_a_view(): void
    {
        $preset = $this->preset();

        captcha_preset($preset->slug);

        $this->assertSame(1, CaptchaStat::where('preset_id', $preset->id)->sum('shown'));
    }

    public function test_passed_and_failed_attempts_are_counted_separately(): void
    {
        $preset = $this->preset();
        $service = app('captcha');

        // Проходим честно: код известен только серверу, достаём его из сессии
        captcha_preset($preset->slug);
        $instances = session('captcha.instances');
        $id = array_key_first($instances);
        $code = $instances[$id]['code'];

        $this->assertTrue($service->verifyInstance((string) $code, $id));

        captcha_preset($preset->slug);
        $instances = session('captcha.instances');
        $id = array_key_last($instances);

        $this->assertFalse($service->verifyInstance('заведомо неверный ответ', $id));

        $row = CaptchaStat::where('preset_id', $preset->id)->first();

        $this->assertSame(2, $row->shown);
        $this->assertSame(1, $row->passed);
        $this->assertSame(1, $row->failed);
    }

    public function test_captcha_without_a_preset_writes_nothing(): void
    {
        // Каптча из шаблона или старого хелпера не привязана к сборке —
        // приписывать её показы некому.
        app('captcha')->render('math');

        $this->assertSame(0, CaptchaStat::count());
    }

    public function test_totals_are_grouped_by_preset(): void
    {
        $first = $this->preset(['name' => 'Первая']);
        $second = $this->preset(['name' => 'Вторая']);

        CaptchaStat::bump($first->id, 'shown');
        CaptchaStat::bump($first->id, 'shown');
        CaptchaStat::bump($second->id, 'failed');

        $totals = CaptchaStat::totals();

        $this->assertSame(2, $totals[$first->id]['shown']);
        $this->assertSame(1, $totals[$second->id]['failed']);
        $this->assertSame(0, $totals[$second->id]['shown']);
    }

    public function test_stats_are_shown_on_the_constructor_page(): void
    {
        $preset = $this->preset();
        CaptchaStat::bump($preset->id, 'shown');
        CaptchaStat::bump($preset->id, 'passed');

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->withSession(['app_locale' => 'ru'])
            ->get(route('admin.captcha.index'))
            ->assertStatus(200)
            ->assertSee('cap-stats', false)
            ->assertSee('100%', false);
    }

    public function test_broken_stats_do_not_break_the_captcha(): void
    {
        $preset = $this->preset();

        // Таблицы нет — каптча обязана продолжать работать: счётчик
        // вспомогательный, из-за него форма не должна перестать отправляться.
        \Illuminate\Support\Facades\Schema::drop('captcha_stats');

        $html = captcha_preset($preset->slug);

        $this->assertNotSame('', (string) $html);
    }
}
