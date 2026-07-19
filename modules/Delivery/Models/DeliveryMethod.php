<?php

namespace Modules\Delivery\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryMethod extends Model
{
    /**
     * 📦 Указываем, какие поля можно массово заполнять (mass assignment).
     * Это нужно для методов вроде create() и update().
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',                  // 🏷️ Название метода доставки (например, "Курьером", "Почта России")
        'description',            // 📝 Подробное описание (можно оставить пустым)
        'price',                  // 💰 Стоимость доставки в рублях (например, 300.00)
        'active',                 // ✅ Флаг активности (true — доступен, false — скрыт)
        'code',                   // 🔑 Уникальный код службы (например: cdek, pek, boxberry, pochta)
        'is_russian',             // 🇷🇺 Флаг российской службы доставки
        'api_enabled',            // 🌐 Включена ли API интеграция
        'api_settings',           // ⚙️ Настройки API (JSON)
        'type',                   // 🚚 Тип доставки: courier, pickup, post, terminal
        'min_days',               // 📅 Минимальные сроки доставки (дни)
        'max_days',               // 📅 Максимальные сроки доставки (дни)
        'weight_limit',           // ⚖️ Ограничение по весу (кг)
        'regions',                // 🗺️ Доступные регионы (JSON массив)
        'free_delivery_threshold', // 🎁 Порог суммы заказа для бесплатной доставки (₽)
        'sort_order',             // 🔢 Порядок сортировки
    ];

    /**
     * 🧠 Преобразования типов
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'active' => 'boolean',
            'is_russian' => 'boolean',
            'api_enabled' => 'boolean',
            'api_settings' => 'array',
            'min_days' => 'integer',
            'max_days' => 'integer',
            'weight_limit' => 'decimal:2',
            'regions' => 'array',
            'free_delivery_threshold' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    /**
     * 🇷🇺 Скоуп для российских служб доставки
     */
    public function scopeRussian($query)
    {
        return $query->where('is_russian', true);
    }

    /**
     * 🌐 Скоуп для служб с API интеграцией
     */
    public function scopeWithApi($query)
    {
        return $query->where('api_enabled', true);
    }

    /**
     * 📦 Скоуп для активных служб
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * 🔑 Скоуп по коду службы
     */
    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    /**
     * 🚚 Скоуп по типу доставки
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * 📦 Форматирование цены для отображения
     */
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 2, ',', ' ') . ' ₽';
    }

    /**
     * 📅 Форматирование сроков доставки
     */
    public function getDeliveryDaysAttribute()
    {
        if ($this->min_days && $this->max_days) {
            return "{$this->min_days}-{$this->max_days} дн.";
        }
        if ($this->min_days) {
            return "от {$this->min_days} дн.";
        }
        if ($this->max_days) {
            return "до {$this->max_days} дн.";
        }
        return '—';
    }

    /**
     * 🎁 Проверка доступности бесплатной доставки
     */
    public function isFreeDeliveryAvailable(float $orderTotal): bool
    {
        return $this->free_delivery_threshold > 0 && $orderTotal >= $this->free_delivery_threshold;
    }

    /**
     * 🗺️ Проверка доступности в регионе
     */
    public function isAvailableInRegion(?string $region): bool
    {
        if (empty($this->regions)) {
            return true;
        }

        if (in_array('Все регионы РФ', $this->regions)) {
            return true;
        }

        return in_array($region, $this->regions);
    }

    /**
     * ⚖️ Проверка ограничения по весу
     */
    public function isWeightAllowed(float $weight): bool
    {
        if (!$this->weight_limit) {
            return true;
        }

        return $weight <= $this->weight_limit;
    }

    /**
     * 📦 Получить список российских регионов (статический метод)
     */
    public static function getRussianRegions(): array
    {
        return [
            'Москва',
            'Санкт-Петербург',
            'Московская область',
            'Ленинградская область',
            'Новосибирская область',
            'Екатеринбург',
            'Казань',
            'Нижний Новгород',
            'Челябинск',
            'Самара',
            'Омск',
            'Ростов-на-Дону',
            'Уфа',
            'Красноярск',
            'Воронеж',
            'Пермь',
            'Волгоград',
            'Краснодар',
            'Саратов',
            'Тюмень',
            'Тольятти',
            'Ижевск',
            'Барнаул',
            'Ульяновск',
            'Иркутск',
            'Хабаровск',
            'Ярославль',
            'Владивосток',
            'Махачкала',
            'Томск',
            'Оренбург',
            'Кемерово',
            'Новокузнецк',
            'Рязань',
            'Астрахань',
            'Набережные Челны',
            'Пенза',
            'Липецк',
            'Киров',
            'Чебоксары',
            'Калининград',
            'Тула',
            'Курск',
            'Сочи',
            'Ставрополь',
            'Улан-Удэ',
            'Магнитогорск',
            'Тверь',
            'Иваново',
            'Брянск',
            'Все регионы РФ',
        ];
    }
}

