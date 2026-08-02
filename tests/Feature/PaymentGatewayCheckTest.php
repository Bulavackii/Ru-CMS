<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Payments\Console\Commands\SeedDefaultPaymentMethodsCommand;
use Modules\Payments\Models\PaymentMethod;
use Tests\TestCase;

/**
 * Кнопка «Проверить настройки» у способа оплаты.
 *
 * Смысл проверки — сказать владельцу правду о реквизитах до первого
 * заказа, поэтому тесты следят и за тем, чтобы заглушка не выдавалась
 * за рабочую интеграцию.
 */
class PaymentGatewayCheckTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function method(string $code): PaymentMethod
    {
        SeedDefaultPaymentMethodsCommand::seed();

        return PaymentMethod::where('code', $code)->firstOrFail();
    }

    public function test_offline_method_needs_no_check(): void
    {
        $response = $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'ru'])
            ->post(route('admin.payments.check', $this->method('cash')->id));

        $response->assertStatus(200);
        $this->assertTrue($response->json('ok'));
    }

    public function test_empty_credentials_are_reported_before_any_request(): void
    {
        // Ни одного HTTP-запроса быть не должно: проверять нечего.
        Http::fake();

        $response = $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'ru'])
            ->post(route('admin.payments.check', $this->method('yookassa')->id));

        $response->assertStatus(422);
        $this->assertFalse($response->json('ok'));
        Http::assertNothingSent();
    }

    public function test_yookassa_reports_success_on_a_valid_response(): void
    {
        Http::fake(['api.yookassa.ru/*' => Http::response(['items' => []], 200)]);

        $method = $this->method('yookassa');
        $method->update(['settings' => ['shop_id' => '123', 'secret_key' => 'test_key']]);

        $response = $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'ru'])
            ->post(route('admin.payments.check', $method->id));

        $response->assertStatus(200);
        $this->assertTrue($response->json('ok'));
    }

    public function test_yookassa_reports_a_rejected_key(): void
    {
        Http::fake(['api.yookassa.ru/*' => Http::response(['type' => 'error'], 401)]);

        $method = $this->method('yookassa');
        $method->update(['settings' => ['shop_id' => '123', 'secret_key' => 'неверный']]);

        $response = $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'ru'])
            ->post(route('admin.payments.check', $method->id));

        $response->assertStatus(422);
        $this->assertFalse($response->json('ok'));
        $this->assertStringContainsString('401', $response->json('message'));
    }

    public function test_missing_driver_says_so_instead_of_pretending(): void
    {
        // У СБП драйвера ещё нет. Ответ обязан честно об этом сказать,
        // а не отрапортовать успех.
        $method = $this->method('sbp');
        $method->update(['settings' => ['merchant_id' => '1', 'account' => '2']]);

        $response = $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'ru'])
            ->post(route('admin.payments.check', $method->id));

        $response->assertStatus(422);
        $this->assertFalse($response->json('ok'));
        $this->assertStringContainsString('драйвер', mb_strtolower((string) $response->json('message')));
    }

    public function test_check_is_closed_for_non_admins(): void
    {
        $method = $this->method('cash');

        $this->post(route('admin.payments.check', $method->id))->assertRedirect();

        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->post(route('admin.payments.check', $method->id))
            ->assertForbidden();
    }

    public function test_credential_fields_cover_every_seeded_method(): void
    {
        $fields = SeedDefaultPaymentMethodsCommand::credentialFields();

        foreach (SeedDefaultPaymentMethodsCommand::definitions() as $definition) {
            $this->assertArrayHasKey($definition['code'], $fields);
        }
    }
}
