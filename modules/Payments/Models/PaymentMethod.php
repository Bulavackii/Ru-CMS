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

