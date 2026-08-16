<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\PaymentMethod;
use Modules\Payments\Services\PaymentGatewayService;
use Tests\TestCase;

/**
 * 🔴 Недописанный драйвер не должен доходить до покупателя.
 *
 * У СБП драйвер написан заглушкой: «QR-код» — это base64 от строки, а не
 * картинка; статус платежа читается из НАШЕЙ базы, а не у банка; уведомления
 * намеренно не обрабатываются. Включённый способ выглядел бы на сайте
 * обычным, а покупатель получил бы нерабочую оплату — и, что хуже, заказ мог
 * бы оказаться «оплаченным» без денег.
 *
 * Заслон стоит в трёх местах, и проверяются все три: форма панели, создание
 * платежа и приём уведомлений. Одной формы мало — способ может оказаться
 * включённым мимо неё (сидером, выгрузкой, правкой в базе).
 */
class PaymentDriverReadinessTest extends TestCase
{
    use RefreshDatabase;

    private function способ(string $код, bool $активен = false): PaymentMethod
    {
        return PaymentMethod::create([
            'title' => 'Способ ' . $код,
            'code' => $код,
            'type' => $код,
            'active' => $активен,
            'commission' => 0,
        ]);
    }

    /** Заглушка помечена, готовый способ — нет. */
    public function test_readiness_is_reported(): void
    {
        $this->assertSame('stub', $this->способ('sbp')->readiness());
        $this->assertSame('untested', $this->способ('tbank')->readiness());
        $this->assertSame('ready', $this->способ('yookassa')->readiness());

        // Офлайновые способы драйвера не имеют — ломаться нечему
        $this->assertSame('ready', $this->способ('cash')->readiness());
    }

    /** Создание платежа заглушкой отбивается. */
    public function test_stub_driver_cannot_create_a_payment(): void
    {
        $способ = $this->способ('sbp', true);

        $заказ = Order::create([
            'payment_method_id' => $способ->id,
            'status' => 'pending',
            'customer_name' => 'Иван',
            'items_total' => 1000, 'delivery_price' => 0, 'commission' => 0, 'total' => 1000,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('~заглушк~u');

        app(PaymentGatewayService::class)->createPayment($заказ, $способ);
    }

    /** Уведомление от заглушки статус заказа не меняет. */
    public function test_stub_driver_never_confirms_a_payment(): void
    {
        $способ = $this->способ('sbp', true);

        $заказ = Order::create([
            'payment_method_id' => $способ->id,
            'status' => 'pending',
            'customer_name' => 'Иван',
            'items_total' => 1000, 'delivery_price' => 0, 'commission' => 0, 'total' => 1000,
        ]);

        $принято = app(PaymentGatewayService::class)->handleWebhook('sbp', [
            'status' => 'paid',
            'order_id' => $заказ->id,
        ]);

        $this->assertFalse($принято, 'Уведомление от недописанного драйвера приняли');
        $this->assertSame('pending', $заказ->fresh()->status, 'Заказ объявлен оплаченным без денег');
    }

    /** Включить заглушку через форму панели нельзя. */
    public function test_stub_driver_cannot_be_enabled_from_the_panel(): void
    {
        $способ = $this->способ('sbp');

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->put(route('admin.payments.update', $способ->id), [
            'title' => 'СБП',
            'type' => 'sbp',
            'code' => 'sbp',
            'active' => 1,
            'bik' => '044525225',
            'account' => '40702810900000000001',
        ])->assertSessionHasErrors('active');

        $this->assertFalse((bool) $способ->fresh()->active, 'Заглушку удалось включить');
    }

    /**
     * А выключенной её по-прежнему можно сохранять.
     *
     * Иначе нельзя было бы поправить у неё даже название или порядок.
     */
    public function test_stub_driver_can_still_be_saved_disabled(): void
    {
        $способ = $this->способ('sbp');

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->put(route('admin.payments.update', $способ->id), [
            'title' => 'Оплата по QR',
            'type' => 'sbp',
            'code' => 'sbp',
            'active' => 0,
            'bik' => '044525225',
            'account' => '40702810900000000001',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Оплата по QR', $способ->fresh()->title);
    }

    /**
     * Обе страницы раздела открываются и предупреждение на них видно.
     *
     * Разметка предупреждения новая, а по списку и форме «Оплаты» рендерных
     * проверок не было вовсе — 500 здесь никто бы не поймал.
     */
    public function test_warning_is_visible_on_both_pages(): void
    {
        $заглушка = $this->способ('sbp');
        $this->способ('tbank');

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->withSession(['app_locale' => 'ru']);

        $this->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee(__('admin.payments.ready_stub'))
            ->assertSee(__('admin.payments.ready_untested'));

        // На форме заглушки галочка «Активен» ещё и заблокирована
        $this->get(route('admin.payments.edit', $заглушка->id))
            ->assertOk()
            ->assertSee(__('admin.payments.ready_stub'))
            ->assertSee('disabled', false);
    }

    /**
     * Сидер не включает ничего, чему нужны реквизиты.
     *
     * Отдельная проверка: инвариант «после установки корзина рабочая, но ни
     * один непроверенный способ не включён» держится именно здесь.
     */
    public function test_seeder_never_enables_an_unready_driver(): void
    {
        // ⚠️ Не через artisan: провайдер модуля регистрируется только у
        // активных модулей, а в тестах таблица `modules` пуста — команды по
        // имени там нет. Зовём тот же метод, что зовёт мастер установки.
        \Modules\Payments\Console\Commands\SeedDefaultPaymentMethodsCommand::seed();

        foreach (PaymentMethod::where('active', true)->get() as $способ) {
            $this->assertSame(
                'ready',
                $способ->readiness(),
                "Сидер включил способ «{$способ->code}», не готовый к бою"
            );
        }
    }
}
