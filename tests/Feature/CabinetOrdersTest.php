<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Delivery\Models\DeliveryMethod;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\PaymentMethod;
use Tests\TestCase;

/**
 * Кабинет покупателя: список заказов.
 *
 * По этой странице не было ни одного теста, хотя она годами печатала
 * сырые значения из базы вместо подписей.
 */
class CabinetOrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Иначе слушатели полезут слать письма и складывать уведомления.
        Event::fake();
    }

    private function order(User $user, string $status = 'paid'): Order
    {
        // firstOrCreate, а не create: код способа уникален, а заказов в
        // тесте бывает несколько.
        $payment = PaymentMethod::firstOrCreate(
            ['code' => 'yookassa'],
            ['title' => 'ЮKassa', 'type' => 'yookassa', 'active' => true],
        );

        $delivery = DeliveryMethod::firstOrCreate(
            ['code' => 'boxberry'],
            ['title' => 'Boxberry', 'price' => 320, 'active' => true],
        );

        return Order::create([
            'user_id' => $user->id,
            'payment_method_id' => $payment->id,
            'delivery_method_id' => $delivery->id,
            'total' => 1490, 'status' => $status,
            'customer_name' => 'Покупатель', 'customer_email' => 'buyer@example.com',
            'customer_phone' => '+70000000000',
        ]);
    }

    public function test_orders_page_shows_methods_with_their_marks(): void
    {
        $user = User::factory()->create();
        $this->order($user);

        $html = $this->actingAs($user)
            ->withSession(['app_locale' => 'ru'])
            ->get(route('dashboard.orders'))
            ->assertOk()
            ->getContent();

        // Знак платёжной системы и службы доставки — те же файлы, что в
        // панели и в корзине. Раньше здесь были эмодзи и голый текст.
        $this->assertStringContainsString('images/payments/yookassa', $html);
        $this->assertStringContainsString('images/delivery/boxberry', $html);

        $this->assertStringContainsString('ЮKassa', $html);
        $this->assertStringContainsString('Boxberry', $html);
    }

    public function test_status_is_shown_translated_not_raw(): void
    {
        // Раньше выводился ucfirst($order->status): покупатель на русском
        // видел «Paid» — сырое значение из базы.
        $user = User::factory()->create();
        $this->order($user, 'paid');

        $this->actingAs($user)
            ->withSession(['app_locale' => 'ru'])
            ->get(route('dashboard.orders'))
            ->assertOk()
            ->assertSee(__('frontend.account.st_paid'), false)
            ->assertDontSee('>paid<', false);
    }

    public function test_single_page_still_reports_how_many_orders_there_are(): void
    {
        // Общий компонент постраничного вывода прячется сам, когда страница
        // одна: без своей сводки покупатель не видел вообще ничего и решал,
        // что список обрезан. Та же ловушка уже была в Медиатеке.
        $user = User::factory()->create();
        $this->order($user);

        $this->actingAs($user)
            ->withSession(['app_locale' => 'ru'])
            ->get(route('dashboard.orders'))
            ->assertOk()
            ->assertSee(__('frontend.account.orders_showing', ['from' => 1, 'to' => 1, 'total' => 1]), false);
    }

    public function test_user_sees_only_their_own_orders(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();

        $mine = $this->order($user);
        $theirs = $this->order($stranger);

        $html = $this->actingAs($user)
            ->get(route('dashboard.orders'))
            ->assertOk()
            ->getContent();

        // Проверяем по разметке карточки, а не по подстроке «#2»: в ответе
        // полно шестнадцатеричных цветов вроде #2563eb, и голая проверка
        // «не вижу #2» падала бы на них.
        $this->assertSame(1, substr_count($html, 'class="ord-card"'), 'Карточек должно быть ровно одна');
        $this->assertStringContainsString('>#' . $mine->id . '<', $html);
        $this->assertStringNotContainsString('>#' . $theirs->id . '<', $html);
    }
}
