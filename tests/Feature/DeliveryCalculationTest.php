<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Delivery\Models\DeliveryMethod;
use Modules\Delivery\Services\DeliveryCalculatorService;
use Tests\TestCase;

/**
 * Расчёт стоимости доставки: вес, регион, порог бесплатной доставки.
 *
 * Логика расчёта в проекте уже была, но без тестов — и содержала тот же
 * баг с литералом «Все регионы РФ» вместо константы, что и модель.
 */
class DeliveryCalculationTest extends TestCase
{
    use RefreshDatabase;

    private function calculator(): DeliveryCalculatorService
    {
        return app(DeliveryCalculatorService::class);
    }

    private function method(array $override = []): DeliveryMethod
    {
        return DeliveryMethod::create(array_merge([
            'title' => 'СДЭК', 'code' => 'cdek', 'type' => 'courier',
            'active' => true, 'price' => 400, 'min_days' => 2, 'max_days' => 5,
            'api_enabled' => false,
        ], $override));
    }

    public function test_fixed_price_is_returned_without_api(): void
    {
        $result = $this->calculator()->calculate($this->method(), ['weight' => 1]);

        $this->assertSame(400.0, (float) $result['price']);
        $this->assertSame(2, $result['days']);
    }

    public function test_free_delivery_above_the_threshold(): void
    {
        $method = $this->method(['free_delivery_threshold' => 5000]);

        $result = $this->calculator()->calculate($method, ['order_total' => 5000]);

        $this->assertSame(0, $result['price']);
        $this->assertNotEmpty($result['message']);
    }

    public function test_below_the_threshold_the_price_stays(): void
    {
        $method = $this->method(['free_delivery_threshold' => 5000]);

        $result = $this->calculator()->calculate($method, ['order_total' => 4999]);

        $this->assertSame(400.0, (float) $result['price']);
    }

    public function test_weight_limit_blocks_the_calculation(): void
    {
        $method = $this->method(['weight_limit' => 10]);

        $result = $this->calculator()->calculate($method, ['weight' => 10.5]);

        $this->assertArrayHasKey('error', $result);
    }

    public function test_all_regions_constant_is_recognised(): void
    {
        // Раньше здесь стоял литерал: если бы подпись «Все регионы РФ»
        // где-то перевели, доставка перестала бы работать везде.
        $method = $this->method(['regions' => [DeliveryMethod::ALL_REGIONS]]);

        $result = $this->calculator()->calculate($method, ['region' => 'Курск']);

        $this->assertArrayNotHasKey('error', $result);
        $this->assertSame(400.0, (float) $result['price']);
    }

    public function test_region_outside_the_list_is_rejected(): void
    {
        $method = $this->method(['regions' => ['Москва', 'Санкт-Петербург']]);

        $result = $this->calculator()->calculate($method, ['region' => 'Владивосток']);

        $this->assertArrayHasKey('error', $result);
    }

    public function test_listed_region_is_allowed(): void
    {
        $method = $this->method(['regions' => ['Москва', 'Курск']]);

        $result = $this->calculator()->calculate($method, ['region' => 'Курск']);

        $this->assertArrayNotHasKey('error', $result);
    }

    public function test_customer_messages_follow_the_locale(): void
    {
        // Сообщения показываются покупателю — на его языке, а не всегда
        // по-русски, как было до этого.
        $method = $this->method(['regions' => ['Москва']]);

        app()->setLocale('en');
        $english = $this->calculator()->calculate($method, ['region' => 'Курск']);

        app()->setLocale('ru');
        $russian = $this->calculator()->calculate($method, ['region' => 'Курск']);

        $this->assertNotSame($english['error'], $russian['error']);
        $this->assertStringContainsString('Delivery', $english['error']);
    }
}
