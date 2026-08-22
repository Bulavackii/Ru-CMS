<?php

namespace Modules\Install\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Services\SecurityService;
use App\Services\SubscriptionService;

class InstallController extends Controller
{
    /**
     * Страны, доступные на экране приветствия. Не берём это из
     * config('localization.*') — там другая структура (supported_countries,
     * без flag/native_name/currency_code), рассчитанная на нужды самого
     * приложения, а не мастера установки.
     */
    private const COUNTRY_PRESETS = [
        'RU' => ['name' => 'Россия', 'native_name' => 'Россия', 'flag' => '🇷🇺', 'lang' => 'Русский', 'locale' => 'ru', 'timezone' => 'Europe/Moscow', 'currency_code' => 'RUB', 'currency_symbol' => '₽', 'date_format' => 'd.m.Y', 'time_format' => 'H:i', 'decimal_separator' => ',', 'thousands_separator' => ' ', 'decimal_places' => 2],
        'BY' => ['name' => 'Беларусь', 'native_name' => 'Беларусь', 'flag' => '🇧🇾', 'lang' => 'Русский', 'locale' => 'ru', 'timezone' => 'Europe/Minsk', 'currency_code' => 'BYN', 'currency_symbol' => 'Br', 'date_format' => 'd.m.Y', 'time_format' => 'H:i', 'decimal_separator' => ',', 'thousands_separator' => ' ', 'decimal_places' => 2],
        'KZ' => ['name' => 'Казахстан', 'native_name' => 'Қазақстан', 'flag' => '🇰🇿', 'lang' => 'Русский', 'locale' => 'ru', 'timezone' => 'Asia/Almaty', 'currency_code' => 'KZT', 'currency_symbol' => '₸', 'date_format' => 'd.m.Y', 'time_format' => 'H:i', 'decimal_separator' => ',', 'thousands_separator' => ' ', 'decimal_places' => 2],
        'US' => ['name' => 'США', 'native_name' => 'United States', 'flag' => '🇺🇸', 'lang' => 'English', 'locale' => 'en', 'timezone' => 'America/New_York', 'currency_code' => 'USD', 'currency_symbol' => '$', 'date_format' => 'm/d/Y', 'time_format' => 'h:i A', 'decimal_separator' => '.', 'thousands_separator' => ',', 'decimal_places' => 2],
    ];

    /**
     * Единственная поддерживаемая мастером СУБД. PostgreSQL — открытая,
     * бесплатная и не завязанная ни на один вендор; MySQL/MariaDB и SQLite
     * сознательно убраны из установщика, чтобы не плодить конфигурации,
     * которые никто не тестирует.
     */
    private const DB_DEFAULT_PORT = '5432';

    /**
     * Порядок шагов и флаг сессии, наличие которого обязательно для
     * доступа к шагу. null — шаг всегда доступен.
     */
    private const STEP_PREREQUISITES = [
        'welcome' => null,
        'requirements' => null,
        'features' => null,
        'database' => null,
        'admin' => 'database',
        'smtp' => 'admin',
        'license' => 'smtp',
        'finish' => 'license',
    ];

    private SecurityService $securityService;
    private SubscriptionService $subscriptionService;

    /**
     * Ключи `.env`, которые финальный шаг запишет ОДНИМ разом в самом конце.
     *
     * Запись в `.env` посреди шага стоит запроса целиком: `php artisan serve`
     * следит за mtime этого файла и при изменении гасит серверный процесс
     * вместе с тем, что он сейчас выполняет. Поэтому всё, что финал хочет
     * записать, копится здесь и уходит на диск после работы.
     */
    private array $pendingEnv = [];

    public function __construct(SecurityService $securityService, SubscriptionService $subscriptionService)
    {
        $this->securityService = $securityService;
        $this->subscriptionService = $subscriptionService;
    }

    /** 🚀 Стартовая страница с выбором языка и страны */

    /**
     * По одной стране на каждый доступный язык.
     *
     * Страна нужна не сама по себе, а как источник пояса, валюты и форматов:
     * что-то подставить при установке всё равно надо. Уточняются они потом в
     * разделе «Локализация» — на первом экране это лишний выбор.
     *
     * @return array<string, array<string, mixed>>
     */
    private function localeChoices(): array
    {
        $available = function_exists('available_locales') ? available_locales() : ['ru'];
        $seen = [];
        $choices = [];

        foreach (self::COUNTRY_PRESETS as $code => $country) {
            $locale = $country['locale'] ?? 'ru';

            // Первая страна с этим языком и выигрывает: в списке пресетов
            // Россия стоит раньше Беларуси и Казахстана, поэтому русский
            // достаётся ей, а не им.
            if (! in_array($locale, $available, true) || isset($seen[$locale])) {
                continue;
            }

            $seen[$locale] = true;
            $choices[$code] = $country;
        }

        // Ни одного словаря не нашлось — показываем хотя бы русский, иначе на
        // первом шаге установки не будет ни одной кнопки.
        return $choices ?: ['RU' => self::COUNTRY_PRESETS['RU']];
    }

    public function welcome(Request $request)
    {
        // На первом шаге выбирают ЯЗЫК, а не страну, поэтому показываем ровно
        // столько кнопок, сколько языков реально есть в resources/lang.
        //
        // Раньше кнопок было четыре: Россия, Беларусь, Казахстан, США. Пока у
        // проекта были четыре словаря, каждая меняла язык. После того как
        // беларуский и казахский убрали, три кнопки из четырёх стали вести на
        // русский — под заголовком «Язык интерфейса» человек видел три
        // одинаковые подписи «Русский», различающиеся только флагом.
        //
        // Список строится по каталогам словарей, а не задан вручную: появится
        // новый язык с готовым пресетом — кнопка добавится сама.
        $presetCountries = $this->localeChoices();

        if ($request->has('country_code')) {
            $countryCode = strtoupper($request->get('country_code'));

            if (isset($presetCountries[$countryCode])) {
                $country = $presetCountries[$countryCode];
                session([
                    'install_country_code' => $countryCode,
                    'install_locale' => $country['locale'] ?? 'ru',
                    'install_timezone' => $country['timezone'] ?? 'Europe/Moscow',
                ]);

                app()->setLocale($country['locale'] ?? 'ru');
            }
        } else {
            $countryCode = session('install_country_code', 'RU');
            $country = $presetCountries[$countryCode] ?? $presetCountries['RU'];
            app()->setLocale($country['locale'] ?? 'ru');
        }

        return view('Install::welcome', [
            'currentCountry' => $countryCode ?? 'RU',
            'currentLocale' => app()->getLocale(),
            'presetCountries' => $presetCountries,
        ]);
    }

    /** 🔍 Системные требования */
    public function requirements()
    {
        $requirements = [
            'PHP >= 8.5' => version_compare(PHP_VERSION, '8.5.0', '>='),
            'PDO PostgreSQL (pdo_pgsql)' => extension_loaded('pdo') && extension_loaded('pdo_pgsql'),
            'OpenSSL'                   => extension_loaded('openssl'),
            'Mbstring'                  => extension_loaded('mbstring'),
            'Tokenizer'                 => extension_loaded('tokenizer'),
            'XML'                       => extension_loaded('xml'),
            'Ctype'                     => extension_loaded('ctype'),
            'JSON'                      => extension_loaded('json'),
            'Fileinfo'                  => extension_loaded('fileinfo'),
            'Zip'                       => extension_loaded('zip'),
            // Названия требований — технические идентификаторы: они же служат
            // ключами для подсказок во вьюхе, поэтому остаются нейтральными и
            // одинаковыми на всех языках. Переводятся только расшифровки.
            'GD / Imagick'              => extension_loaded('gd') || extension_loaded('imagick'),
            'Writable: storage/'        => is_writable(storage_path()),
            'Writable: bootstrap/cache' => is_writable(base_path('bootstrap/cache')),
        ];

        $allPassed = !in_array(false, $requirements, true);

        return view('Install::requirements', compact('requirements', 'allPassed'));
    }

    /**
     * 🎯 Презентация возможностей.
     *
     * Иконка и признак «ключевая» живут в коде, а заголовок с описанием —
     * в resources/lang/<locale>/install.php (секция features.items), чтобы
     * страница переводилась вместе со всем мастером.
     */
    private const FEATURE_CARDS = [
        ['key' => 'modular',       'icon' => 'blocks',          'highlight' => true],
        ['key' => 'security',      'icon' => 'shield-check',    'highlight' => true],
        ['key' => 'performance',   'icon' => 'zap'],
        ['key' => 'i18n',          'icon' => 'globe'],
        ['key' => 'backups',       'icon' => 'database-backup'],
        ['key' => 'updates',       'icon' => 'refresh-cw'],
        ['key' => 'subscriptions', 'icon' => 'credit-card'],
        ['key' => 'analytics',     'icon' => 'bar-chart-3'],
        ['key' => 'api',           'icon' => 'plug'],
        ['key' => 'responsive',    'icon' => 'smartphone'],
        ['key' => 'comments',      'icon' => 'message-square'],
        ['key' => 'push',          'icon' => 'bell'],
    ];

    public function features()
    {
        $features = array_map(static fn (array $card): array => [
            'icon'        => $card['icon'],
            'title'       => __("install.features.items.{$card['key']}.title"),
            'description' => __("install.features.items.{$card['key']}.desc"),
            'highlight'   => $card['highlight'] ?? false,
        ], self::FEATURE_CARDS);

        return view('Install::features', compact('features'));
    }

    /** ⚙️ Настройка БД и генерация .env */
    public function database(Request $request)
    {
        if ($request->isMethod('get')) {
            return view('Install::database', ['defaultPort' => self::DB_DEFAULT_PORT]);
        }

        // POST
        $v = Validator::make($request->all(), [
            'host'       => ['required', 'string', 'max:255'],
            'port'       => ['required', 'numeric'],
            'database'   => ['required', 'string', 'max:191'],
            'username'   => ['required', 'string', 'max:191'],
            'password'   => ['nullable', 'string', 'max:191'],
        ], [], [
            'host'     => __('install.attributes.host'),
            'port'     => __('install.attributes.port'),
            'database' => __('install.attributes.database'),
            'username' => __('install.attributes.username'),
            'password' => __('install.attributes.password'),
        ]);

        if ($v->fails()) {
            return back()->withErrors($v)->withInput();
        }

        $host = (string) $request->input('host');
        $port = (string) $request->input('port');
        $db   = (string) $request->input('database');
        $user = (string) $request->input('username');
        $pass = $request->input('password');

        // Проверка на SQL injection
        if ($this->securityService->detectSqlInjection($host . $db . $user)) {
            return back()->withErrors(['security' => __('install.errors.sql_injection')])->withInput();
        }

        // 1) Тест соединения БД
        $ok = $this->testConnection($host, $port, $db, $user, $pass, $err);
        if (!$ok) {
            return back()->withErrors(['database' => __('install.errors.db_connect', ['error' => $err])])->withInput();
        }

        // 2) Запись .env
        try {
            $countryCode = session('install_country_code', 'RU');
            $locale = session('install_locale', 'ru');
            $timezone = session('install_timezone', 'Europe/Moscow');

            $appKey = config('app.key');
            if (empty($appKey)) {
                $appKey = 'base64:' . base64_encode(random_bytes(32));
            }

            $this->writeEnv([
                'APP_URL'          => rtrim($request->getSchemeAndHttpHost(), '/'),
                'APP_KEY'          => $appKey,
                'APP_LOCALE'       => $locale,
                'APP_TIMEZONE'     => $timezone,
                'LOCALIZATION_DEFAULT_COUNTRY' => $countryCode,
                'DB_CONNECTION'    => 'pgsql',
                'DB_HOST'          => $host,
                'DB_PORT'          => $port,
                'DB_DATABASE'      => $db,
                'DB_USERNAME'      => $user,
                'DB_PASSWORD'      => $pass,
                'SESSION_DRIVER'   => 'file', // Временно file до завершения установки
                'CACHE_STORE'      => 'file', // Временно file до завершения установки
                'QUEUE_CONNECTION' => 'sync', // Временно sync до завершения установки
            ]);
        } catch (\Throwable $e) {
            return back()->withErrors(['env' => __('install.errors.env_write', ['error' => $e->getMessage()])])->withInput();
        }

        // 3) Очистка конфигов/кэша
        try {
            Artisan::call('config:clear');
            if (!config('app.key')) {
                Artisan::call('key:generate', ['--force' => true]);
            }
        } catch (\Throwable $e) {
            // не фатально
        }

        session(['install.completed.database' => true]);

        return redirect()->route('install.admin');
    }

