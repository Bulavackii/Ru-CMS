<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Delivery\Models\DeliveryMethod;
use Modules\News\Models\News;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\PaymentMethod;
use Tests\TestCase;

/**
 * Корзина: что кладётся, что показывается и что уходит в заказ.
 *
 * Проверяются места, где браузер и сервер могли разойтись в цифрах или в
 * составе — а расходятся они всегда не в пользу владельца.
 */
class CartIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function товар(array $поля = []): News
    {
        return News::create(array_merge([
            'title' => 'Товар',
            'slug' => 'tovar-' . uniqid(),
            'content' => '<p>x</p>',
            'template' => 'products',
            'published' => true,
            'price' => 1000,
            'stock' => 5,
        ], $поля));
    }

    private function оплата(array $поля = []): PaymentMethod
    {
        return PaymentMethod::create(array_merge([
            'title' => 'Наличными', 'code' => 'cash', 'type' => 'offline',
            'active' => true, 'commission' => 0,
        ], $поля));
    }

    private function доставка(array $поля = []): DeliveryMethod
    {
        return DeliveryMethod::create(array_merge([
            'title' => 'Курьер', 'code' => 'courier_local', 'type' => 'courier',
            'active' => true, 'price' => 300,
        ], $поля));
    }

    /* ─────────────── Что попадает в корзину ─────────────── */

    /**
     * Цена и название берутся из базы, а не из формы.
     *
     * Подделать сумму заказа этим было нельзя (оформление перечитывает цены),
     * но корзина показывала присланное число — покупателю можно было подсунуть
     * чужую цену.
     */
    public function test_cart_stores_price_from_the_database(): void
    {
        $товар = $this->товар(['price' => 1000]);

        $this->postJson(route('cart.add'), [
            'id' => $товар->id, 'qty' => 1,
            'title' => 'Подделка', 'price' => 1,
        ])->assertOk();

        $корзина = session('cart');

        $this->assertSame(1000.0, (float) $корзина[$товар->id]['price'], 'В корзину попала цена из запроса');
        $this->assertSame('Товар', $корзина[$товар->id]['title'], 'В корзину попало название из запроса');
    }

    /**
     * Материал без цены заказать нельзя.
     *
     * Раньше в корзину клался ЛЮБОЙ материал по идентификатору — статья, урок,
     * что угодно. Цена у него пустая, значит заказ оформлялся на 0 ₽.
     */
    public function test_non_product_cannot_be_added(): void
    {
        $статья = $this->товар(['template' => 'default', 'price' => null, 'stock' => null]);

        $this->postJson(route('cart.add'), ['id' => $статья->id, 'qty' => 1])
            ->assertStatus(400);

        $this->assertEmpty(session('cart', []), 'Материал без цены попал в корзину');
    }

    /** Снятый с публикации товар в корзину не кладётся. */
    public function test_unpublished_product_cannot_be_added(): void
    {
        $товар = $this->товар(['published' => false]);

        $this->postJson(route('cart.add'), ['id' => $товар->id, 'qty' => 1])
            ->assertStatus(400);
    }

    /**
     * Отрицательное количество отбивается.
     *
     * `intval()` без проверки давал ноль на пустом поле, а отрицательное число
     * УМЕНЬШАЛО уже лежащее в корзине.
     */
    public function test_negative_quantity_is_rejected(): void
    {
        $товар = $this->товар();

        $this->postJson(route('cart.add'), ['id' => $товар->id, 'qty' => -3])
            ->assertStatus(422);
    }

    /* ─────────────── Что показывается ─────────────── */

    /**
     * Корзина сверяется с базой при показе.
     *
     * Она живёт в сессии два часа: за это время цена меняется, товар снимают
     * с продажи. Покупатель видел то, что положил, а на оформлении получал
     * другую сумму.
     */
    public function test_cart_page_refreshes_stale_prices(): void
    {
        $товар = $this->товар(['price' => 1000]);

        session(['cart' => [$товар->id => [
            'id' => $товар->id, 'title' => 'Старое название', 'price' => 100, 'qty' => 2,
        ]]]);

        $товар->update(['price' => 1500]);

        $this->get(route('cart.index'))->assertOk();

        $корзина = session('cart');

        $this->assertSame(1500.0, (float) $корзина[$товар->id]['price'], 'Показана устаревшая цена');
        $this->assertSame('Товар', $корзина[$товар->id]['title']);
    }

    /** Исчезнувший товар пропадает из корзины, а не роняет оформление. */
    public function test_cart_page_drops_vanished_products(): void
    {
        $живой = $this->товар();
        $пропавший = $this->товар();

        session(['cart' => [
            $живой->id => ['id' => $живой->id, 'title' => 'Товар', 'price' => 1000, 'qty' => 1],
            $пропавший->id => ['id' => $пропавший->id, 'title' => 'Товар', 'price' => 1000, 'qty' => 1],
        ]]);

        $пропавший->delete();

        $this->get(route('cart.index'))->assertOk();

        $this->assertArrayNotHasKey($пропавший->id, session('cart'), 'Удалённый товар остался в корзине');
        $this->assertArrayHasKey($живой->id, session('cart'), 'Живой товар выкинуло вместе с удалённым');
    }

    /** Количество подрезается по остатку. */
    public function test_cart_page_trims_quantity_to_stock(): void
    {
        $товар = $this->товар(['stock' => 2]);

        session(['cart' => [$товар->id => [
            'id' => $товар->id, 'title' => 'Товар', 'price' => 1000, 'qty' => 10,
        ]]]);

        $this->get(route('cart.index'))->assertOk();

        $this->assertSame(2, session('cart')[$товар->id]['qty'], 'Количество больше остатка');
    }

    /* ─────────────── Что уходит в заказ ─────────────── */

    /**
     * 🔴 Порог бесплатной доставки действует НА СЕРВЕРЕ.
     *
     * Раньше он применялся только в браузере: покупатель видел «доставка
     * бесплатно, к оплате 5000», а заказ создавался на 5300 — и ровно столько
     * запрашивалось у платёжной системы.
     */
    public function test_free_delivery_threshold_is_applied_on_the_server(): void
    {
        Event::fake([\Modules\Payments\Events\OrderCreated::class]);

        $товар = $this->товар(['price' => 5000]);
        $оплата = $this->оплата();
        $доставка = $this->доставка(['price' => 300, 'free_delivery_threshold' => 3000]);

        $this->post(route('cart.checkout'), [
            'payment_method_id' => $оплата->id,
            'delivery_method_id' => $доставка->id,
            'terms_agree' => 1,
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '+7 900 000-00-00',
            'customer_address' => 'Курск, ул. Ленина, 1',
            'items' => [['id' => $товар->id, 'qty' => 1]],
        ]);

        $заказ = Order::latest('id')->first();

        $this->assertNotNull($заказ);
        $this->assertEqualsWithDelta(0.0, (float) $заказ->delivery_price, 0.01, 'Доставка посчитана платной');
        $this->assertEqualsWithDelta(5000.0, (float) $заказ->total, 0.01, 'Итог не совпал с обещанным в корзине');
    }

    /** Ниже порога доставка платная — правило работает в обе стороны. */
    public function test_delivery_is_charged_below_the_threshold(): void
    {
        Event::fake([\Modules\Payments\Events\OrderCreated::class]);

        $товар = $this->товар(['price' => 1000]);
        $оплата = $this->оплата();
        $доставка = $this->доставка(['price' => 300, 'free_delivery_threshold' => 3000]);

        $this->post(route('cart.checkout'), [
            'payment_method_id' => $оплата->id,
            'delivery_method_id' => $доставка->id,
            'terms_agree' => 1,
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '+7 900 000-00-00',
            'customer_address' => 'Курск, ул. Ленина, 1',
            'items' => [['id' => $товар->id, 'qty' => 1]],
        ]);

        $заказ = Order::latest('id')->first();

        $this->assertEqualsWithDelta(300.0, (float) $заказ->delivery_price, 0.01);
        $this->assertEqualsWithDelta(1300.0, (float) $заказ->total, 0.01);
    }

    /**
     * Пределы платёжной системы считаются по СПИСЫВАЕМОЙ сумме.
     *
     * Раньше сравнивалась сумма товаров: заказ на 950 ₽ товаров с доставкой
     * 300 уходил в систему с потолком 1000 и отбивался уже на её стороне.
     */
    public function test_payment_limits_count_delivery_and_commission(): void
    {
        Event::fake([\Modules\Payments\Events\OrderCreated::class]);

        $товар = $this->товар(['price' => 950]);
        $оплата = $this->оплата(['max_amount' => 1000]);
        $доставка = $this->доставка(['price' => 300]);

        $this->post(route('cart.checkout'), [
            'payment_method_id' => $оплата->id,
            'delivery_method_id' => $доставка->id,
            'terms_agree' => 1,
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '+7 900 000-00-00',
            'customer_address' => 'Курск, ул. Ленина, 1',
            'items' => [['id' => $товар->id, 'qty' => 1]],
        ])->assertRedirect(route('cart.index'));

        $this->assertNull(Order::latest('id')->first(), 'Заказ сверх потолка системы создался');
    }

    /* ─────────────── Покупатель ─────────────── */

    /**
     * 🔴 Заказ без контактов покупателя не оформляется.
     *
     * Форма вообще не спрашивала ни имени, ни телефона, ни адреса — магазин
     * принимал заказы, которые физически некому доставить и не с кем уточнить,
     * а письмо покупателю уходило в никуда.
     */
    public function test_checkout_requires_the_buyer(): void
    {
        Event::fake([\Modules\Payments\Events\OrderCreated::class]);

        $товар = $this->товар();

        $this->post(route('cart.checkout'), [
            'payment_method_id' => $this->оплата()->id,
            'delivery_method_id' => $this->доставка()->id,
            'terms_agree' => 1,
            'items' => [['id' => $товар->id, 'qty' => 1]],
        ])->assertSessionHasErrors(['customer_name', 'customer_phone', 'customer_address']);

        $this->assertNull(Order::latest('id')->first(), 'Заказ без покупателя создался');
        $this->assertSame(5, $товар->fresh()->stock, 'Остаток тронут у несостоявшегося заказа');
    }

    /** Контакты действительно доезжают до заказа. */
    public function test_buyer_details_reach_the_order(): void
    {
        Event::fake([\Modules\Payments\Events\OrderCreated::class]);

        $товар = $this->товар();

        $this->post(route('cart.checkout'), [
            'payment_method_id' => $this->оплата()->id,
            'delivery_method_id' => $this->доставка()->id,
            'terms_agree' => 1,
            'customer_name' => 'Пётр Петров',
            'customer_phone' => '+7 900 111-22-33',
            'customer_email' => 'petr@example.com',
            'customer_address' => 'Курск, ул. Ленина, 1',
            'comment' => 'Позвонить за час',
            'items' => [['id' => $товар->id, 'qty' => 1]],
        ]);

        $заказ = Order::latest('id')->first();

        $this->assertNotNull($заказ);
        $this->assertSame('Пётр Петров', $заказ->customer_name);
        $this->assertSame('+7 900 111-22-33', $заказ->customer_phone);
        $this->assertSame('petr@example.com', $заказ->customer_email);
        $this->assertSame('Курск, ул. Ленина, 1', $заказ->customer_address);
        $this->assertSame('Позвонить за час', $заказ->comment);
    }

    /**
     * Самовывозу адрес не нужен — и не требуется.
     *
     * Лишнее обязательное поле это брошенная корзина, а проверка идёт на
     * сервере: скрытое в браузере поле ничего не доказывает.
     */
    public function test_pickup_does_not_require_an_address(): void
    {
        Event::fake([\Modules\Payments\Events\OrderCreated::class]);

        $товар = $this->товар();

        $this->post(route('cart.checkout'), [
            'payment_method_id' => $this->оплата()->id,
            'delivery_method_id' => $this->доставка(['code' => 'pickup', 'type' => 'pickup', 'price' => 0])->id,
            'terms_agree' => 1,
            'customer_name' => 'Пётр Петров',
            'customer_phone' => '+7 900 111-22-33',
            'items' => [['id' => $товар->id, 'qty' => 1]],
        ])->assertSessionHasNoErrors();

        $this->assertNotNull(Order::latest('id')->first(), 'Самовывоз потребовал адрес');
    }

    /** Почта вошедшего покупателя подставляется, если поле оставили пустым. */
    public function test_email_falls_back_to_the_account(): void
    {
        Event::fake([\Modules\Payments\Events\OrderCreated::class]);

        $товар = $this->товар();

        $this->actingAs(User::factory()->create(['email' => 'account@example.com']))
            ->post(route('cart.checkout'), [
                'payment_method_id' => $this->оплата()->id,
                'delivery_method_id' => $this->доставка()->id,
                'terms_agree' => 1,
                'customer_name' => 'Пётр Петров',
                'customer_phone' => '+7 900 111-22-33',
                'customer_address' => 'Курск, ул. Ленина, 1',
                'items' => [['id' => $товар->id, 'qty' => 1]],
            ]);

        $this->assertSame('account@example.com', Order::latest('id')->first()->customer_email);
    }

    /** Форма на странице корзины эти поля действительно показывает. */
    public function test_cart_page_shows_the_buyer_fields(): void
    {
        $товар = $this->товар();
        $this->оплата();
        $this->доставка();

        session(['cart' => [$товар->id => [
            'id' => $товар->id, 'title' => 'Товар', 'price' => 1000, 'qty' => 1,
        ]]]);

        $ответ = $this->get(route('cart.index'))->assertOk();

        // ⚠️ Отдельно от проверки сохранения: запрос напрямую проходит и при
        // отсутствующей форме — на этом уже обжигались с ценой материала.
        foreach (['customer_name', 'customer_phone', 'customer_email', 'customer_address'] as $поле) {
            $ответ->assertSee('name="' . $поле . '"', false);
        }
    }

    /* ─────────────── Чужой заказ ─────────────── */

    /**
     * 🔴 Страница подтверждения не показывает чужой заказ.
     *
     * Раньше она отдавала любой заказ по номеру в адресе: посторонний
     * перебирал /cart/confirm/1, /2, /3 и читал всю историю продаж магазина.
     */
    public function test_confirmation_page_hides_other_peoples_orders(): void
    {
        Event::fake([\Modules\Payments\Events\OrderCreated::class]);

        $чужой = Order::create([
            'payment_method_id' => $this->оплата()->id,
            'status' => 'pending', 'customer_name' => 'Чужой покупатель',
            'items_total' => 9999, 'delivery_price' => 0, 'commission' => 0, 'total' => 9999,
        ]);

        $this->get(route('cart.confirm', ['id' => $чужой->id]))->assertNotFound();
    }

    /** А свой — показывает: покупатель только что его оформил. */
    public function test_buyer_sees_the_order_they_just_placed(): void
    {
        Event::fake([\Modules\Payments\Events\OrderCreated::class]);

        $товар = $this->товар();
        $оплата = $this->оплата();
        $доставка = $this->доставка();

        $this->post(route('cart.checkout'), [
            'payment_method_id' => $оплата->id,
            'delivery_method_id' => $доставка->id,
            'terms_agree' => 1,
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '+7 900 000-00-00',
            'customer_address' => 'Курск, ул. Ленина, 1',
            'items' => [['id' => $товар->id, 'qty' => 1]],
        ]);

        $заказ = Order::latest('id')->first();

        $this->get(route('cart.confirm', ['id' => $заказ->id]))->assertOk();
    }

    /** Администратору видно любой заказ — он их и обрабатывает. */
    public function test_admin_sees_any_order(): void
    {
        Event::fake([\Modules\Payments\Events\OrderCreated::class]);

        $заказ = Order::create([
            'payment_method_id' => $this->оплата()->id,
            'status' => 'pending', 'customer_name' => 'Покупатель',
            'items_total' => 100, 'delivery_price' => 0, 'commission' => 0, 'total' => 100,
        ]);

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('cart.confirm', ['id' => $заказ->id]))
            ->assertOk();
    }
}
