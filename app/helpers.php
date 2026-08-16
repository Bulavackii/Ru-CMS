<?php

if (! function_exists('site_has_products')) {
    /**
     * Есть ли на сайте хоть один ОПУБЛИКОВАННЫЙ товар.
     *
     * По этому признаку в шапке показывается корзина: магазина нет — нет и
     * ссылки на него.
     *
     * ⚠️ Раньше признак считался в ДВУХ местах (шапка сайта и фрагмент
     * оформления) и в обоих БЕЗ проверки публикации: черновик товара —
     * материал, который посетитель открыть не может, — всё равно зажигал
     * корзину. Купить в ней было нечего.
     *
     * ⚠️ Памяти между вызовами здесь НЕТ намеренно. Сначала стояла
     * static-переменная «на время запроса» — и первый же тест это вскрыл:
     * static живёт, пока жив ПРОЦЕСС, поэтому второй тест получил ответ
     * первого. Под Octane и RoadRunner было бы ровно то же самое на боевом
     * сайте: добавили первый товар, а корзина не появляется до перезапуска.
     *
     * Стоимость отказа от памяти невелика: это EXISTS по индексируемому
     * полю, вызываемый один-два раза на страницу.
     */
    function site_has_products(): bool
    {
        try {
            return \Modules\News\Models\News::query()
                ->where('template', 'products')
                ->published()
                ->exists();
        } catch (\Throwable $e) {
            // До установки таблицы может не быть — шапка обязана отрисоваться.
            return false;
        }
    }
}

if (! function_exists('content_excerpt')) {
    /**
     * Краткое изложение материала для карточки.
     *
     * ⚠️ Обычный strip_tags СКЛЕИВАЕТ слова: он убирает `</p><p>` не
     * оставляя ничего на их месте, и конец абзаца прирастает к началу
     * следующего — «…отвечаем на вопросы в чате.Запись остаётся в архиве».
     * Владелец поймал это на карточке шаблона «Игры». Поэтому блочные теги
     * и переводы строк сначала превращаются в пробел, и только потом
     * снимается разметка.
     *
     * Шорткоды убираются тоже: `[captcha preset="x"]` в анонсе — мусор.
     */
    function content_excerpt(?string $html, int $limit = 130): string
    {
        $text = (string) $html;

        // Блочные границы — это пробел, а не пустое место.
        $text = preg_replace('~<\s*(br|/p|/div|/li|/h[1-6]|/tr|/td|/blockquote)[^>]*>~i', ' ', $text);

        $text = strip_tags($text);
        $text = function_exists('strip_shortcodes') ? strip_shortcodes($text) : $text;
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('~\s+~u', ' ', $text));

        return \Illuminate\Support\Str::limit($text, $limit);
    }
}

if (! function_exists('install_lock_path')) {
    /**
     * Путь к файлу-замку установки.
     *
     * Единственное место, где он вычисляется. Раньше install_lock_path()
     * был вписан в одиннадцати файлах, и подменить его для тестов было нечем —
     * прогон требовал настоящего файла в рабочем каталоге владельца.
     */
    function install_lock_path(): string
    {
        return storage_path((string) config('install.lock', 'install.lock'));
    }
}

/**
 * 📚 LOCAL_FONTS — реестр самохостящихся шрифтов (latin + cyrillic).
 *
 * Файлы вендорятся из @fontsource в public/assets/fonts/{slug}/ и не
 * требуют обращения к Google Fonts/Bunny Fonts. Ключ — slug для
 * font_provider='local' в настройках темы, значение — CSS font-family,
 * подпись для UI и вид гарнитуры (для группировки в списках).
 *
 * ⚖️ Лицензии. Все шрифты, кроме Roboto Slab, распространяются под SIL Open
 * Font License 1.1; Roboto Slab — под Apache License 2.0. Обе разрешают
 * коммерческое использование и распространение в составе продукта. OFL при
 * этом ТРЕБУЕТ, чтобы файл лицензии сопровождал шрифт, поэтому рядом с
 * каждым набором лежит его LICENSE — не удалять при чистке репозитория.
 *
 * Ubuntu намеренно не берём: он под собственной Ubuntu Font Licence с
 * оговорками об именовании производных. Лицензия свободная, но особая, а
 * заводить единственное исключение ради одной гарнитуры незачем.
 *
 * В наборе только гарнитуры С КИРИЛЛИЦЕЙ: латинский шрифт на русском сайте
 * подменяется системным, и владелец видит не то, что выбрал.
 */