    /** 👤 Создание администратора + миграции */
    public function admin(Request $request)
    {
        if ($redirect = $this->guardStep('admin')) {
            return $redirect;
        }

        if ($request->isMethod('get')) {
            return view('Install::admin');
        }

        // POST
        $v = Validator::make($request->all(), [
            'name'     => ['required', 'string', 'max:191'],
            'email'    => ['required', 'email', 'max:191'],
            'password' => ['required', 'string', 'min:8', 'max:191'],
        ], [], [
            'name'     => __('install.attributes.name'),
            'email'    => __('install.attributes.email'),
            'password' => __('install.attributes.password'),
        ]);

        if ($v->fails()) {
            return back()->withErrors($v)->withInput();
        }

        // Проверка сложности пароля
        $passwordCheck = $this->securityService->validatePasswordStrength($request->password);
        if (!$passwordCheck['valid']) {
            return back()->withErrors(['password' => implode(', ', $passwordCheck['errors'])])->withInput();
        }

        try {
            // Все миграции проекта (включая модульные) живут в единой
            // database/migrations/ — одного вызова достаточно, отдельный
            // проход по путям модулей больше не нужен.
            Artisan::call('migrate', ['--force' => true]);

            // Регистрация модулей в таблице `modules`. Без этого шага таблица
            // после установки остаётся пустой: миграции её только создают, а
            // наполняет отдельная команда. Пустая таблица = пустая вкладка
            // «Модули» в админке и незагруженные модули (работали бы только
            // те, что перечислены в $legacyModules у ModuleServiceProvider).
            try {
                Artisan::call('modules:sync');
            } catch (\Throwable $e) {
                // Не блокируем установку: ModuleServiceProvider доведёт
                // синхронизацию сам при следующем запросе.
                Log::warning('Не удалось выполнить modules:sync при установке', [
                    'error' => $e->getMessage(),
                ]);
            }

            // Симлинк public/storage → storage/app/public. Без него любой
            // загруженный файл (картинки новостей, вложения, медиа визуального
            // редактора) отдаёт 404: ссылки строятся через asset('storage/...').
            try {
                if (!file_exists(public_path('storage'))) {
                    Artisan::call('storage:link');
                }
            } catch (\Throwable $e) {
                // На части хостингов симлинки запрещены — это не повод
                // прерывать установку, предупредим на финальном экране.
                $this->pushInstallWarning(__('install.errors.storage_link'));
                Log::warning('Не удалось создать симлинк storage', [
                    'error' => $e->getMessage(),
                ]);
            }

            // Проверка обязательных таблиц (без них система нежизнеспособна)
            $missing = $this->verifyInstalledTables();
            if (!empty($missing)) {
                $output = trim(Artisan::output());
                return back()->withErrors([
                    'migrations' => __('install.errors.migrations_missing', ['tables' => implode(', ', $missing)]),
                    'artisan'    => $output ?: __('install.errors.artisan_empty'),
                ])->withInput();
            }

            // Опциональные таблицы модулей — не блокируем установку, но
            // предупредим пользователя на финальном экране.
            if ($warning = $this->optionalModuleTablesWarning()) {
                $this->pushInstallWarning($warning);
            }
        } catch (\Throwable $e) {
            $output = trim(Artisan::output());
            return back()->withErrors([
                'migrate' => __('install.errors.migrate', ['error' => $e->getMessage()]),
                'artisan' => $output ?: __('install.errors.artisan_empty'),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ])->withInput();
        }

        try {
            // 4) Создание администратора через модель — не в обход
            // кастов/хуков (пароль хэшируется через cast 'password' => 'hashed')
            $admin = User::where('email', $request->email)->first();
            if (!$admin) {
                $countryCode = session('install_country_code', 'RU');
                $locale = session('install_locale', 'ru');

                $userData = [
                    'name'     => $request->name,
                    'email'    => $request->email,
                    'password' => $request->password,
                    'is_admin' => true,
                ];

                if (Schema::hasColumn('users', 'country_code')) {
                    $userData['country_code'] = $countryCode;
                }
                if (Schema::hasColumn('users', 'locale')) {
                    $userData['locale'] = $locale;
                }

                $admin = User::create($userData);
            }

            // Запоминаем созданного администратора: на финальном шаге он будет
            // авторизован автоматически, чтобы после установки попасть сразу
            // в админку, а не на страницу входа.
            if ($admin) {
                session(['install_admin_id' => $admin->id]);
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['user' => __('install.errors.user_create', ['error' => $e->getMessage()])])->withInput();
        }

        session(['install.completed.admin' => true]);

        return redirect()->route('install.smtp');
    }

    /**
     * ✉️ Настройка почты (SMTP). Реквизиты пишутся в .env (MAIL_*) и затем
     * используются приложением для отправки писем — в первую очередь для
     * восстановления доступа к админке по e-mail («забыли пароль»). Шаг
     * необязателен: можно пропустить и настроить почту позже в .env/админке.
     */
    public function smtp(Request $request)
    {
        if ($redirect = $this->guardStep('smtp')) {
            return $redirect;
        }

        if ($request->isMethod('get')) {
            return view('Install::smtp', [
                'mail' => [
                    'host'         => env('MAIL_HOST', ''),
                    'port'         => env('MAIL_PORT', '587'),
                    'username'     => env('MAIL_USERNAME', ''),
                    'encryption'   => $this->currentMailEncryption(),
                    'from_address' => env('MAIL_FROM_ADDRESS', ''),
                    'from_name'    => env('MAIL_FROM_NAME', config('app.name', 'Nexum Core')),
                ],
                'adminEmail' => optional(User::where('is_admin', true)->first())->email,
            ]);
        }

        // POST — пропуск шага (почту настроим позже)
        if ($request->boolean('smtp_skip')) {
            session(['install.completed.smtp' => true]);
            return redirect()->route('install.license');
        }

        // POST — сохранение реквизитов
        $v = Validator::make($request->all(), [
            'mail_host'         => ['required', 'string', 'max:255'],
            'mail_port'         => ['required', 'numeric'],
            'mail_username'     => ['nullable', 'string', 'max:255'],
            'mail_password'     => ['nullable', 'string', 'max:500'],
            'mail_encryption'   => ['required', 'in:tls,ssl,none'],
            'mail_from_address' => ['required', 'email', 'max:255'],
            'mail_from_name'    => ['nullable', 'string', 'max:255'],
        ], [], [
            'mail_host'         => __('install.attributes.mail_host'),
            'mail_port'         => __('install.attributes.mail_port'),
            'mail_username'     => __('install.attributes.mail_username'),
            'mail_password'     => __('install.attributes.mail_password'),
            'mail_encryption'   => __('install.attributes.mail_encryption'),
            'mail_from_address' => __('install.attributes.mail_from_address'),
            'mail_from_name'    => __('install.attributes.mail_from_name'),
        ]);

        if ($v->fails()) {
            return back()->withErrors($v)->withInput();
        }

        $host       = (string) $request->input('mail_host');
        $port       = (string) $request->input('mail_port');
        $username   = $request->input('mail_username');
        $password   = $request->input('mail_password');
        $encryption = (string) $request->input('mail_encryption'); // tls|ssl|none
        $fromAddr   = (string) $request->input('mail_from_address');
        $fromName   = (string) ($request->input('mail_from_name') ?: config('app.name', 'Nexum Core'));

        // Проверка подключения к SMTP (можно отключить галочкой) — чтобы не
        // записать в .env заведомо нерабочие реквизиты.
        if ($request->boolean('smtp_verify', true)) {
            $err = null;
            if (!$this->testSmtp($host, $port, $username, $password, $encryption, $err)) {
                return back()->withErrors([
                    'smtp' => __('install.errors.smtp_connect', ['error' => $err]),
                ])->withInput();
            }
        }

        // Запись в .env. Laravel 12 определяет шифрование по MAIL_SCHEME
        // (smtp — STARTTLS/без, smtps — неявный TLS на 465), поэтому пишем
        // именно его; MAIL_ENCRYPTION дублируем для наглядности.
        try {
            $scheme = $encryption === 'ssl' ? 'smtps' : 'smtp';
            $this->writeEnv([
                'MAIL_MAILER'       => 'smtp',
                'MAIL_HOST'         => $host,
                'MAIL_PORT'         => $port,
                'MAIL_USERNAME'     => (string) $username,
                'MAIL_PASSWORD'     => (string) $password,
                'MAIL_SCHEME'       => $scheme,
                'MAIL_ENCRYPTION'   => $encryption === 'none' ? '' : $encryption,
                'MAIL_FROM_ADDRESS' => $fromAddr,
                'MAIL_FROM_NAME'    => $fromName,
                // Без таймаута недоступный почтовый сервер держит запрос
                // до лимита PHP и роняет страницу.
                'MAIL_TIMEOUT'      => '5',
            ]);

            Artisan::call('config:clear');
        } catch (\Throwable $e) {
            return back()->withErrors(['env' => __('install.errors.env_write', ['error' => $e->getMessage()])])->withInput();
        }

        session(['install.completed.smtp' => true]);

        return redirect()->route('install.license');
    }

    /**
     * DEVELOPER_MODE=true в .env — значит это твоя собственная копия CMS,
     * а не инсталляция для клиента. В этом случае шаг лицензии можно
     * полностью пропустить: доступ к php artisan license:generate уже
     * гейтится тем же флагом, так что связь смысловая, не только косметика.
     */
    private function isDeveloperMode(): bool
    {
        // Читаем сам .env, а не env(): переменные окружения разбираются
        // один раз на буте и переживают перезапись файла — мастер как раз
        // переписывает .env на шаге базы данных. Пропади оттуда ключ,
        // env() продолжал бы отдавать прежнее значение, и кнопка обхода
        // лицензии осталась бы видна в клиентской копии.
        $path = base_path('.env');

        if (!is_readable($path)) {
            return false;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!preg_match('~^DEVELOPER_MODE\s*=\s*(.*)$~i', $line, $m)) {
                continue;
            }

            return in_array(strtolower(trim($m[1], " \t\"'")), ['1', 'true', 'on', 'yes'], true);
        }

