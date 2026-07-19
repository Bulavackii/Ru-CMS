<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Modules\Localization\Services\LocalizationService;
use Symfony\Component\HttpFoundation\Response;

/**
 * 🌍 LocalizationMiddleware - Автоматическое определение языка
 */
class LocalizationMiddleware
{
    private LocalizationService $localizationService;

    public function __construct(LocalizationService $localizationService)
    {
        $this->localizationService = $localizationService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Пропускаем маршруты установки
        if ($request->is('install*')) {
            return $next($request);
        }

        // Определение страны из разных источников
        $countryCode = $this->determineCountry($request);
        
        // Получить страну из модуля
        $country = $this->localizationService->getCountryByCode($countryCode);
        
        if ($country && $country->active) {
            // Использовать локаль из страны
            $locale = $country->locale ?? $this->determineLocale($request);
            
            // Установить часовой пояс из страны
            if ($country->timezone) {
                $this->localizationService->setTimezone($countryCode);
            }
            
            // Сохранить в сессию
            Session::put('country_code', $countryCode);
            
            // Если пользователь авторизован, но у него нет сохраненной страны - сохранить
            if ($request->user() && !$request->user()->country_code) {
                $request->user()->update([
                    'country_code' => $countryCode,
                    'locale' => $locale,
                ]);
            }
        } else {
            // Fallback на старую логику определения языка
            $locale = $this->determineLocale($request);
        }

        // Установка локали
        App::setLocale($locale);
        Session::put('locale', $locale);

        // Передача данных в представления
        view()->share('currentLocale', $locale);
        view()->share('currentCountryCode', $countryCode);
        view()->share('localizationService', $this->localizationService);

        return $next($request);
    }

    /**
     * Определение страны из запроса
     */
    private function determineCountry(Request $request): string
    {
        // 1. Из параметра запроса
        if ($request->has('country')) {
            $code = strtoupper($request->get('country'));
            $country = $this->localizationService->getCountryByCode($code);
            if ($country && $country->active) {
                return $code;
            }
        }

        // 2. Из сессии
        if (Session::has('country_code')) {
            $code = Session::get('country_code');
            $country = $this->localizationService->getCountryByCode($code);
            if ($country && $country->active) {
                return $code;
            }
        }

        // 3. Из профиля пользователя (если авторизован) - приоритет
        if ($request->user()) {
            $user = $request->user();
            
            // Сначала проверяем country_code
            if ($user->country_code) {
                $code = strtoupper($user->country_code);
                $country = $this->localizationService->getCountryByCode($code);
                if ($country && $country->active) {
                    return $code;
                }
            }
            
            // Если country_code нет, но есть locale - пытаемся найти страну по локали
            if ($user->locale && !$user->country_code) {
                $countries = $this->localizationService->getCountries();
                foreach ($countries as $country) {
                    if ($country->locale === $user->locale && $country->active) {
                        // Сохраняем найденную страну в профиль
                        $user->country_code = $country->code;
                        $user->save();
                        return $country->code;
                    }
                }
            }
        }

        // 4. По умолчанию из конфига
        return config('localization.default_country', 'RU');
    }

    /**
     * Определение языка из запроса
     */
    private function determineLocale(Request $request): string
    {
        // 1. Из параметра запроса
        if ($request->has('lang')) {
            $lang = $request->get('lang');
            if (in_array($lang, ['ru', 'en'])) {
                return $lang;
            }
        }

        // 2. Из сессии
        if (Session::has('locale')) {
            $lang = Session::get('locale');
            if (in_array($lang, ['ru', 'en'])) {
                return $lang;
            }
        }

        // 3. Из настроек пользователя (если авторизован)
        if ($request->user() && $request->user()->locale) {
            return $request->user()->locale;
        }

        // 4. Из Accept-Language заголовка
        $preferredLang = $request->getPreferredLanguage(['ru', 'en']);
        if ($preferredLang) {
            return $preferredLang;
        }

        // 5. По умолчанию - русский для СНГ/РФ
        return config('app.locale', 'ru');
    }
}

