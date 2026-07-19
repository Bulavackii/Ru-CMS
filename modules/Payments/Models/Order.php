<?php

namespace Modules\Payments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Modules\Delivery\Models\DeliveryMethod;
use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;

class Order extends Model
{
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
        ];
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
    protected static function boot(): void
    {
        parent::boot();

        // Событие создания заказа
        static::created(function ($order) {
            // Загружаем связи для уведомлений
            $order->load(['items', 'user', 'paymentMethod', 'deliveryMethod']);
            event(new OrderCreated($order));
        });

        // Событие изменения статуса
        static::updating(function ($order) {
            if ($order->isDirty('status')) {
                $order->old_status = $order->getOriginal('status');
            }
        });

        static::updated(function ($order) {
            if (isset($order->old_status) && $order->old_status !== $order->status) {
                // Загружаем связи для уведомлений
                $order->load(['items', 'user', 'paymentMethod', 'deliveryMethod']);
                event(new OrderStatusChanged($order, $order->old_status, $order->status));
                unset($order->old_status);
            }
        });
    }
}
