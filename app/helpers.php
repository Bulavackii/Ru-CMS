<?php

/**
 * 📚 LOCAL_FONTS — реестр самохостящихся шрифтов (latin + cyrillic).
 *
 * Файлы вендорятся из @fontsource в public/assets/fonts/{slug}/ и не
 * требуют обращения к Google Fonts/Bunny Fonts. Ключ — slug для
 * font_provider='local' в настройках темы, значение — CSS font-family
 * и подпись для UI.
 */
if (!defined('LOCAL_FONTS')) {
    define('LOCAL_FONTS', [
        'inter' => ['family' => 'Inter', 'label' => 'Inter'],
        'roboto' => ['family' => 'Roboto', 'label' => 'Roboto'],
        'pt-sans' => ['family' => 'PT Sans', 'label' => 'PT Sans'],
        'manrope' => ['family' => 'Manrope', 'label' => 'Manrope'],
    ]);
}

/**
 * 🧩 module_path()
 *
 * Возвращает абсолютный путь к указанному модулю в директории `modules/`.
 * Работает как `base_path()` + `modules/...`, удобно для сервис-провайдеров, миграций и пр.
 *
 * 🔹 Пример использования:
 *   module_path('News') → /путь_к_проекту/modules/News
 *   module_path('News', 'Routes/web.php') → /путь_к_проекту/modules/News/Routes/web.php
 *
 * @param string $module Название модуля (папки внутри `modules/`)
 * @param string $path   Относительный путь внутри модуля
 * @return string        Абсолютный путь до файла или папки
 */
if (!function_exists('module_path')) {
    function module_path(string $module, string $path = ''): string
    {
        return base_path('modules/' . $module . ($path ? '/' . $path : ''));
    }
}

/**
 * 🌍 __t() - Функция перевода с поддержкой мультиязычности
 *
 * Использует модуль Localization для переводов
 *
 * @param string $key Ключ перевода
 * @param array $replace Замены в тексте
 * @param string|null $locale Локаль (если null - используется текущая)
 * @return string
 */
if (!function_exists('__t')) {
    function __t(string $key, array $replace = [], ?string $locale = null): string
    {
        if (app()->bound('localization')) {
            $localizationService = app('localization');
            $countryCode = $locale ?: app()->getLocale();
            return $localizationService->translate($key, null, $countryCode) ?: __($key, $replace, $locale);
        }
        
        return __($key, $replace, $locale);
    }
}

/**
 * 💰 format_currency() - Форматирование валюты для РФ/СНГ
 *
 * @param float $amount Сумма
 * @param string|null $currency Валюта (RUB по умолчанию)
 * @return string
 */
if (!function_exists('format_currency')) {
    function format_currency(float $amount, ?string $currency = 'RUB'): string
    {
        if (app()->bound('localization')) {
            $localizationService = app('localization');
            return $localizationService->formatCurrency($amount, 'RU');
        }
        
        // Fallback форматирование
        return number_format($amount, 2, ',', ' ') . ' ₽';
    }
}

/**
 * 📅 format_date() - Форматирование даты для РФ/СНГ
 *
 * @param mixed $date Дата
 * @param string|null $format Формат (если null - используется формат страны)
 * @return string
 */
if (!function_exists('format_date')) {
    function format_date($date, ?string $format = null): string
    {
        if (app()->bound('localization')) {
            $localizationService = app('localization');
            if (!$format) {
                return $localizationService->formatDate($date, 'RU');
            }
        }
        
        return \Carbon\Carbon::parse($date)->format($format ?: 'd.m.Y');
    }
}

/**
 * 📅 format_date_ru() - Форматирование даты в российском формате (дд.мм.гггг)
 *
 * @param mixed $date Дата
 * @param bool $includeTime Включать время
 * @return string
 */
if (!function_exists('format_date_ru')) {
    function format_date_ru($date, bool $includeTime = false): string
    {
        if (!$date) {
            return '';
        }

        if (is_string($date)) {
            $date = \Carbon\Carbon::parse($date);
        }

        $format = $includeTime ? 'd.m.Y H:i' : 'd.m.Y';
        
        return $date->format($format);
    }
}

