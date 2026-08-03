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

/**
 * 🌍 available_locales() - Доступные языки интерфейса.
 *
 * Коды каталогов resources/lang. Языков интерфейса два: ru и en.
 * Эталон 'ru' идёт первым. Используется языковым переключателем в шапке,
 * роутом смены локали (frontend.locale.set) и LocalizationMiddleware (валидация).
 *
 * @return array<int,string>
 */
if (!function_exists('available_locales')) {
    function available_locales(): array
    {
        $path = function_exists('app') ? app()->langPath() : base_path('resources/lang');
        $dirs = is_dir($path) ? glob($path . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) : [];

        // Только настоящие коды локалей (2 буквы, опционально регион)
        $codes = array_values(array_filter(
            array_map('basename', $dirs ?: []),
            fn($c) => (bool) preg_match('~^[a-z]{2}([_-][A-Za-z0-9]+)?$~', $c)
        ));

        sort($codes);
        if (($i = array_search('ru', $codes, true)) !== false) {
            unset($codes[$i]);
            array_unshift($codes, 'ru');
        }

        return array_values($codes);
    }
}

/**
 * ⏱️ reading_time() - Примерное время чтения материала в минутах.
 *
 * Считает слова регуляркой по \p{L}\p{N} с флагом /u, а НЕ через str_word_count():
 * та функция ASCII-ориентированная и на кириллице врёт (у страницы из 125 слов
 * возвращала 3, у новости из 11 слов — 1), из-за чего время чтения всегда было
 * «~1 мин» независимо от объёма текста.
 *
 * Скорость 150 слов/мин — средний темп чтения русскоязычного текста.
 *
 * @param string|null $html HTML или обычный текст материала
 * @param int $wpm Слов в минуту
 * @return int Минуты (минимум 1)
 */
if (!function_exists('reading_time')) {
    function reading_time(?string $html, int $wpm = 150): int
    {
        $text = strip_tags((string) $html);
        $words = preg_match_all('/[\p{L}\p{N}]+/u', $text) ?: 0;

        return max(1, (int) ceil($words / max(1, $wpm)));
    }
}

/**
 * 🏴 locale_flag() - Инлайн-SVG флаг страны для локали.
 *
 * Именно SVG, а не эмодзи: Windows не рисует эмодзи-флаги (нет в Segoe UI Emoji).
 * Упрощённые, но узнаваемые. Размер/скругление задаёт класс .flag в CSS.
 */
if (!function_exists('locale_flag')) {
    function locale_flag(string $code): string
    {
        $p = 'preserveAspectRatio="none"';
        $flags = [
            'ru' => '<svg viewBox="0 0 3 2" '.$p.' class="flag"><rect width="3" height="2" fill="#fff"/><rect y=".667" width="3" height=".667" fill="#0039A6"/><rect y="1.333" width="3" height=".667" fill="#D52B1E"/></svg>',
            'en' => '<svg viewBox="0 0 19 10" '.$p.' class="flag"><rect width="19" height="10" fill="#B22234"/><rect y="1" width="19" height="1" fill="#fff"/><rect y="3" width="19" height="1" fill="#fff"/><rect y="5" width="19" height="1" fill="#fff"/><rect y="7" width="19" height="1" fill="#fff"/><rect y="9" width="19" height="1" fill="#fff"/><rect width="8" height="6" fill="#3C3B6E"/></svg>',
        ];

        return $flags[$code]
            ?? '<svg viewBox="0 0 3 2" '.$p.' class="flag"><rect width="3" height="2" fill="#9ca3af"/></svg>';
    }
}
/**
 * 🔤 Оператор LIKE, не зависящий от регистра букв.
 *
 * В PostgreSQL (боевая БД проекта) LIKE регистрозависим: запрос «МОДУЛЬ»
 * не находил запись «Модульная архитектура» — поиск по админке, глобальный
 * поиск в шапке и поиск на сайте молча теряли совпадения, если пользователь
 * набирал не в том регистре. ILIKE решает это, но есть только у Postgres,
 * поэтому оператор выбирается по драйверу: в SQLite (тесты) LIKE и без того
 * регистронезависим.
 *
 * Важно: ILIKE в Postgres учитывает кириллицу, а LOWER()-обходной путь
 * в SQLite — нет, поэтому выбран именно этот способ.
 */
if (!function_exists('search_like')) {
    function search_like(): string
    {
        return \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql'
            ? 'ilike'
            : 'like';
    }
}

/**
 * 🎨 readable_ink() - Цвет текста, читаемый на заданном фоне.
 *
 * Акценты тем в проекте очень разной яркости: у «Контраста» это тёмно-синий
 * #1d4ed8, а у «Графита» — светло-голубой #38bdf8. Белый текст на втором даёт
 * контраст 2.14:1 при норме WCAG AA 4.5:1 — активный пункт сайдбара и кнопка
 * «Создать» в шапке на этой теме читались плохо. Поэтому цвет надписи не
 * прибивается литералом, а выбирается по яркости фона.
 *
 * Считается относительная яркость по WCAG 2.1 (с гамма-коррекцией sRGB), а не
 * наивное (R+G+B)/3: последнее для #38bdf8 и #1d4ed8 даёт близкие числа, хотя
 * глазом они отличаются радикально.
 *
 * @param string $background HEX-цвет фона (#rgb или #rrggbb)
 * @param string $dark       Что вернуть на светлом фоне
 * @param string $light      Что вернуть на тёмном фоне
 */
