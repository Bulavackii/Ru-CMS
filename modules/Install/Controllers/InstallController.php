<?php

namespace Modules\Install\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
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
        'BY' => ['name' => 'Беларусь', 'native_name' => 'Беларусь', 'flag' => '🇧🇾', 'lang' => 'Беларуская', 'locale' => 'be', 'timezone' => 'Europe/Minsk', 'currency_code' => 'BYN', 'currency_symbol' => 'Br', 'date_format' => 'd.m.Y', 'time_format' => 'H:i', 'decimal_separator' => ',', 'thousands_separator' => ' ', 'decimal_places' => 2],
        'KZ' => ['name' => 'Казахстан', 'native_name' => 'Қазақстан', 'flag' => '🇰🇿', 'lang' => 'Қазақша', 'locale' => 'kk', 'timezone' => 'Asia/Almaty', 'currency_code' => 'KZT', 'currency_symbol' => '₸', 'date_format' => 'd.m.Y', 'time_format' => 'H:i', 'decimal_separator' => ',', 'thousands_separator' => ' ', 'decimal_places' => 2],
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
        'demo' => 'license',
        'finish' => 'demo',
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
            return redirect()->route('install.demo');
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

        return redirect()->route('install.demo');
    }

    /** 📦 Установка демо-данных */
    public function demo(Request $request)
    {
        if ($redirect = $this->guardStep('demo')) {
            return $redirect;
        }

        if ($request->isMethod('get')) {
            $installDemo = session('install_demo_data', false);
            return view('Install::demo', compact('installDemo'));
        }

        // POST - установка демо-данных
        if ($request->boolean('install_demo')) {
            try {
                $this->installDemoData();
            } catch (\Throwable $e) {
                return back()->withErrors(['demo' => __('install.errors.demo', ['error' => $e->getMessage()])]);
            }
        }

        session(['install.completed.demo' => true]);

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
            'demo'     => 'install.demo',
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
                'title' => 'Добро пожаловать в RU CMS!',
                'content' => 'Это ваша первая новость. Вы можете редактировать её в админ-панели.',
                'slug' => 'welcome-to-ru-cms',
                'published' => true,
                'template' => 'default',
            ],
            [
                'title' => 'Модульная архитектура',
                'content' => 'RU CMS построена на модульной архитектуре. Легко подключайте и отключайте модули.',
                'slug' => 'modular-architecture',
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

        // Демо-меню (идемпотентно по паре title+position)
        $menuId = DB::table('menus')
            ->where('title', 'Главное меню')
            ->where('position', 'header')
            ->value('id');
        if (!$menuId) {
            $menuId = DB::table('menus')->insertGetId([
                'title' => 'Главное меню',
                'position' => 'header',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Пункты меню — вставляем только отсутствующие (по menu_id + url)
        $menuItems = [
            ['title' => 'Главная', 'url' => '/', 'order' => 1],
            ['title' => 'Новости', 'url' => '/news', 'order' => 2],
        ];
        foreach ($menuItems as $item) {
            $exists = DB::table('menu_items')
                ->where('menu_id', $menuId)
                ->where('url', $item['url'])
                ->exists();
            if ($exists) {
                continue;
            }
            DB::table('menu_items')->insert([
                'menu_id' => $menuId,
                'title' => $item['title'],
                'url' => $item['url'],
                'order' => $item['order'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        });
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