        return false;
    }

    /** 🔑 Ввод лицензионного ключа или промокода */
    public function license(Request $request)
    {
        if ($redirect = $this->guardStep('license')) {
            return $redirect;
        }

        if ($request->isMethod('get')) {
            return view('Install::license', ['developerMode' => $this->isDeveloperMode()]);
        }

        // POST — пропуск шага разработчиком
        if ($request->boolean('developer_skip') && $this->isDeveloperMode()) {
            session(['install.completed.license' => true]);
            return redirect()->route('install.finish');
        }

        // POST
        $v = Validator::make($request->all(), [
            'license_key' => ['nullable', 'string', 'max:255'],
            'promo_code' => ['nullable', 'string', 'max:255'],
        ], [], [
            'license_key' => __('install.attributes.license_key'),
            'promo_code' => __('install.attributes.promo_code'),
        ]);

        if ($v->fails()) {
            return back()->withErrors($v)->withInput();
        }

        $licenseKey = $request->input('license_key');
        $promoCode = $request->input('promo_code');

        if (empty($licenseKey) && empty($promoCode)) {
            return back()->withErrors(['license' => __('install.errors.license_required')])->withInput();
        }

        if (!empty($promoCode)) {
            $promoResult = $this->subscriptionService->applyPromoCode($promoCode, 'basic');
            if (!$promoResult['success']) {
                return back()->withErrors(['promo_code' => $promoResult['message']])->withInput();
            }
            session(['install_promo_code' => $promoCode]);
            session(['install_promo_id' => $promoResult['promo_id']]);
        }

        if (!empty($licenseKey)) {
            if (!preg_match('/^[A-Z0-9]{8}-[A-Z0-9]{8}-[A-Z0-9]{8}-[A-Z0-9]{8}$/i', $licenseKey)) {
                return back()->withErrors(['license_key' => __('install.errors.license_format')])->withInput();
            }
            session(['install_license_key' => strtoupper($licenseKey)]);
        }

        try {
            $envLicenseKey = $licenseKey ?: 'PENDING';
            $this->writeEnv(['LICENSE_KEY' => $envLicenseKey]);
        } catch (\Throwable $e) {
            return back()->withErrors(['env' => __('install.errors.license_env', ['error' => $e->getMessage()])])->withInput();
        }

        session(['install.completed.license' => true]);

        return redirect()->route('install.finish');
    }

    /** 🏁 Завершение */
    public function finish()
    {
        if ($redirect = $this->guardStep('finish')) {
            return $redirect;
        }

        // ⚠️ Пропускаем перезапуск сервера разработки, иначе он убьёт этот
        // запрос на середине.
        //
        // `php artisan serve` каждые 500 мс сверяет mtime файла .env и при
        // изменении делает `$process->stop(5)` — гасит PHP-сервер ВМЕСТЕ с
        // запросом, который в этот момент выполняется (ServeCommand::handle).
        // Предыдущий шаг, «Лицензия», пишет в .env ключ и сразу ведёт сюда,
        // а этот шаг самый длинный — под нож попадал именно он. В браузере
        // это выглядит как ERR_CONNECTION_RESET на ровном месте, причём
        // содержимое к тому моменту уже записано.
        //
        // Замерено у владельца: .env изменён в 16:14:57.47, сервер поднят
        // заново в 16:14:58, установка прошла со второй попытки в 16:15:04.
        //
        // Отдаём мгновенную страницу ожидания; она сама вернётся сюда, когда
        // сервер уже перезапустился. На боевом хостинге такого сторожа нет —
        // там задержки не будет вовсе.
        if ($delay = $this->devServerRestartDelay()) {
            return view('Install::waiting', ['delay' => $delay, 'target' => route('install.finish')]);
        }

        // ⚠️ Шаг ставит ВСЁ содержимое за один запрос: меню, страницы,
        // демо-новости, слайдшоу, файлы, темы, формы, каптчу, фрагменты,
        // категории, переводы содержимого, SEO-записи и права. На типовом
        // хостинге max_execution_time — 30 секунд, и запрос обрывался на
        // середине: браузер показывал ERR_CONNECTION_RESET, а по нему
        // понять причину невозможно. Лимиты снимаем только здесь и только
        // если хостинг это разрешает (на многих ini_set запрещён —
        // тогда вызов просто ничего не делает).
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        try {
            $this->createSubscriptionFromInstall();
            $this->applyLocalizationSettings();
            $this->seedDefaultMenu();
            $this->seedDefaultPaymentMethods();
            $this->seedDefaultDeliveryMethods();
            $this->seedDefaultPages();
            // Демо-контент ставится всегда: отдельного шага с выбором больше
            // нет, а пустой сайт сразу после установки никому не нужен.
            $this->installDemoData();
            $this->seedDefaultSlideshows();
            $this->seedDefaultFiles();
            $this->seedDefaultNotification();
            $this->seedDefaultThemes();
            $this->seedDefaultForms();
            $this->seedDefaultCaptchaPresets();
            $this->seedDefaultFragments();
            $this->seedDemoOrder();
            // Категории — последними из содержимого: команда не только заводит
            // набор, но и раскладывает по нему уже созданные новости, страницы
            // и файлы. Вызови её раньше — привязывать будет нечего.
            $this->seedDefaultCategories();
            $this->seedContentTranslations();
            $this->seedDefaultAccessibility();
            // Витрина — ПОСЛЕ всего содержимого и ДО SEO: она заводит
            // продающие страницы и снимает с главной демо-разделы, которые
            // показывают шаблоны, но ничего не говорят о продукте. Раньше
            // сюда было бы нечего раскладывать, позже — SEO не описало бы
            // новые страницы.
            $this->seedPresentation();
            // SEO — ПОСЛЕ всего содержимого: команда описывает то, что уже
            // создано. Вызови её раньше — описывать будет нечего.
            $this->seedSeoPages();
            $this->seedRolesAndPermissions();
            $this->hardenPublicStorage();

            // Чистка кешей — ПОСЛЕ ответа и по одной команде в своей
            // защите: содержимое уже создано, и падение чистки не должно
            // отнимать у владельца финальный экран.
            after_response(function () {
                foreach (['config:clear', 'cache:clear'] as $command) {
                    try {
                        Artisan::call($command);
                    } catch (\Throwable $e) {
                        Log::warning('Install: не удалось выполнить ' . $command, ['error' => $e->getMessage()]);
                    }
                }
            });
        } catch (\Throwable $e) {
            Log::warning('Install finish error', ['error' => $e->getMessage()]);
            $this->pushInstallWarning(__('install.errors.finish_partial', ['error' => $e->getMessage()]));
        }

        $warnings = session('install.warnings', []);
        session()->forget('install.warnings');

        $countryCode = session('install_country_code', 'RU');

        // Авто-вход администратора, созданного на шаге /install/admin. Так
        // после завершения установки редирект уходит прямо в админку (/admin),
        // минуя страницу входа и личный кабинет пользователя на фронтенде.
        try {
            $adminId = session('install_admin_id');
            if ($adminId && !Auth::check()) {
                Auth::loginUsingId($adminId);
                request()->session()->regenerate();
            }
        } catch (\Throwable $e) {
            Log::warning('Install auto-login failed', ['error' => $e->getMessage()]);
        }

        // Накопленные ключи пишем ОДНИМ разом и только теперь, когда всё
        // содержимое уже создано: под сервером разработки эта запись
        // перезапускает сервер, и пусть это случится после работы, а не
        // посреди неё. writeEnv() при совпадении содержимого не пишет вовсе.
        if ($this->pendingEnv !== []) {
            try {
                $this->writeEnv($this->pendingEnv);
            } catch (\Throwable $e) {
                Log::warning('Install: не удалось дописать .env', ['error' => $e->getMessage()]);
            }
        }

        // Итог кладём в сессию и уходим коротким редиректом. Отдавать саму
        // страницу этим же запросом нельзя: он длинный, и любой обрыв на
        // последних байтах стоил бы владельцу финального экрана.
        //
        // Порядок здесь несущий. Сводку записываем на диск ЯВНО и только
        // потом создаём файл-замок: пока замка нет, мастер остаётся
        // открытым и повторный заход просто пройдёт шаг заново (сидеры
        // идемпотентны). А раз замок появился — значит сводка уже
        // сохранена, и любой следующий запрос найдёт итог.
        session(['install.summary' => [
            'warnings' => $warnings,
            'country' => $countryCode,
        ]]);
        session()->save();

        File::put(install_lock_path(), 'Installed at ' . now()->toDateTimeString());

        // Если ключи всё-таки записались, сервер разработки сейчас
        // перезапускается — обычный редирект увёл бы браузер на итог ровно в
        // ту секунду, когда порт ещё не поднялся. Отдаём ту же страницу
        // ожидания: она переждёт перезапуск и откроет итог на живом сервере.
        if ($delay = $this->devServerRestartDelay()) {
            return view('Install::waiting', ['delay' => $delay, 'target' => route('install.done')]);
        }

        return redirect()->route('install.done');
    }

    /**
     * Сколько миллисекунд ждать, чтобы не попасть под перезапуск сервера
     * разработки. Ноль — ждать не нужно.
     *
     * Сторож живёт только во встроенном сервере PHP (`php artisan serve`),
     * поэтому под nginx/php-fpm метод сразу отдаёт ноль. Запас поверх шага
     * опроса — на остановку старого процесса и старт нового.
     */
    private function devServerRestartDelay(): int
    {
        if (PHP_SAPI !== 'cli-server') {
            return 0;
        }

        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            return 0;
        }

        clearstatcache(false, $envPath);

        // 500 мс — шаг опроса ServeCommand, остальное — на перезапуск.
        $settleMs = 2000;
        $ageMs = (int) round((microtime(true) - filemtime($envPath)) * 1000);

        return max(0, $settleMs - $ageMs);
    }

    /**
     * 🎉 Итог установки — отдельным лёгким запросом.
     *
     * Сюда попадают и по редиректу с /install/finish, и обновлением
     * страницы после обрыва связи. Сводки в сессии нет (чужой браузер,
     * закладка, истёкшая сессия) — уводим на главную страницу сайта:
     * установка уже позади, показывать нечего.
     */
    public function done()
    {
        $summary = session('install.summary');

        if (!is_array($summary)) {
            return redirect('/');
        }

        return view('Install::finish', [
            'warnings' => $summary['warnings'] ?? [],
            'selectedCountry' => self::COUNTRY_PRESETS[$summary['country'] ?? 'RU'] ?? null,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Не даёт открыть шаг напрямую по URL, пока не пройден обязательный
     * предыдущий шаг (например /install/admin без настроенной БД).
     */
    private function guardStep(string $step): ?RedirectResponse
    {
        $prerequisite = self::STEP_PREREQUISITES[$step] ?? null;
        if ($prerequisite === null) {
            return null;
        }

        if (session("install.completed.{$prerequisite}")) {
            return null;
        }

        $routeMap = [
            'database' => 'install.database',
            'admin'    => 'install.admin',
            'smtp'     => 'install.smtp',
            'license'  => 'install.license',
        ];

        return redirect()
            ->route($routeMap[$prerequisite] ?? 'install.welcome')
            ->with('install_notice', __('install.errors.step_order'));
    }

    private function pushInstallWarning(string $message): void
    {
        $warnings = session('install.warnings', []);
        $warnings[] = $message;
        session(['install.warnings' => $warnings]);
    }

    private function testConnection(
        string $host,
        string $port,
        string $db,
        string $user,
        ?string $pass,
        ?string &$err = null
    ): bool {
        $tmp = [
            'driver' => 'pgsql',
            'host' => $host,
            'port' => $port,
            'database' => $db,
            'username' => $user,
            'password' => $pass,
            'charset' => 'utf8',
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ];

        $origDefault = config('database.default');

        try {
            config(['database.connections.__install__' => $tmp, 'database.default' => '__install__']);
            DB::purge('__install__');
            DB::connection('__install__')->getPdo();
            return true;
        } catch (\Throwable $e) {
            $err = $e->getMessage();
            return false;
        } finally {
            config(['database.default' => $origDefault]);
            try {
                DB::purge('__install__');
            } catch (\Throwable $e) {
            }
        }
    }

    /**
     * Проверка подключения к SMTP-серверу «вживую»: открываем соединение и
     * (если заданы) проверяем логин/пароль. Работает напрямую через Symfony
     * Mailer, не завися от текущего mail-конфига приложения.
     */
    private function testSmtp(
        string $host,
        string $port,
        ?string $user,
        ?string $pass,
        string $encryption,
        ?string &$err = null
    ): bool {
        try {
            // ssl → неявный TLS; tls → STARTTLS (auto); none → без шифрования
            $tls = $encryption === 'ssl' ? true : ($encryption === 'tls' ? null : false);

            $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
                $host,
                (int) $port,
                $tls
            );

            if (!empty($user)) {
                $transport->setUsername($user);
            }
            if (!empty($pass)) {
                $transport->setPassword($pass);
            }

            // Ограничиваем ожидание, чтобы форма не «висела» на глухом хосте.
            $stream = $transport->getStream();
            if (method_exists($stream, 'setTimeout')) {
                $stream->setTimeout(10);
            }

            $transport->start();
            $transport->stop();

            return true;
        } catch (\Throwable $e) {
            $err = $e->getMessage();
            return false;
        }
    }

    /**
     * Текущее «человеческое» шифрование из .env для предзаполнения формы:
     * MAIL_SCHEME=smtps → ssl, иначе смотрим MAIL_ENCRYPTION, по умолчанию tls.
     */
    private function currentMailEncryption(): string
    {
        $scheme = strtolower((string) env('MAIL_SCHEME', ''));
        if ($scheme === 'smtps') {
            return 'ssl';
        }

        $enc = strtolower((string) env('MAIL_ENCRYPTION', ''));
        if (in_array($enc, ['ssl', 'tls'], true)) {
            return $enc;
        }

        return 'tls';
    }

    private function writeEnv(array $pairs): void
    {
        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');
        if (!File::exists($envPath)) {
            if (File::exists($envExamplePath)) {
                File::copy($envExamplePath, $envPath);
            } else {
                File::put($envPath, "APP_NAME=\"Nexum Core\"\nAPP_ENV=local\nAPP_KEY=\nAPP_DEBUG=true\n");
            }
        }

        $content = File::get($envPath);

        foreach ($pairs as $key => $value) {
            $value = (string) $value;
            if ($key === 'APP_KEY' && str_starts_with($value, 'base64:')) {
                $line = $key . '=' . $value;
            } else {
                $escapedValue = str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value);
                $line = $key . '="' . $escapedValue . '"';
            }
            $pattern = "/^{$key}=.*$/m";
            if (preg_match($pattern, $content)) {
                // preg_replace_callback, а не preg_replace: обычный
                // preg_replace() трактует \1, \2 и т.п. в СТРОКЕ ЗАМЕНЫ как
                // backreference-подстановки, так что любой бэкслэш в
                // значении (например, путь Windows C:\...) ломает и портит
                // результат ещё до того, как файл вообще дойдёт до dotenv-
                // парсера.
                $content = preg_replace_callback($pattern, fn () => $line, $content);
            } else {
                $content .= PHP_EOL . $line;
            }
        }

        // Ничего не изменилось — файл не трогаем ВООБЩЕ.
        //
        // Это не экономия записи, а защита от перезапуска сервера: под
        // `php artisan serve` сторож каждые 500 мс сверяет mtime .env и при
        // любом изменении гасит серверный процесс вместе с текущим запросом.
        // Финальный шаг писал сюда LICENSE_KEY, который шаг лицензии уже
        // записал минутой раньше, — значение то же, а перезапуск настоящий,
        // и приходился он ровно на середину самой длинной работы мастера.
        if ($content === File::get($envPath)) {
            return;
        }

        @File::copy($envPath, $envPath . '.bak');
        $tmp = $envPath . '.tmp';
        File::put($tmp, $content);
        @rename($tmp, $envPath);
    }

    /**
     * Жёстко обязательные таблицы — без них система в принципе не
     * загрузится. Раньше сюда были захардкожены таблицы конкретных
     * опциональных модулей (news/categories/menus/...), из-за чего мастер
     * ложно "падал" на урезанных сборках без части модулей.
     */
    private function verifyInstalledTables(): array
    {
        $required = ['migrations', 'users', 'sessions', 'modules'];

        $missing = [];
        foreach ($required as $t) {
            try {
                if (!Schema::hasTable($t)) $missing[] = $t;
            } catch (\Throwable $e) {
                $missing[] = $t;
            }
        }
        return $missing;
    }

    /**
     * Таблицы опциональных модулей — их отсутствие не блокирует установку,
     * но стоит показать пользователю на финальном экране.
     */
    private function optionalModuleTablesWarning(): ?string
    {
        $optional = ['news', 'categories', 'menus', 'files', 'subscriptions', 'promo_codes', 'security_logs'];
        $missing = [];
        foreach ($optional as $t) {
            try {
                if (!Schema::hasTable($t)) $missing[] = $t;
            } catch (\Throwable $e) {
                $missing[] = $t;
            }
        }

        if (empty($missing)) {
            return null;
        }

        return __('install.errors.optional_tables', ['tables' => implode(', ', $missing)]);
    }

    /** 📦 Установка демо-данных */
    private function installDemoData(): void
    {
        $userId = DB::table('users')->where('is_admin', true)->value('id');

        if (!$userId) {
            return;
        }

        // Весь демо-контент создаётся в одной транзакции: либо появляется
        // целиком (категории + новости + меню), либо — при сбое на любой из
        // таблиц — откатывается полностью, не оставляя «половины» данных.
        DB::transaction(function () {
        // Демо-категории (колонка называется title, не name; template на
        // categories не существует — это отдельное поле только у News).
        // Идемпотентно: если категория с таким slug уже есть (повторный заход
        // на шаг демо-данных), переиспользуем её id, а не вставляем дубль —
        // slug уникален, иначе была бы ошибка 23505 (unique violation).
        // Набор категорий описан в ОДНОМ месте — команде модуля Категории.
        // Раньше он дублировался здесь тремя штуками, и два определения уже
        // разошлись: демо-новости шаблонов magazine, clinic и gaming не
        // получали категорию вовсе, а заведённая тут же «Услуги» не
        // доставалась ни одной странице. Возвращается карта «слаг → id»;
        // раскладку по материалам делает та же команда в конце установки.
        $categoryIds = \Modules\Categories\Console\Commands\SeedDefaultCategoriesCommand::ensure();

        // Демо-новости
        $newsItems = [
            [
                'title' => 'Добро пожаловать в Nexum Core',
                'content' => '<p class="lead">Сайт установлен и уже наполнен: меню, страницы, новости и оформление настроены. Эта заметка — короткая карта, чтобы вы поняли, где что лежит.</p>

<h2>🚀 С чего начать</h2>
<ol>
  <li><strong>Откройте панель управления</strong> — ссылка «Админка» в шапке сайта.</li>
  <li><strong>Замените демо-тексты своими.</strong> Всё, что вы сейчас видите, — обычные материалы, их можно править и удалять.</li>
  <li><strong>Загляните в «Темы»</strong> и выберите оформление. Смена применяется сразу ко всему сайту.</li>
</ol>

<h2>🗂️ Как устроено содержимое</h2>
<ul>
  <li><strong>Новости</strong> — то, что вы читаете сейчас. Лента с датами, категориями и обложками.</li>
  <li><strong>Страницы</strong> — статичные разделы: о проекте, контакты, соглашение.</li>
  <li><strong>Меню</strong> — навигация в шапке, в подвале и в выдвижной панели слева.</li>
  <li><strong>Фрагменты</strong> — редактируемые блоки, которые выводятся в разных местах сайта: полоса над шапкой, памятка в подвале панели.</li>
</ul>

<blockquote>💡 Ничего из демо-содержимого не является «системным». Удаляйте смело — сайт не сломается.</blockquote>

<h2>🎨 Оформление</h2>
<p>В разделе «Темы» лежат готовые наборы: цвета, шрифт, скругления, иконки и фоновая картинка. Кнопка «Применить» меняет оформление сразу и на сайте, и в панели. Своя тема создаётся там же — можно скопировать готовую и поправить под себя.</p>

<h2>🧩 Модули</h2>
<p>Разделы панели — это модули, их можно включать и выключать. Не нужен магазин — выключите «Оплату», «Заказы» и «Доставку», и они пропадут из меню.</p>',
                'slug' => 'welcome-to-nexum-core',
                'published' => true,
                'template' => 'default',
            ],
            [
                'title' => 'Как редактировать страницы и шаблоны',
                'content' => '<p class="lead">Каждая страница и новость выводится через <strong>шаблон</strong>. Шаблон решает, как расположены блоки: одна колонка, две, широкая обложка сверху. Содержимое при этом остаётся вашим.</p>

<h2>✏️ Правка содержимого</h2>
<ol>
  <li>Панель → <strong>Страницы</strong> (или <strong>Новости</strong>) → нужный материал.</li>
  <li>Заголовок, текст, обложка и адрес правятся прямо в форме.</li>
  <li>Галочка <strong>«Опубликовано»</strong> решает, виден ли материал посетителям. Снимите её, пока не дописали.</li>
</ol>

<h2>🧱 Выбор шаблона</h2>
<p>Поле <strong>«Шаблон»</strong> в форме материала переключает вёрстку. Один и тот же текст можно показать по-разному, не переписывая его. Попробуйте переключить и посмотреть — это безопасно, содержимое не меняется.</p>

<h2>🔍 Заголовок и описание для поиска</h2>
<p>Раздел <strong>SEO</strong> хранит заголовок и описание, которые видит поисковик. Если их не заполнить, система возьмёт заголовок материала и начало текста — но своими словами почти всегда получается лучше.</p>

<blockquote>⚠️ Адрес страницы (slug) лучше не менять после публикации: старые ссылки перестанут работать. Если всё же меняете — заведите перенаправление в разделе SEO.</blockquote>

<h2>🖼️ Картинки</h2>
<p>Изображения загружаются прямо в редакторе или через раздел <strong>Файлы</strong>. Обложка материала задаётся отдельным полем — именно она показывается в ленте и при отправке ссылки в мессенджер.</p>',
                'slug' => 'kak-redaktirovat-stranicy',
                'published' => true,
                'template' => 'default',
            ],
            [
                'title' => 'Возможности CMS: что уже работает',
                'content' => '<p class="lead">Ниже — краткий обзор того, что доступно сразу после установки. Все разделы находятся в панели управления в меню слева.</p>

<h2>📰 Содержимое</h2>
<ul>
  <li><strong>Новости и Страницы</strong> — с обложками, категориями, шаблонами и SEO.</li>
  <li><strong>Категории</strong> — группировка материалов, вложенность поддерживается.</li>
  <li><strong>Меню</strong> — до трёх уровней, перетаскиванием мыши, отдельно для шапки, подвала и боковой панели.</li>
  <li><strong>Слайдшоу</strong> — баннеры на главной, вставляются шорткодом.</li>
  <li><strong>Фрагменты</strong> — небольшие редактируемые блоки в шапке и подвале.</li>
</ul>

<h2>🛒 Продажи</h2>
<ul>
  <li><strong>Оплата</strong> — типовые для России способы. После установки они созданы, но <em>выключены и без ключей</em>: реквизиты вводите сами.</li>
  <li><strong>Доставка</strong> — Почта России, СДЭК, Boxberry, самовывоз и курьер.</li>
  <li><strong>Заказы</strong> — список с фильтрами, карточка заказа, смена статуса, выгрузка в CSV.</li>
</ul>

<h2>👥 Люди и обратная связь</h2>
<ul>
  <li><strong>Пользователи</strong> — роли, права, история входов.</li>
  <li><strong>Сообщения</strong> — внутренняя переписка администраторов.</li>
  <li><strong>Отзывы и Комментарии</strong> — с премодерацией.</li>
  <li><strong>Каптча</strong> — конструктор проверок для форм.</li>
</ul>

<h2>♿ Доступность</h2>
<p>Модуль <strong>«Спецвозможности»</strong> добавляет на сайт панель помощи: размер шрифта, контрастная тема, озвучивание, маска для чтения, шрифт для дислексии. По умолчанию выключен — включите, если ваша аудитория в этом нуждается.</p>

<h2>🌍 Языки</h2>
<p>Интерфейс переведён на русский и английский. Переключатель языка — в шапке сайта и в панели. Тексты материалов переводятся отдельно, на вкладке «Переводы» в форме материала.</p>',
                'slug' => 'vozmozhnosti-cms',
                'published' => true,
                'template' => 'default',
            ],
            [
                'title' => 'Настройка магазина: оплата и доставка',
                'content' => '<p class="lead">Магазинная часть установлена и настроена, но <strong>намеренно выключена</strong>. Так сделано ради безопасности: боевых ключей в коде быть не должно.</p>

<h2>💳 Способы оплаты</h2>
<p>Панель → <strong>Оплата</strong>. Там уже заведены ЮKassa, СБП, SberPay, Т-Банк, наличные и банковский перевод. Чтобы способ заработал:</p>
<ol>
  <li>Откройте его и впишите реквизиты из личного кабинета платёжной системы.</li>
  <li>Нажмите <strong>«Проверить связь»</strong> — система обратится к API и скажет, приняты ли ключи.</li>
  <li>Только после этого включайте способ.</li>
</ol>

<blockquote>⚠️ Наличные и банковский перевод работают без всяких ключей — их можно включить прямо сейчас.</blockquote>

<h2>📦 Доставка</h2>
<p>Панель → <strong>Доставка</strong>. Почта России, СДЭК, Boxberry, Яндекс Доставка, самовывоз и курьер по городу. У каждого способа своя цена, срок и список регионов. Расчёт по API включается отдельным флажком и тоже требует ключей.</p>

<h2>🧾 Заказы</h2>
<p>Панель → <strong>Заказы</strong>. Новые помечаются значком. В карточке заказа видно покупателя, состав, сумму и способ оплаты; статус меняется прямо там. Есть выгрузка в CSV и печатная форма.</p>

<h2>🔒 Про безопасность</h2>
<p>Уведомления от платёжных систем проверяются на сервере: система переспрашивает статус платежа у самой платёжной системы и сверяет сумму. Телу входящего запроса она не верит — иначе заказ можно было бы «оплатить» подделкой.</p>',
                'slug' => 'nastroyka-magazina',
                'published' => true,
                'template' => 'default',
            ],
            [
                'title' => 'Кому подойдёт эта CMS',
                'content' => '<p class="lead">Система не привязана к одной нише. Ниже — как её обычно используют и что для этого включать.</p>

<h2>📖 Журналы и блоги</h2>
<p>Новости с обложками и категориями, страницы для рубрик, комментарии с премодерацией, поиск по сайту. Магазинные модули можно выключить — меню станет короче.</p>

<h2>🏥 Клиники и врачи</h2>
<p>Страницы услуг и специалистов, запись через форму обратной связи с каптчей, раздел «Вопросы» для частых вопросов пациентов. Отдельно стоит включить <strong>«Спецвозможности»</strong>: у медицинских сайтов аудитория часто нуждается в крупном шрифте и контрастной теме.</p>

<h2>🎮 Игровые сообщества</h2>
<p>Лента новостей, слайдшоу с анонсами, комментарии, тёмное оформление в разделе «Темы». Пользователи с ролями — если нужно пускать редакторов, но не давать им доступ ко всему.</p>

<h2>🛍️ Магазины</h2>
<p>Каталог на категориях, оплата, доставка и заказы. Подробности — в заметке «Настройка магазина: оплата и доставка».</p>

<h2>🏢 Компании и услуги</h2>
<p>Страницы о компании, услугах и контактах, форма обратной связи, карта в контактах. Самый простой случай: почти всё нужное уже стоит после установки.</p>

<blockquote>💡 Общий совет: выключайте ненужные модули в разделе «Модули». Чем короче меню панели, тем быстрее в ней разбирается новый сотрудник.</blockquote>',
                'slug' => 'komu-podoydet',
                'published' => true,
                'template' => 'default',
            ],
        ];

        foreach ($newsItems as $news) {
            $newsId = DB::table('news')->where('slug', $news['slug'])->value('id');
            if (!$newsId) {
                $newsId = DB::table('news')->insertGetId([
                    'title' => $news['title'],
                    'content' => $news['content'],
                    'slug' => $news['slug'],
                    'published' => $news['published'],
                    'template' => $news['template'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (!empty($categoryIds['news'])) {
                $alreadyLinked = DB::table('news_category')
                    ->where('news_id', $newsId)
                    ->where('category_id', $categoryIds['news'])
                    ->exists();
                if (!$alreadyLinked) {
                    DB::table('news_category')->insert([
                        'news_id' => $newsId,
                        'category_id' => $categoryIds['news'],
                    ]);
                }
            }
        }

        // 🛍️ Демо-товары.
        //
        // Товар в этой CMS — обычный материал с ценой, остатком и шаблоном
        // products: корзина работает именно с News (см. CartController).
        // Отдельная таблица products в схеме есть, но ни к чему не
        // подключена — не используйте её, товары заводятся здесь.
        //
        // Картинка лежит первым тегом в содержимом: колонки cover у news
        // нет, и шаблон берёт изображение из тела материала.
        $goods = [
            [
                'title' => 'Беспроводные наушники',
                'slug' => 'tovar-naushniki',
                'price' => 4990,
                'stock' => 12,
                // Вес нужен службам доставки: по нему считается цена
                // и отбиваются заказы сверх лимита.
                'weight' => 0.290,
                'content' => "<p><img src=\"/images/products/naushniki.svg\" alt=\"Беспроводные наушники\" style=\"width:100%;max-width:420px;height:auto\"></p>\n<p>Накладные наушники с шумоподавлением и автономностью до 30 часов. Складная конструкция, чехол в комплекте.</p>",
            ],
            [
                'title' => 'Умные часы',
                'slug' => 'tovar-chasy',
                'price' => 8900,
                'stock' => 7,
                // Вес нужен службам доставки: по нему считается цена
                // и отбиваются заказы сверх лимита.
                'weight' => 0.055,
                'content' => "<p><img src=\"/images/products/chasy.svg\" alt=\"Умные часы\" style=\"width:100%;max-width:420px;height:auto\"></p>\n<p>Пульс, шаги, сон и уведомления с телефона. Влагозащита, экран читается на солнце, зарядки хватает на неделю.</p>",
            ],
            [
                'title' => 'Городской рюкзак',
                'slug' => 'tovar-ryukzak',
                'price' => 3450,
                'stock' => 20,
                // Вес нужен службам доставки: по нему считается цена
                // и отбиваются заказы сверх лимита.
                'weight' => 0.750,
                'content' => "<p><img src=\"/images/products/ryukzak.svg\" alt=\"Городской рюкзак\" style=\"width:100%;max-width:420px;height:auto\"></p>\n<p>Отделение под ноутбук до 15\", влагостойкая ткань, потайной карман на спинке. Объём 22 литра.</p>",
            ],
            [
                'title' => 'Керамическая кружка',
                'slug' => 'tovar-kruzhka',
                'price' => 690,
                'stock' => 48,
                // Вес нужен службам доставки: по нему считается цена
                // и отбиваются заказы сверх лимита.
                'weight' => 0.380,
                'content' => "<p><img src=\"/images/products/kruzhka.svg\" alt=\"Керамическая кружка\" style=\"width:100%;max-width:420px;height:auto\"></p>\n<p>Объём 350 мл, подходит для посудомоечной машины и микроволновки. Плотные стенки дольше держат тепло.</p>",
            ],
            [
                'title' => 'Блокнот в твёрдой обложке',
                'slug' => 'tovar-bloknot',
                'price' => 850,
                'stock' => 35,
                'content' => "<p><img src=\"/images/products/bloknot.svg\" alt=\"Блокнот в твёрдой обложке\" style=\"width:100%;max-width:420px;height:auto\"></p>\n<p>Формат A5, 192 страницы плотной бумаги, ляссе и резинка. Разлиновка в точку — удобна и для текста, и для схем.</p>",
            ],
            [
                'title' => 'Настольная лампа',
                'slug' => 'tovar-lampa',
                'price' => 2790,
                'stock' => 9,
                'content' => "<p><img src=\"/images/products/lampa.svg\" alt=\"Настольная лампа\" style=\"width:100%;max-width:420px;height:auto\"></p>\n<p>Три уровня яркости и регулировка цветовой температуры. Питание от USB-C, поворотный плафон.</p>",
            ],
        ];

        $productsCategoryId = DB::table('categories')->where('slug', 'products')->value('id');

        foreach ($goods as $item) {
            $existing = DB::table('news')->where('slug', $item['slug'])->value('id');

            if ($existing) {
                continue;
            }

            $goodId = DB::table('news')->insertGetId([
                'title' => $item['title'],
                'content' => $item['content'],
                'slug' => $item['slug'],
                'price' => $item['price'],
                'stock' => $item['stock'],
                // Вес демо-товаров задан правдоподобно: без него владелец не
                // увидит, как работают ограничения службы доставки, а правило
                // проекта требует, чтобы чистая установка совпадала с боевой.
                'weight' => $item['weight'] ?? null,
                'published' => true,
                'template' => 'products',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($productsCategoryId) {
                DB::table('news_category')->insert([
                    'news_id' => $goodId,
                    'category_id' => $productsCategoryId,
                ]);
            }
        }

        // 📖 Журнальные материалы: показывают возможности CMS и заодно
        // демонстрируют шаблон magazine (крупный ведущий + сетка).
        $magazine = [
            [
                'title' => 'Быстрый старт: сайт за один вечер',
                'slug' => 'zhurnal-bystryy-start',
                'content' => '<p><img src="/images/magazine/start.svg" alt="Быстрый старт: сайт за один вечер" style="width:100%;max-width:520px;height:auto"></p>
<h2>🎯 Что нужно сделать в первую очередь</h2>
<p>После установки сайт уже наполнен: есть меню, страницы, новости, товары и оформление. Дальше остаётся заменить демо-содержимое своим — и это можно сделать за один вечер.</p>
<ol>
  <li><strong>Пройдитесь по материалам.</strong> Панель → Новости и Страницы. Всё, что не подходит, правьте или удаляйте — системного среди них ничего нет.</li>
  <li><strong>Соберите меню.</strong> Панель → Меню. Пункты перетаскиваются мышью, вложенность до трёх уровней.</li>
  <li><strong>Выберите оформление.</strong> Панель → Темы. Кнопка «Применить» меняет вид сразу и на сайте, и в панели.</li>
  <li><strong>Отключите ненужное.</strong> Панель → Модули. Не продаёте — выключите Оплату, Доставку и Заказы.</li>
</ol>
<blockquote>💡 Начните с меню: когда навигация собрана, сразу видно, каких страниц не хватает.</blockquote>
<h2>⏱️ Сколько это занимает</h2>
<p>Небольшой сайт-визитка собирается примерно за два часа: час на тексты, полчаса на меню и оформление, полчаса на контакты и реквизиты.</p>',
            ],
            [
                'title' => 'Шаблоны материалов: какой когда брать',
                'slug' => 'zhurnal-shablony',
                'content' => '<p><img src="/images/magazine/shablony.svg" alt="Шаблоны материалов: какой когда брать" style="width:100%;max-width:520px;height:auto"></p>
<h2>🧱 Что такое шаблон</h2>
<p>Шаблон решает, как материал выглядит на сайте: одна колонка, сетка карточек, крупная обложка. Текст при этом остаётся тем же — переключение безопасно, попробуйте несколько и оставьте подходящий.</p>
<h2>📚 Готовые шаблоны</h2>
<ul>
  <li><strong>Новости</strong> — обычная лента с датами и обложками. Подходит почти всему.</li>
  <li><strong>Журнал</strong> — крупный ведущий материал и сетка остальных. Для лонгридов, интервью и обзоров.</li>
  <li><strong>Товары</strong> — карточка с ценой, остатком и кнопкой в корзину.</li>
  <li><strong>Наши услуги</strong> — перечень услуг с описаниями.</li>
  <li><strong>Вопросы</strong> — раскрывающиеся ответы, удобно для частых вопросов.</li>
  <li><strong>Отзывы</strong> — мнения клиентов.</li>
  <li><strong>Релизы</strong> — история обновлений по датам.</li>
</ul>
<blockquote>⚠️ Шаблон задаётся у каждого материала отдельно, в поле «Шаблон» его формы. Материалы с одним шаблоном собираются в общий блок на главной.</blockquote>
<h2>🖼️ Откуда берётся картинка</h2>
<p>Из первого изображения в тексте материала. Нет картинки — шаблон нарисует буквицу из заголовка, поэтому пустых мест не будет.</p>',
            ],
            [
                'title' => 'Оформление: как настроить под себя',
                'slug' => 'zhurnal-oformlenie',
                'content' => '<p><img src="/images/magazine/temy.svg" alt="Оформление: как настроить под себя" style="width:100%;max-width:520px;height:auto"></p>
<h2>🎨 Готовые темы</h2>
<p>В разделе «Темы» лежат пять наборов: Индиго, Алый, Лазурь, Графит и Пурпур. Все светлые и рассчитаны на фоновую картинку по умолчанию. Контраст текста проверен — читается на любой из них.</p>
<h2>🛠️ Своя тема</h2>
<ol>
  <li>Откройте похожую тему и нажмите «Создать тему» — проще править готовое, чем начинать с нуля.</li>
  <li>Задайте цвета: фон, текст, основной и акцентный. Основной идёт на кнопки, акцентный — на выделения.</li>
  <li>Выберите шрифт и скругления. Ноль пикселей даёт строгий вид с прямыми углами.</li>
  <li>Загрузите фоновую картинку — она общая для всех тем.</li>
</ol>
<blockquote>💡 Проверяйте контраст: тёмный текст на светлом фоне читается почти всегда, светлый на светлом — почти никогда.</blockquote>
<h2>🧩 Фрагменты</h2>
<p>Полоса над шапкой, блок под ней и подписи в подвале — это фрагменты. Они правятся отдельно, в разделе «Фрагменты», и выключаются по одному.</p>',
            ],
            [
                'title' => 'Как сайт попадает в поиск',
                'slug' => 'zhurnal-seo',
                'content' => '<p><img src="/images/magazine/seo.svg" alt="Как сайт попадает в поиск" style="width:100%;max-width:520px;height:auto"></p>
<h2>🔍 Заголовок и описание</h2>
<p>У каждого материала есть поля для поисковика: заголовок и описание. Если их не заполнить, система возьмёт заголовок материала и начало текста — работает, но своими словами почти всегда получается лучше.</p>
<h2>🗺️ Карта сайта</h2>
<p>Раздел SEO собирает карту сайта — список всех страниц для поисковых систем. Пересобирается кнопкой после того, как вы добавили материалы.</p>
<h2>↪️ Перенаправления</h2>
<p>Если меняете адрес опубликованной страницы, заведите перенаправление со старого адреса на новый. Иначе внешние ссылки и результаты поиска приведут посетителя на пустую страницу.</p>
<blockquote>⚠️ Самая частая ошибка — менять адрес материала после публикации и не ставить перенаправление. Проверьте раздел «Перенаправления», прежде чем править slug.</blockquote>
<h2>🤖 robots.txt</h2>
<p>Тоже в разделе SEO. По умолчанию сайт открыт для поисковиков. Пока сайт не готов, закройте его от индексации, чтобы черновики не попали в выдачу.</p>',
            ],
        ];

        foreach ($magazine as $item) {
            if (DB::table('news')->where('slug', $item['slug'])->exists()) {
                continue;
            }

            DB::table('news')->insert([
                'title' => $item['title'],
                'content' => $item['content'],
                'slug' => $item['slug'],
                'published' => true,
                'template' => 'magazine',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 🏥 Демо-услуги клиники. Иконка карточки берётся из ведущего
        // эмодзи заголовка — так редактор задаёт значок, не трогая вёрстку.
        $clinic = [
            ['title' => '🩺 Приём терапевта', 'slug' => 'usluga-terapevt', 'price' => 1800, 'content' => '<p>Первичная консультация: осмотр, сбор анамнеза, назначение обследований. Врач объяснит, что происходит, и составит план лечения. Приём длится 30 минут.</p><ul><li>Осмотр и опрос</li><li>Расшифровка анализов, если они уже есть</li><li>Направления на обследования</li></ul>'],
            ['title' => '🦷 Лечение кариеса', 'slug' => 'usluga-karies', 'price' => 3500, 'content' => '<p>Лечение под местной анестезией за одно посещение. Используем композитные материалы, которые подбираются по цвету вашей эмали.</p><ul><li>Анестезия включена в стоимость</li><li>Гарантия на пломбу — один год</li><li>Контрольный осмотр через месяц бесплатно</li></ul>'],
            ['title' => '🔬 Лабораторные анализы', 'slug' => 'usluga-analizy', 'price' => 600, 'content' => '<p>Более двухсот видов исследований. Забор крови утром натощак, результаты приходят на почту в течение суток.</p><ul><li>Общий и биохимический анализ крови</li><li>Гормоны, витамины, аллергопанели</li><li>Результат в электронном виде</li></ul>'],
            ['title' => '🫀 УЗИ и диагностика', 'slug' => 'usluga-uzi', 'price' => 2400, 'content' => '<p>Ультразвуковое исследование внутренних органов, сосудов, щитовидной железы. Заключение выдаётся сразу после процедуры.</p><ul><li>Без очереди, по записи</li><li>Заключение на руки в тот же день</li><li>Снимки записываем на носитель по просьбе</li></ul>'],
            ['title' => '💉 Вакцинация', 'slug' => 'usluga-vakcinaciya', 'price' => 1200, 'content' => '<p>Прививки взрослым и детям по национальному календарю и перед поездками. Перед вакцинацией обязателен осмотр врача.</p><ul><li>Осмотр перед прививкой входит в стоимость</li><li>Оформление справки и отметки в карте</li><li>Наблюдение 30 минут после процедуры</li></ul>'],
            ['title' => '👶 Детский приём', 'slug' => 'usluga-pediatr', 'price' => 2000, 'content' => '<p>Педиатр принимает детей с рождения. Плановые осмотры, оформление справок в сад и школу, вопросы питания и развития.</p><ul><li>Отдельная игровая зона в ожидании</li><li>Справки в детский сад, школу и бассейн</li><li>Патронаж новорождённых на дому</li></ul>'],
        ];

        foreach ($clinic as $item) {
            if (DB::table('news')->where('slug', $item['slug'])->exists()) {
                continue;
            }

            DB::table('news')->insert([
                'title' => $item['title'],
                'content' => $item['content'],
                'slug' => $item['slug'],
                'price' => $item['price'],
                'published' => true,
                'template' => 'clinic',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Материалы под остальные шаблоны. Без них четыре шаблона из десяти
        // было НЕ НА ЧЕМ посмотреть: они выбирались в форме материала, но на
        // свежей установке ни одной такой записи не появлялось, и владелец
        // видел пустой раздел вместо оформления.
        $поШаблонам = [
            // Услуги — О ТОМ, ЧТО МЫ ДЕЛАЕМ С САМОЙ CMS. Раньше здесь лежал
            // единственный «Монтаж под ключ» из чужого проекта про
            // металлоконструкции: раздел был непустым и при этом ни о чём.
            // ⚠️ Эмодзи в НАЧАЛЕ заголовка — не украшение: шаблон берёт его
            // как значок карточки, а в строке оставляет остаток.
            // ⚠️ Первые три <li> из текста шаблон показывает как «что входит»,
            // поэтому список — часть карточки, а не только страницы услуги.
            ['template' => 'ourworks', 'slug' => 'usluga-ustanovka', 'title' => '🚀 Установка и настройка', 'price' => 9000,
             'content' => '<p>Ставим систему на ваш хостинг и доводим до рабочего состояния: база, почта, адрес сайта, права на каталоги. Вы получаете готовую панель и учётную запись администратора.</p><ul><li>Проверка хостинга до начала работ</li><li>Настройка почты и проверка отправки писем</li><li>Резервная копия сразу после установки</li><li>Короткая видеозапись с разбором панели</li></ul>'],

            ['template' => 'ourworks', 'slug' => 'usluga-perenos', 'title' => '📦 Перенос сайта на Nexum Core', 'price' => 24000,
             'content' => '<p>Переносим действующий сайт: материалы, страницы, категории, изображения и адреса. Старые ссылки продолжают работать — для них настраиваются переадресации, чтобы не потерять позиции в поиске.</p><ul><li>Перенос материалов с сохранением дат</li><li>Переадресация старых адресов на новые</li><li>Проверка карты сайта и файла robots</li><li>Переключение в согласованное время, без простоя</li></ul>'],

            ['template' => 'ourworks', 'slug' => 'usluga-dorabotka', 'title' => '🧩 Доработка под задачу', 'price' => 3500,
             'content' => '<p>Пишем свой модуль или дорабатываем существующий, когда нужной возможности в системе ещё нет. Работа идёт в модульной части, поэтому обновления ядра её не затирают.</p><ul><li>Оценка задачи до начала работ</li><li>Свой модуль вместо правок ядра</li><li>Тесты на новую логику</li><li>Исходный код остаётся у вас</li></ul>'],

            ['template' => 'ourworks', 'slug' => 'usluga-oformlenie', 'title' => '🎨 Оформление под ваш стиль', 'price' => 18000,
             'content' => '<p>Собираем тему по вашим цветам, шрифтам и логотипу. Оформление живёт отдельно от содержимого, поэтому его можно поменять позже, не трогая материалы.</p><ul><li>Тема в фирменных цветах и шрифтах</li><li>Проверка на телефоне, планшете и мониторе</li><li>Контраст текста по стандарту доступности</li><li>Светлый и тёмный вариант</li></ul>'],

            ['template' => 'ourworks', 'slug' => 'usluga-obuchenie', 'title' => '🎓 Обучение вашей команды', 'price' => 6000,
             'content' => '<p>Показываем, как вести сайт своими силами: материалы, меню, товары, изображения, резервные копии. Занятие идёт на вашем сайте, а не на учебном примере.</p><ul><li>Занятие на вашем сайте и ваших материалах</li><li>Запись встречи остаётся у вас</li><li>Короткая памятка по частым действиям</li><li>Ответы на вопросы две недели после занятия</li></ul>'],

            ['template' => 'ourworks', 'slug' => 'usluga-podderzhka', 'title' => '🛟 Техническая поддержка', 'price' => 8000,
             'content' => '<p>Берём на себя обновления, резервные копии и наблюдение за сайтом. Если что-то ломается, чиним мы, а не вы — и обычно до того, как это заметят посетители.</p><ul><li>Резервные копии по расписанию</li><li>Обновления системы и проверка после них</li><li>Наблюдение за доступностью сайта</li><li>Ответ в рабочие часы в тот же день</li></ul>'],


            // Уроки — по четыре на каждый из четырёх шаблонов. Раньше эти
            // шаблоны нельзя было даже ВЫБРАТЬ в форме материала (их не было
            // в списке TEMPLATES), поэтому и материалов под них не заводилось:
            // четыре готовых шаблона не существовали для владельца вовсе.
            //
            // Темы уроков — не абстрактные основы языка, а устройство САМОЙ
            // системы: белый список редактора, блоки содержимого, события
            // модели, кеш. Такой раздел полезен покупателю, а отвлечённый
            // учебник по HTML он найдёт где угодно.
            //
            // ⚠️ Число блоков <pre> шаблон показывает как «примеров кода» —
            // пример в уроке не украшение, а часть карточки.
            ['template' => 'base-html', 'slug' => 'urok-razmetka-materiala', 'title' => 'Разметка материала: какие теги переживают редактор',
             'content' => '<p>Редактор чистит содержимое по белому списку: всё, что уходит в базу, попадёт на страницу посетителя как разметка. Поэтому список разрешённых тегов узкий, и знать его полезно.</p><pre><code>&lt;p&gt;Абзац&lt;/p&gt;
&lt;ul&gt;&lt;li&gt;Пункт списка&lt;/li&gt;&lt;/ul&gt;
&lt;details&gt;&lt;summary&gt;Вопрос&lt;/summary&gt;Ответ&lt;/details&gt;</code></pre><p>Скрипты, стили и любые обработчики событий вырезаются молча — это защита, а не ошибка. Неизвестный тег не удаляется, а разворачивается: текст внутри писал автор, терять его нельзя.</p>'],

            ['template' => 'base-html', 'slug' => 'urok-tablicy-v-materiale', 'title' => 'Таблицы в материале: как не сломать телефон',
             'content' => '<p>Таблица — единственный блок, который невозможно сузить: у неё своя минимальная ширина, и на узком экране она растягивает всю страницу, добавляя горизонтальную прокрутку всему сайту.</p><pre><code>&lt;div class="pc-scroll"&gt;
  &lt;table&gt; ... &lt;/table&gt;
&lt;/div&gt;</code></pre><p>Поэтому таблицу заворачивают в блок с собственной прокруткой: ездит она, а не страница. Кнопка «Таблица» в редакторе делает это сама — руками обёртку добавлять не нужно.</p>'],

            ['template' => 'base-html', 'slug' => 'urok-kartinki-i-video', 'title' => 'Картинки и видео: что редактор оставит, а что вырежет',
             'content' => '<p>Картинку можно загрузить с диска или выбрать из медиатеки — во втором случае она уже лежит на сервере и не займёт места повторно. Загрузка из редактора идёт именно в медиатеку, а не мимо неё.</p><pre><code>&lt;img src="/storage/uploads/shema.png" alt="Схема" style="width:100%;max-width:560px"&gt;
&lt;video controls&gt;&lt;source src="/storage/uploads/rolik.mp4"&gt;&lt;/video&gt;</code></pre><p>Из атрибутов остаются размеры, alt и настройки проигрывателя. Всё остальное снимается: содержимое выводится на сайт как разметка, и лишний атрибут — это чужой код на странице посетителя.</p>'],

            ['template' => 'base-html', 'slug' => 'urok-shortkody', 'title' => 'Шорткоды: каптча и форма прямо в тексте',
             'content' => '<p>Шорткод — короткая метка в тексте, которая на сайте превращается в живой блок. В базе лежит именно метка, а в редакторе она показывается плашкой, чтобы её нельзя было случайно разобрать по буквам.</p><pre><code>[captcha preset="obratnaya-svyaz"]
[form id="3"]</code></pre><p>Список распознаваемых имён перечислен явно — иначе обычный текст в квадратных скобках вроде «см. приложение 2» тоже стал бы плашкой. Незнакомая метка остаётся текстом.</p>'],

            ['template' => 'base-css', 'slug' => 'urok-bloki-soderzhimogo', 'title' => 'Блоки содержимого: вёрстка страницы без вёрстки',
             'content' => '<p>Оформление содержимого живёт в одном файле, который подключается и на сайте, и внутри редактора. Поэтому блок в редакторе выглядит ровно так, как его увидит посетитель.</p><pre><code>.pc-grid  — сетка карточек
.pc-stats — строка цифр
.pc-steps — нумерованные шаги
.pc-cta   — призыв к действию</code></pre><p>Вставлять их руками не нужно: в панели редактора есть кнопка «Блоки», она кладёт готовую заготовку с примерным содержимым. Останется заменить текст.</p>'],

            ['template' => 'base-css', 'slug' => 'urok-temy-sayta', 'title' => 'Темы: откуда берутся цвета сайта',
             'content' => '<p>Цвета, шрифт и фон задаёт активная тема. Она объявляет набор переменных, а вся вёрстка ссылается на них — поэтому смена темы перекрашивает сайт целиком, не трогая ни одного шаблона.</p><pre><code>--color-primary  основной цвет
--color-accent   дополнительный
--surface        фон карточки
--surface-ink    цвет текста на ней</code></pre><p>Правило простое: в новой вёрстке не пишут цвет числом. Написали — этот кусок перестанет следовать теме, и на тёмном оформлении текст пропадёт. Так уже случалось не раз.</p>'],

            ['template' => 'base-css', 'slug' => 'urok-klassy-sborki', 'title' => 'Почему часть классов молча не работает',
             'content' => '<p>Готовая сборка стилей включает стандартный набор классов, но НЕ включает те, что собираются на лету: прозрачность через дробь, произвольные значения в скобках и половину цветов.</p><pre><code>bg-white/80      не работает
text-[13px]      не работает
bg-emerald-500   не работает
bg-indigo-500    работает</code></pre><p>Класса просто нет в файле, поэтому ошибки не будет — блок останется без оформления. Перед использованием непривычного класса его ищут в самой сборке, а всё нестандартное пишут обычным CSS.</p>'],

            ['template' => 'base-css', 'slug' => 'urok-adaptiv-porog', 'title' => 'Адаптив: один порог на весь проект',
             'content' => '<p>Для телефонов и планшетов во всём проекте один порог. Планшет в альбомной ориентации ровно 1024 пикселя и при этом сенсорный, поэтому граница включительная; вторая половина условия ловит телефон, положенный набок.</p><pre><code>@media (max-width: 1024px), (max-height: 500px) { ... }</code></pre><p>Свой порог заводить не нужно: однажды разница в один пиксель скрыла на планшете всю вложенность меню — подменю были в разметке и не показывались никогда, потому что раскрывались по наведению, а наведения на сенсорном экране нет.</p>'],

            ['template' => 'base-js', 'slug' => 'urok-svoi-knopki-redaktora', 'title' => 'Свои кнопки в редакторе: три реестра',
             'content' => '<p>Редактор написан без сборщика — это обычные скрипты. Своя кнопка добавляется тремя вызовами, ядро само сделает снимок для отмены, синхронизацию с полем формы и обновление панели.</p><pre><code>RuEditor.registerCommand("myThing", function (editor) { /* меняем документ */ });
RuEditor.registerButton("myThing", { icon: "fas fa-star", command: "myThing" });
RuEditor.registerPlugin("my-plugin", { init: function (editor) { /* подписки */ } });</code></pre><p>Кнопка попадёт на панель, когда её имя перечислено в наборе <code>toolbar</code>. Русский текст в скрипт не зашивают: строки берутся из словаря и уезжают в браузер отдельно.</p>'],

            ['template' => 'base-js', 'slug' => 'urok-alpine-na-sayte', 'title' => 'Alpine: где он уже подключён и как им пользоваться',
             'content' => '<p>Небольшая библиотека для поведения прямо в разметке уже подключена и на сайте, и в панели. Отдельный скрипт для выпадающего списка или переключателя писать не нужно.</p><pre><code>&lt;div x-data="{ open: false }"&gt;
  &lt;button @click="open = !open"&gt;Показать&lt;/button&gt;
  &lt;div x-show="open"&gt;Содержимое&lt;/div&gt;
&lt;/div&gt;</code></pre><p>⚠️ Подключать её вторым тегом нельзя: два экземпляра инициализируют разметку дважды, и каждый список выводится в двойном количестве. Однажды так и было — вместо четырёх подсказок поиска показывалось восемь.</p>'],

            ['template' => 'base-js', 'slug' => 'urok-peretaskivanie-menyu', 'title' => 'Перетаскивание пунктов меню: как это устроено',
             'content' => '<p>Дерево меню перетаскивается мышью и пальцем. Внутри — обычная библиотека сортировки, но два её условия обязательны, и оба когда-то были нарушены.</p><pre><code>ghostClass: "mi-ghost"      один токен, не список
emptyInsertThreshold: 15   иначе в пустой список не уронить</code></pre><p>Класс должен быть ОДНИМ словом: библиотека вешает его напрямую, а пробел в имени класса — ошибка, обрывающая жест. И пустой список-приёмник обязан иметь ненулевой размер, иначе попасть в него нечем.</p>'],

            ['template' => 'base-js', 'slug' => 'urok-prosmotr-kartinok', 'title' => 'Просмотр картинки во весь экран — общий на весь сайт',
             'content' => '<p>Клик по картинке в материале открывает её во весь экран. Раньше это работало только внутри слайдшоу, а в остальных местах клик перехватывали расширения браузера.</p><pre><code>header, footer, nav, aside, form, button — служебные зоны,
там картинки не трогаем</code></pre><p>Правило подбора написано от обратного: перечислены зоны, где трогать не надо, а не места, где надо. Иначе каждый новый шаблон пришлось бы вносить в список руками — и о нём бы забыли.</p>'],

            ['template' => 'base-php', 'slug' => 'urok-svoy-modul', 'title' => 'Свой модуль: куда класть код, чтобы его не затёрло',
             'content' => '<p>Система собрана по модулям. Своя логика пишется отдельным модулем, а не правкой ядра — тогда обновление системы её не затирает.</p><pre><code>modules/МойМодуль/
  Controllers/  Models/  Views/  Routes/
  МойМодульServiceProvider.php
  module.json</code></pre><p>⚠️ Миграции — исключение: все до единой лежат в общей папке <code>database/migrations</code>. Миграция, положенная внутрь модуля, молча никогда не выполнится.</p>'],

            ['template' => 'base-php', 'slug' => 'urok-migracii', 'title' => 'Миграции: почему все в одной папке и без сырого SQL',
             'content' => '<p>Раньше миграции были раскиданы по четырнадцати папкам внутри модулей. Половина не выполнялась вовсе: их подхватывал только активный модуль, а неактивный оставлял таблицы ненадёжными.</p><pre><code>Schema::table("orders", function (Blueprint $table) {
    $table->timestamp("stock_returned_at")->nullable();
});</code></pre><p>Пишут их строителем схемы, а не сырым запросом: боевая база — PostgreSQL, а тесты гоняются на SQLite ради скорости. Запрос, написанный под одну базу, упадёт на другой — и заметят это в худший момент.</p>'],

            ['template' => 'base-php', 'slug' => 'urok-sobytiya-materiala', 'title' => 'События материала: чем saved отличается от updated',
             'content' => '<p>Модель сама сообщает об изменениях, и на этом держится сброс кеша. Но два способа изменить поле поднимают РАЗНЫЕ события, и разница однажды стоила неверных остатков на сайте.</p><pre><code>$товар->save();              saving, updating, updated, saved
$товар->decrement("stock");  updating, updated — и всё</code></pre><p>Счётчики событие «сохранено» не поднимают вовсе. Поэтому подписываются на «создано» и «изменено»: они покрывают и обычное сохранение, и списание остатка при покупке.</p>'],

            ['template' => 'base-php', 'slug' => 'urok-kesh-proekta', 'title' => 'Кеш: что и когда сбрасывать',
             'content' => '<p>Часть страниц собирается заранее и лежит готовой. Это заметно ускоряет сайт и ровно так же заметно вредит, если забыть сбросить нужный ключ: правка есть в базе, а посетитель видит старое.</p><pre><code>home_pages           страницы на главной
menu.header          пункты меню
template_ШАБЛОН_...  блок материалов</code></pre><p>Правило: заводя кеш, перечисли ВСЕ модели, чьё изменение делает его неверным. Пункт меню собирается из страницы, поэтому удаление страницы обязано сбрасывать и кеш меню — иначе удалённая страница висит в меню ещё час.</p>'],

            // Уроки Git — про работу С ЭТИМ проектом: одна ветка, коммиты
            // по-русски, что в репозиторий не кладут.
            ['template' => 'base-git', 'slug' => 'urok-git-pervyy-kommit', 'title' => 'Первый коммит: что попадает в историю, а что нет',
             'content' => '<p>История проекта — это список коммитов. В коммит попадает не всё подряд: настройки машины, загруженные картинки и собранные файлы в репозиторий не кладут, иначе он распухнет и в нём начнут конфликтовать вещи, которые у каждого свои.</p><pre><code>git status            что изменилось
git add файл          отобрать в коммит
git commit -m "Текст" записать в историю</code></pre><p>Что в этом проекте НЕ коммитят: файл окружения с паролями, каталог зависимостей, содержимое хранилища и собранные стили. Всё это перечислено в <code>.gitignore</code> — если файл вдруг просится в коммит, сначала проверьте, не забыли ли его туда внести.</p>'],

            ['template' => 'base-git', 'slug' => 'urok-git-soobshchenie-kommita', 'title' => 'Сообщение коммита: зачем оно по-русски и что в нём писать',
             'content' => '<p>Сообщение пишут для того, кто откроет историю через полгода — чаще всего для себя. Поэтому в этом проекте они на русском: список изменений читают владелец и разработчик, и англоязычный заголовок никому здесь не помогает.</p><pre><code>Заказы: суммы не рвутся на телефоне

Разряды в цене разделены пробелом, и обычный перенос
ломал число посреди строки. Числовым ячейкам задан
запрет переноса.</code></pre><p>Строение простое: первая строка — что сделано, дальше пустая строка и почему. «Правки» и «фиксы» в заголовке не говорят ничего: через месяц по такому списку нельзя понять, где искать нужное изменение.</p>'],

            ['template' => 'base-git', 'slug' => 'urok-git-vetki-i-master', 'title' => 'Ветки: почему здесь работают прямо в master',
             'content' => '<p>Ветка — отдельная линия истории. В больших командах каждую задачу делают в своей ветке и потом сливают. В этом проекте ветка одна — <code>master</code>, и это осознанный выбор: разработчик один, а лишний шаг с ветками и слияниями стоит времени и ничего не даёт.</p><pre><code>git branch            где я сейчас
git switch master     вернуться в основную
git log --oneline -5  что было в последний раз</code></pre><p>Правило простое: изменения уходят в <code>master</code> и сразу на сервер. Если работа большая и оставит сайт нерабочим посередине — вот тогда заводят ветку и сливают её, когда всё сложилось.</p>'],

            ['template' => 'base-git', 'slug' => 'urok-git-otkat-izmeneniy', 'title' => 'Откат: как вернуть файл или весь коммит',
             'content' => '<p>Испортили файл — история для того и нужна. Важно различать три случая: вернуть файл, отменить последний коммит и отменить коммит, который уже ушёл на сервер.</p><pre><code>git checkout -- файл       вернуть файл как в истории
git reset --soft HEAD~1    отменить коммит, правки оставить
git revert КОММИТ          отменить коммит отдельной записью</code></pre><p>⚠️ Разница между двумя последними существенная. <code>reset</code> переписывает историю — так можно, пока коммит не ушёл на сервер. Если ушёл, применяют <code>revert</code>: он не стирает прошлое, а добавляет обратную запись, и у всех остальных история остаётся целой.</p>'],

            ['template' => 'base-git', 'slug' => 'urok-git-otpravka-na-server', 'title' => 'Отправка на сервер: push, конфликты и что делать, если отклонили',
             'content' => '<p>Коммит живёт на вашей машине, пока его не отправили. Отправка — отдельное действие, и она может быть отклонена: значит, в общей истории появилось что-то, чего у вас нет.</p><pre><code>git push origin master   отправить
git pull --rebase        забрать чужое и положить своё сверху
git push origin master   повторить</code></pre><p>Конфликт — это не поломка, а сообщение «два человека правили одно место, решите какое верно». Файл с конфликтом открывают, оставляют нужное, убирают пометки и делают обычный коммит. Чем чаще забираете чужие изменения, тем реже это случается.</p>'],

            ['template' => 'base-git', 'slug' => 'urok-git-chto-smotret-pered-pravkoy', 'title' => 'Что смотреть перед правкой: история как источник причин',
             'content' => '<p>Прежде чем менять код, полезно узнать, почему он такой. История отвечает на это лучше комментариев: в ней видно, что меняли, когда и с каким объяснением.</p><pre><code>git log --oneline -10          последние изменения
git log -p файл               история одного файла
git show КОММИТ               что именно было в коммите
git blame файл                кто и когда написал строку</code></pre><p>В этом проекте у части коммитов сообщения длинные — с разбором причины. Это сделано намеренно: строку кода можно прочитать, а вот почему она написана именно так, кроме истории, узнать негде.</p>'],

            // Вопросы — О САМОЙ CMS: заголовок материала это вопрос, текст —
            // ответ. Один материал = один вопрос, иначе в списке раскрывается
            // сразу пачка ответов и искать становится некогда.
            ['template' => 'faq', 'slug' => 'vopros-bez-programmista', 'title' => 'Нужен ли программист, чтобы вести сайт?',
             'content' => '<p>Нет. Разделы, страницы, меню, товары и оформление правятся в панели управления. Разработчик нужен, только если понадобится своя логика, которой в системе ещё нет.</p><p>Тексты и картинки редактор показывает так же, как их увидит посетитель, поэтому вёрстку сломать трудно.</p>'],

            ['template' => 'faq', 'slug' => 'vopros-baza-dannyh', 'title' => 'Какая база данных нужна?',
             'content' => '<p>PostgreSQL. Выбор осознанный: свободная, надёжная и хорошо работает с большими объёмами материалов.</p><p>Мастер установки проверяет соединение до того, как что-либо записать, и подсказывает, если реквизиты не подходят.</p>'],

            ['template' => 'faq', 'slug' => 'vopros-magazin', 'title' => 'Как устроен магазин?',
             'content' => '<p>Товар — обычный материал с ценой и остатком, поэтому добавляет его тот же человек, что пишет новости. Корзина работает сразу.</p><p>Способы оплаты и доставки включаются в панели по одному: те, которым не нужны реквизиты, готовы к работе после установки.</p>'],

            ['template' => 'faq', 'slug' => 'vopros-internet', 'title' => 'Обязателен ли доступ в интернет?',
             'content' => '<p>Нет. Шрифты, значки и библиотеки лежат локально, наружу система сама не ходит. Есть отдельный ключ, который запрещает исходящие запросы совсем — даже если ключи интеграций прописаны.</p><p>Внешние сервисы подключаются по желанию, а не по умолчанию.</p>'],

            ['template' => 'faq', 'slug' => 'vopros-obnovleniya', 'title' => 'Как выходят обновления?',
             'content' => '<p>Файлами. Отдельного сервера обновлений у системы нет и она никуда не отправляет лицензионный ключ: адрес проверки пуст по умолчанию.</p><p>После обновления обычно достаточно очистить кеш шаблонов командой <code>php artisan view:clear</code>.</p>'],

            // Отзывы — О САМОЙ CMS, а не о ремонте: демо-содержимое должно
            // объяснять продукт, а не изображать чужую нишу. Оценка — тот же
            // rating, что у обзоров «Игр», шкала 0..10.
            ['template' => 'reviews', 'slug' => 'otzyv-perenos-sayta', 'rating' => 9.0, 'title' => 'Перенесли сайт за вечер',
             'content' => '<p>Переезжали со старой самописной системы. Разделы, меню и страницы завели через панель за один вечер, вёрстку не трогали вообще.</p><p>Отдельно порадовало, что <strong>адреса страниц удалось сохранить прежними</strong> — поисковая выдача не просела.</p><p>Из минусов: пришлось разобраться с шаблонами материалов, но в подсказках всё описано.</p>'],

            ['template' => 'reviews', 'slug' => 'otzyv-magazin', 'rating' => 8.5, 'title' => 'Небольшой магазин без программиста',
             'content' => '<p>Держим витрину на полсотни позиций. Товар — обычный материал с ценой, поэтому добавляет их менеджер, а не разработчик.</p><p>Корзина, доставка и оплата настраиваются в панели: <strong>подключили наличные и перевод, остальное выключили</strong>, чтобы не смущать покупателя.</p><p>Хотелось бы складской учёт, но для нашего объёма хватает поля «Остаток».</p>'],

            ['template' => 'reviews', 'slug' => 'otzyv-redaktor', 'rating' => 9.5, 'title' => 'Редактор, в котором не страшно дать править текст',
             'content' => '<p>Раньше правки в текстах присылали письмом, потому что боялись сломать вёрстку. Теперь контент-менеджер работает сам.</p><p>Готовые блоки вставляются кнопкой, и <strong>материал выглядит в редакторе так же, как на сайте</strong> — это оказалось важнее всех остальных возможностей.</p>'],

            ['template' => 'reviews', 'slug' => 'otzyv-podderzhka', 'rating' => 8.0, 'title' => 'Работает на своём сервере без интернета',
             'content' => '<p>У нас закрытый контур: наружу система не ходит, и это было условием.</p><p>Проверили — <strong>шрифты, скрипты и значки лежат локально</strong>, обновления и оповещения отключаются одним ключом. Внешние сервисы подключаются по желанию, а не по умолчанию.</p><p>Установка заняла минут двадцать вместе с базой.</p>'],

            // Релизы — история изменений САМОЙ CMS. Номер версии берётся из
            // заголовка, поэтому он в нём обязателен: «Версия 1.1.0».
            ['template' => 'release', 'slug' => 'reliz-1-0-0', 'title' => 'Версия 1.0.0 — первый выпуск',
             'content' => '<h3>Что вошло</h3><ul><li>Материалы, страницы, меню и категории</li><li>Мастер установки с проверкой окружения</li><li>Пять оформлений и свой редактор содержимого</li></ul><p>Первая сборка, с которой систему можно поставить и вести сайт без разработчика.</p>'],

            ['template' => 'release', 'slug' => 'reliz-1-0-5', 'title' => 'Версия 1.0.5',
             'content' => '<h3>Добавлено</h3><ul><li>Корзина, способы оплаты и доставки</li><li>Двухфакторная проверка входа</li><li>Раздел «Отзывы» с оценкой</li></ul><h3>Исправлено</h3><ul><li>Пункт меню без родителя не сохранялся</li><li>Смена статуса заказа падала с ошибкой</li></ul>'],

            ['template' => 'release', 'slug' => 'reliz-1-1-0', 'title' => 'Версия 1.1.0',
             'content' => '<h3>Добавлено</h3><ul><li>Согласие на обработку данных в корзине</li><li>Оформление страницы новости под её шаблон</li><li>Плашка оценки с цветом по значению</li></ul><h3>Исправлено</h3><ul><li>Обложки срезались на части раскладок</li><li>Слипались слова в анонсах карточек</li><li>Подменю не открывались на планшете в альбомной</li></ul><p>Обновление применяется без миграций: достаточно обновить файлы и очистить кеш командой <code>php artisan view:clear</code>.</p>'],

            ['template' => 'release', 'slug' => 'reliz-1-2-0', 'title' => 'Версия 1.2.0',
             'content' => '<h3>Добавлено</h3><ul><li>Раздел «Вопросы» раскрывающимся списком</li><li>Лента выпусков вместо карточек</li><li>Общая плашка заголовка у всех разделов</li></ul><h3>Исправлено</h3><ul><li>Корзина зажигалась неопубликованным товаром</li><li>Оценка не сохранялась у отзывов</li></ul><p>Совместимо с 1.1: правок в базе не требуется.</p>'],
        ];

        foreach ($поШаблонам as $item) {
            if (DB::table('news')->where('slug', $item['slug'])->exists()) {
                continue;
            }

            DB::table('news')->insert([
                'title' => $item['title'],
                'content' => $item['content'],
                'slug' => $item['slug'],
                'rating' => $item['rating'] ?? null,
                // Цена нужна услугам: без неё карточка показывает «По запросу»
                // у всех шести подряд, и раздел перестаёт что-либо сообщать.
                'price' => $item['price'] ?? null,
                'published' => true,
                'template' => $item['template'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 🎮 Демо-материалы игрового раздела. Метка карточки — ведущий
        // эмодзи заголовка, оценка обзора — собственное поле rating.
        $gaming = [
            ['title' => '🔥 Патч 1.4: что изменилось', 'slug' => 'igry-patch-14', 'rating' => 9.0, 'content' => '<p><img src="/images/gaming/patch.svg" alt="🔥 Патч 1.4: что изменилось" style="width:100%;max-width:560px;height:auto"></p><p>Крупное обновление баланса и производительности. Перед установкой рекомендуем проверить драйверы видеокарты.</p><ul><li>Переработан баланс ближнего боя</li><li>Загрузка уровней ускорена примерно на треть</li><li>Исправлено 84 ошибки, включая вылет на финальной сцене</li></ul>'],
            ['title' => '🏆 Турнир выходного дня', 'slug' => 'igry-turnir', 'rating' => 8.0, 'content' => '<p><img src="/images/gaming/turnir.svg" alt="🏆 Турнир выходного дня" style="width:100%;max-width:560px;height:auto"></p><p>Открытая сетка на 128 участников, регистрация до пятницы. Формат — двойное выбывание, финал в воскресенье вечером.</p><ul><li>Призовой фонд делится между первой тройкой</li><li>Матчи транслируются на канале сообщества</li><li>Участие бесплатное, нужен только аккаунт</li></ul>'],
            ['title' => '⭐ Обзор: стоит ли брать', 'slug' => 'igry-obzor', 'rating' => 8.5, 'content' => '<p><img src="/images/gaming/obzor.svg" alt="⭐ Обзор: стоит ли брать" style="width:100%;max-width:560px;height:auto"></p><p>Прошли основную кампанию за 22 часа и собрали впечатления. Сильная постановка и звук, вопросы к последней трети сюжета.</p><ul><li>Плюсы: атмосфера, музыка, боевая система</li><li>Минусы: затянутая концовка, редкие просадки</li><li>Вывод: брать, но можно дождаться скидки</li></ul>'],
            ['title' => '🚀 Анонс дополнения', 'slug' => 'igry-anons', 'rating' => 7.5, 'content' => '<p><img src="/images/gaming/anons.svg" alt="🚀 Анонс дополнения" style="width:100%;max-width:560px;height:auto"></p><p>Новая сюжетная арка, два региона и режим совместного прохождения. Выход — в начале следующего сезона.</p><ul><li>Около 12 часов новой кампании</li><li>Кооператив до трёх игроков</li><li>Владельцам издания «Максимум» — бесплатно</li></ul>'],
            ['title' => '📘 Гайд для новичка', 'slug' => 'igry-gaydy', 'rating' => 9.5, 'content' => '<p><img src="/images/gaming/gaydy.svg" alt="📘 Гайд для новичка" style="width:100%;max-width:560px;height:auto"></p><p>Что делать в первые три часа, чтобы не застрять. Коротко, без спойлеров основного сюжета.</p><ul><li>Соберите базовый набор до первого босса</li><li>Не тратьте редкий ресурс на ранние улучшения</li><li>Сложность меняется в любой момент без штрафов</li></ul>'],
            ['title' => '🎬 Стрим по пятницам', 'slug' => 'igry-strim', 'rating' => 8.0, 'content' => '<p><img src="/images/gaming/strim.svg" alt="🎬 Стрим по пятницам" style="width:100%;max-width:560px;height:auto"></p><p>Играем вместе каждую пятницу в 20:00. Разбираем сложные места, отвечаем на вопросы в чате.</p><ul><li>Запись остаётся в архиве канала</li><li>Темы предлагаются в комментариях</li><li>Иногда зовём разработчиков</li></ul>'],
        ];

        foreach ($gaming as $item) {
            if (DB::table('news')->where('slug', $item['slug'])->exists()) {
                continue;
            }

            DB::table('news')->insert([
                'title' => $item['title'],
                'content' => $item['content'],
                'slug' => $item['slug'],
                'rating' => $item['rating'],
                'published' => true,
                'template' => 'gaming',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Главная кеширует блоки по шаблонам на 5 минут (HomeController).
        // Без сброса свежесозданные материалы не появятся там до истечения
        // срока — со стороны это выглядит как «товары не создались».
        Cache::flush();

        // Меню-хедер по умолчанию (Главная/О нас/Вопросы/Контакты) сидируется
        // отдельным идемпотентным методом seedDefaultMenu(), который ВСЕГДА
        // вызывается на шаге finish — вне зависимости от того, ставились ли
        // демо-данные. Дублировать его тут не нужно: одно определение меню.
        $this->seedDefaultMenu();
        });
    }

    /**
     * Дефолтное меню-хедер после установки.
     *
     * Вызывается ВСЕГДА на шаге finish (и заодно из installDemoData), поэтому
     * навигация на фронте есть сразу после установки, даже без демо-данных —
     * во фронтовой шапке статичного fallback больше нет, источник правды только
     * БД. Идемпотентно: «Главное меню» (header) переиспользуется, если уже есть;
     * пункты вставляются только отсутствующие (по паре menu_id + url), так что
     * повторный вызов ничего не дублирует и не затирает правки владельца.
     *
     * type='url' и active=true проставляются явно: фронтовая шапка строит ссылку
     * через match($item->type) (без type ссылка ушла бы в '#'), а список фильтрует
     * по active.
     */
    private function seedDefaultMenu(): void
    {
        // Единый источник дефолтного меню-хедера (Главная/О нас/Вопросы/Контакты
        // с валидными Lucide-иконками) — команда модуля Menu. Тот же код доступен
        // владельцу вручную: `php artisan menu:seed-default [--reset]`. Здесь без
        // --reset: идемпотентное дозаполнение, чужие пункты не трогаем.
        \Modules\Menu\Console\Commands\SeedDefaultMenuCommand::seed(false);
    }

    /**
     * Демо-страницы после установки: «О проекте», «Возможности», «Частые вопросы».
     * Единый источник — команда модуля Menu (`php artisan pages:seed-default`),
     * вызывается всегда на шаге finish. Идемпотентно (страницы ищутся по slug),
     * без --reset: уже существующие страницы владельца не перезаписываются.
     */
    /**
     * Типовые для РФ способы оплаты после установки: ЮKassa, СБП, SberPay,
     * Т-Банк, наличные и банковский перевод.
     *
     * Единый источник — команда модуля (`php artisan payments:seed-default`).
     * Методы создаются ВЫКЛЮЧЕННЫМИ и без ключей: реквизиты владелец вводит
     * сам в панели, в коде их нет и быть не должно. Идемпотентно (сверка по
     * коду метода), без --reset — уже настроенное не перезаписывается.
     */
    private function seedDefaultPaymentMethods(): void
    {
        \Modules\Payments\Console\Commands\SeedDefaultPaymentMethodsCommand::seed(false);
    }

    /**
     * Один демонстрационный заказ.
     *
     * Раздел «Заказы» после установки был пуст, и посмотреть его в работе
     * можно было только оформив покупку руками. Заказ собирается из уже
     * заведённых товаров и включённых способов оплаты и доставки, поэтому
     * зовётся ПОСЛЕ демо-содержимого — раньше собирать было бы не из чего.
     *
     * Остаток товара при этом не списывается: это витрина раздела, а не
     * покупка (подробности — в самой команде).
     */
    private function seedDemoOrder(): void
    {
        \Modules\Payments\Console\Commands\SeedDemoOrderCommand::seed(false);
    }

    /**
     * Типовые для РФ службы доставки после установки: Почта России, СДЭК,
     * Boxberry, Яндекс Доставка, самовывоз и курьер по городу.
     *
     * Как и у оплаты: методы создаются ВЫКЛЮЧЕННЫМИ и без ключей API,
     * идемпотентно (сверка по коду), без --reset.
     */
    private function seedDefaultDeliveryMethods(): void
    {
        \Modules\Delivery\Console\Commands\SeedDefaultDeliveryMethodsCommand::seed(false);
    }

    private function seedDefaultPages(): void
    {
        \Modules\Menu\Console\Commands\SeedDefaultPagesCommand::seed(false);
    }

    /**
     * Демо-слайдшоу после установки: «Верхний баннер» (верх главной) и
     * «Нижний баннер» (низ главной). Единый источник — команда модуля Slideshow
     * (`php artisan slideshow:seed-default`). Баннеры копируются из ресурсов
     * модуля в storage/app/public, поэтому вызывается ПОСЛЕ storage:link.
     * Идемпотентно (слайдшоу ищутся по slug), без --reset.
     */
    private function seedDefaultSlideshows(): void
    {
        \Modules\Slideshow\Console\Commands\SeedDefaultSlideshowsCommand::seed(false);
    }

    /**
     * Демо-файлы в медиа-библиотеке после установки: логотип, пример обложки
     * и заглушка 16:9. Единый источник — команда модуля Files
     * (`php artisan files:seed-default`). Файлы копируются из ресурсов модуля
     * в storage/app/public/files/defaults, поэтому вызывается после storage:link.
     * Идемпотентно (записи ищутся по path), без --reset.
     */
    private function seedDefaultFiles(): void
    {
        \Modules\Files\Console\Commands\SeedDefaultFilesCommand::seed(false);
    }

    /**
     * Демо-уведомление после установки: пример объявления о техработах со всеми
     * заполненными полями. Создаётся ВЫКЛЮЧЕННЫМ — это образец для правки, а не
     * баннер, который должен всплыть у посетителей свежего сайта.
     * Единый источник — команда модуля (`php artisan notifications:seed-default`).
     */
    private function seedDefaultNotification(): void
    {
        \Modules\Notifications\Console\Commands\SeedDefaultNotificationCommand::seed(false);
    }

    /**
     * Пять готовых тем оформления после установки, одна из них активна.
     * Без этого таблица visual_themes оставалась пустой: раздел «Темы» был
     * пустым, а сайт рисовался значениями, зашитыми в лейаутах.
     * Единый источник — команда модуля (`php artisan themes:seed-default`).
     */
    private function seedDefaultThemes(): void
    {
        \Modules\Visual\Console\Commands\SeedDefaultThemesCommand::seed(false);
    }

    /**
     * Фрагменты для зон сайта и панели — сразу включённые: новый администратор
     * должен увидеть их на страницах и понять, что это редактируемые блоки
     * (в панели они прямо на это указывают). Любой выключается переключателем
     * в разделе. Единый источник — команда модуля (`fragments:seed-default`).
     */
    /**
     * Сборки каптчи по умолчанию.
     *
     * Единый источник — команда модуля (`php artisan captcha:seed-default`).
     * Ставится рядом с формами: каптча вставляется в форму, и пустой список
     * сборок делал бы настройку формы тупиковой.
     */
    private function seedDefaultCaptchaPresets(): void
    {
        \Modules\Captcha\Console\Commands\SeedDefaultCaptchaPresetsCommand::seed(false);
    }

    /**
     * Формы по умолчанию.
     *
     * Единый источник — команда модуля (`php artisan forms:seed-default`).
     */
    private function seedDefaultForms(): void
    {
        \Modules\Forms\Console\Commands\SeedDefaultFormsCommand::seed(false);
    }

    /**
     * Категории по умолчанию и раскладка по ним материалов.
     *
     * Единый источник — команда модуля (`php artisan categories:seed-default`).
     */
    private function seedDefaultCategories(): void
    {
        \Modules\Categories\Console\Commands\SeedDefaultCategoriesCommand::seed(false);
    }

    private function seedDefaultFragments(): void
    {
        \Modules\Visual\Console\Commands\SeedDefaultFragmentsCommand::seed(false);
    }

    /**
     * Переводы демо-контента: без них переключение языка выглядело недоделкой —
     * интерфейс менялся, а меню, страницы и новости оставались русскими, потому
     * что их тексты лежат в базе, а не в словарях.
     */
    private function seedContentTranslations(): void
    {
        \Modules\Localization\Console\Commands\SeedContentTranslationsCommand::seed(false);
    }

    /**
     * Роли и права доступа (RBAC).
     *
     * RbacSeeder создаёт 4 базовые роли (Администратор/Редактор/Автор/Просмотр)
     * и 18 прав, но раньше не вызывался НИГДЕ: ни из DatabaseSeeder, ни при
     * установке. Из-за этого таблица roles оставалась пустой, а в разделе
     * «Пользователи» действие «Назначить роль» вело в тупик — выбирать было
     * нечего. Сидер идемпотентен (firstOrCreate по slug).
     */
    /**
     * Настройки спецвозможностей. Без них строка заводится лениво и
     * приходит ВЫКЛЮЧЕННОЙ — кнопки спецвозможностей на сайте нет вовсе.
     */
    private function seedDefaultAccessibility(): void
    {
        \Modules\Accessibility\Console\Commands\SeedDefaultAccessibilityCommand::seed(false);
    }

    /**
     * SEO-записи для созданного содержимого. Демо-материалы вставляются
     * запросом, а не через модель, поэтому события синхронизации не
     * срабатывают и раздел SEO после установки оставался пустым.
     */
    private function seedSeoPages(): void
    {
        \Modules\Seo\Console\Commands\SeedSeoPagesCommand::seed(false);
    }

    /**
     * Витрина: продающая главная вместо набора демо-разделов.
     *
     * Свежая установка показывала вперемешку магазин, клинику, игры и учебные
     * уроки — это демонстрация шаблонов, а не рассказ о продукте. Плюс на
     * главную выводились ВСЕ страницы, включая соглашение и карту сайта.
     *
     * ⚠️ Демо-материалы НЕ удаляются: они остаются опубликованными и
     * доступными по своим адресам, снимается только показ на главной.
     */
    private function seedPresentation(): void
    {
        \App\Console\Commands\SeedPresentationCommand::seed(false);
    }

    private function seedRolesAndPermissions(): void
    {
        (new \Database\Seeders\RbacSeeder())->run();
    }

    /**
     * Подстраховка для публичного хранилища.
     *
     * Всё, что лежит в storage/app/public, доступно из веба через симлинк
     * public/storage. Загрузку опасных типов уже режет белый список в
     * FileController, но если файл попадёт туда иначе (перенос со старого сайта,
     * ручное копирование, сторонний модуль) — исполняемым он быть не должен.
     * Кладём .htaccess, запрещающий выполнение скриптов: для Apache этого
     * достаточно. Для nginx аналогичное правило задаётся в конфиге сервера —
     * location ~* ^/storage/.*\.(php|phtml|phar|cgi|pl)$ { deny all; }
     *
     * Файл в .gitignore (storage/app/public/*), поэтому создаём его при установке.
     */
    private function hardenPublicStorage(): void
    {
        $path = storage_path('app/public/.htaccess');

        if (File::exists($path)) {
            return;
        }

        File::put($path, <<<'HTACCESS'
# Запрет выполнения скриптов в публичном хранилище (Apache).
# Файлы отдаются как статика; PHP и прочие обработчики отключены.
<FilesMatch "\.(php|phtml|phar|php[0-9]|cgi|pl|py|sh|htaccess)$">
    Require all denied
</FilesMatch>

php_flag engine off
Options -ExecCGI -Indexes
AddType text/plain .php .phtml .phar .cgi .pl .py .sh
HTACCESS);
    }

    /**
     * Применение настроек локализации после установки.
     * Возвращает предупреждение вместо тихого поглощения ошибки.
     *
     * ⚠️ Ключи для `.env` метод НЕ пишет сам, а складывает в
     * `$this->pendingEnv`: под сервером разработки запись в `.env` роняет
     * текущий запрос (сторож `artisan serve` гасит процесс), а этот метод
     * вызывается В НАЧАЛЕ финального шага — до двух десятков сидеров.
     * Пишет их `finish()` в самом конце, когда работа уже сделана.
     */
    private function applyLocalizationSettings(): void
    {
        try {
            $countryCode = session('install_country_code', 'RU');
            $countryData = self::COUNTRY_PRESETS[$countryCode] ?? null;

            if (!$countryData || !Schema::hasTable('countries')) {
                return;
            }

            $country = DB::table('countries')->where('code', $countryCode)->first();

            if (!$country) {
                DB::table('countries')->insert([
                    'code' => $countryCode,
                    'name' => $countryData['name'],
                    'native_name' => $countryData['native_name'],
                    'flag' => $countryData['flag'],
                    'currency_code' => $countryData['currency_code'],
                    'currency_symbol' => $countryData['currency_symbol'],
                    'locale' => $countryData['locale'],
                    'timezone' => $countryData['timezone'],
                    'date_format' => $countryData['date_format'],
                    'time_format' => $countryData['time_format'],
                    'decimal_separator' => $countryData['decimal_separator'],
                    'thousands_separator' => $countryData['thousands_separator'],
                    'decimal_places' => $countryData['decimal_places'],
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('countries')
                    ->where('code', $countryCode)
                    ->update([
                        'active' => true,
                        'locale' => $countryData['locale'],
                        'timezone' => $countryData['timezone'],
                        'updated_at' => now(),
                    ]);
            }

            if (Schema::hasTable('localization_settings')) {
                $countryId = DB::table('countries')->where('code', $countryCode)->value('id');

                if ($countryId) {
                    $translations = [
                        'RU' => 'Добро пожаловать',
                        'BY' => 'Сардэчна запрашаем',
                        'KZ' => 'Қош келдіңіз',
                        'UA' => 'Ласкаво просимо',
                        'US' => 'Welcome',
                        'DE' => 'Willkommen',
                    ];

                    $existing = DB::table('localization_settings')
                        ->where('country_id', $countryId)
                        ->where('key', 'welcome_message')
                        ->exists();

                    if (!$existing) {
                        DB::table('localization_settings')->insert([
                            'country_id' => $countryId,
                            'key' => 'welcome_message',
                            'value' => $translations[$countryCode] ?? 'Welcome',
                            'type' => 'string',
                            'group' => 'translation',
                            'description' => 'Приветственное сообщение',
                            'is_system' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            $this->pendingEnv += [
                'LOCALIZATION_DEFAULT_COUNTRY' => $countryCode,
                'APP_LOCALE' => $countryData['locale'],
                'APP_TIMEZONE' => $countryData['timezone'],
            ];
        } catch (\Throwable $e) {
            Log::warning('Failed to apply localization settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->pushInstallWarning('Не удалось применить настройки локализации: ' . $e->getMessage());
        }
    }

    /**
     * Создание подписки на основе лицензии или промокода из установки.
     */
    private function createSubscriptionFromInstall(): void
    {
        try {
            $userId = DB::table('users')->where('is_admin', true)->value('id');
            if (!$userId) {
                return;
            }

            $licenseKey = session('install_license_key');
            $promoCode = session('install_promo_code');
            $promoId = session('install_promo_id');

            if ($licenseKey) {
                $existing = DB::table('subscriptions')->where('license_key', $licenseKey)->first();

                if ($existing) {
                    $this->writeEnv(['LICENSE_KEY' => $licenseKey]);
                    return;
                }

                DB::table('subscriptions')->insert([
                    'user_id' => $userId,
                    'plan' => 'basic',
                    'license_key' => $licenseKey,
                    'starts_at' => now(),
                    'expires_at' => now()->addYear(),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->writeEnv(['LICENSE_KEY' => $licenseKey]);
            } elseif ($promoCode && $promoId) {
                $licenseKey = $this->subscriptionService->generateLicenseKey();

                DB::table('subscriptions')->insert([
                    'user_id' => $userId,
                    'plan' => 'basic',
                    'license_key' => $licenseKey,
                    'starts_at' => now(),
                    'expires_at' => now()->addYear(),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->subscriptionService->activatePromoCode($promoId, $userId);

                $this->writeEnv(['LICENSE_KEY' => $licenseKey]);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to create subscription during install', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->pushInstallWarning('Не удалось оформить подписку/лицензию автоматически: ' . $e->getMessage());
        }
    }
}
