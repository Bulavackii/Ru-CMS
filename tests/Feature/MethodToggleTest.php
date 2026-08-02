<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Delivery\Models\DeliveryMethod;
use Modules\Payments\Models\PaymentMethod;
use Tests\TestCase;

/**
 * Переключатели «включён / выключен» у методов доставки и оплаты.
 *
 * Регрессия, которую поймал владелец: галочка снималась, но метод
 * оставался включённым. prepareForValidation() определял флаг через
 * has() — «ключ пришёл», а не «галочка стоит». Пока формы не слали
 * скрытое поле, это совпадало. После переработки формы рядом с чекбоксом
 * появился <input type="hidden" value="0">, ключ стал приходить всегда,
 * и флаг залип во включённом состоянии.
 */
class MethodToggleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    /** Как это шлёт браузер: скрытый 0 и, если галочка стоит, единица следом. */
    private function checkbox(bool $checked): string
    {
        return $checked ? '1' : '0';
    }

    private function deliveryPayload(bool $active, bool $api = false): array
    {
        return [
            'title' => 'СДЭК', 'code' => 'cdek', 'type' => 'courier',
            'price' => 400, 'sort_order' => 0,
            'active' => $this->checkbox($active),
            'api_enabled' => $this->checkbox($api),
            'is_russian' => '1',
        ];
    }

    public function test_delivery_method_can_be_turned_off(): void
    {
        $method = DeliveryMethod::create([
            'title' => 'СДЭК', 'code' => 'cdek', 'type' => 'courier',
            'price' => 400, 'active' => true,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.delivery.update', $method->id), $this->deliveryPayload(false))
            ->assertSessionHasNoErrors();

        $this->assertFalse((bool) $method->fresh()->active);
    }

    public function test_delivery_method_can_be_turned_on(): void
    {
        $method = DeliveryMethod::create([
            'title' => 'СДЭК', 'code' => 'cdek', 'type' => 'courier',
            'price' => 400, 'active' => false,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.delivery.update', $method->id), $this->deliveryPayload(true))
            ->assertSessionHasNoErrors();

        $this->assertTrue((bool) $method->fresh()->active);
    }

    public function test_delivery_api_flag_can_be_turned_off(): void
    {
        $method = DeliveryMethod::create([
            'title' => 'СДЭК', 'code' => 'cdek', 'type' => 'courier',
            'price' => 400, 'active' => true, 'api_enabled' => true,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.delivery.update', $method->id), $this->deliveryPayload(true, false))
            ->assertSessionHasNoErrors();

        $this->assertFalse((bool) $method->fresh()->api_enabled);
    }

    public function test_payment_method_can_be_turned_off(): void
    {
        // Та же форма и тот же паттерн в PaymentMethodRequest — значит и
        // та же поломка.
        $method = PaymentMethod::create([
            'title' => 'ЮKassa', 'code' => 'yookassa', 'type' => 'yookassa', 'active' => true,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.payments.update', $method->id), [
                'title' => 'ЮKassa', 'code' => 'yookassa', 'type' => 'yookassa',
                'active' => '0', 'test_mode' => '0', 'is_russian' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse((bool) $method->fresh()->active);
    }

    public function test_payment_test_mode_can_be_turned_off(): void
    {
        $method = PaymentMethod::create([
            'title' => 'ЮKassa', 'code' => 'yookassa', 'type' => 'yookassa',
            'active' => true, 'test_mode' => true,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.payments.update', $method->id), [
                'title' => 'ЮKassa', 'code' => 'yookassa', 'type' => 'yookassa',
                'active' => '1', 'test_mode' => '0', 'is_russian' => '1',
                'settings' => ['shop_id' => '1', 'secret_key' => 'k'],
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse((bool) $method->fresh()->test_mode);
    }
}