/**
 * 📅 format_datetime_ru() - Форматирование даты и времени в российском формате
 *
 * @param mixed $date Дата
 * @return string
 */
if (!function_exists('format_datetime_ru')) {
    function format_datetime_ru($date): string
    {
        return format_date_ru($date, true);
    }
}

/**
 * 🔒 has_subscription() - Проверка активной подписки
 *
 * @return bool
 */
if (!function_exists('has_subscription')) {
    function has_subscription(): bool
    {
        if (app()->bound('subscription')) {
            return app('subscription')->hasActiveSubscription();
        }
        
        return false;
    }
}

/**
 * 🔑 get_license_key() - Получить лицензионный ключ
 *
 * @return string|null
 */
if (!function_exists('get_license_key')) {
    function get_license_key(): ?string
    {
        if (!app()->bound('subscription')) {
            return null;
        }
        
        $subscription = app('subscription')->getCurrentSubscription();
        return $subscription?->license_key ?? null;
    }
}

/**
 * 📦 local_css() - Получить путь к локальному CSS ресурсу
 *
 * Используется для подключения локальных CSS файлов вместо CDN
 *
 * @param string $name Имя файла (например: 'tailwind.min.css')
 * @param string|null $version Версия для кэширования (опционально)
 * @return string URL к локальному CSS файлу
 */
if (!function_exists('local_css')) {
    function local_css(string $name, ?string $version = null): string
    {
        $url = asset("assets/css/{$name}");
        return $version ? "{$url}?v={$version}" : $url;
    }
}

/**
 * 📦 local_js() - Получить путь к локальному JS ресурсу
 *
 * Используется для подключения локальных JavaScript файлов вместо CDN
 *
 * @param string $name Имя файла (например: 'alpine.min.js')
 * @param string|null $version Версия для кэширования (опционально)
 * @return string URL к локальному JS файлу
 */
if (!function_exists('local_js')) {
    function local_js(string $name, ?string $version = null): string
    {
        $url = asset("assets/js/{$name}");
        return $version ? "{$url}?v={$version}" : $url;
    }
}

/**
 * 🎨 theme_icon_asset() - Получить путь к ресурсу иконок по режиму темы
 *
 * Возвращает путь к CSS или JS файлу иконок в зависимости от режима темы
 *
 * @param string $mode Режим иконок: 'fa', 'bootstrap', 'remix', 'tabler', 'lucide', 'svg'
 * @param string|null $version Версия для кэширования (опционально)
 * @return string URL к ресурсу иконок или пустая строка
 */
if (!function_exists('theme_icon_asset')) {
    function theme_icon_asset(string $mode, ?string $version = null): string
    {
        return match($mode) {
            'bootstrap' => local_css('bootstrap-icons.css', $version),
            'remix' => local_css('remixicon.css', $version),
            'tabler' => local_css('tabler-icons.min.css', $version),
            'lucide' => local_js('lucide.min.js', $version),
            'fa' => local_css('font-awesome/all.min.css', $version),
            default => '',
        };
    }
}

/**
 * 🔤 local_font() - Получить путь к локальному шрифту
 *
 * @param string $name Имя файла шрифта (например: 'Inter-Regular.woff2')
 * @param string|null $family Семейство шрифта (опционально, для подпапки)
 * @return string URL к локальному файлу шрифта
 */
if (!function_exists('local_font')) {
    function local_font(string $name, ?string $family = null): string
    {
        $path = $family ? "assets/fonts/{$family}/{$name}" : "assets/fonts/{$name}";
        return asset($path);
    }
}

/**
 * 🔤 local_font_css() - Получить путь к CSS локально захостенного шрифта
 *
 * Семейства из public/assets/fonts/{slug}/{slug}.css (latin + cyrillic,
 * без обращений к Google Fonts/Bunny Fonts). См. LOCAL_FONTS в helpers.php.
 *
 * @param string $slug Идентификатор шрифта (например: 'inter', 'roboto')
 * @return string URL к CSS файлу шрифта или '' если такого шрифта нет локально
 */
if (!function_exists('local_font_css')) {
    function local_font_css(string $slug): string
    {
        if (!array_key_exists($slug, LOCAL_FONTS)) {
            return '';
        }

        return asset("assets/fonts/{$slug}/{$slug}.css");
    }
}