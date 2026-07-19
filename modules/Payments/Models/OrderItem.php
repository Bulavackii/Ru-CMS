<?php

namespace Modules\Payments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\News\Models\News;

class OrderItem extends Model
{
    use HasFactory;


    /**
     * 🧾 Разрешённые поля для массового заполнения
     */
    protected $fillable = [
        'order_id',    // 🔗 Связь с заказом
        'product_id',  // 🛍️ ID продукта (из таблицы news)
        'title',       // 📘 Название товара (на момент покупки)
        'price',       // 💵 Цена за единицу
        'qty',         // 🔢 Количество
    ];

    /**
     * 🔗 Связь с заказом
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * 🛍️ Связь с товаром (новостью с шаблоном products)
     */
    public function product()
    {
        return $this->belongsTo(News::class, 'product_id');
    }
}
