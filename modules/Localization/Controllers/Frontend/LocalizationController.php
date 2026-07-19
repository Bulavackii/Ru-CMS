<?php

namespace Modules\Localization\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Localization\Services\LocalizationService;

class LocalizationController extends Controller
{
    private LocalizationService $service;

    public function __construct(LocalizationService $service)
    {
        $this->service = $service;
    }

    /**
     * 🌍 Получить данные локализации для текущей страны
     */
    public function current(Request $request): JsonResponse
    {
        $countryCode = $request->input('country', config('localization.default_country', 'RU'));

        $country = $this->service->getCountryByCode($countryCode);
        $settings = $this->service->getSettings($countryCode);

        if (!$country) {
            return response()->json(['error' => 'Country not found'], 404);
        }

        return response()->json([
            'country' => [
                'code' => $country->code,
                'name' => $country->name,
                'native_name' => $country->native_name,
                'flag' => $country->flag,
                'currency_code' => $country->currency_code,
                'currency_symbol' => $country->currency_symbol,
                'locale' => $country->locale,
                'timezone' => $country->timezone,
                'date_format' => $country->date_format,
                'time_format' => $country->time_format,
            ],
            'settings' => $settings,
        ]);
    }

    /**
     * 💰 Форматирование валюты
     */
    public function formatCurrency(Request $request): JsonResponse
    {
        $amount = (float) $request->input('amount', 0);
        $countryCode = $request->input('country', config('localization.default_country', 'RU'));

        $formatted = $this->service->formatCurrency($amount, $countryCode);

        return response()->json([
            'amount' => $amount,
            'formatted' => $formatted,
            'country' => $countryCode,
        ]);
    }

    /**
     * 📅 Форматирование даты
     */
    public function formatDate(Request $request): JsonResponse
    {
        $date = $request->input('date');
        $countryCode = $request->input('country', config('localization.default_country', 'RU'));

        $formatted = $this->service->formatDate($date, $countryCode);

        return response()->json([
            'date' => $date,
            'formatted' => $formatted,
            'country' => $countryCode,
        ]);
    }

    /**
     * ⏰ Форматирование времени
     */
    public function formatTime(Request $request): JsonResponse
    {
        $time = $request->input('time');
        $countryCode = $request->input('country', config('localization.default_country', 'RU'));

        $formatted = $this->service->formatTime($time, $countryCode);

        return response()->json([
            'time' => $time,
            'formatted' => $formatted,
            'country' => $countryCode,
        ]);
    }

    /**
     * 📅 Форматирование даты и времени
     */
    public function formatDateTime(Request $request): JsonResponse
    {
        $datetime = $request->input('datetime');
        $countryCode = $request->input('country', config('localization.default_country', 'RU'));

        $formatted = $this->service->formatDateTime($datetime, $countryCode);

        return response()->json([
            'datetime' => $datetime,
            'formatted' => $formatted,
            'country' => $countryCode,
        ]);
    }

    /**
     * 🔢 Форматирование числа
     */
    public function formatNumber(Request $request): JsonResponse
    {
        $number = (float) $request->input('number', 0);
        $countryCode = $request->input('country', config('localization.default_country', 'RU'));

        $formatted = $this->service->formatNumber($number, $countryCode);

        return response()->json([
            'number' => $number,
            'formatted' => $formatted,
            'country' => $countryCode,
        ]);
    }

    /**
     * 🌍 Получить список всех активных стран
     */
    public function countries(): JsonResponse
    {
        $countries = $this->service->getCountries()->map(function ($country) {
            return [
                'code' => $country->code,
                'name' => $country->name,
                'native_name' => $country->native_name,
                'flag' => $country->flag,
                'currency_code' => $country->currency_code,
                'currency_symbol' => $country->currency_symbol,
                'locale' => $country->locale,
                'timezone' => $country->timezone,
            ];
        });

        return response()->json($countries);
    }

