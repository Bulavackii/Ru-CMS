<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Modules\Delivery\Models\DeliveryMethod;
use Modules\News\Models\News;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\PaymentMethod;
use Tests\TestCase;

/**
 * 🔴 Ограничения доставки по весу и региону — при ОФОРМЛЕНИИ заказа.
 *
 * `DeliveryCalculatorService` умел их с самого начала, но корзина его не звала:
 * она брала `$deliveryMethod->price` как есть. Значит, служба, которая не возит
 * в этот город или не берёт такой вес, спокойно принимала заказ — и владелец
 * узнавал об этом, когда шёл на почту с посылкой. В панели ограничения при
 * этом можно было задать, что создавало ложное чувство работающей проверки.
 *
 * Применить их было нечем по двум причинам: у товара не было веса вовсе, а
 * адрес собирался одной свободной строкой, из которой регион не выделить.
 * Теперь у товара есть `weight`, у заказа — `customer_city`.
 */
class DeliveryRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([\Modules\Payments\Events\OrderCreated::class]);

        // Ни один тест не должен ходить наружу. Если запрос всё-таки уйдёт,
        // прогон упадёт — это и есть проверка «оформление не звонит в API».
        Http::preventStrayRequests();
    }

    private function товар(array $поля = []): News
    {
        return News::create(array_merge([
            'title' => 'Товар', 'slug' => 'tovar-' . uniqid(),
            'content' => '<p>x</p>', 'template' => 'products',
            'published' => true, 'price' => 1000, 'stock' => 10,
        ], $поля));
    }

    private function оплата(): PaymentMethod
    {
        return PaymentMethod::firstOrCreate(
            ['code' => 'cash'],
            ['title' => 'Наличными', 'type' => 'offline', 'active' => true, 'commission' => 0],
        );
    }

    private function доставка(array $поля = []): DeliveryMethod
    {
        return DeliveryMethod::create(array_merge([
            'title' => 'Курьер', 'code' => 'courier_local', 'type' => 'courier',
            'active' => true, 'price' => 300,
        ], $поля));
    }

    private function оформить(News $товар, DeliveryMethod $доставка, array $правки = [])
    {
        return $this->post(route('cart.checkout'), array_merge([
            'payment_method_id' => $this->оплата()->id,
            'delivery_method_id' => $доставка->id,
            'terms_agree' => 1,
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '+7 900 000-00-00',
            'customer_address' => 'ул. Ленина, 1',
            'customer_city' => 'Курск',
            'items' => [['id' => $товар->id, 'qty' => 1]],
        ], $правки));
    }

    /* ─────────────── Вес ─────────────── */

    /** Заказ тяжелее лимита службы не оформляется. */
    public function test_order_heavier_than_the_limit_is_rejected(): void
    {
        $товар = $this->товар(['weight' => 12]);
        $доставка = $this->доставка(['weight_limit' => 10]);

        $this->оформить($товар, $доставка)->assertRedirect(route('cart.index'));

        $this->assertNull(Order::latest('id')->first(), 'Служба приняла заказ сверх своего лимита по весу');
        $this->assertSame(10, $товар->fresh()->stock, 'Остаток тронут у несостоявшегося заказа');
    }

    /** Вес считается по КОЛИЧЕСТВУ, а не по одной штуке. */
    public function test_weight_is_multiplied_by_quantity(): void
    {
        $товар = $this->товар(['weight' => 4]);
        $доставка = $this->доставка(['weight_limit' => 10]);

        // 4 кг × 3 шт = 12 кг — сверх лимита, хотя одна штука проходит
        $this->оформить($товар, $доставка, ['items' => [['id' => $товар->id, 'qty' => 3]]]);

        $this->assertNull(Order::latest('id')->first(), 'Вес посчитан без учёта количества');
    }

    /** В пределах лимита заказ проходит, а вес сохраняется в заказе. */
    public function test_order_within_the_limit_goes_through(): void
    {
        $товар = $this->товар(['weight' => 2.5]);
        $доставка = $this->доставка(['weight_limit' => 10]);

        $this->оформить($товар, $доставка, ['items' => [['id' => $товар->id, 'qty' => 2]]])
            ->assertSessionHasNoErrors();

        $заказ = Order::latest('id')->first();

        $this->assertNotNull($заказ);
        $this->assertEqualsWithDelta(5.0, (float) $заказ->total_weight, 0.001, 'Вес заказа не сохранён');
    }

    /**
     * ⚠️ Пустой вес — это «не взвешиваем», а не ноль.
     *
     * Заказ из одних услуг не должен ни отбиваться по весу, ни проходить лимит
     * «формально»: вес у него остаётся пустым.
     */
    public function test_weightless_order_ignores_the_limit(): void
    {
        $услуга = $this->товар(['template' => 'ourworks', 'weight' => null, 'stock' => null]);
        $доставка = $this->доставка(['weight_limit' => 1]);

        $this->оформить($услуга, $доставка)->assertSessionHasNoErrors();

        $заказ = Order::latest('id')->first();

        $this->assertNotNull($заказ, 'Заказ из услуг отбит по весу, которого у него нет');
        $this->assertNull($заказ->total_weight, 'У заказа без весов появился вес');
    }

    /** У службы без лимита вес ничего не решает. */
    public function test_method_without_a_limit_accepts_any_weight(): void
    {
        $товар = $this->товар(['weight' => 500]);
        $доставка = $this->доставка(['weight_limit' => null]);

        $this->оформить($товар, $доставка)->assertSessionHasNoErrors();

        $this->assertNotNull(Order::latest('id')->first());
    }

    /* ─────────────── Регион ─────────────── */

    /** Служба, которая туда не возит, заказ не принимает. */
    public function test_order_to_an_unserved_city_is_rejected(): void
    {
        $товар = $this->товар();
        $доставка = $this->доставка(['regions' => ['Москва', 'Санкт-Петербург']]);

        $this->оформить($товар, $доставка, ['customer_city' => 'Курск'])
            ->assertRedirect(route('cart.index'));

        $this->assertNull(Order::latest('id')->first(), 'Заказ принят в город, куда служба не возит');
    }

    /** В свой город — принимает. */
    public function test_order_to_a_served_city_goes_through(): void
    {
        $товар = $this->товар();
        $доставка = $this->доставка(['regions' => ['Москва', 'Курск']]);

        $this->оформить($товар, $доставка, ['customer_city' => 'Курск'])
            ->assertSessionHasNoErrors();

        $заказ = Order::latest('id')->first();

        $this->assertNotNull($заказ);
        $this->assertSame('Курск', $заказ->customer_city, 'Город не сохранён в заказе');
    }

    /**
     * «Все регионы РФ» — это идентификатор в базе, а не подпись.
     *
     * Служба с ним возит куда угодно, и переводить это значение нельзя: на
     * другом языке в базу писалась бы другая строка.
     */
    public function test_all_regions_marker_serves_everywhere(): void
    {
        $товар = $this->товар();
        $доставка = $this->доставка(['regions' => [DeliveryMethod::ALL_REGIONS]]);

        $this->оформить($товар, $доставка, ['customer_city' => 'Владивосток'])
            ->assertSessionHasNoErrors();

        $this->assertNotNull(Order::latest('id')->first());
    }

    /** Пустой список регионов значит «возит везде». */
    public function test_empty_region_list_serves_everywhere(): void
    {
        $товар = $this->товар();
        $доставка = $this->доставка(['regions' => null]);

        $this->оформить($товар, $доставка, ['customer_city' => 'Анадырь'])
            ->assertSessionHasNoErrors();

        $this->assertNotNull(Order::latest('id')->first());
    }

    /* ─────────────── Город как поле ─────────────── */

    /**
     * Город обязателен — иначе правило по регионам применить не к чему.
     *
     * ⚠️ Кроме самовывоза: там покупатель приходит сам, и спрашивать город
     * незачем. Решает это СЕРВЕР по типу службы, а не скрытое в браузере поле.
     */
    public function test_city_is_required_except_for_pickup(): void
    {
        $товар = $this->товар();

        $this->оформить($товар, $this->доставка(), ['customer_city' => null])
            ->assertSessionHasErrors('customer_city');

        $самовывоз = $this->доставка(['code' => 'pickup', 'type' => 'pickup', 'price' => 0]);

        $this->оформить($товар, $самовывоз, ['customer_city' => null, 'customer_address' => null])
            ->assertSessionHasNoErrors();
    }

    /** Форма корзины действительно спрашивает город. */
    public function test_cart_form_asks_for_the_city(): void
    {
        $товар = $this->товар();
        $this->оплата();
        $this->доставка();

        session(['cart' => [$товар->id => [
            'id' => $товар->id, 'title' => 'Товар', 'price' => 1000, 'qty' => 1,
        ]]]);

        // Отдельно от проверки сохранения: запрос напрямую проходит и при
        // отсутствующем поле в форме.
        $this->get(route('cart.index'))->assertOk()->assertSee('name="customer_city"', false);
    }

    /* ─────────────── Вес у товара ─────────────── */

    /** Вес сохраняется у товара и не появляется у услуги. */
    public function test_weight_is_saved_only_for_products(): void
    {
        $админ = \App\Models\User::factory()->create(['is_admin' => true]);
        $this->actingAs($админ);

        $this->post(route('admin.news.store'), [
            'title' => 'Тяжёлый товар', 'content' => '<p>x</p>',
            'template' => 'products', 'price' => 100, 'stock' => 5, 'weight' => 3.5,
        ]);

        $this->assertEqualsWithDelta(
            3.5,
            (float) News::where('title', 'Тяжёлый товар')->value('weight'),
            0.001,
            'Вес товара не сохранился'
        );

        // У услуги веса нет по смыслу: присланное значение обнуляется
        $this->post(route('admin.news.store'), [
            'title' => 'Услуга', 'content' => '<p>x</p>',
            'template' => 'ourworks', 'price' => 5000, 'weight' => 3.5,
        ]);

        $this->assertNull(
            News::where('title', 'Услуга')->value('weight'),
            'У услуги появился вес'
        );
    }

    /** Поле веса есть в форме материала — отдельно от проверки сохранения. */
    public function test_weight_field_is_in_the_form(): void
    {
        $this->actingAs(\App\Models\User::factory()->create(['is_admin' => true]));

        $товар = $this->товар(['weight' => 2]);

        $this->get(route('admin.news.create'))->assertOk()->assertSee('name="weight"', false);
        $this->get(route('admin.news.edit', $товар->id))->assertOk()->assertSee('name="weight"', false);
    }

    /**
     * 🔴 Оформление НЕ звонит в API службы доставки.
     *
     * Расчёт через API имеет таймаут 15–20 секунд: покупатель полминуты смотрит
     * на замершую кнопку, а недоступная служба рвёт покупку. Правила (вес,
     * регион, порог) проверяются до этой ветки и работают всегда — наружу не
     * уходит только цена.
     *
     * `Http::preventStrayRequests()` в setUp роняет тест на любом исходящем
     * запросе, так что проверка настоящая, а не по коду.
     */
    public function test_checkout_never_calls_the_delivery_api(): void
    {
        $товар = $this->товар(['weight' => 1]);

        $доставка = $this->доставка([
            'code' => 'cdek',
            'api_enabled' => true,
            'api_settings' => ['account' => 'a', 'secure_password' => 'b'],
            'weight_limit' => 10,
        ]);

        $this->оформить($товар, $доставка)->assertSessionHasNoErrors();

        $заказ = Order::latest('id')->first();

        $this->assertNotNull($заказ, 'Заказ не создался при включённом API службы');
        $this->assertEqualsWithDelta(
            300.0,
            (float) $заказ->delivery_price,
            0.01,
            'Взята не фиксированная цена — значит, ходили в API'
        );
    }
}
