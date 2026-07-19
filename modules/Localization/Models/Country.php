<?php

namespace Modules\Localization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use SoftDeletes;

    /**
     * 💾 Массово заполняемые поля
     */
    protected $fillable = [
        'code',
        'name',
        'native_name',
        'flag',
        'currency_code',
        'currency_symbol',
        'locale',
        'timezone',
        'date_format',
        'time_format',
        'decimal_separator',
        'thousands_separator',
        'decimal_places',
        'active',
        'translations',
    ];

    /**
     * 🔧 Касты полей
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'translations' => 'array',
            'decimal_places' => 'integer',
        ];
    }

    /**
     * 📊 Связь с настройками локализации
     */
    public function settings(): HasMany
    {
        return $this->hasMany(LocalizationSetting::class, 'country_id');
    }

    /**
     * 🌍 Получить страну по коду (кеширование)
     */
    public static function getByCode(string $code): ?self
    {
        return cache()->remember(
            "country_{$code}",
            3600,
            fn() => self::where('code', $code)->first()
        );
    }

    /**
     * 💰 Получить форматированный денежный символ
     */
    public function getFormattedCurrencySymbol(): string
    {
        return $this->currency_symbol ?? $this->currency_code;
    }

    /**
     * 📅 Получить формат даты с учетом настроек
     */
    public function getDateFormat(): string
    {
        return $this->date_format ?? 'd.m.Y';
    }

    /**
     * ⏰ Получить формат времени с учетом настроек
     */
    public function getTimeFormat(): string
    {
        return $this->time_format ?? 'H:i';
    }

    /**
     * 🔢 Форматирование числа
     */
    public function formatNumber(float $number): string
    {
        $decimals = $this->decimal_places ?? 2;
        $decSep = $this->decimal_separator ?? '.';
        $thousandsSep = $this->thousands_separator ?? ' ';

        return number_format($number, $decimals, $decSep, $thousandsSep);
    }

    /**
     * 💰 Форматирование валюты
     */
    public function formatCurrency(float $amount): string
    {
        $formatted = $this->formatNumber($amount);
        $symbol = $this->getFormattedCurrencySymbol();

        // Позиция символа валюты
        // Для RU, KZ, DE, FR, IT - символ после (1 234,56 ₽)
        // Для US, GB - символ до ($1,234.56)
        $symbolAfter = in_array($this->code, ['RU', 'KZ', 'DE', 'FR', 'IT', 'BY']);
        
        // Проверяем настройку из базы
        $setting = \Modules\Localization\Models\LocalizationSetting::getForCountry(
            $this->id ?? 0, 
            'currency_position', 
            $symbolAfter ? 'after' : 'before'
        );
        
        $symbolAfter = ($setting === 'after');

        return $symbolAfter ? "{$formatted} {$symbol}" : "{$symbol}{$formatted}";
    }

    /**
     * 📅 Форматирование даты
     */
    public function formatDate($date): string
    {
        if (!$date) return '';

        $format = $this->getDateFormat();
        $timezone = $this->timezone ?? 'UTC';

        try {
            return \Carbon\Carbon::parse($date, $timezone)->format($format);
        } catch (\Exception $e) {
            return \Carbon\Carbon::parse($date)->format($format);
        }
    }

    /**
     * ⏰ Форматирование времени
     */
    public function formatTime($time): string
    {
        if (!$time) return '';

        $format = $this->getTimeFormat();
        $timezone = $this->timezone ?? 'UTC';

        try {
            return \Carbon\Carbon::parse($time, $timezone)->format($format);
        } catch (\Exception $e) {
            return \Carbon\Carbon::parse($time)->format($format);
        }
    }

    /**
     * 🌐 Получить локаль для Laravel
     */
    public function getLaravelLocale(): string
    {
        return $this->locale ?? 'ru_RU';
    }

    /**
     * 🔄 Получить все активные страны
     */
    public static function active(): \Illuminate\Database\Eloquent\Collection
    {
        return cache()->remember(
            'countries_active',
            3600,
            fn() => self::where('active', true)->orderBy('name')->get()
        );
    }

    /**
     * 📝 Получить перевод для ключа
     */
    public function translate(string $key, string $default = ''): string
    {
        $translations = $this->translations ?? [];
        return $translations[$key] ?? $default;
    }

    /**
     * 📚 Получить все переводы для страны
     */
    public function getTranslations(): array
    {
        return $this->translations ?? [];
    }

    /**
     * 🔄 Очистить кеш при сохранении
     */
    protected static function booted(): void
    {
        static::saved(function ($country) {
            cache()->forget("country_{$country->code}");
            cache()->forget('countries_active');
        });

        static::deleted(function ($country) {
            cache()->forget("country_{$country->code}");
            cache()->forget('countries_active');
        });
    }
}
