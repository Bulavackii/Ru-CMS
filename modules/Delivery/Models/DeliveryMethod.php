<?php

namespace Modules\Delivery\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryMethod extends Model
{
    use HasFactory;

    /**
     * Значение «все регионы» в списке regions. Это идентификатор, который
     * лежит в базе, а не подпись: переводить его нельзя — на другом языке
     * в базу писалась бы другая строка, и сравнение перестало бы работать.
     * Подпись для интерфейса — в admin.delivery.all_regions.
     */
    public const ALL_REGIONS = 'Все регионы РФ';

    /**
     * Фирменные цвета и значки служб доставки — одно место на весь
     * раздел: список, форма и всё, что появится позже.
     *
     * Ключ — КОД службы: он же имя файла логотипа и он же идентификатор
     * драйвера в калькуляторе. Цвета сняты с самих знаков, а не подобраны
     * на глаз (Почта — синий подложки, Boxberry — малиновый, СДЭК —
     * зелёный шара, Яндекс — красный кружка «Я»).
     */
    public const BRANDS = [
        'pochta'          => ['color' => '#004B9C', 'icon' => 'fa-envelope'],
        'cdek'            => ['color' => '#8CC63F', 'icon' => 'fa-truck-fast'],
        'boxberry'        => ['color' => '#C0004A', 'icon' => 'fa-box'],
        'yandex_delivery' => ['color' => '#FC3F1D', 'icon' => 'fa-motorcycle'],
        'pickup'          => ['color' => '#0EA5E9', 'icon' => 'fa-store'],
        'courier_local'   => ['color' => '#6366F1', 'icon' => 'fa-person-biking'],
    ];

    /**
     * Запасной набор по ТИПУ — для служб, которые владелец завёл сам.
     * Без него своя служба выглядела бы бесцветной заготовкой рядом с
     * фирменными карточками.
     */
    public const TYPE_BRANDS = [
        'post'     => ['color' => '#0EA5E9', 'icon' => 'fa-envelope'],
        'courier'  => ['color' => '#6366F1', 'icon' => 'fa-truck-fast'],
        'terminal' => ['color' => '#7C3AED', 'icon' => 'fa-store'],
        'pickup'   => ['color' => '#64748B', 'icon' => 'fa-person-walking-luggage'],
    ];

    /**
     * Где лежат логотипы служб: public/images/delivery/<код>.png|svg|webp.
     *
     * Файлы лежат в проекте, а не тянутся из сети: CMS обязана работать
     * без интернета (см. «Независимость от внешних служб» в CLAUDE.md).
     */
    public const LOGO_DIR = 'images/delivery';

    /**
     * Ссылка на логотип службы или null, если файла нет.
     *
     * Ищем сначала по КОДУ (pochta, cdek), потом по ТИПУ — так своя
     * служба владельца с типом post получит хотя бы общий знак.
     */
    public function logoUrl(): ?string
    {
        foreach ([$this->code, $this->type] as $name) {
            if (blank($name)) {
                continue;
            }

            foreach (['svg', 'png', 'webp'] as $ext) {
                $relative = self::LOGO_DIR . '/' . $name . '.' . $ext;
                $absolute = public_path($relative);

                if (is_file($absolute)) {
                    // Метка времени файла в адресе: заменённый логотип
                    // виден сразу, без чистки кеша браузера.
                    return asset($relative) . '?v=' . filemtime($absolute);
                }
            }
        }

        return null;
    }

    /**
     * Цвет, значок, цвет надписи поверх цвета и логотип — всё, что нужно
     * карточке службы.
     *
     * @return array{color: string, icon: string, ink: string, logo: ?string}
     */
    public function brand(): array
    {
        $brand = self::BRANDS[$this->code]
            ?? self::TYPE_BRANDS[$this->type]
            ?? ['color' => '#6366F1', 'icon' => 'fa-truck'];

        // Цвета служб очень разной яркости: на светло-зелёном СДЭК белая
        // надпись нечитаема, на тёмно-синей Почте — наоборот.
        $brand['ink'] = readable_ink($brand['color']);
        $brand['logo'] = $this->logoUrl();

        return $brand;
    }


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
        'docs_url',               // 📖 Где владельцу взять ключи API этой службы
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
        // !== null, а не truthy-проверка: 0 — легитимное значение
        // (например, доставка "в тот же день"), но falsy в PHP.
        if ($this->min_days !== null && $this->max_days !== null) {
            return "{$this->min_days}-{$this->max_days} дн.";
        }
        if ($this->min_days !== null) {
            return "от {$this->min_days} дн.";
        }
        if ($this->max_days !== null) {
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

        if (in_array(self::ALL_REGIONS, $this->regions, true)) {
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
            self::ALL_REGIONS,
        ];
    }
}

