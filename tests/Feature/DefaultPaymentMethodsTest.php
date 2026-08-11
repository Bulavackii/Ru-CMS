<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payments\Console\Commands\SeedDefaultPaymentMethodsCommand;
use Modules\Payments\Models\PaymentMethod;
use Tests\TestCase;

/**
 * Типовые для РФ способы оплаты, создаваемые при установке.
 *
 * Главное, что здесь проверяется, — не «методы появились», а что они
 * появились БЕЗОПАСНО: выключенными, без ключей, и что повторный запуск
 * не затирает то, что владелец успел настроить.
 */
class DefaultPaymentMethodsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_creates_the_expected_methods(): void
    {
        SeedDefaultPaymentMethodsCommand::seed();

        $codes = PaymentMethod::orderBy('sort_order')->pluck('code')->all();

        $this->assertSame(
            ['yookassa', 'sbp', 'sberpay', 'tbank', 'cash', 'bank_transfer'],
            $codes
        );
    }

    public function test_only_key_free_methods_are_enabled(): void
    {
        // Включённая онлайн-система без ключей показалась бы покупателю в
        // корзине и не смогла бы принять платёж. Наличные и перевод ключей
        // не требуют, и без них корзина на свежем сайте нерабочая.
        SeedDefaultPaymentMethodsCommand::seed();

        $this->assertSame(
            SeedDefaultPaymentMethodsCommand::ENABLED_BY_DEFAULT,
            PaymentMethod::where('active', true)->orderBy('sort_order')->pluck('code')->all()
        );

        // Ни одна система, которой нужны реквизиты, не включена.
        $needKeys = array_keys(array_filter(SeedDefaultPaymentMethodsCommand::credentialFields()));

        $this->assertSame(
            [],
            PaymentMethod::where('active', true)->whereIn('type', $needKeys)->pluck('code')->all()
        );
    }

    public function test_no_credentials_are_shipped_in_the_seed(): void
    {
        SeedDefaultPaymentMethodsCommand::seed();

        foreach (PaymentMethod::all() as $method) {
            foreach ((array) $method->settings as $key => $value) {
                $this->assertSame('', $value, "У метода {$method->code} заполнен ключ {$key}");
            }
        }
    }

    public function test_seeding_is_idempotent(): void
    {
        SeedDefaultPaymentMethodsCommand::seed();
        $before = PaymentMethod::count();

        SeedDefaultPaymentMethodsCommand::seed();

        $this->assertSame($before, PaymentMethod::count());
    }

    public function test_repeat_run_does_not_overwrite_owner_settings(): void
    {
        SeedDefaultPaymentMethodsCommand::seed();

        $method = PaymentMethod::where('code', 'yookassa')->first();
        $method->update(['active' => true, 'settings' => ['shop_id' => '123', 'secret_key' => 'секрет']]);

        SeedDefaultPaymentMethodsCommand::seed();

        $method->refresh();

        $this->assertTrue($method->active);
        $this->assertSame('123', $method->settings['shop_id']);
    }

    public function test_reset_rewrites_the_method(): void
    {
        SeedDefaultPaymentMethodsCommand::seed();
        PaymentMethod::where('code', 'yookassa')->update(['active' => true]);

        SeedDefaultPaymentMethodsCommand::seed(true);

        $this->assertFalse((bool) PaymentMethod::where('code', 'yookassa')->value('active'));
    }

    public function test_every_online_method_points_to_its_documentation(): void
    {
        // Метод создаётся без ключей — владельцу надо показать, где их берут.
        SeedDefaultPaymentMethodsCommand::seed();

        foreach (PaymentMethod::where('type', '!=', 'offline')->get() as $method) {
            $this->assertNotEmpty($method->docs_url, "У метода {$method->code} нет ссылки на документацию");
            $this->assertStringStartsWith('https://', $method->docs_url);
        }
    }

    public function test_the_artisan_command_works(): void
    {
        // В тестах таблица modules пуста, поэтому провайдер модуля Payments
        // не грузится и его команда не зарегистрирована. На боевой базе, где
        // модуль активен, она есть — регистрируем её здесь явно, чтобы
        // проверять саму команду, а не порядок загрузки модулей.
        $this->app->register(\Modules\Payments\PaymentsServiceProvider::class);

        $this->artisan('payments:seed-default')->assertExitCode(0);

        $this->assertSame(6, PaymentMethod::count());
    }
}
