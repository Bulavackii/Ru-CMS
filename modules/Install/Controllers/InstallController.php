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

    public function __construct(SecurityService $securityService, SubscriptionService $subscriptionService)
    {
        $this->securityService = $securityService;
        $this->subscriptionService = $subscriptionService;
    }

    /** 🚀 Стартовая страница с выбором языка и страны */
    public function welcome(Request $request)
    {
        $presetCountries = self::COUNTRY_PRESETS;

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
                    'from_name'    => env('MAIL_FROM_NAME', config('app.name', 'RU CMS')),
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
        $fromName   = (string) ($request->input('mail_from_name') ?: config('app.name', 'RU CMS'));

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
        return env('DEVELOPER_MODE', false) === true || env('DEVELOPER_MODE') === 'true';
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
            $this->seedDefaultFragments();
            $this->seedContentTranslations();
            $this->seedRolesAndPermissions();
            $this->hardenPublicStorage();

            File::put(storage_path('install.lock'), 'Installed at ' . now()->toDateTimeString());

            Artisan::call('config:clear');
            Artisan::call('cache:clear');
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

        return view('Install::finish', [
            'warnings' => $warnings,
            'selectedCountry' => self::COUNTRY_PRESETS[$countryCode] ?? null,
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
                File::put($envPath, "APP_NAME=\"RU CMS\"\nAPP_ENV=local\nAPP_KEY=\nAPP_DEBUG=true\n");
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
        $categoryIds = [];
        $categories = [
            ['title' => 'Новости', 'slug' => 'news', 'type' => 'news'],
            ['title' => 'Товары', 'slug' => 'products', 'type' => 'product'],
            ['title' => 'Услуги', 'slug' => 'services', 'type' => 'page'],
        ];

        foreach ($categories as $cat) {
            $existingId = DB::table('categories')->where('slug', $cat['slug'])->value('id');
            if ($existingId) {
                $categoryIds[] = $existingId;
                continue;
            }
            $categoryIds[] = DB::table('categories')->insertGetId([
                'title' => $cat['title'],
                'slug' => $cat['slug'],
                'type' => $cat['type'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Демо-новости
        $newsItems = [
            [
                'title' => 'Добро пожаловать в RU CMS',
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
                'slug' => 'welcome-to-ru-cms',
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

            if (!empty($categoryIds)) {
                $alreadyLinked = DB::table('news_category')
                    ->where('news_id', $newsId)
                    ->where('category_id', $categoryIds[0])
                    ->exists();
                if (!$alreadyLinked) {
                    DB::table('news_category')->insert([
                        'news_id' => $newsId,
                        'category_id' => $categoryIds[0],
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
                'content' => "<p><img src=\"/images/products/naushniki.svg\" alt=\"Беспроводные наушники\" style=\"width:100%;max-width:420px;height:auto\"></p>\n<p>Накладные наушники с шумоподавлением и автономностью до 30 часов. Складная конструкция, чехол в комплекте.</p>",
            ],
            [
                'title' => 'Умные часы',
                'slug' => 'tovar-chasy',
                'price' => 8900,
                'stock' => 7,
                'content' => "<p><img src=\"/images/products/chasy.svg\" alt=\"Умные часы\" style=\"width:100%;max-width:420px;height:auto\"></p>\n<p>Пульс, шаги, сон и уведомления с телефона. Влагозащита, экран читается на солнце, зарядки хватает на неделю.</p>",
            ],
            [
                'title' => 'Городской рюкзак',
                'slug' => 'tovar-ryukzak',
                'price' => 3450,
                'stock' => 20,
                'content' => "<p><img src=\"/images/products/ryukzak.svg\" alt=\"Городской рюкзак\" style=\"width:100%;max-width:420px;height:auto\"></p>\n<p>Отделение под ноутбук до 15\", влагостойкая ткань, потайной карман на спинке. Объём 22 литра.</p>",
            ],
            [
                'title' => 'Керамическая кружка',
                'slug' => 'tovar-kruzhka',
                'price' => 690,
                'stock' => 48,
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

        // 🎮 Демо-материалы игрового раздела. Метка карточки — ведущий
        // эмодзи заголовка, оценка обзора — собственное поле rating.
        $gaming = [
            ['title' => '🔥 Патч 1.4: что изменилось', 'slug' => 'igry-patch-14', 'rating' => null, 'content' => '<p><img src="/images/gaming/patch.svg" alt="🔥 Патч 1.4: что изменилось" style="width:100%;max-width:560px;height:auto"></p><p>Крупное обновление баланса и производительности. Перед установкой рекомендуем проверить драйверы видеокарты.</p><ul><li>Переработан баланс ближнего боя</li><li>Загрузка уровней ускорена примерно на треть</li><li>Исправлено 84 ошибки, включая вылет на финальной сцене</li></ul>'],
            ['title' => '🏆 Турнир выходного дня', 'slug' => 'igry-turnir', 'rating' => null, 'content' => '<p><img src="/images/gaming/turnir.svg" alt="🏆 Турнир выходного дня" style="width:100%;max-width:560px;height:auto"></p><p>Открытая сетка на 128 участников, регистрация до пятницы. Формат — двойное выбывание, финал в воскресенье вечером.</p><ul><li>Призовой фонд делится между первой тройкой</li><li>Матчи транслируются на канале сообщества</li><li>Участие бесплатное, нужен только аккаунт</li></ul>'],
            ['title' => '⭐ Обзор: стоит ли брать', 'slug' => 'igry-obzor', 'rating' => 8.5, 'content' => '<p><img src="/images/gaming/obzor.svg" alt="⭐ Обзор: стоит ли брать" style="width:100%;max-width:560px;height:auto"></p><p>Прошли основную кампанию за 22 часа и собрали впечатления. Сильная постановка и звук, вопросы к последней трети сюжета.</p><ul><li>Плюсы: атмосфера, музыка, боевая система</li><li>Минусы: затянутая концовка, редкие просадки</li><li>Вывод: брать, но можно дождаться скидки</li></ul>'],
            ['title' => '🚀 Анонс дополнения', 'slug' => 'igry-anons', 'rating' => null, 'content' => '<p><img src="/images/gaming/anons.svg" alt="🚀 Анонс дополнения" style="width:100%;max-width:560px;height:auto"></p><p>Новая сюжетная арка, два региона и режим совместного прохождения. Выход — в начале следующего сезона.</p><ul><li>Около 12 часов новой кампании</li><li>Кооператив до трёх игроков</li><li>Владельцам издания «Максимум» — бесплатно</li></ul>'],
            ['title' => '📘 Гайд для новичка', 'slug' => 'igry-gaydy', 'rating' => null, 'content' => '<p><img src="/images/gaming/gaydy.svg" alt="📘 Гайд для новичка" style="width:100%;max-width:560px;height:auto"></p><p>Что делать в первые три часа, чтобы не застрять. Коротко, без спойлеров основного сюжета.</p><ul><li>Соберите базовый набор до первого босса</li><li>Не тратьте редкий ресурс на ранние улучшения</li><li>Сложность меняется в любой момент без штрафов</li></ul>'],
            ['title' => '🎬 Стрим по пятницам', 'slug' => 'igry-strim', 'rating' => null, 'content' => '<p><img src="/images/gaming/strim.svg" alt="🎬 Стрим по пятницам" style="width:100%;max-width:560px;height:auto"></p><p>Играем вместе каждую пятницу в 20:00. Разбираем сложные места, отвечаем на вопросы в чате.</p><ul><li>Запись остаётся в архиве канала</li><li>Темы предлагаются в комментариях</li><li>Иногда зовём разработчиков</li></ul>'],
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
     * Демо-слайдшоу после установки: «Главный баннер» (верх главной) и
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

            $this->writeEnv([
                'LOCALIZATION_DEFAULT_COUNTRY' => $countryCode,
                'APP_LOCALE' => $countryData['locale'],
                'APP_TIMEZONE' => $countryData['timezone'],
            ]);
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