if (!function_exists('readable_ink')) {
    function readable_ink(string $background, string $dark = '#111827', string $light = '#ffffff'): string
    {
        $hex = ltrim(trim($background), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (!preg_match('~^[0-9a-fA-F]{6}$~', $hex)) {
            return $light; // непонятный цвет — ведём себя как раньше
        }

        $channel = function (int $value): float {
            $c = $value / 255;

            return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };

        $luminance = 0.2126 * $channel((int) hexdec(substr($hex, 0, 2)))
            + 0.7152 * $channel((int) hexdec(substr($hex, 2, 2)))
            + 0.0722 * $channel((int) hexdec(substr($hex, 4, 2)));

        // Контраст белого к фону: 1.05 / (L + 0.05).
        //
        // Порог 3.0, а не 4.5. Здесь речь о полужирных подписях кнопок и
        // активного пункта меню, а не о наборном тексте. Порог 4.5 переворачивал
        // бы и фирменный индиго #6366f1 (4.47:1 — буквально на волосок ниже),
        // то есть весь установившийся вид панели, ничего при этом не улучшая.
        // По-настоящему нечитаемые случаи — вроде светло-голубого #38bdf8 темы
        // «Графит» с его 2.14:1 — отсекаются и порогом 3.0.
        return (1.05 / ($luminance + 0.05)) >= 3.0 ? $light : $dark;
    }
}

/**
 * 🔖 render_shortcodes() - Раскрытие шорткодов в содержимом материала.
 *
 * Текст новостей и страниц пишется в TinyMCE и хранится как HTML, поэтому
 * вызовы Blade-хелперов в нём не сработают: шаблонизатор к этому моменту уже
 * отработал. Чтобы сохранённую сборку каптчи можно было вставить прямо в
 * материал, в тексте живёт шорткод [captcha preset="slug"], а разворачивается
 * он здесь — при выводе.
 *
 * Неизвестный шорткод остаётся как есть: молча съедать кусок текста хуже,
 * чем показать его. А вот несуществующий пресет ничего не рисует (см.
 * captcha_preset) — материал со ссылкой на удалённую сборку должен
 * открываться как обычно, а не падать.
 *
 * @param string|null $html Содержимое материала
 */
if (!function_exists('render_shortcodes')) {
    function render_shortcodes(?string $html): string
    {
        $html = (string) $html;

        if ($html === '' || !str_contains($html, '[captcha')) {
            return $html;
        }

        return preg_replace_callback(
            '~\[captcha\s+preset=(["\'])(?<slug>[a-z0-9\-_]+)\1\s*\]~i',
            function (array $match): string {
                if (!function_exists('captcha_preset')) {
                    return '';
                }

                return (string) captcha_preset($match['slug']);
            },
            $html
        ) ?? $html;
    }
}

/**
 * 🧹 strip_shortcodes() - Убрать шорткоды из текста.
 *
 * Нужна там, где содержимое материала превращается в ПРОСТОЙ текст: мета-описание
 * страницы, сниппет в выдаче, письмо. Иначе посетитель видит в описании
 * служебное [captcha preset="..."] — так и было, пока описание собиралось
 * одним strip_tags(), который про шорткоды не знает.
 */
if (!function_exists('strip_shortcodes')) {
    function strip_shortcodes(?string $text): string
    {
        $text = (string) $text;

        if ($text === '' || !str_contains($text, '[')) {
            return $text;
        }

        return trim(preg_replace('~\[[a-z_]+(?:\s+[^\]]*)?\]~i', '', $text) ?? $text);
    }
}

if (! function_exists('max_upload_kb')) {
    /**
     * Сколько килобайт реально примет сервер за одну загрузку.
     *
     * Правила вида max:10240 в формах ничего не значат, если PHP настроен
     * жёстче: файл сверх upload_max_filesize отбрасывается ДО того, как
     * Laravel его увидит, и остаётся только глухое «Не удалось загрузить
     * файл». Так на боевой машине владельца (upload_max_filesize = 2M)
     * не грузился фон темы, хотя форма обещала 10 МБ.
     *
     * Берём минимум из upload_max_filesize и post_max_size: второй
     * ограничивает весь запрос целиком.
     */
    function max_upload_kb(?int $appLimitKb = null): int
    {
        $toKb = static function (string $value): ?int {
            $value = trim($value);

            if ($value === '' || $value === '0') {
                return null; // 0 или пусто у PHP означает «без ограничения»
            }

            $unit = strtolower(substr($value, -1));
            $number = (float) $value;

            return (int) match ($unit) {
                'g' => $number * 1024 * 1024,
                'm' => $number * 1024,
                'k' => $number,
                default => $number / 1024,
            };
        };

        $limits = array_filter([
            $toKb((string) ini_get('upload_max_filesize')),
            $toKb((string) ini_get('post_max_size')),
            $appLimitKb,
        ]);

        return $limits ? (int) min($limits) : ($appLimitKb ?? 2048);
    }
}

if (! function_exists('max_upload_label')) {
    /**
     * Тот же лимит, но человеческой строкой для подсказки в форме.
     */
    function max_upload_label(?int $appLimitKb = null): string
    {
        $kb = max_upload_kb($appLimitKb);

        return $kb >= 1024
            ? rtrim(rtrim(number_format($kb / 1024, 1, ',', ' '), '0'), ',') . ' МБ'
            : $kb . ' КБ';
    }
}