    /**
     * 📝 Перевод текста
     */
    public function translate(Request $request): JsonResponse
    {
        $key = $request->input('key');
        $default = $request->input('default', $key);
        $countryCode = $request->input('country', config('localization.default_country', 'RU'));

        if (!$key) {
            return response()->json(['error' => 'Key is required'], 400);
        }

        $translated = $this->service->translate($key, $default, $countryCode);

        return response()->json([
            'key' => $key,
            'translated' => $translated,
            'country' => $countryCode,
        ]);
    }

    /**
     * ⚙️ Получить конкретную настройку
     */
    public function setting(Request $request): JsonResponse
    {
        $key = $request->input('key');
        $default = $request->input('default');
        $countryCode = $request->input('country', config('localization.default_country', 'RU'));

        if (!$key) {
            return response()->json(['error' => 'Key is required'], 400);
        }

        $value = $this->service->getSetting($key, $default, $countryCode);

        return response()->json([
            'key' => $key,
            'value' => $value,
            'country' => $countryCode,
        ]);
    }

    /**
     * 🔄 Установить часовой пояс для сессии
     */
    public function setTimezone(Request $request): JsonResponse
    {
        $countryCode = $request->input('country', config('localization.default_country', 'RU'));

        $this->service->setTimezone($countryCode);

        return response()->json([
            'success' => true,
            'timezone' => $this->service->getTimezone($countryCode),
            'country' => $countryCode,
        ]);
    }

    /**
     * 📊 Получить полный набор данных для фронтенда
     */
    public function frontendData(Request $request): JsonResponse
    {
        $countryCode = $request->input('country', config('localization.default_country', 'RU'));

        $country = $this->service->getCountryByCode($countryCode);

        if (!$country) {
            return response()->json(['error' => 'Country not found'], 404);
        }

        $settings = $this->service->getSettings($countryCode);

        // Форматирование примеров
        $examples = [
            'currency' => $this->service->formatCurrency(1234.56, $countryCode),
            'date' => $this->service->formatDate(now(), $countryCode),
            'time' => $this->service->formatTime(now(), $countryCode),
            'datetime' => $this->service->formatDateTime(now(), $countryCode),
            'number' => $this->service->formatNumber(9876543.21, $countryCode),
        ];

        return response()->json([
            'country' => [
                'code' => $country->code,
                'name' => $country->name,
                'native_name' => $country->native_name,
                'flag' => $country->flag,
                'currency_code' => $country->currency_code,
                'currency_symbol' => $country->currency_symbol,
                'locale' => $country->locale,
                'timezone' => $country->timezone,
                'date_format' => $country->date_format,
                'time_format' => $country->time_format,
                'decimal_separator' => $country->decimal_separator,
                'thousands_separator' => $country->thousands_separator,
                'decimal_places' => $country->decimal_places,
            ],
            'settings' => $settings,
            'examples' => $examples,
        ]);
    }

    /**
     * 🔄 Переключение страны
     */
    public function switchCountry(Request $request)
    {
        $validated = $request->validate([
            'country_code' => 'required|string|size:2',
        ]);

        $countryCode = strtoupper($validated['country_code']);
        
        // Проверить существование страны
        $country = $this->service->getCountryByCode($countryCode);
        
        if (!$country || !$country->active) {
            return redirect()->back()->with('error', 'Страна не найдена или неактивна');
        }

        // Сохранить в сессию
        session(['country_code' => $countryCode]);
        
        // Установить локаль и часовой пояс
        $locale = $country->locale ?? 'ru';
        app()->setLocale($locale);
        session(['locale' => $locale]);
        
        // Установить часовой пояс
        if ($country->timezone) {
            $this->service->setTimezone($countryCode);
        }

        // Сохранить в профиль пользователя (если авторизован)
        if (auth()->check()) {
            $user = auth()->user();
            $user->country_code = $countryCode;
            $user->locale = $locale;
            $user->save();
        }

        return redirect()->back()->with('success', "Страна изменена на {$country->name}");
    }
}
