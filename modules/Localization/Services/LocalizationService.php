<?php

namespace Modules\Localization\Services;

use Modules\Localization\Models\Country;
use Modules\Localization\Models\LocalizationSetting;
use Carbon\Carbon;

class LocalizationService
{
    private ?Country $currentCountry = null;
    private array $cache = [];

    /**
     * 🚀 Инициализация сервиса
     */
    public function __construct(?string $countryCode = null)
    {
        if ($countryCode) {
            $this->setCountry($countryCode);
        }
    }

    /**
     * 🌍 Установить текущую страну
     */
    public function setCountry(string $countryCode): self
    {
        $this->currentCountry = Country::getByCode($countryCode);
        return $this;
    }

    /**
     * 🌍 Получить текущую страну
     */
    public function getCountry(): ?Country
    {
        return $this->currentCountry;
    }

    /**
     * 💰 Форматирование валюты
     */
    public function formatCurrency(float $amount, ?string $countryCode = null): string
    {
        $country = $this->getCountryForOperation($countryCode);

        if (!$country) {
            return number_format($amount, 2, '.', ' ') . ' ' . ($countryCode ?? 'CUR');
        }

        return $country->formatCurrency($amount);
    }

    /**
     * 📅 Форматирование даты
     */
    public function formatDate($date, ?string $countryCode = null): string
    {
        $country = $this->getCountryForOperation($countryCode);

        if (!$country) {
            return Carbon::parse($date)->format('d.m.Y');
        }

        return $country->formatDate($date);
    }

    /**
     * ⏰ Форматирование времени
     */
    public function formatTime($time, ?string $countryCode = null): string
    {
        $country = $this->getCountryForOperation($countryCode);

        if (!$country) {
            return Carbon::parse($time)->format('H:i');
        }

        return $country->formatTime($time);
    }

    /**
     * 🔢 Форматирование числа
     */
    public function formatNumber(float $number, ?string $countryCode = null): string
    {
        $country = $this->getCountryForOperation($countryCode);

        if (!$country) {
            return number_format($number, 2, '.', ' ');
        }

        return $country->formatNumber($number);
    }

    /**
     * 📅 Форматирование даты и времени
     */
    public function formatDateTime($datetime, ?string $countryCode = null): string
    {
        $country = $this->getCountryForOperation($countryCode);

        if (!$country) {
            return Carbon::parse($datetime)->format('d.m.Y H:i');
        }

        $date = $country->formatDate($datetime);
        $time = $country->formatTime($datetime);

        return "{$date} {$time}";
    }

    /**
     * 🌐 Получить локаль Laravel для страны
     */
    public function getLocale(?string $countryCode = null): string
    {
        $country = $this->getCountryForOperation($countryCode);

        return $country ? $country->getLaravelLocale() : 'ru_RU';
    }

    /**
     * ⏰ Получить часовой пояс
     */
    public function getTimezone(?string $countryCode = null): string
    {
        $country = $this->getCountryForOperation($countryCode);

        return $country ? $country->timezone : 'UTC';
    }

    /**
     * 🔄 Установить часовой пояс для текущего запроса
     */
    public function setTimezone(?string $countryCode = null): void
    {
        $timezone = $this->getTimezone($countryCode);
        date_default_timezone_set($timezone);
        config(['app.timezone' => $timezone]);
    }

    /**
     * 📋 Получить все настройки для страны
     */
    public function getSettings(?string $countryCode = null): array
    {
        $country = $this->getCountryForOperation($countryCode);

        if (!$country) {
            return [];
        }

        $cacheKey = "settings_{$country->code}";

        if (!isset($this->cache[$cacheKey])) {
            $this->cache[$cacheKey] = LocalizationSetting::getAllForCountry($country->id);
        }

        return $this->cache[$cacheKey];
    }

    /**
     * ⚙️ Получить конкретную настройку
     */
    public function getSetting(string $key, $default = null, ?string $countryCode = null)
    {
        $country = $this->getCountryForOperation($countryCode);

        if (!$country) {
            return $default;
        }

        return LocalizationSetting::getForCountry($country->id, $key, $default);
    }

    /**
     * 🌍 Получить список всех активных стран
     */
    public function getCountries(): \Illuminate\Database\Eloquent\Collection
    {
        return Country::active();
    }

    /**
     * 🌍 Получить страну по коду
     */
    public function getCountryByCode(string $code): ?Country
    {
        return Country::getByCode($code);
    }

    /**
      * 📝 Перевести текст с учетом страны
      */
    public function translate(string $key, ?string $default = null, ?string $countryCode = null): string
    {
        $country = $this->getCountryForOperation($countryCode);

        if (!$country) {
            return $default ?? $key;
        }

        $translation = $country->translate($key);

        return $translation ?: ($default ?? $key);
    }

    /**
      * 📚 Получить все переводы для страны
      */
    public function getTranslations(?string $countryCode = null): array
    {
        $country = $this->getCountryForOperation($countryCode);

        if (!$country) {
            return [];
        }

        $cacheKey = "translations_{$country->code}";

        if (!isset($this->cache[$cacheKey])) {
            $this->cache[$cacheKey] = $country->getTranslations();
        }

        return $this->cache[$cacheKey];
    }

