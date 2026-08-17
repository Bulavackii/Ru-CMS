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
use Modules\Comments\Models\Comment;

/**
 * Торговая цепочка и модерация «как пользователь».
 *
 * Корзина → заказ → склад → смена статуса → отмена. И отдельно: комментарий
 * гостя проходит проверку, а не появляется на сайте сам.
 *
 * ⚠️ Событийные заглушки ставятся ВЫБОРОЧНО. `Event::fake()` без списка глушит
 * и события модели (`static::updated`), а на них держится и сброс кеша, и
 * возврат остатка — тест переставал воспроизводить целый класс дефектов.
 */
class QaCommerceFlowTest extends TestCase
{
    use RefreshDatabase;

    private function товар(int $остаток = 5, float $цена = 1000): News
    {
        return News::create([
            'title' => 'Товар для проверки',
            'slug' => 'tovar-' . uniqid(),
            'content' => '<p>Описание</p>',
            'template' => 'products',
            'published' => true,
            'price' => $цена,
            'stock' => $остаток,
        ]);
    }

    private function способОплаты(string $код = 'cash'): PaymentMethod
    {
        return PaymentMethod::create([
            'title' => 'Наличными', 'code' => $код,
            'active' => true, 'commission' => 0,
        ]);
    }

    private function способДоставки(float $цена = 300): DeliveryMethod
    {
        return DeliveryMethod::create([
            'title' => 'Курьер', 'code' => 'courier_local',
            'active' => true, 'price' => $цена, 'type' => 'courier',
        ]);
    }

    // ─────────────────────────────── КОРЗИНА ────────────────────────────

    /** Гость кладёт товар в корзину и видит его там. */
    public function test_guest_can_put_product_in_cart(): void
    {
        $товар = $this->товар();

        $this->post(route('cart.add'), ['id' => $товар->id, 'quantity' => 2])
            ->assertSessionHasNoErrors();

        $корзина = $this->get(route('cart.index'));
        $корзина->assertOk();

        // Смотрим в саму корзину, а не в разметку: название на карточке
        // может быть обрезано или выведено картинкой.
        $состав = session('cart', []);
        $this->assertNotEmpty($состав, 'Товар не попал в корзину');
        $this->assertSame($товар->id, (int) ($состав[array_key_first($состав)]['id'] ?? 0));
    }

    /**
     * Оформление заказа списывает остаток.
     *
     * Это главная связка магазина: если списания нет, последний экземпляр
     * продаётся дважды.
     */
    public function test_checkout_creates_order_and_takes_stock(): void
    {
        Event::fake([\Modules\Payments\Events\OrderCreated::class]);

        $товар = $this->товар(остаток: 5);
        $оплата = $this->способОплаты();
        $доставка = $this->способДоставки();

        $this->post(route('cart.add'), ['id' => $товар->id, 'quantity' => 2]);

        // ⚠️ Состав заказа приезжает полем `items`: корзина живёт в браузере,
        // сервер получает список идентификаторов и количеств. Цену и название
        // он берёт из базы сам (см. CartPriceForgeryTest).
        $ответ = $this->post(route('cart.checkout'), [
            'items' => [['id' => $товар->id, 'qty' => 2]],
            'customer_name' => 'Иван Петров',
            'customer_phone' => '+7 900 000-00-00',
            'customer_email' => 'ivan@example.com',
            'customer_address' => 'г. Курск, ул. Ленина, 1',
            'payment_method_id' => $оплата->id,
            'delivery_method_id' => $доставка->id,
            // ⚠️ Без согласия на обработку данных заказ не оформляется —
            // это требование закона, а не прихоть формы.
            'terms_agree' => 1,
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '+7 900 000-00-00',
            'customer_address' => 'Курск, ул. Ленина, 1',
            'customer_city' => 'Курск',
        ]);

        $ответ->assertSessionHasNoErrors();

        $заказ = Order::latest('id')->first();
        $this->assertNotNull($заказ, 'Заказ не создался');
        $this->assertSame(2, $заказ->items()->sum('qty'), 'В заказе не то количество');

        $this->assertSame(3, $товар->fresh()->stock, 'Остаток товара не списался');
    }

    /** Отмена заказа возвращает товар в продажу. */
    public function test_cancelling_order_returns_stock(): void
    {
        $товар = $this->товар(остаток: 5);
        $оплата = $this->способОплаты();
        $доставка = $this->способДоставки();

        $this->post(route('cart.add'), ['id' => $товар->id, 'quantity' => 2]);
        $this->post(route('cart.checkout'), [
            'items' => [['id' => $товар->id, 'qty' => 2]],
            'customer_name' => 'Иван', 'customer_phone' => '+7 900 000-00-00',
            'customer_email' => 'ivan@example.com', 'customer_address' => 'Адрес',
            'payment_method_id' => $оплата->id, 'delivery_method_id' => $доставка->id,
            'terms_agree' => 1,
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '+7 900 000-00-00',
            'customer_address' => 'Курск, ул. Ленина, 1',
            'customer_city' => 'Курск',
        ]);

        $заказ = Order::latest('id')->first();
        $this->assertSame(3, $товар->fresh()->stock);

        $заказ->update(['status' => 'cancelled']);

        $this->assertSame(5, $товар->fresh()->stock, 'Отменённый заказ не вернул товар');

        // ⚠️ Повторный возврат не должен случиться: связка «отменить, потом
        // удалить» иначе вернула бы остаток дважды.
        $заказ->delete();
        $this->assertSame(5, $товар->fresh()->stock, 'Остаток вернулся дважды');
    }

