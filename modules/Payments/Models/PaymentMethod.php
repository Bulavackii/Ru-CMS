<?php

namespace Modules\Payments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ?? Модель способа оплаты
 *
 * Хранит информацию о доступных методах оплаты:
 * - Название (например, "Картой онлайн")
 * - Описание (подробности, как работает)
 * - Тип (онлайн / оффлайн / СБП / ЮKassa и т.д.)
 * - Активность (включён или выключен)
 * - Настройки (в виде массива)
 * - Код платежной системы (уникальный идентификатор)
 * - Флаг российской платежной системы
 */
class PaymentMethod extends Model
{
    use HasFactory;

    // ?? Название таблицы в БД
    protected $table = 'payment_methods';

    /**
     * 🎨 Оформление метода: фирменный цвет и значок.
     *
     * Цвета — корпоративные, публично известные: это факт, а не товарный
     * знак. Сами ЛОГОТИПЫ здесь намеренно не воспроизводятся: это чужие
     * знаки, их нельзя просто нарисовать по памяти, а качать в рантайме
     * проекту нельзя — он работает и без интернета (см. «Независимость от
     * внешних служб» в CLAUDE.md). Вместо логотипа — значок, который
     * говорит о СПОСОБЕ оплаты: карта, QR, телефон, наличные, перевод.
     *
     * Цвет надписи на плитке не задаётся руками: его считает readable_ink()
     * по яркости фона. Иначе на жёлтом Т-Банке белые буквы дали бы
     * контраст около 1.5.
     */
    public const BRANDS = [
        'yookassa'      => ['color' => '#8B3FFD', 'icon' => 'fa-wallet'],
        'sbp'           => ['color' => '#5B2D8E', 'icon' => 'fa-qrcode'],
        'sberpay'       => ['color' => '#21A038', 'icon' => 'fa-mobile-screen-button'],
        'sberbank'      => ['color' => '#21A038', 'icon' => 'fa-credit-card'],
        'tbank'         => ['color' => '#FFDD2D', 'icon' => 'fa-credit-card'],
        'tinkoff'       => ['color' => '#FFDD2D', 'icon' => 'fa-credit-card'],
        'cloudpayments' => ['color' => '#0091FF', 'icon' => 'fa-cloud'],
        'robokassa'     => ['color' => '#12B34A', 'icon' => 'fa-robot'],
        'qiwi'          => ['color' => '#FF8C00', 'icon' => 'fa-wallet'],
        'online'        => ['color' => '#6366F1', 'icon' => 'fa-credit-card'],
        'offline'       => ['color' => '#64748B', 'icon' => 'fa-money-bill-wave'],
        'transfer'      => ['color' => '#0EA5E9', 'icon' => 'fa-building-columns'],
    ];

    /**
     * Где лежат логотипы платёжных систем.
     *
     * Файл кладётся руками: public/images/payments/<тип>.svg (или .png).
     * Свои знаки эти системы публикуют сами — качать их в рантайме проекту
     * нельзя, он работает и без интернета. Пока файла нет, на плитке
     * остаётся значок способа оплаты, и раздел выглядит законченно.
     */
    public const LOGO_DIR = 'images/payments';

    /**
     * Логотип метода, если файл положен; иначе null.
     *
     * Сначала ищем по КОДУ, потом по типу. Наличные и банковский перевод
     * заведены одним типом `offline`, но знак у них разный — по типу их не
     * различить, а код у каждого свой.
     */
    public function logoUrl(): ?string
    {
        foreach ([$this->code, $this->type] as $key) {
            if (blank($key)) {
                continue;
            }

            foreach (['svg', 'png', 'webp'] as $ext) {
                $relative = self::LOGO_DIR . '/' . $key . '.' . $ext;

                if (is_file(public_path($relative))) {
                    // Метка времени в адресе: заменили файл — браузер увидит
                    // новый, а не свою старую копию.
                    return asset($relative) . '?v=' . filemtime(public_path($relative));
                }
            }
        }

        return null;
    }

    /** Оформление этого метода; для незнакомого типа — нейтральное. */
    public function brand(): array
    {
        $brand = self::BRANDS[$this->type] ?? ['color' => '#6366F1', 'icon' => 'fa-credit-card'];

        // Банковский перевод заводится типом offline, но выглядеть как
        // наличные не должен: у него своё дело и свой значок.
        if ($this->type === 'offline' && str_contains(mb_strtolower((string) $this->title), 'перевод')) {
            $brand = self::BRANDS['transfer'];
        }

        $brand['ink'] = readable_ink($brand['color']);
        $brand['logo'] = $this->logoUrl();

        return $brand;
    }

    // ?? Разрешённые к массовому заполнению поля
    protected $fillable = [
        'title',        // ??? Название метода оплаты
        'description',  // ?? Краткое описание
        'type',         // ?? Тип: online / offline / sbp / yookassa / tinkoff / sberbank / sberpay / qiwi / robokassa / cloudpayments
        'active',       // ? Включён ли метод
        'settings',     // ?? Настройки в виде массива (JSON)
        'code',         // ?? Уникальный код платежной системы (например: sbp, yookassa)
        'is_russian',   // ???? Флаг российской платежной системы
        'commission',   // ?? Комиссия в процентах
        'min_amount',   // ?? Минимальная сумма платежа
        'max_amount',   // ?? Максимальная сумма платежа
        'currencies',   // ?? Поддерживаемые валюты (JSON массив)
        'test_mode',    // ?? Режим тестирования
        'sort_order',   // Порядок вывода в корзине
        'docs_url',     // Где владельцу взять ключи для этого метода
    ];

