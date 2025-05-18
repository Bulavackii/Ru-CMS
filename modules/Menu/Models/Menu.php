<?php

namespace Modules\Menu\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 📦 Модель Menu
 *
 * 🔹 Представляет навигационное меню (хедер, футер и т.д.)
 * 🔹 Связана с пунктами меню (MenuItem)
 */
class Menu extends Model
{
    // 🗂️ Название таблицы в базе данных
    protected $table = 'menus';

    // ✅ Разрешённые для массового заполнения поля
    protected $fillable = [
        'title',     // 🏷️ Название меню
        'position',  // 📍 Позиция (header, footer, sidebar и т.д.)
        'active',    // ✅ Флаг активности
    ];

    /**
     * 📑 Метод items()
     *
     * 🔁 Связь "одно-ко-многим" с пунктами меню
     * Возвращает все пункты меню, отсортированные по полю order
     *
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('order');
    }
}