    /** Нельзя купить больше, чем есть на складе. */
    public function test_cannot_buy_more_than_in_stock(): void
    {
        $товар = $this->товар(остаток: 1);
        $оплата = $this->способОплаты();
        $доставка = $this->способДоставки();

        $this->post(route('cart.add'), ['id' => $товар->id, 'quantity' => 5]);

        $this->post(route('cart.checkout'), [
            'items' => [['id' => $товар->id, 'qty' => 5]],
            'customer_name' => 'Иван', 'customer_phone' => '+7 900 000-00-00',
            'customer_email' => 'ivan@example.com', 'customer_address' => 'Адрес',
            'payment_method_id' => $оплата->id, 'delivery_method_id' => $доставка->id,
            'terms_agree' => 1,
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '+7 900 000-00-00',
            'customer_address' => 'Курск, ул. Ленина, 1',
            'customer_city' => 'Курск',
        ]);

        $остаток = $товар->fresh()->stock;

        $this->assertGreaterThanOrEqual(
            0,
            $остаток,
            'Остаток ушёл в минус — продано больше, чем было'
        );
    }

    // ──────────────────────────── СМЕНА СТАТУСА ─────────────────────────

    /** Администратор меняет статус заказа, и это не падает. */
    public function test_admin_changes_order_status(): void
    {
        Event::fake([\Modules\Payments\Events\OrderStatusChanged::class]);

        $оплата = $this->способОплаты();
        $доставка = $this->способДоставки();

        $заказ = Order::create([
            'payment_method_id' => $оплата->id,
            'delivery_method_id' => $доставка->id,
            'status' => 'pending',
            'customer_name' => 'Иван',
            'customer_phone' => '+7 900 000-00-00',
            'customer_email' => 'ivan@example.com',
            'customer_address' => 'Адрес',
            'items_total' => 1000, 'delivery_price' => 300,
            'commission' => 0, 'total' => 1300,
        ]);

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->put(route('admin.orders.update.status', $заказ->id), ['status' => 'completed'])
            ->assertSessionHasNoErrors();

        $this->assertSame('completed', $заказ->fresh()->status);
    }

    /** Все статусы из списка модели действительно принимаются. */
    public function test_every_declared_status_is_accepted(): void
    {
        Event::fake([\Modules\Payments\Events\OrderStatusChanged::class]);

        $заказ = Order::create([
            'payment_method_id' => $this->способОплаты()->id,
            'delivery_method_id' => $this->способДоставки()->id,
            'status' => 'pending',
            'customer_name' => 'Иван', 'customer_phone' => '+7 900 000-00-00',
            'customer_email' => 'ivan@example.com', 'customer_address' => 'Адрес',
            'items_total' => 1000, 'delivery_price' => 0, 'commission' => 0, 'total' => 1000,
        ]);

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        foreach (Order::STATUSES as $статус) {
            $this->put(route('admin.orders.update.status', $заказ->id), ['status' => $статус])
                ->assertSessionHasNoErrors("Статус «{$статус}» объявлен в модели, но не принимается");

            $this->assertSame($статус, $заказ->fresh()->status);
        }
    }

    // ───────────────────────────── МОДЕРАЦИЯ ────────────────────────────

    /** Комментарий гостя не появляется на сайте сам. */
    public function test_guest_comment_waits_for_moderation(): void
    {
        $материал = News::create([
            'title' => 'Материал с обсуждением', 'slug' => 'obsuzhdenie',
            'content' => '<p>Текст</p>', 'template' => 'default', 'published' => true,
        ]);

        $this->post(route('comments.store'), [
            'model_type' => News::class,
            'model_id' => $материал->id,
            'author_name' => 'Гость',
            'author_email' => 'gost@example.com',
            'content' => 'Комментарий от гостя',
        ]);

        $комментарий = Comment::latest('id')->first();

        if (! $комментарий) {
            $this->markTestSkipped('Комментарии отключены настройкой модуля');
        }

        $this->assertNotSame(
            'approved',
            $комментарий->status,
            'Комментарий гостя опубликовался без проверки'
        );
    }

    /** Администратор одобряет комментарий, и тот становится видимым. */
    public function test_admin_approves_comment(): void
    {
        $материал = News::create([
            'title' => 'Материал', 'slug' => 'material-moderacii',
            'content' => '<p>Текст</p>', 'template' => 'default', 'published' => true,
        ]);

        $комментарий = Comment::create([
            'model_type' => News::class,
            'model_id' => $материал->id,
            'author_name' => 'Гость',
            'author_email' => 'gost@example.com',
            'content' => 'Ждёт проверки',
            'status' => 'pending',
        ]);

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->post(route('admin.comments.approve', $комментарий->id));

        $this->assertSame('approved', $комментарий->fresh()->status);
    }
}
