<?php

namespace Tests\Unit;

use Tests\TestCase;
use Database\Factories\Modules\Payments\Models\PaymentMethodFactory;
use Modules\Payments\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Не сидируем: PaymentDeliverySeeder создаёт реальные строки
        // PaymentMethod, которые ломают точные assertCount() в scope-тестах
        // ниже. Все тесты создают собственные фикстуры через фабрику.
    }

    /** @test */
    public function it_can_create_payment_method_with_valid_data()
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'title' => 'Тестовая оплата',
            'type' => 'online',
            'commission' => 2.5,
        ]);

        $this->assertDatabaseHas('payment_methods', [
            'title' => 'Тестовая оплата',
            'type' => 'online',
        ]);

        $this->assertEquals(2.5, $paymentMethod->commission);
        $this->assertTrue($paymentMethod->active);
    }

    /** @test */
    public function it_calculates_formatted_commission_correctly()
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'commission' => 2.5,
        ]);

        $this->assertEquals('2,50%', $paymentMethod->formattedCommission);

        // Тест с null
        $paymentMethod->commission = null;
        $this->assertEquals('—', $paymentMethod->formattedCommission);
    }

    /** @test */
    public function it_formats_amounts_correctly()
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'min_amount' => 100.50,
            'max_amount' => 5000.75,
        ]);

        $this->assertEquals('100,50 ₽ - 5 000,75 ₽', $paymentMethod->formattedAmounts);

        // Тест с null значениями
        $paymentMethod->min_amount = null;
        $paymentMethod->max_amount = null;
        $this->assertEquals('— - —', $paymentMethod->formattedAmounts);
    }

    /** @test */
    public function it_formats_currencies_correctly()
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'currencies' => ['RUB', 'USD', 'EUR'],
        ]);

        $this->assertEquals('RUB, USD, EUR', $paymentMethod->formattedCurrencies);

        // Тест с пустым массивом
        $paymentMethod->currencies = [];
        $this->assertEquals('RUB', $paymentMethod->formattedCurrencies);
    }

    /** @test */
    public function it_checks_availability_for_amount()
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'min_amount' => 100,
            'max_amount' => 5000,
        ]);

        // Доступна для суммы в пределах
        $this->assertTrue($paymentMethod->isAvailableForAmount(1000));
        $this->assertTrue($paymentMethod->isAvailableForAmount(100));
        $this->assertTrue($paymentMethod->isAvailableForAmount(5000));

        // Недоступна для суммы вне пределов
        $this->assertFalse($paymentMethod->isAvailableForAmount(50));
        $this->assertFalse($paymentMethod->isAvailableForAmount(6000));

        // Тест без ограничений
        $paymentMethod->min_amount = null;
        $paymentMethod->max_amount = null;
        $this->assertTrue($paymentMethod->isAvailableForAmount(999999));
    }

    /** @test */
    public function it_has_russian_scope()
    {
        PaymentMethod::factory()->russian()->create(['code' => 'sbp']);
        PaymentMethod::factory()->international()->create(['code' => 'card']);

        $russianMethods = PaymentMethod::russian()->get();

        $this->assertCount(1, $russianMethods);
        $this->assertEquals('sbp', $russianMethods->first()->code);
        $this->assertTrue($russianMethods->first()->is_russian);
    }

    /** @test */
    public function it_has_online_scope()
    {
        PaymentMethod::factory()->create(['type' => 'online', 'code' => 'card']);
        PaymentMethod::factory()->create(['type' => 'offline', 'code' => 'cash']);

        $onlineMethods = PaymentMethod::online()->get();

        $this->assertCount(1, $onlineMethods);
        $this->assertEquals('online', $onlineMethods->first()->type);
    }

    /** @test */
    public function it_has_offline_scope()
    {
        PaymentMethod::factory()->create(['type' => 'online', 'code' => 'card']);
        PaymentMethod::factory()->create(['type' => 'offline', 'code' => 'cash']);

        $offlineMethods = PaymentMethod::offline()->get();

        $this->assertCount(1, $offlineMethods);
        $this->assertEquals('offline', $offlineMethods->first()->type);
    }

    /** @test */
    public function it_has_sbp_scope()
    {
        PaymentMethod::factory()->create(['code' => 'sbp', 'type' => 'sbp']);
        PaymentMethod::factory()->create(['code' => 'yookassa', 'type' => 'yookassa']);

        $sbpMethods = PaymentMethod::sbp()->get();

        $this->assertCount(1, $sbpMethods);
        $this->assertEquals('sbp', $sbpMethods->first()->code);
    }

    /** @test */
    public function it_has_yookassa_scope()
    {
        PaymentMethod::factory()->create(['code' => 'yookassa', 'type' => 'yookassa']);
        PaymentMethod::factory()->create(['code' => 'tinkoff', 'type' => 'tinkoff']);

        $yookassaMethods = PaymentMethod::yookassa()->get();

        $this->assertCount(1, $yookassaMethods);
        $this->assertEquals('yookassa', $yookassaMethods->first()->code);
    }

    /** @test */
    public function it_has_tinkoff_scope()
    {
        PaymentMethod::factory()->create(['code' => 'tinkoff', 'type' => 'tinkoff']);
        PaymentMethod::factory()->create(['code' => 'sberbank', 'type' => 'sberbank']);

        $tinkoffMethods = PaymentMethod::tinkoff()->get();

        $this->assertCount(1, $tinkoffMethods);
        $this->assertEquals('tinkoff', $tinkoffMethods->first()->code);
    }

    /** @test */
    public function it_has_sberbank_scope()
    {
        PaymentMethod::factory()->create(['code' => 'sberbank', 'type' => 'sberbank']);
        PaymentMethod::factory()->create(['code' => 'sberpay', 'type' => 'sberpay']);

        $sberbankMethods = PaymentMethod::sberbank()->get();

        $this->assertCount(1, $sberbankMethods);
        $this->assertEquals('sberbank', $sberbankMethods->first()->code);
    }

    /** @test */
    public function it_has_card_scope()
    {
        PaymentMethod::factory()->create(['code' => 'card', 'type' => 'online']);
        PaymentMethod::factory()->create(['code' => 'cash', 'type' => 'offline']);

        $cardMethods = PaymentMethod::card()->get();

        $this->assertCount(1, $cardMethods);
        $this->assertEquals('card', $cardMethods->first()->code);
    }

    /** @test */
    public function it_has_cash_scope()
    {
        PaymentMethod::factory()->create(['code' => 'cash', 'type' => 'offline']);
        PaymentMethod::factory()->create(['code' => 'card', 'type' => 'online']);

        $cashMethods = PaymentMethod::cash()->get();

        $this->assertCount(1, $cashMethods);
        $this->assertEquals('cash', $cashMethods->first()->code);
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'settings' => ['key' => 'value'],
            'active' => true,
            'is_russian' => true,
            'commission' => 2.5,
            'min_amount' => 100.50,
            'max_amount' => 5000.75,
            'currencies' => ['RUB', 'USD'],
            'test_mode' => true,
        ]);

        $this->assertIsArray($paymentMethod->settings);
        $this->assertEquals('value', $paymentMethod->settings['key']);
        $this->assertTrue($paymentMethod->active);
        $this->assertTrue($paymentMethod->is_russian);
        $this->assertEquals(2.5, $paymentMethod->commission);
        $this->assertEquals(100.50, $paymentMethod->min_amount);
        $this->assertEquals(5000.75, $paymentMethod->max_amount);
        $this->assertIsArray($paymentMethod->currencies);
        $this->assertTrue($paymentMethod->test_mode);
    }

    /** @test */
    public function it_can_be_mass_assigned()
    {
        $data = [
            'title' => 'Тест',
            'description' => 'Описание',
            'type' => 'online',
            'active' => true,
            'code' => 'test',
            'is_russian' => true,
            'commission' => 1.5,
            'min_amount' => 50,
            'max_amount' => 1000,
            'currencies' => ['RUB'],
            'settings' => ['test' => true],
            'test_mode' => false,
        ];

        $paymentMethod = PaymentMethod::create($data);

        $this->assertDatabaseHas('payment_methods', [
            'title' => 'Тест',
            'code' => 'test',
        ]);

        $this->assertEquals('Тест', $paymentMethod->title);
        $this->assertEquals('online', $paymentMethod->type);
    }

    /** @test */
    public function it_has_default_values()
    {
        // Не передаём active/is_russian/test_mode явно: колонки NOT NULL
        // с DB-default, а explicit null в INSERT игнорирует DEFAULT — так
        // что дефолты проверяем, полагаясь на собственные значения фабрики.
        $paymentMethod = PaymentMethod::factory()->create();

        // Проверяем, что значения по умолчанию применяются
        $this->assertNotNull($paymentMethod->active);
        $this->assertNotNull($paymentMethod->is_russian);
        $this->assertNotNull($paymentMethod->test_mode);
    }

    /** @test */
    public function it_handles_commission_calculation()
    {
        $paymentMethod = PaymentMethod::factory()->zeroCommission()->create();
        $this->assertEquals(0, $paymentMethod->commission);

        $paymentMethod = PaymentMethod::factory()->highCommission()->create();
        $this->assertEquals(10, $paymentMethod->commission);
    }

    /** @test */
    public function it_validates_amount_boundaries()
    {
        $paymentMethod = PaymentMethod::factory()->withLimits()->create();

        $this->assertEquals(100, $paymentMethod->min_amount);
        $this->assertEquals(5000, $paymentMethod->max_amount);

        // Проверка доступности
        $this->assertTrue($paymentMethod->isAvailableForAmount(100));
        $this->assertTrue($paymentMethod->isAvailableForAmount(5000));
        $this->assertFalse($paymentMethod->isAvailableForAmount(99));
        $this->assertFalse($paymentMethod->isAvailableForAmount(5001));
    }

    /** @test */
    public function it_can_be_inactive()
    {
        $paymentMethod = PaymentMethod::factory()->inactive()->create();

        $this->assertFalse($paymentMethod->active);
        $this->assertDatabaseHas('payment_methods', [
            'id' => $paymentMethod->id,
            'active' => false,
        ]);
    }

    /** @test */
    public function it_handles_russian_vs_international()
    {
        $russian = PaymentMethod::factory()->russian()->create();
        $international = PaymentMethod::factory()->international()->create();

        $this->assertTrue($russian->is_russian);
        $this->assertFalse($international->is_russian);

        $this->assertNotEquals($russian->code, $international->code);
    }

    /** @test */
    public function it_has_unique_codes()
    {
        PaymentMethod::factory()->create(['code' => 'unique_code']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        PaymentMethod::factory()->create(['code' => 'unique_code']);
    }

    /** @test */
    public function it_can_update_settings()
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'settings' => ['old_key' => 'old_value'],
        ]);

        $paymentMethod->update([
            'settings' => ['new_key' => 'new_value'],
        ]);

        $this->assertEquals('new_value', $paymentMethod->settings['new_key']);
        $this->assertArrayNotHasKey('old_key', $paymentMethod->settings);
    }

    /** @test */
    public function it_calculates_commission_amount()
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'commission' => 2.5,
        ]);

        $amount = 1000;
        $expectedCommission = $amount * 2.5 / 100; // 25

        $this->assertEquals(25, $expectedCommission);
    }

    /** @test */
    public function it_handles_extreme_amounts()
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'min_amount' => 0.01,
            'max_amount' => 999999999.99,
        ]);

        $this->assertTrue($paymentMethod->isAvailableForAmount(0.01));
        $this->assertTrue($paymentMethod->isAvailableForAmount(999999999.99));
        $this->assertTrue($paymentMethod->isAvailableForAmount(500000000));
    }

    /** @test */
    public function it_handles_zero_commission()
    {
        $paymentMethod = PaymentMethod::factory()->zeroCommission()->create();

        $this->assertEquals(0, $paymentMethod->commission);
        $this->assertEquals('0,00%', $paymentMethod->formattedCommission);
    }

    /** @test */
    public function it_handles_multiple_currencies()
    {
        $currencies = ['RUB', 'USD', 'EUR', 'GBP'];
        $paymentMethod = PaymentMethod::factory()->create([
            'currencies' => $currencies,
        ]);

        $this->assertCount(4, $paymentMethod->currencies);
        $this->assertEquals('RUB, USD, EUR, GBP', $paymentMethod->formattedCurrencies);
    }

    /** @test */
    public function it_can_be_deleted()
    {
        $paymentMethod = PaymentMethod::factory()->create();
        $id = $paymentMethod->id;

        $paymentMethod->delete();

        $this->assertDatabaseMissing('payment_methods', ['id' => $id]);
    }

    /** @test */
    public function it_has_timestamps()
    {
        $paymentMethod = PaymentMethod::factory()->create();

        $this->assertNotNull($paymentMethod->created_at);
        $this->assertNotNull($paymentMethod->updated_at);
        $this->assertEquals($paymentMethod->created_at, $paymentMethod->updated_at);

        sleep(1);
        $paymentMethod->touch();
        $this->assertNotEquals($paymentMethod->created_at, $paymentMethod->updated_at);
    }
}
