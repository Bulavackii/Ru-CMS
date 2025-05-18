<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 🛍️ Product
 *
 * Модель товара, используемая в e-commerce разделе системы.
 *
 * Содержит:
 * - название, описание, цену
 */
class Product extends Model
{
    /**
     * 🔓 Разрешённые к массовому заполнению поля (fillable)
     *
     * - name        — название товара (например: "Ноутбук Lenovo")
     * - description — описание товара (HTML или текст)
     * - price       — цена товара (в копейках или рублях, зависит от реализации)
     */
    protected $fillable = [
        'name',
        'description',
        'price',
    ];
}
