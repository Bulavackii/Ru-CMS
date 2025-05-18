<?php

namespace Modules\Payments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Modules\Delivery\Models\DeliveryMethod;

class Order extends Model
{
    /**
     * 🧾 Массово заполняемые поля
     */
    protected $fillable = [
        'user_id',             // 👤 Пользователь (если авторизован)
        'payment_method_id',   // 💳 Метод оплаты
        'delivery_method_id',  // 🚚 Метод доставки
        'total',               // 💰 Общая сумма заказа
        'status',              // 📦 Статус заказа (pending, completed и т.д.)
    ];

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
}
