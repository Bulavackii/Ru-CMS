<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SecurityService;
use App\Support\QrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 🔐 Страница привязки двухфакторной проверки.
 *
 * Проверяем ровно то, что было сломано: на странице стояла картинка, в
 * адрес которой подставлялась строка otpauth://… — то есть СОДЕРЖИМОЕ кода
 * вместо изображения. Браузер показывал пустую рамку, сканировать было
 * нечего, и привязать приложение можно было только ручным вводом ключа.
 */
class TwoFactorSetupTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function на_странице_нарисован_код_а_не_подставлена_строка(): void
    {
        $user = User::factory()->create(['two_factor_secret' => null]);

        $response = $this->actingAs($user)->get(route('two-factor.setup'));

        $response->assertOk();
        $response->assertSee('<svg', false);
        $response->assertDontSee('src="otpauth', false);
    }

    /**
     * Привязка была доступна ТОЛЬКО из настроек в админке: обычный
     * покупатель включить её не мог никак, хотя страница работает для
     * любого вошедшего.
     */
    #[Test]
    public function из_кабинета_на_сайте_есть_вход_в_привязку(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(route('two-factor.setup'), false);
    }

    #[Test]
    public function нарисован_код_именно_того_ключа_что_показан_рядом(): void
    {
        $user = User::factory()->create(['two_factor_secret' => null]);

        $response = $this->actingAs($user)->get(route('two-factor.setup'));

        $secret = session('2fa_temp_secret');
        $this->assertNotEmpty($secret);

        // Ключ показан для ручного ввода…
        $response->assertSee($secret, false);

        // …и та же строка привязки нарисована картинкой. Собираем её тем же
        // способом и сверяем целиком: расхождение означало бы, что телефон
        // получит коды от другого ключа и вход подтвердить не удастся.
        $expected = QrCode::svg(
            app(SecurityService::class)->getQRCodeUrl($user->email, $secret, config('app.name')),
            5
        );

        $response->assertSee($expected, false);
    }
}