    /**
      * 📚 Получить все переводы для фронтенда
      */
    public function getFrontendTranslations(?string $countryCode = null): array
    {
        $country = $this->getCountryForOperation($countryCode);

        if (!$country) {
            return [];
        }

        $cacheKey = "frontend_translations_{$country->code}";

        if (!isset($this->cache[$cacheKey])) {
            $translations = $country->getTranslations();

            // Фильтруем только переводы с префиксом frontend.
            $frontendTranslations = [];
            foreach ($translations as $key => $value) {
                if (str_starts_with($key, 'frontend.')) {
                    $frontendTranslations[$key] = $value;
                }
            }

            $this->cache[$cacheKey] = $frontendTranslations;
        }

        return $this->cache[$cacheKey];
    }

    /**
      * 📚 Получить все настройки для фронтенда
      */
    public function getFrontendSettings(?string $countryCode = null): array
    {
        $country = $this->getCountryForOperation($countryCode);

        if (!$country) {
            return [];
        }

        $cacheKey = "frontend_settings_{$country->code}";

        if (!isset($this->cache[$cacheKey])) {
            $settings = LocalizationSetting::getAllForCountry($country->id);

            // Фильтруем только настройки с префиксом frontend.
            $frontendSettings = [];
            foreach ($settings as $key => $value) {
                if (str_starts_with($key, 'frontend.')) {
                    $frontendSettings[$key] = $value;
                }
            }

            $this->cache[$cacheKey] = $frontendSettings;
        }

        return $this->cache[$cacheKey];
    }

    /**
      * 📚 Получить все данные для фронтенда
      */
    public function getFrontendData(?string $countryCode = null): array
    {
        $country = $this->getCountryForOperation($countryCode);

        if (!$country) {
            return [];
        }

        $cacheKey = "frontend_data_{$country->code}";

        if (!isset($this->cache[$cacheKey])) {
            $this->cache[$cacheKey] = [
                'country' => [
                    'code' => $country->code,
                    'name' => $country->name,
                    'flag' => $country->flag,
                    'currency_code' => $country->currency_code,
                    'currency_symbol' => $country->currency_symbol,
                    'locale' => $country->getLaravelLocale(),
                    'timezone' => $country->timezone,
                    'date_format' => $country->date_format,
                    'time_format' => $country->time_format,
                    'decimal_separator' => $country->decimal_separator,
                    'thousands_separator' => $country->thousands_separator,
                    'decimal_places' => $country->decimal_places,
                ],
                'settings' => $this->getFrontendSettings($country->code),
                'translations' => $this->getFrontendTranslations($country->code),
            ];
        }

        return $this->cache[$cacheKey];
    }

    /**
     * 💾 Сохранить настройку для страны
     */
    public function saveSetting(string $countryCode, string $key, $value, string $type = 'string', string $group = 'general', string $description = ''): bool
    {
        $country = Country::getByCode($countryCode);

        if (!$country) {
            return false;
        }

        LocalizationSetting::set($country->id, $key, $value, $type, $group, $description);

        // Сбросить кеш
        cache()->forget("settings_{$country->code}");
        cache()->forget("country_{$countryCode}");

        return true;
    }

    /**
     * 🗑️ Удалить настройку
     */
    public function deleteSetting(string $countryCode, string $key): bool
    {
        $country = Country::getByCode($countryCode);

        if (!$country) {
            return false;
        }

        $setting = LocalizationSetting::where('country_id', $country->id)->where('key', $key)->first();

        if (!$setting) {
            return false;
        }

        $result = $setting->delete();

        // Сбросить кеш
        cache()->forget("settings_{$country->code}");

        return $result;
    }

    /**
     * 🔄 Создать или обновить страну
     */
    public function createOrUpdateCountry(array $data): Country
    {
        $country = Country::where('code', $data['code'])->firstOrNew([]);

        foreach ($data as $key => $value) {
            if ($country->hasAttribute($key)) {
                $country->$key = $value;
            }
        }

        $country->save();

        // Сбросить кеш
        cache()->forget("country_{$country->code}");
        cache()->forget('countries_active');

        return $country;
    }

    /**
     * 🗑️ Удалить страну
     */
    public function deleteCountry(string $countryCode): bool
    {
        $country = Country::getByCode($countryCode);

        if (!$country) {
            return false;
        }

        // Удалить связанные настройки
        LocalizationSetting::where('country_id', $country->id)->delete();

        $result = $country->delete();

        // Сбросить кеш
        cache()->forget("country_{$countryCode}");
        cache()->forget('countries_active');

        return $result;
    }

    /**
     * 🔄 Получить страну для операции
     */
    private function getCountryForOperation(?string $countryCode = null): ?Country
    {
        if ($countryCode) {
            return Country::getByCode($countryCode);
        }

        return $this->currentCountry;
    }

    /**
     * 🧹 Очистить весь кеш
     */
    public function clearCache(): void
    {
        cache()->forget('countries_active');

        $countries = Country::all();
        foreach ($countries as $country) {
            cache()->forget("country_{$country->code}");
            cache()->forget("settings_{$country->code}");
        }

        $this->cache = [];
    }

    /**
     * 📊 Получить статистику
     */
    public function getStats(): array
    {
        return [
            'total_countries' => Country::count(),
            'active_countries' => Country::where('active', true)->count(),
            'total_settings' => LocalizationSetting::count(),
            'system_settings' => LocalizationSetting::where('is_system', true)->count(),
        ];
    }
}
