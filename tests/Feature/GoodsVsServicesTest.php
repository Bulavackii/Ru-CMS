<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Delivery\Models\DeliveryMethod;
use Modules\News\Models\News;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\PaymentMethod;
use Tests\TestCase;

/**
 * Товар везут, услугу оказывают.
 *
 * 🔴 Дефект, ради которого написан этот файл: корзина предлагала доставку
 * всему, у чего есть цена. Детский приём в клинике можно было отправить
 * Почтой России за 350 ₽ и указать адрес для курьера — покупатель платил за
 * доставку того, что никуда не едет, а владелец получал заказ с адресом
 * доставки на приём у врача.
 *
 * Проверяется обе стороны: и что показано покупателю, и что принял сервер.
 * Разметку можно поправить из инструментов разработчика за секунду, поэтому
 * тест, смотрящий только на страницу, ничего не доказывает.
 */
class GoodsVsServicesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Заказ поднимает события (письма, списание) — здесь проверяется
        // состав и деньги, а не оповещения.
        Event::fake();
    }

    private function товар(array $поля = []): News
    {
        return News::create(array_merge([
            'title'     => 'Кружка',
            'slug'      => 'kruzhka-' . uniqid(),
            'content'   => '<p>x</p>',
            'template'  => 'products',
            'published' => true,
            'price'     => 1000,
            'stock'     => 10,
            'weight'    => 0.3,
        ], $поля));
    }

    /** Услуга: цена есть, а везти нечего. Ровно случай владельца. */
    private function услуга(array $поля = []): News
    {
        return News::create(array_merge([
            'title'     => 'Детский приём',
            'slug'      => 'detskiy-priem-' . uniqid(),
            'content'   => '<p>x</p>',
            'template'  => 'clinic',
            'published' => true,
            'price'     => 2000,
        ], $поля));
    }

    private function оплата(): PaymentMethod
    {
        return PaymentMethod::create([
            'title' => 'Наличными', 'code' => 'cash', 'type' => 'offline',
            'active' => true, 'commission' => 0,
        ]);
    }

    private function доставка(): DeliveryMethod
    {
        return DeliveryMethod::create([
            'title' => 'Почта России', 'code' => 'russian_post', 'type' => 'post',
            'active' => true, 'price' => 350,
        ]);
    }

    /* ─────────────── Признак на модели ─────────────── */

    public function test_везут_только_физические_товары(): void
    {
        $this->assertTrue($this->товар()->доставляется());
        $this->assertFalse($this->услуга()->доставляется());

        // Шаблон — три состояния одной группы, и ни одно не пересылается.
        foreach (['default', '', null] as $шаблон) {
            $this->assertFalse(
                $this->товар(['template' => $шаблон, 'weight' => null])->доставляется(),
                'Шаблон ' . var_export($шаблон, true) . ' не должен считаться пересылаемым'
            );
        }
    }

    /* ─────────────── Что видит покупатель ─────────────── */

    public function test_у_услуги_корзина_не_показывает_шаг_доставки(): void
    {
        $услуга = $this->услуга();
        $this->оплата();
        $this->доставка();

        $ответ = $this->withSession([
            'app_locale' => 'ru',
            'cart' => [$услуга->id => ['id' => $услуга->id, 'title' => $услуга->title, 'price' => 2000, 'qty' => 1]],
        ])->get(route('cart.index'));

        $ответ->assertOk();
        // Ни выбора службы, ни полей адреса.
        //
        // ⚠️ Искать голое `name="delivery_method_id"` нельзя: ровно эта строка
        // стоит в селекторе внутри скрипта той же страницы
        // (`input[name="delivery_method_id"]`), и проверка срабатывала бы
        // всегда. Проверяем разметку переключателя целиком.
        $ответ->assertDontSee('type="radio" name="delivery_method_id"', false);
        $ответ->assertDontSee('<input type="text" name="customer_city"', false);
        $ответ->assertDontSee('<input type="text" name="customer_address"', false);
        // Покупатель — третий шаг, а не четвёртый: пропущенного номера нет.
        $ответ->assertSee('>03</span>', false);
        $ответ->assertDontSee('>04</span>', false);
    }

    public function test_у_товара_шаг_доставки_на_месте(): void
    {
        $товар = $this->товар();
        $this->оплата();
        $this->доставка();

        $ответ = $this->withSession([
            'app_locale' => 'ru',
            'cart' => [$товар->id => ['id' => $товар->id, 'title' => $товар->title, 'price' => 1000, 'qty' => 1]],
        ])->get(route('cart.index'));

        $ответ->assertOk();
        $ответ->assertSee('type="radio" name="delivery_method_id"', false);
        $ответ->assertSee('<input type="text" name="customer_city"', false);
        $ответ->assertSee('>04</span>', false);
    }

    public function test_смешанный_набор_считается_требующим_доставки(): void
    {
        $товар  = $this->товар();
        $услуга = $this->услуга();
        $this->оплата();
        $this->доставка();

        $ответ = $this->withSession([
            'app_locale' => 'ru',
            'cart' => [
                $товар->id  => ['id' => $товар->id,  'title' => $товар->title,  'price' => 1000, 'qty' => 1],
                $услуга->id => ['id' => $услуга->id, 'title' => $услуга->title, 'price' => 2000, 'qty' => 1],
            ],
        ])->get(route('cart.index'));

        $ответ->assertOk();
        $ответ->assertSee('type="radio" name="delivery_method_id"', false);
    }

    /* ─────────────── Что принимает сервер ─────────────── */

    public function test_услуга_оформляется_без_доставки_адреса_и_города(): void
    {
        $услуга = $this->услуга();
        $оплата = $this->оплата();
        $this->доставка();

        $ответ = $this->post(route('cart.checkout'), [
            'payment_method_id' => $оплата->id,
            'terms_agree'       => '1',
            'customer_name'     => 'Иван',
            'customer_phone'    => '+7 900 000-00-00',
            'items'             => [['id' => $услуга->id, 'qty' => 1]],
        ]);

        $ответ->assertSessionHasNoErrors();

        $заказ = Order::latest('id')->first();
        $this->assertNotNull($заказ, 'Заказ на услугу должен создаваться');
        $this->assertNull($заказ->delivery_method_id, 'Услуге служба доставки не назначается');
        $this->assertEquals(0.0, (float) $заказ->delivery_price, 'За услугу доставка не берётся');
        $this->assertEquals(2000.0, (float) $заказ->total);
        $this->assertNull($заказ->total_weight, 'У услуги веса нет по смыслу');
    }

    /**
     * 🔴 Присланная «на всякий случай» служба не должна попадать в заказ.
     *
     * Иначе покупателя, купившего один приём, можно было бы обсчитать на
     * стоимость пересылки подделкой скрытого поля — или он сам выбрал бы
     * доставку, оставшуюся в форме от прошлого набора.
     */
    public function test_у_услуги_присланная_служба_доставки_отбрасывается(): void
    {
        $услуга   = $this->услуга();
        $оплата   = $this->оплата();
        $доставка = $this->доставка();

        $this->post(route('cart.checkout'), [
            'payment_method_id'  => $оплата->id,
            'delivery_method_id' => $доставка->id,   // подделка
            'terms_agree'        => '1',
            'customer_name'      => 'Иван',
            'customer_phone'     => '+7 900 000-00-00',
            'items'              => [['id' => $услуга->id, 'qty' => 1]],
        ])->assertSessionHasNoErrors();

        $заказ = Order::latest('id')->first();
        $this->assertNull($заказ->delivery_method_id);
        $this->assertEquals(0.0, (float) $заказ->delivery_price);
        $this->assertEquals(2000.0, (float) $заказ->total, 'Доставка не должна попасть в сумму');
    }

    /**
     * Обратная сторона: у настоящего товара доставку по-прежнему требуют.
     *
     * Без этой проверки достаточно было бы перестать слать
     * `delivery_method_id`, чтобы получить бесплатную пересылку.
     */
    public function test_у_товара_доставка_остаётся_обязательной(): void
    {
        $товар  = $this->товар();
        $оплата = $this->оплата();
        $this->доставка();

        $this->post(route('cart.checkout'), [
            'payment_method_id' => $оплата->id,
            'terms_agree'       => '1',
            'customer_name'     => 'Иван',
            'customer_phone'    => '+7 900 000-00-00',
            'items'             => [['id' => $товар->id, 'qty' => 1]],
        ])->assertSessionHasErrors(['delivery_method_id', 'customer_city', 'customer_address']);

        $this->assertNull(Order::latest('id')->first(), 'Заказ без доставки создаваться не должен');
    }

    public function test_у_товара_доставка_считается_как_прежде(): void
    {
        $товар    = $this->товар();
        $оплата   = $this->оплата();
        $доставка = $this->доставка();

        $this->post(route('cart.checkout'), [
            'payment_method_id'  => $оплата->id,
            'delivery_method_id' => $доставка->id,
            'terms_agree'        => '1',
            'customer_name'      => 'Иван',
            'customer_phone'     => '+7 900 000-00-00',
            'customer_city'      => 'Курск',
            'customer_address'   => 'ул. Ленина, 1',
            'items'              => [['id' => $товар->id, 'qty' => 1]],
        ])->assertSessionHasNoErrors();

        $заказ = Order::latest('id')->first();
        $this->assertEquals($доставка->id, $заказ->delivery_method_id);
        $this->assertEquals(350.0, (float) $заказ->delivery_price);
        $this->assertEquals(1350.0, (float) $заказ->total);
    }

    /**
     * Смешанный заказ: везут товар, услуга едет строкой в том же заказе.
     * Вес складывается ТОЛЬКО из известных — иначе услуга формально прошла бы
     * любое ограничение по весу.
     */
    public function test_в_смешанном_заказе_вес_только_от_товара(): void
    {
        $товар    = $this->товар(['weight' => 0.5]);
        $услуга   = $this->услуга();
        $оплата   = $this->оплата();
        $доставка = $this->доставка();

        $this->post(route('cart.checkout'), [
            'payment_method_id'  => $оплата->id,
            'delivery_method_id' => $доставка->id,
            'terms_agree'        => '1',
            'customer_name'      => 'Иван',
            'customer_phone'     => '+7 900 000-00-00',
            'customer_city'      => 'Курск',
            'customer_address'   => 'ул. Ленина, 1',
            'items'              => [
                ['id' => $товар->id,  'qty' => 2],
                ['id' => $услуга->id, 'qty' => 1],
            ],
        ])->assertSessionHasNoErrors();

        $заказ = Order::latest('id')->first();
        $this->assertEquals(1.0, (float) $заказ->total_weight, 'Вес — только две кружки по 0,5 кг');
        $this->assertEquals($доставка->id, $заказ->delivery_method_id);
    }
}
