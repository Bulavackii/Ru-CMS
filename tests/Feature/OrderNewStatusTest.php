<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Delivery\Models\DeliveryMethod;
use Modules\News\Models\News;
use Modules\Payments\Console\Commands\CancelUnpaidOrdersCommand;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\PaymentMethod;
use Tests\TestCase;

/**
 * Статус «Новый» — заказ, заведённый в панели руками.
 *
 * Его не было в `Order::STATUSES`, хотя контроллер панели именно его и
 * ставил. Следствия: подпись показывалась сырым кодом «new», отборы по
 * статусу такой заказ не находили, а выбрать его в списке смены статуса было
 * нельзя.
 *
 * ⚠️ И он НЕ равен `pending`. `pending` ставит корзина, и именно эти заказы
 * забирает автоотмена по сроку. Заказ, о котором владелец договорился с
 * покупателем лично, отменять по таймеру нельзя — поэтому статус отдельный,
 * а не слитый с `pending`.
 */
class OrderNewStatusTest extends TestCase
{
    use RefreshDatabase;

    private function способОплаты(string $тип = 'online'): PaymentMethod
    {
        return PaymentMethod::create([
            'title' => 'Онлайн', 'code' => 'yookassa',
            'active' => true, 'commission' => 0, 'type' => $тип,
        ]);
    }

    /**
     * Способ доставки один на весь тест.
     *
     * ⚠️ Заводить его внутри `заказ()` нельзя: код службы уникален, и второй
     * заказ падал на дубликате `courier_local`, а выглядело это как поломка
     * самого статуса.
     */
    private function способДоставки(): DeliveryMethod
    {
        return DeliveryMethod::firstOrCreate(
            ['code' => 'courier_local'],
            ['title' => 'Курьер', 'active' => true, 'price' => 0, 'type' => 'courier'],
        );
    }

    private function заказ(string $статус, ?PaymentMethod $оплата = null): Order
    {
        return Order::create([
            'payment_method_id' => ($оплата ?? $this->способОплаты())->id,
            'delivery_method_id' => $this->способДоставки()->id,
            'status' => $статус,
            'customer_name' => 'Иван', 'customer_phone' => '+7 900 000-00-00',
            'customer_email' => 'ivan@example.com', 'customer_address' => 'Адрес',
            'items_total' => 1000, 'delivery_price' => 0, 'commission' => 0, 'total' => 1000,
        ]);
    }

    /** У статуса есть человеческая подпись на обоих языках. */
    public function test_new_status_has_a_label(): void
    {
        $this->assertContains('new', Order::STATUSES, 'Статус «new» отсутствует в списке');

        foreach (['ru', 'en'] as $язык) {
            $подпись = __('admin.orders.st_new', [], $язык);

            $this->assertNotSame(
                'admin.orders.st_new',
                $подпись,
                "У статуса «new» нет подписи в словаре {$язык} — покажется сырой код"
            );
        }
    }

    /** Статус принимается при смене из панели. */
    public function test_new_status_is_accepted_by_the_panel(): void
    {
        Event::fake([\Modules\Payments\Events\OrderStatusChanged::class]);

        $заказ = $this->заказ('pending');

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->put(route('admin.orders.update.status', $заказ->id), ['status' => 'new'])
            ->assertSessionHasNoErrors();

        $this->assertSame('new', $заказ->fresh()->status);
    }

    /**
     * 🔴 Автоотмена по сроку НЕ трогает заказы, заведённые в панели.
     *
     * Иначе владелец договорился с покупателем по телефону, завёл заказ — и
     * через десять минут система его отменила, вернув товар в продажу.
     */
    public function test_timer_never_touches_manual_orders(): void
    {
        $онлайн = $this->способОплаты('online');

        $изПанели = $this->заказ('new', $онлайн);
        $изКорзины = $this->заказ('pending', $онлайн);

        // Состариваем оба заказа
        foreach ([$изПанели, $изКорзины] as $заказ) {
            $заказ->forceFill(['created_at' => now()->subHour()])->saveQuietly();
        }

        $подОтмену = CancelUnpaidOrdersCommand::просроченные()->pluck('id')->all();

        $this->assertContains($изКорзины->id, $подОтмену, 'Заказ из корзины должен отменяться по сроку');
        $this->assertNotContains($изПанели->id, $подОтмену, 'Заказ из панели попал под автоотмену');
    }

    /**
     * Форма заведения заказа открывается и несёт все нужные поля.
     *
     * ⚠️ Тест на сохранение этого НЕ заменяет: он шлёт запрос напрямую и
     * проходит даже при отсутствующей форме — ровно так уже было с ценой
     * материала (см. CLAUDE.md, «Правка материалов в редакторе»).
     */
    public function test_manual_order_form_opens(): void
    {
        News::create([
            'title' => 'Товар для формы', 'slug' => 'tovar-dlya-formy',
            'content' => '<p>x</p>', 'template' => 'products',
            'published' => true, 'price' => 500, 'stock' => 3,
        ]);

        $this->способОплаты();
        $this->способДоставки();

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $ответ = $this->get(route('admin.orders.create'))->assertOk();

        foreach (['customer_name', 'payment_method_id', 'delivery_method_id'] as $поле) {
            $ответ->assertSee('name="' . $поле . '"', false);
        }

        // ⚠️ Состав собирается скриптом из `@js(...)`, а она экранирует
        // кириллицу в `\uXXXX` — искать название в разметке бесполезно.
        // Проверяем сами данные вьюхи.
        $ответ->assertViewHas('товары', fn ($список) => $список->contains('title', 'Товар для формы'));
    }

    /** Заказ, заведённый в панели, действительно получает этот статус. */
    public function test_panel_creates_order_with_new_status(): void
    {
        Event::fake([\Modules\Payments\Events\OrderCreated::class]);

        $товар = News::create([
            'title' => 'Товар', 'slug' => 'tovar-ruchnoy',
            'content' => '<p>x</p>', 'template' => 'products',
            'published' => true, 'price' => 500, 'stock' => 3,
        ]);

        $оплата = $this->способОплаты();

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->post(route('admin.orders.store'), [
            'items' => [['id' => $товар->id, 'qty' => 2]],
            'payment_method_id' => $оплата->id,
            'customer_name' => 'Пётр Петров',
            'customer_phone' => '+7 900 111-22-33',
        ])->assertSessionHasNoErrors();

        $заказ = Order::latest('id')->first();

        $this->assertNotNull($заказ, 'Заказ из панели не создался');
        $this->assertSame('new', $заказ->status);
        $this->assertSame('Пётр Петров', $заказ->customer_name, 'Покупатель не сохранился');

        // Суммы считает СЕРВЕР по ценам из базы: раньше метод их не считал
        // вовсе и заказ выходил с нулём в итоге.
        $this->assertEqualsWithDelta(1000.0, (float) $заказ->items_total, 0.01, 'Сумма товаров не посчитана');
        $this->assertEqualsWithDelta(1000.0, (float) $заказ->total, 0.01, 'Итог заказа не посчитан');

        // И остаток списан
        $this->assertSame(1, $товар->fresh()->stock, 'Остаток не списался');

        // И подпись у него человеческая, а не сырой код
        $this->assertArrayHasKey('new', Order::statusLabels());
    }
}
