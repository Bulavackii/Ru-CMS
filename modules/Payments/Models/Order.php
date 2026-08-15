<?php

namespace Modules\Payments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Modules\Delivery\Models\DeliveryMethod;
use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;

class Order extends Model
{
    use HasFactory;


    /**
     * 🧾 Массово заполняемые поля
     */
    protected $fillable = [
        'user_id',             // 👤 Пользователь (если авторизован)
        'payment_method_id',   // 💳 Метод оплаты
        'payment_id',          // 💳 ID платежа в платежной системе
        'delivery_method_id',  // 🚚 Метод доставки
        'total',               // 💰 Общая сумма заказа (с доставкой и комиссией)
        'items_total',         // 💰 Сумма товаров
        'delivery_price',      // 💰 Стоимость доставки
        'commission',          // 💰 Комиссия платежной системы
        'status',              // 📦 Статус заказа (pending, completed и т.д.)
        'is_new',              // 🆕 Новый заказ (для админки)
        'customer_name',       // 📛 Имя клиента
        'customer_phone',      // 📞 Телефон клиента
        'customer_email',      // 📧 Email клиента
        'customer_address',    // 🏠 Адрес доставки
        'comment',             // 💬 Комментарий к заказу
        'stock_returned_at',   // 🔄 Когда остатки по заказу вернули на склад
        'cancel_reason',       // ❌ Почему отменён (напр. не оплачен в срок)
    ];

    /**
     * 🧠 Преобразования типов
     */
    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'items_total' => 'decimal:2',
            'delivery_price' => 'decimal:2',
            'commission' => 'decimal:2',
            'is_new' => 'boolean',
            'stock_returned_at' => 'datetime',
        ];
    }

    /**
     * Вернуть на склад товары этого заказа. Ровно ОДИН раз.
     *
     * Возврат жил только в удалении заказа: отменённый заказ списывал товар
     * навсегда, и склад молча худел с каждой отменой. Теперь его зовут и
     * отмена, и удаление — а отметка `stock_returned_at` не даёт вернуть
     * дважды, когда заказ сначала отменили, а потом удалили.
     *
     * ⚠️ Остаток NULL — это «не считаем», а не «ноль»: у услуг и материалов
     * без склада поле пустое, и наращивать там нечего.
     *
     * @return bool Возврат выполнен именно сейчас
     */
    public function returnStockOnce(): bool
    {
        if ($this->stock_returned_at !== null) {
            return false;
        }

        foreach ($this->items as $item) {
            $product = \Modules\News\Models\News::find($item->product_id);

            if ($product && $product->stock !== null) {
                $product->increment('stock', $item->qty);
            }
        }

        // Отметка ставится даже когда возвращать было нечего: заказ обработан,
        // и повторный проход по нему ничего изменить не должен.
        $this->forceFill(['stock_returned_at' => now()])->save();

        return true;
    }

    /**
     * 📦 Элементы заказа (товары)
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * 💳 Метод оплаты
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * 👤 Пользователь, оформивший заказ
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 🚚 Метод доставки
     */
    public function deliveryMethod(): BelongsTo
    {
        return $this->belongsTo(DeliveryMethod::class);
    }

    /**
     * 📊 Форматирование общей суммы
     */
    public function getFormattedTotalAttribute()
    {
        return number_format($this->total, 2, ',', ' ') . ' ₽';
    }

    /**
     * 📊 Форматирование суммы товаров
     */
    public function getFormattedItemsTotalAttribute()
    {
        return number_format($this->items_total, 2, ',', ' ') . ' ₽';
    }

    /**
     * 📊 Форматирование стоимости доставки
     */
    public function getFormattedDeliveryPriceAttribute()
    {
        return number_format($this->delivery_price, 2, ',', ' ') . ' ₽';
    }

    /**
     * 📊 Форматирование комиссии
     */
    public function getFormattedCommissionAttribute()
    {
        return number_format($this->commission, 2, ',', ' ') . ' ₽';
    }

    /**
     * 🎨 Статус заказа с цветом
     */
    public function getStatusBadgeAttribute()
    {
        $statuses = [
            'pending' => '<span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded">⏳ В ожидании</span>',
            'processing' => '<span class="bg-blue-100 text-blue-800 px-2 py-1 rounded">🔄 В обработке</span>',
            'completed' => '<span class="bg-green-100 text-green-800 px-2 py-1 rounded">✅ Завершен</span>',
            'cancelled' => '<span class="bg-red-100 text-red-800 px-2 py-1 rounded">❌ Отменен</span>',
        ];
        return $statuses[$this->status] ?? '<span class="bg-gray-100 text-gray-800 px-2 py-1 rounded">❓ Неизвестно</span>';
    }

    /**
     * Boot метод для событий модели
     */
    /**
     * Статусы заказа — единственный источник на весь модуль.
     *
     * Раньше набор был описан трижды (обе вьюхи и правило валидации) и
     * успел разойтись: панель предлагала «Оплачен», а валидация его не
     * принимала — сохранить такой статус было невозможно.
     *
     * Значения — идентификаторы в базе, переводить их нельзя; подписи
     * лежат в admin.orders.st_<статус>.
     */
    public const STATUSES = ['pending', 'paid', 'processing', 'completed', 'cancelled'];

    /**
     * Подписи статусов для выпадающих списков и плашек.
     *
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        $labels = [];

        foreach (self::STATUSES as $status) {
            $labels[$status] = __('admin.orders.st_' . $status);
        }

        return $labels;
    }

    protected static function boot(): void
    {
        parent::boot();

        // Событие создания заказа
        static::created(function ($order) {
            // Загружаем связи для уведомлений
            $order->load(['items', 'user', 'paymentMethod', 'deliveryMethod']);
            event(new OrderCreated($order));
        });

        // Событие изменения статуса.
        //
        // ⚠️ Прежний вариант складывал старый статус в `$order->old_status`.
        // У модели нет такого свойства, поэтому присваивание заводило
        // АТРИБУТ, и Eloquent добавлял его в UPDATE: «столбец old_status
        // не существует», 500 на КАЖДОЙ смене статуса из панели.
        //
        // Ничего запоминать и не нужно: syncOriginal() выполняется уже
        // после события updated, так что getOriginal() здесь ещё отдаёт
        // значение до сохранения, а wasChanged() — факт изменения.
        static::updated(function ($order) {
            if (! $order->wasChanged('status')) {
                return;
            }

            // Загружаем связи для уведомлений
            $order->load(['items', 'user', 'paymentMethod', 'deliveryMethod']);

            event(new OrderStatusChanged(
                $order,
                (string) $order->getOriginal('status'),
                (string) $order->status,
            ));
        });
    }
}