    // ?? Преобразования типов для работы как с массивами/булевыми
    protected function casts(): array
    {
        return [
            'settings' => 'array',   // ?? Преобразовать в массив автоматически
            'active' => 'boolean',   // ? Активность как true/false
            'is_russian' => 'boolean', // ???? Флаг российской системы
            'commission' => 'decimal:2', // ?? Комиссия
            'min_amount' => 'decimal:2', // ?? Минимальная сумма
            'max_amount' => 'decimal:2', // ?? Максимальная сумма
            'currencies' => 'array', // ?? Валюты
            'test_mode' => 'boolean', // ?? Тестовый режим
        ];
    }

    public const SETTINGS_FIELDS = [
        'inn',
        'bik',
        'account',
        'shop_id',
        'secret_key',
        'terminal_key',
        'api_key',
        'public_id',
        'bank_name',
        'kpp',
        'correspondent_account',
        'callback_url',
        'success_url',
        'fail_url',
        'sandbox',
        'webhook_url',
        'timeout',
        'retries',
        'shop_url',
    ];

    protected function getSettingValue(string $key)
    {
        $settings = $this->settings ?? [];
        if (!is_array($settings)) {
            return null;
        }

        return $settings[$key] ?? null;
    }

    public function getInnAttribute()
    {
        return $this->getSettingValue('inn');
    }

    public function getBikAttribute()
    {
        return $this->getSettingValue('bik');
    }

    public function getAccountAttribute()
    {
        return $this->getSettingValue('account');
    }

    public function getShopIdAttribute()
    {
        return $this->getSettingValue('shop_id');
    }

    public function getSecretKeyAttribute()
    {
        return $this->getSettingValue('secret_key');
    }

    public function getTerminalKeyAttribute()
    {
        return $this->getSettingValue('terminal_key');
    }

    public function getApiKeyAttribute()
    {
        return $this->getSettingValue('api_key');
    }

    public function getPublicIdAttribute()
    {
        return $this->getSettingValue('public_id');
    }

    public function getBankNameAttribute()
    {
        return $this->getSettingValue('bank_name');
    }

    public function getKppAttribute()
    {
        return $this->getSettingValue('kpp');
    }

    public function getCorrespondentAccountAttribute()
    {
        return $this->getSettingValue('correspondent_account');
    }

    public function getCallbackUrlAttribute()
    {
        return $this->getSettingValue('callback_url');
    }

    public function getSuccessUrlAttribute()
    {
        return $this->getSettingValue('success_url');
    }

    public function getFailUrlAttribute()
    {
        return $this->getSettingValue('fail_url');
    }

    public function getSandboxAttribute()
    {
        return $this->getSettingValue('sandbox');
    }

    public function getWebhookUrlAttribute()
    {
        return $this->getSettingValue('webhook_url');
    }

    public function getTimeoutAttribute()
    {
        return $this->getSettingValue('timeout');
    }

    public function getRetriesAttribute()
    {
        return $this->getSettingValue('retries');
    }

    public function getShopUrlAttribute()
    {
        return $this->getSettingValue('shop_url');
    }

    /**
     * ???? Скоуп для российских платежных систем
     */
    public function scopeRussian($query)
    {
        return $query->where('is_russian', true);
    }

    /**
     * ?? Скоуп для онлайн платежных систем
     */
    public function scopeOnline($query)
    {
        return $query->where('type', 'online');
    }

    /**
     * ?? Скоуп для офлайн платежных систем
     */
    public function scopeOffline($query)
    {
        return $query->where('type', 'offline');
    }

    /**
     * ???? Скоуп для СБП
     */
    public function scopeSBP($query)
    {
        return $query->where('code', 'sbp');
    }

    /**
     * ?? Скоуп для ЮKassa
     */
    public function scopeYookassa($query)
    {
        return $query->where('code', 'yookassa');
    }

    /**
     * ?? Скоуп для Тинькофф
     */
    public function scopeTinkoff($query)
    {
        return $query->where('code', 'tinkoff');
    }

    /**
     * ?? Скоуп для Сбербанк
     */
    public function scopeSberbank($query)
    {
        return $query->where('code', 'sberbank');
    }

    /**
     * ?? Скоуп для банковских карт
     */
    public function scopeCard($query)
    {
        return $query->where('code', 'card');
    }

    /**
     * ?? Скоуп для наличных
     */
    public function scopeCash($query)
    {
        return $query->where('code', 'cash');
    }

    /**
     * ?? Форматирование комиссии для отображения
     */
    public function getFormattedCommissionAttribute()
    {
        if ($this->commission === null) {
            return '—';
        }
        return number_format($this->commission, 2, ',', ' ') . '%';
    }

    /**
     * ?? Форматирование сумм для отображения
     */
    public function getFormattedAmountsAttribute()
    {
        $min = $this->min_amount ? number_format($this->min_amount, 2, ',', ' ') . ' ₽' : '—';
        $max = $this->max_amount ? number_format($this->max_amount, 2, ',', ' ') . ' ₽' : '—';
        return "{$min} - {$max}";
    }

    /**
     * ?? Форматирование валют для отображения
     */
    public function getFormattedCurrenciesAttribute()
    {
        if (empty($this->currencies)) {
            return 'RUB';
        }
        return implode(', ', $this->currencies);
    }

    /**
     * ?? Проверка доступности метода для суммы
     */
    public function isAvailableForAmount($amount)
    {
        if ($this->min_amount && $amount < $this->min_amount) {
            return false;
        }
        if ($this->max_amount && $amount > $this->max_amount) {
            return false;
        }
        return true;
    }
}

