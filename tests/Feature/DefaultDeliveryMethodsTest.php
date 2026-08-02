<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Delivery\Console\Commands\SeedDefaultDeliveryMethodsCommand;
use Modules\Delivery\Models\DeliveryMethod;
use Tests\TestCase;

/**
 * Типовые для РФ службы доставки, создаваемые при установке.
 *
 * Проверяется не столько «методы появились», сколько что они появились
 * безопасно: выключенными, без ключей API, и что повторный запуск не
 * затирает настроенное владельцем.
 */
class DefaultDeliveryMethodsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_creates_the_expected_methods(): void
    {
        SeedDefaultDeliveryMethodsCommand::seed();

        $this->assertSame(
            ['pochta', 'cdek', 'boxberry', 'yandex_delivery', 'pickup', 'courier_local'],
            DeliveryMethod::orderBy('sort_order')->pluck('code')->all()
        );
    }

    public function test_methods_are_created_disabled(): void
    {
        SeedDefaultDeliveryMethodsCommand::seed();

        $this->assertSame(0, DeliveryMethod::where('active', true)->count());
        $this->assertSame(0, DeliveryMethod::where('api_enabled', true)->count());
    }

    public function test_no_api_keys_are_shipped(): void
    {
        SeedDefaultDeliveryMethodsCommand::seed();

        foreach (DeliveryMethod::all() as $method) {
            foreach ((array) $method->api_settings as $key => $value) {
                $this->assertSame('', $value, "У службы {$method->code} заполнен ключ {$key}");
            }
        }
    }

    public function test_seeding_is_idempotent(): void
    {
        SeedDefaultDeliveryMethodsCommand::seed();
        $before = DeliveryMethod::count();

        SeedDefaultDeliveryMethodsCommand::seed();

        $this->assertSame($before, DeliveryMethod::count());
    }

    public function test_repeat_run_keeps_owner_settings(): void
    {
        SeedDefaultDeliveryMethodsCommand::seed();

        $method = DeliveryMethod::where('code', 'cdek')->first();
        $method->update([
            'active' => true,
            'api_enabled' => true,
            'api_settings' => ['account' => 'acc', 'secure_password' => 'pw'],
        ]);

        SeedDefaultDeliveryMethodsCommand::seed();
        $method->refresh();

        $this->assertTrue($method->active);
        $this->assertSame('acc', $method->api_settings['account']);
    }

    public function test_reset_rewrites_the_method(): void
    {
        SeedDefaultDeliveryMethodsCommand::seed();
        DeliveryMethod::where('code', 'cdek')->update(['active' => true]);

        SeedDefaultDeliveryMethodsCommand::seed(true);

        $this->assertFalse((bool) DeliveryMethod::where('code', 'cdek')->value('active'));
    }

    public function test_api_services_point_to_their_documentation(): void
    {
        SeedDefaultDeliveryMethodsCommand::seed();

        foreach (DeliveryMethod::whereNotIn('code', ['pickup', 'courier_local'])->get() as $method) {
            $this->assertNotEmpty($method->docs_url, "У службы {$method->code} нет ссылки на документацию");
            $this->assertStringStartsWith('https://', $method->docs_url);
        }
    }

    public function test_codes_match_the_calculator_driver_map(): void
    {
        // Коды — идентификаторы: по ним калькулятор выбирает драйвер.
        // На оплате сидер и карта драйверов разошлись, и метод остался
        // без драйвера — здесь это ловится сразу.
        SeedDefaultDeliveryMethodsCommand::seed();

        $calculator = app(\Modules\Delivery\Services\DeliveryCalculatorService::class);
        $reflection = new \ReflectionMethod($calculator, 'getService');
        $reflection->setAccessible(true);

        foreach (['cdek', 'boxberry', 'pochta'] as $code) {
            $method = DeliveryMethod::where('code', $code)->first();

            $this->assertNotNull(
                $reflection->invoke($calculator, $method),
                "Для службы {$code} не нашёлся драйвер расчёта"
            );
        }
    }

    public function test_seeded_regions_use_the_constant_not_a_translated_label(): void
    {
        SeedDefaultDeliveryMethodsCommand::seed();

        $method = DeliveryMethod::where('code', 'cdek')->first();

        $this->assertSame([DeliveryMethod::ALL_REGIONS], $method->regions);
        $this->assertTrue($method->isAvailableInRegion('Курск'));
    }

    public function test_the_artisan_command_works(): void
    {
        // В тестах таблица modules пуста, поэтому провайдер модуля не
        // грузится и команда не зарегистрирована. Регистрируем явно,
        // чтобы проверять саму команду, а не порядок загрузки модулей.
        $this->app->register(\Modules\Delivery\Providers\DeliveryServiceProvider::class);

        $this->artisan('delivery:seed-default')->assertExitCode(0);

        $this->assertSame(6, DeliveryMethod::count());
    }
}
