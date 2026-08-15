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
 * Согласие на обработку персональных данных при оформлении заказа.
 *
 * Раньше под кнопкой стояла строка «Нажимая кнопку, вы соглашаетесь…» —
 * это уведомление, а не согласие: покупатель ничего не отмечал, и
 * подтвердить его волю было нечем. Теперь отметка обязательна, а проверка
 * живёт на сервере: атрибут required снимается из инструментов
 * разработчика за секунду.
 *
 * По оформлению заказа тестов не было ВООБЩЕ — отсюда и то, что пробел
 * дожил до просьбы владельца.
 */
class CartConsentTest extends TestCase
{
    use RefreshDatabase;

    private function товар(): News
    {
        return News::create([
            'title'     => 'Настольная лампа',
            'slug'      => 'tovar-lampa-test',
            'content'   => 'Три уровня яркости.',
            'template'  => 'products',
            'published' => true,
            'price'     => 2790,
            'stock'     => 5,
        ]);
    }

    private function способы(): array
    {
        return [
            PaymentMethod::create([
                'title' => 'Наличными', 'code' => 'cash', 'type' => 'cash', 'active' => true,
            ]),
            DeliveryMethod::create([
                'title' => 'Курьер', 'code' => 'courier', 'type' => 'courier',
                'price' => 300, 'active' => true,
            ]),
        ];
    }

    /** Тело запроса на оформление — без отметки согласия. */
    private function заказ(News $товар, PaymentMethod $оплата, DeliveryMethod $доставка): array
    {
        return [
            'payment_method_id'  => $оплата->id,
            'delivery_method_id' => $доставка->id,
            'items' => [[
                'id' => $товар->id, 'title' => $товар->title,
                'price' => $товар->price, 'qty' => 1,
            ]],
        ];
    }

    public function test_order_is_rejected_without_consent(): void
    {
        Event::fake();
        $товар = $this->товар();
        [$оплата, $доставка] = $this->способы();

        $ответ = $this->post(route('cart.checkout'), $this->заказ($товар, $оплата, $доставка));

        $ответ->assertSessionHasErrors('terms_agree');

        // Главное — заказа не появилось. Проверять только код ответа мало:
        // редирект отдаётся и при успехе.
        $this->assertSame(0, Order::count());

        // И остаток товара не тронут.
        $this->assertSame(5, $товар->fresh()->stock);
    }

    public function test_order_goes_through_with_consent(): void
    {
        Event::fake();
        $товар = $this->товар();
        [$оплата, $доставка] = $this->способы();

        $ответ = $this->post(
            route('cart.checkout'),
            $this->заказ($товар, $оплата, $доставка) + ['terms_agree' => '1']
        );

        $ответ->assertSessionHasNoErrors();
        $this->assertSame(1, Order::count());
        $this->assertSame(4, $товар->fresh()->stock);
    }

    /**
     * Значение «нет» тоже должно отвергаться: правило accepted принимает
     * только 1/true/on/yes, но браузеры и старые формы шлют разное.
     */
    public function test_falsy_consent_is_not_accepted(): void
    {
        Event::fake();
        $товар = $this->товар();
        [$оплата, $доставка] = $this->способы();

        foreach (['0', 'false', 'off', ''] as $значение) {
            $ответ = $this->post(
                route('cart.checkout'),
                $this->заказ($товар, $оплата, $доставка) + ['terms_agree' => $значение]
            );
            $ответ->assertSessionHasErrors('terms_agree');
        }

        $this->assertSame(0, Order::count());
    }

    /**
     * Ссылки ведут на страницы, которые правятся в редакторе панели.
     * Битая ссылка в согласии — это согласие ни с чем.
     */
    public function test_consent_links_point_to_editable_pages(): void
    {
        $товар = $this->товар();
        $this->способы();
        session(['cart' => [$товар->id => [
            'id' => $товар->id, 'title' => $товар->title, 'price' => $товар->price, 'qty' => 1,
        ]]]);

        $ответ = $this->withSession(['app_locale' => 'ru'])->get(route('cart.index'));

        $ответ->assertOk();
        $ответ->assertSee('name="terms_agree"', false);
        $ответ->assertSee('required', false);
        $ответ->assertSee(url('/terms'), false);
        $ответ->assertSee(url('/privacy'), false);
    }
}