if (!defined('LOCAL_FONTS')) {
    define('LOCAL_FONTS', [
        // Без засечек — основной рабочий текст
        'inter'          => ['family' => 'Inter',          'label' => 'Inter',          'kind' => 'sans'],
        'roboto'         => ['family' => 'Roboto',         'label' => 'Roboto',         'kind' => 'sans'],
        'open-sans'      => ['family' => 'Open Sans',      'label' => 'Open Sans',      'kind' => 'sans'],
        'montserrat'     => ['family' => 'Montserrat',     'label' => 'Montserrat',     'kind' => 'sans'],
        'manrope'        => ['family' => 'Manrope',        'label' => 'Manrope',        'kind' => 'sans'],
        'nunito'         => ['family' => 'Nunito',         'label' => 'Nunito',         'kind' => 'sans'],
        'rubik'          => ['family' => 'Rubik',          'label' => 'Rubik',          'kind' => 'sans'],
        'raleway'        => ['family' => 'Raleway',        'label' => 'Raleway',        'kind' => 'sans'],
        'fira-sans'      => ['family' => 'Fira Sans',      'label' => 'Fira Sans',      'kind' => 'sans'],
        'noto-sans'      => ['family' => 'Noto Sans',      'label' => 'Noto Sans',      'kind' => 'sans'],
        'golos-text'     => ['family' => 'Golos Text',     'label' => 'Golos Text',     'kind' => 'sans'],
        'onest'          => ['family' => 'Onest',          'label' => 'Onest',          'kind' => 'sans'],
        'pt-sans'        => ['family' => 'PT Sans',        'label' => 'PT Sans',        'kind' => 'sans'],
        'oswald'         => ['family' => 'Oswald',         'label' => 'Oswald',         'kind' => 'sans'],

        // С засечками — заголовки и длинные тексты
        'pt-serif'       => ['family' => 'PT Serif',       'label' => 'PT Serif',       'kind' => 'serif'],
        'merriweather'   => ['family' => 'Merriweather',   'label' => 'Merriweather',   'kind' => 'serif'],
        'lora'           => ['family' => 'Lora',           'label' => 'Lora',           'kind' => 'serif'],
        'roboto-slab'    => ['family' => 'Roboto Slab',    'label' => 'Roboto Slab',    'kind' => 'serif'],

        // Моноширинные — код и таблицы
        'jetbrains-mono' => ['family' => 'JetBrains Mono', 'label' => 'JetBrains Mono', 'kind' => 'mono'],
        'roboto-mono'    => ['family' => 'Roboto Mono',    'label' => 'Roboto Mono',    'kind' => 'mono'],

        // Рукописный — акценты, подписи
        'caveat'         => ['family' => 'Caveat',         'label' => 'Caveat',         'kind' => 'hand'],
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
            'phosphor' => local_css('phosphor-icons.css', $version),
            'boxicons' => local_css('boxicons.css', $version),
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
 * 🌗 theme_is_dark() — тёмная ли тема сайта по цвету её фона.
 *
 * Класс `fx-theme-dark` на теге body включает весь тёмный набор: подложки
 * карточек, цвет надписи поверх акцента, приглушённый текст. Признак жил
 * блоком `@php` внутри `layouts/frontend` — и макет входа/регистрации
 * (`layouts/guest`) его попросту не имел: страницы входа оставались светлыми
 * при тёмной теме сайта. Ровно тот случай «одно и то же в двух местах»,
 * который в этом проекте уже разъезжался не раз, поэтому признак здесь один.
 *
 * Порог 0.45 по относительной яркости WCAG — то же, что считает
 * readable_ink(). Непонятный цвет означает «не тёмная»: прежнее поведение.
 *
 * @param string|null $background HEX-цвет фона темы (#rgb или #rrggbb)
 */
if (!function_exists('theme_is_dark')) {
    function theme_is_dark(?string $background): bool
    {
        $hex = ltrim(trim((string) $background), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (!preg_match('~^[0-9a-fA-F]{6}$~', $hex)) {
            return false;
        }

        $channel = function (int $value): float {
            $c = $value / 255;

            return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };

        $luminance = 0.2126 * $channel((int) hexdec(substr($hex, 0, 2)))
            + 0.7152 * $channel((int) hexdec(substr($hex, 2, 2)))
            + 0.0722 * $channel((int) hexdec(substr($hex, 4, 2)));

        return $luminance < 0.45;
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

        if ($html === '') {
            return $html;
        }

        // Абзац, в котором кроме шорткода ничего нет, превращаем в блок.
        //
        // Форма и каптча — блочная разметка, а абзац блок внутри себя не
        // держит: разборщик HTML выбрасывает его наружу, и выравнивание,
        // заданное этому абзацу в редакторе, остаётся на пустой оболочке, а
        // вставка встаёт отдельно и всегда слева. Div держит блок и
        // выравнивание доносит.
        $blank = '(?:\s|&nbsp;|&#160;|<br\s*/?>)*';

        $html = preg_replace(
            '~<p([^>]*)>' . $blank . '(\[(?:captcha|form|map|sitemap)[^\]]*\])' . $blank . '</p>~i',
            '<div$1>$2</div>',
            $html
        ) ?? $html;

        if (str_contains($html, '[captcha')) {
            $html = preg_replace_callback(
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

        // Карта: [map q="Курск, улица Ленина 1"] или [map] с адресом по
        // умолчанию. Пока посетитель не нажмёт кнопку, к картографу не уходит
        // ни одного запроса — ни адреса страницы, ни IP. Это и про
        // персональные данные, и про скорость загрузки.
        if (str_contains($html, '[map')) {
            $html = preg_replace_callback(
                '~\[map(?:\s+q=(["\'])(?<q>[^"\']{1,160})\1)?\s*\]~i',
                function (array $match): string {
                    return (string) view('frontend.partials.map', [
                        'query' => trim($match['q'] ?? ''),
                    ])->render();
                },
                $html
            ) ?? $html;
        }

        // Карта сайта: [sitemap]. Список собирается из базы при каждом
        // заходе — добавили страницу или раздел, они появились сами.
        // Вписанный руками список неизбежно расходится с сайтом: прежняя
        // карта ссылалась на «Концепцию», «Прайс-лист» и «Выполненные
        // работы», которых давно нет.
        if (str_contains($html, '[sitemap')) {
            $html = preg_replace_callback(
                '~\[sitemap\s*\]~i',
                fn (): string => (string) view('frontend.partials.sitemap')->render(),
                $html
            ) ?? $html;
        }

        // Формы: [form slug="obratnaya-svyaz"]. Несуществующая или выключенная
        // форма ничего не рисует и не роняет страницу — материал с забытым
        // шорткодом должен открываться как обычно.
        if (str_contains($html, '[form')) {
            $html = preg_replace_callback(
                '~\[form\s+slug=(["\'])(?<slug>[a-z0-9\-_]+)\1\s*\]~i',
                function (array $match): string {
                    if (!function_exists('form_render')) {
                        return '';
                    }

                    return (string) form_render($match['slug']);
                },
                $html
            ) ?? $html;
        }

        return $html;
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

if (! function_exists('social_links')) {
    /**
     * Ссылки на страницы проекта в сетях — один список на подвал сайта и
     * подвал панели.
     *
     * Раньше список был записан в двух шаблонах, и адреса уже разошлись:
     * в панели стоял vk.com/ru_cms, на сайте vk.com/example. Теперь адреса
     * приходят из конфигурации, то есть правятся в .env, а не в разметке.
     *
     * Пустое значение убирает значок из ряда, а не рисует ссылку в никуда:
     * не у каждого владельца есть страница в каждой из сетей.
     */
    function social_links(): array
    {
        // Фирменные цвета знакомых сетей. Ключ определяется по адресу, а не
        // по названию пункта: владелец волен назвать пункт как угодно, а
        // глиф в подвале должен остаться правильным.
        $known = [
            'vk'     => ['label' => 'ВКонтакте', 'color' => '#0077FF', 'match' => 'vk.com'],
            'max'    => ['label' => 'MAX',       'color' => '#3B4BF5', 'match' => 'max.ru'],
            'rutube' => ['label' => 'Rutube',    'color' => '#EE1B3D', 'match' => 'rutube.ru'],
            'github' => ['label' => 'GitHub',    'color' => '#181717', 'match' => 'github.com'],
        ];

        // Сначала меню «Соцсети»: так ссылки правятся в панели, а не в файле.
        // Молчаливый откат к конфигу нужен на случай, когда меню ещё нет —
        // при обновлении с прежней версии или пока не отработал сидер.
        $fromMenu = social_links_from_menu($known);

        if ($fromMenu !== []) {
            return $fromMenu;
        }

        $links = [];

        foreach ($known as $key => $meta) {
            $links[] = [
                'key'   => $key,
                'label' => $meta['label'],
                'color' => $meta['color'],
                'href'  => config('app.social.' . $key),
                'icon'  => null,
                'image' => null,
            ];
        }

        return array_values(array_filter($links, static fn (array $l): bool => filled($l['href'])));
    }
}

if (! function_exists('social_links_from_menu')) {
    /**
     * Ссылки на соцсети из меню с позицией social.
     *
     * Пустой массив означает «меню нет или в нём нечего показывать» — тогда
     * вызывающий берёт адреса из конфига.
     *
     * Сайт не должен падать из-за оформления: пока идёт установка, таблиц
     * меню может не быть вовсе, поэтому всё обёрнуто в try/catch.
     *
     * @param  array<string, array{label:string, color:string, match:string}>  $known
     * @return array<int, array{key:string, label:string, color:string, href:string, icon:?string}>
     */
    function social_links_from_menu(array $known): array
    {
        try {
            if (! class_exists(\Modules\Menu\Models\Menu::class)) {
                return [];
            }

            $menus = \Modules\Menu\Models\Menu::cachedByPosition('social');
        } catch (\Throwable $e) {
            return [];
        }

        $links = [];

        foreach ($menus as $menu) {
            foreach ($menu->items ?? [] as $item) {
                $href = trim((string) $item->url);

                if ($href === '') {
                    continue;
                }

                // Ключ — по домену: он и выбирает фирменный глиф в подвале.
                $key = 'link';
                $meta = ['color' => '#64748b'];

                foreach ($known as $candidate => $data) {
                    if (stripos($href, $data['match']) !== false) {
                        $key = $candidate;
                        $meta = $data;
                        break;
                    }
                }

                $links[] = [
                    'key'   => $key,
                    'label' => $item->title ?: ($meta['label'] ?? $href),
                    'color' => $meta['color'],
                    'href'  => $href,
                    // Значок пункта нужен незнакомым сетям: фирменного глифа
                    // для них нет, и без этого ссылка вышла бы пустой.
                    'icon'  => $item->icon ?: null,
                    // Своя картинка важнее всего: загрузили — показывается она,
                    // даже если домен знакомый и фирменный глиф у нас есть.
                    'image' => $item->iconImageUrl(),
                ];
            }
        }

        return $links;
    }
}

if (! function_exists('contact_links')) {
    /**
     * 📇 Контакты подвала — из меню с позицией contacts.
     *
     * Раньше почта, телефон и адрес были вписаны прямо в шаблон подвала:
     * поменять их без разработчика было нельзя. Теперь это обычные пункты
     * меню, как соцсети.
     *
     * Мелкая подпись над значением («Написать», «Позвонить», «Адрес») НЕ
     * хранится отдельным полем: она выводится из схемы ссылки. Иначе у пункта
     * появилось бы поле, которого больше нигде в меню нет, и его пришлось бы
     * тащить через всю форму, валидацию и сидер ради трёх строк.
     *
     * Пустой массив означает «меню нет» — подвал тогда показывает то же, что
     * и раньше.
     *
     * @return array<int, array{kind:string, label:string, value:string, href:string, icon:?string, image:?string, external:bool}>
     */
    function contact_links(): array
    {
        try {
            if (! class_exists(\Modules\Menu\Models\Menu::class)) {
                return [];
            }

            $menus = \Modules\Menu\Models\Menu::cachedByPosition('contacts');
        } catch (\Throwable $e) {
            return [];
        }

        $links = [];

        foreach ($menus as $menu) {
            foreach ($menu->items ?? [] as $item) {
                $href = trim((string) $item->url);

                if ($href === '') {
                    continue;
                }

                // Вид контакта — по схеме адреса, а не по названию пункта:
                // владелец волен назвать его как угодно.
                if (str_starts_with($href, 'mailto:')) {
                    $kind = 'mail';
                    $label = __('frontend.footer.write');
                } elseif (str_starts_with($href, 'tel:')) {
                    $kind = 'phone';
                    $label = __('frontend.footer.call');
                } else {
                    $kind = 'address';
                    $label = __('frontend.footer.address');
                }

                $links[] = [
                    'kind'     => $kind,
                    'label'    => $label,
                    'value'    => $item->title,
                    'href'     => $href,
                    'icon'     => $item->icon ?: null,
                    'image'    => $item->iconImageUrl(),
                    // Значок «уходит на чужой сайт» уместен только у адреса:
                    // почта и телефон открываются приложением, а не вкладкой.
                    'external' => $kind === 'address',
                ];
            }
        }

        return $links;
    }
}

if (! function_exists('module_active_names')) {
    /**
     * Имена включённых модулей или null, если выяснить это нечем.
     *
     * null — не «модулей нет», а «состояние неизвестно»: идёт установка, БД
     * ещё не настроена или недоступна. В таком случае вызывающий код обязан
     * считать включённым всё, иначе мастер установки остался бы без модулей,
     * которые ему самому и нужны.
     *
     * Ответ запоминается в контейнере, а не в static-переменной: static живёт
     * до конца PHP-процесса, то есть протёк бы между тестами PHPUnit (все они
     * идут одним процессом) и между запросами Octane. Контейнер же у каждого
     * экземпляра приложения свой.
     *
     * @return array<int, string>|null
     */
    function module_active_names(): ?array
    {
        $key = 'modules.active_names';
        $app = app();

        // Обёртка массивом намеренно: instance() с null не считается
        // связанным (bound() проверяет isset), и значение выяснялось бы заново
        // при каждом обращении — ровно в том случае, когда БД недоступна и
        // каждая попытка стоит дороже всего.
        if (! $app->bound($key)) {
            $names = null;

            try {
                if (file_exists(install_lock_path())
                    && class_exists(\Modules\System\Models\Module::class)
                    && \Illuminate\Support\Facades\Schema::hasTable('modules')) {
                    $names = \Modules\System\Models\Module::where('active', true)
                        ->pluck('name')
                        ->all();

                    // Пустая таблица — это «модули ещё не переписаны в БД», а не
                    // «администратор выключил все двадцать». Так бывает сразу
                    // после чистой установки (наполняет её ModuleServiceProvider
                    // при первом запросе) и в тестах, где таблица создаётся
                    // миграцией и остаётся пустой. Считать это выключением
                    // значило бы показать панель вообще без разделов.
                    if ($names === [] && \Modules\System\Models\Module::count() === 0) {
                        $names = null;
                    }
                }
            } catch (\Throwable $e) {
                $names = null;
            }

            $app->instance($key, ['names' => $names]);
        }

        return $app->make($key)['names'];
    }
}

if (! function_exists('module_enabled')) {
    /**
     * Включён ли модуль. Пока состояние неизвестно — да (см. выше).
     *
     * Нужен маршрутам: routes/web.php подключает файлы семи модулей напрямую,
     * и без этой проверки они регистрировались независимо от того, выключен
     * модуль в панели или нет.
     */
    function module_enabled(string $name): bool
    {
        $names = module_active_names();

        return $names === null || in_array($name, $names, true);
    }
}

if (! function_exists('asset_v')) {
    /**
     * Адрес файла из public/ с отметкой времени изменения.
     *
     * Без неё браузер держит скрипт в кеше сколько сочтёт нужным, и после
     * обновления CMS человек продолжает работать со старым кодом — при этом
     * выглядит это не как «не обновилось», а как «перестало работать»: кнопки
     * на месте, а поведение прежнее. Отметка меняется вместе с файлом, то есть
     * кеш сбрасывается ровно тогда, когда файл действительно правили.
     *
     * mtime, а не хеш содержимого: считать хеш на каждый запрос дороже, а
     * задача та же.
     */
    function asset_v(string $path): string
    {
        $url = asset($path);
        $file = public_path($path);

        if (! is_file($file)) {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . 'v=' . filemtime($file);
    }
}

if (! function_exists('outbound_allowed')) {
    /**
     * Можно ли сейчас выходить в интернет.
     *
     * Один рубильник на всю систему (APP_STANDALONE в .env). Смысл в том,
     * чтобы «не ходить наружу» было ОДНИМ решением, а не двадцатью: гасить
     * оплату, доставку, SMS, оповещения, обновления и выгрузки SEO по
     * отдельности — верный способ однажды пропустить одну и не заметить.
     *
     * Проверять эту функцию обязан КАЖДЫЙ исходящий вызов перед отправкой.
     */
    function outbound_allowed(): bool
    {
        return ! config('app.standalone', false);
    }
}

if (! function_exists('send_alert')) {
    /**
     * Сообщить о сбое на заданный владельцем адрес.
     *
     * Раньше здесь был жёстко прошитый Telegram, причём в обработчике
     * исключений он вызывался через file_get_contents БЕЗ таймаута: при
     * недоступности сервиса каждая необработанная ошибка подвешивала запрос
     * до default_socket_timeout, то есть до минуты. Внешний сервис получал
     * возможность положить сайт.
     *
     * Теперь это обычный POST с JSON на адрес из настроек — куда его
     * направить, решает владелец: бот MAX, корпоративный чат, своя
     * страница-приёмник. CMS не привязана ни к одному сервису.
     *
     * Никогда не бросает исключений: оповещение об ошибке не имеет права
     * стать второй ошибкой.
     */
    function send_alert(string $level, string $message, array $context = []): bool
    {
        $url = (string) config('services.alerts.webhook', '');

        if ($url === '' || ! outbound_allowed()) {
            return false;
        }

        try {
            $headers = ['Accept' => 'application/json'];
            $token = (string) config('services.alerts.token', '');

            if ($token !== '') {
                $headers['Authorization'] = 'Bearer ' . $token;
            }

            return \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->timeout((int) config('services.alerts.timeout', 5))
                ->post($url, [
                    'level'   => $level,
                    'message' => $message,
                    'site'    => config('app.name'),
                    'url'     => request()?->fullUrl(),
                    'user'    => auth()->id(),
                    'time'    => now()->toDateTimeString(),
                    'context' => $context,
                ])->successful();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Не удалось отправить оповещение', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

if (! function_exists('qr_svg')) {
    /**
     * QR-код строки картинкой SVG.
     *
     * Своим генератором (App\Support\QrCode), без единой зависимости и без
     * обращения наружу: адрес оплаты нельзя отдавать чужому сервису,
     * который «нарисует картинку».
     *
     * Возвращает пустую строку, если построить код не удалось, — страница
     * оплаты не должна падать из-за картинки.
     */
    function qr_svg(string $строка, int $масштаб = 6): string
    {
        if (trim($строка) === '') {
            return '';
        }

        try {
            // ⚠️ Второй параметр — МАСШТАБ модуля, а не ширина картинки:
            // передашь туда 256 — получишь полотно в тысячи пикселей.
            // Размер на странице задаёт CSS.
            return \App\Support\QrCode::svg($строка, $масштаб);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Не удалось построить QR: ' . $e->getMessage());

            return '';
        }
    }
}

if (! function_exists('auth_pattern_svg')) {
    /**
     * 🔲 Фактура для левой колонки страниц входа.
     *
     * Это НАСТОЯЩИЙ QR-код адреса сайта, нарисованный своим генератором
     * (App\Support\QrCode) и увеличенный так, что в кадр попадает лишь угол.
     * Смысл не в том, чтобы его сканировали — из кадра он всё равно
     * обрезан, — а в том, что модульная сетка и есть облик этой CMS:
     * тот же рисунок собирается на странице привязки двухфакторной
     * проверки. Прежняя фактура была ровной сеткой 42×42 из двух
     * градиентов — она не значила ничего и встречается на каждой второй
     * странице входа.
     *
     * Возвращается голая матрица квадратов: ни белой подложки, ни полей —
     * цвет и прозрачность задаёт CSS колонки. Считается один раз и живёт
     * в кеше: рисовать код на каждый заход входа незачем.
     */
    function auth_pattern_svg(): string
    {
        $url = (string) config('app.url', 'https://localhost');

        return \Illuminate\Support\Facades\Cache::remember(
            'auth.aside.pattern.' . md5($url),
            now()->addDay(),
            static function () use ($url) {
                try {
                    $matrix = \App\Support\QrCode::matrix($url);
                } catch (\Throwable $e) {
                    // Слишком длинный адрес или иная неожиданность: колонка
                    // просто останется без фактуры, а не уронит вход.
                    return '';
                }

                $size = count($matrix);
                $parts = [];

                foreach ($matrix as $row => $cells) {
                    $col = 0;

                    while ($col < $size) {
                        if ($cells[$col] !== 1) {
                            $col++;
                            continue;
                        }

                        $run = 0;

                        while ($col + $run < $size && $cells[$col + $run] === 1) {
                            $run++;
                        }

                        $parts[] = '<rect x="' . $col . '" y="' . $row . '" width="' . $run . '" height="1"/>';
                        $col += $run;
                    }
                }

                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $size . ' ' . $size . '" '
                    . 'preserveAspectRatio="xMinYMin slice" aria-hidden="true" focusable="false">'
                    . '<g fill="currentColor">' . implode('', $parts) . '</g></svg>';
            }
        );
    }
}

if (! function_exists('after_response')) {
    /**
     * Выполнить работу ПОСЛЕ того, как ответ ушёл в браузер.
     *
     * Нужно для писем: очередь в проекте по умолчанию `sync`, поэтому
     * даже слушатель с ShouldQueue выполнялся бы прямо в запросе, и
     * медленный SMTP заставлял бы админа смотреть в белый экран (а при
     * недоступном сервере — ловить фатальную ошибку по лимиту времени).
     *
     * Ошибки внутри гасятся и уходят в лог: ответ уже отдан, показать их
     * всё равно некому, а падение здесь ломает завершение запроса.
     */
    function after_response(callable $work, array $context = []): void
    {
        app()->terminating(function () use ($work, $context) {
            try {
                $work();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error(
                    'Работа после ответа не выполнена',
                    $context + ['error' => $e->getMessage()]
                );
            }
        });
    }
}

if (! function_exists('template_numbers')) {
    /**
     * Сквозные номера материалов внутри шаблона: id => номер.
     *
     * Номер в углу карточки раньше брался из `$loop->iteration`, то есть был
     * ПОЗИЦИЕЙ В СПИСКЕ. Отсюда две неожиданности, обе замечены владельцем:
     * добавленный материал получал номер 01 (он же первый в списке — списки
     * идут свежим вперёд), а на второй странице нумерация начиналась заново.
     *
     * Номер должен быть свойством самого материала: заведён пятым — значит
     * пятый, где бы он ни оказался в списке. Считаем от старых к новым, тогда
     * каждое следующее добавление ПРОДОЛЖАЕТ нумерацию, а не сдвигает чужие.
     *
     * ⚠️ Порядок — по created_at, потом по id: даты у материалов, заведённых
     * сидером одной пачкой, совпадают до секунды, и без второго ключа порядок
     * между ними менялся бы от запроса к запросу вместе с номерами.
     *
     * Результат запоминается на время запроса: партиал зовёт функцию для
     * каждой карточки, а список для шаблона один.
     */
    function template_numbers(string $template): array
    {
        static $память = [];

        if (isset($память[$template])) {
            return $память[$template];
        }

        try {
            $ids = \Modules\News\Models\News::query()
                ->where('template', $template)
                ->published()
                ->orderBy('created_at')
                ->orderBy('id')
                ->pluck('id');
        } catch (\Throwable $e) {
            return $память[$template] = [];
        }

        $номера = [];

        foreach ($ids as $позиция => $id) {
            $номера[$id] = $позиция + 1;
        }

        return $память[$template] = $номера;
    }
}
