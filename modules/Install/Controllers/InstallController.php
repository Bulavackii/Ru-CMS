<?php

namespace Modules\Install\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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
        'RU' => ['name' => 'Россия', 'native_name' => 'Россия', 'flag' => '🇷🇺', 'locale' => 'ru', 'timezone' => 'Europe/Moscow', 'currency_code' => 'RUB', 'currency_symbol' => '₽', 'date_format' => 'd.m.Y', 'time_format' => 'H:i', 'decimal_separator' => ',', 'thousands_separator' => ' ', 'decimal_places' => 2],
        'BY' => ['name' => 'Беларусь', 'native_name' => 'Беларусь', 'flag' => '🇧🇾', 'locale' => 'ru', 'timezone' => 'Europe/Minsk', 'currency_code' => 'BYN', 'currency_symbol' => 'Br', 'date_format' => 'd.m.Y', 'time_format' => 'H:i', 'decimal_separator' => ',', 'thousands_separator' => ' ', 'decimal_places' => 2],
        'KZ' => ['name' => 'Казахстан', 'native_name' => 'Қазақстан', 'flag' => '🇰🇿', 'locale' => 'ru', 'timezone' => 'Asia/Almaty', 'currency_code' => 'KZT', 'currency_symbol' => '₸', 'date_format' => 'd.m.Y', 'time_format' => 'H:i', 'decimal_separator' => ',', 'thousands_separator' => ' ', 'decimal_places' => 2],
        'UA' => ['name' => 'Украина', 'native_name' => 'Україна', 'flag' => '🇺🇦', 'locale' => 'ru', 'timezone' => 'Europe/Kyiv', 'currency_code' => 'UAH', 'currency_symbol' => '₴', 'date_format' => 'd.m.Y', 'time_format' => 'H:i', 'decimal_separator' => ',', 'thousands_separator' => ' ', 'decimal_places' => 2],
        'US' => ['name' => 'США', 'native_name' => 'United States', 'flag' => '🇺🇸', 'locale' => 'en', 'timezone' => 'America/New_York', 'currency_code' => 'USD', 'currency_symbol' => '$', 'date_format' => 'm/d/Y', 'time_format' => 'h:i A', 'decimal_separator' => '.', 'thousands_separator' => ',', 'decimal_places' => 2],
        'DE' => ['name' => 'Германия', 'native_name' => 'Deutschland', 'flag' => '🇩🇪', 'locale' => 'en', 'timezone' => 'Europe/Berlin', 'currency_code' => 'EUR', 'currency_symbol' => '€', 'date_format' => 'd.m.Y', 'time_format' => 'H:i', 'decimal_separator' => ',', 'thousands_separator' => '.', 'decimal_places' => 2],
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
        'license' => 'admin',
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
            'GD или Imagick'            => extension_loaded('gd') || extension_loaded('imagick'),
            'Writable: storage/'        => is_writable(storage_path()),
            'Writable: bootstrap/cache' => is_writable(base_path('bootstrap/cache')),
        ];

        $allPassed = !in_array(false, $requirements, true);

        return view('Install::requirements', compact('requirements', 'allPassed'));
    }

    /** 🎯 Презентация возможностей */
    public function features()
    {
        $features = [
            [
                'icon' => 'blocks',
                'title' => 'Модульная архитектура',
                'description' => 'HMVC архитектура с независимыми модулями. Легко подключайте, отключайте и создавайте собственные модули.',
                'highlight' => true,
            ],
            [
                'icon' => 'shield-check',
                'title' => 'Безопасность',
                'description' => '2FA аутентификация, защита от SQL injection и XSS, rate limiting, автоматическая блокировка подозрительных IP, аудит-лог действий.',
                'highlight' => true,
            ],
            [
                'icon' => 'zap',
                'title' => 'Производительность',
                'description' => 'Оптимизация БД, расширенное кэширование, оптимизация изображений, Gzip сжатие, устранение N+1 проблем.',
            ],
            [
                'icon' => 'globe',
                'title' => 'Мультиязычность',
                'description' => 'Поддержка русского и английского языков. Автоматическое определение языка, форматирование валют и дат для РФ/СНГ.',
            ],
            [
                'icon' => 'database-backup',
                'title' => 'Автоматические бэкапы',
                'description' => 'Ежедневное резервное копирование БД, еженедельное копирование файлов, загрузка в облако, управление через админ-панель.',
            ],
            [
                'icon' => 'refresh-cw',
                'title' => 'Централизованные обновления',
                'description' => 'Автоматическая проверка обновлений, безопасная установка с проверкой целостности, автоматические бэкапы перед обновлением.',
            ],
            [
                'icon' => 'credit-card',
                'title' => 'Система подписок',
                'description' => 'Гибкие тарифы (Basic, Pro, Enterprise), промокоды со скидками, лицензионные ключи, ограничения по тарифам.',
            ],
            [
                'icon' => 'bar-chart-3',
                'title' => 'Аналитика',
                'description' => 'Отслеживание просмотров, статистика посетителей, популярный контент, интеграция с Яндекс.Метрикой, графики и отчеты.',
            ],
            [
                'icon' => 'plug',
                'title' => 'REST API',
                'description' => 'Полноценный REST API с JWT аутентификацией, Swagger документация, версионирование API, rate limiting.',
            ],
            [
                'icon' => 'smartphone',
                'title' => 'Адаптивный дизайн',
                'description' => 'Современный интерфейс на TailwindCSS, работает на всех устройствах. Тёмная тема, настраиваемые темы и фрагменты.',
            ],
            [
                'icon' => 'message-square',
                'title' => 'Комментарии и модерация',
                'description' => 'Система комментариев с модерацией, вложенные комментарии, интеграция с Captcha, система голосования.',
            ],
            [
                'icon' => 'bell',
                'title' => 'Web Push уведомления',
                'description' => 'Push-уведомления в браузере, уведомления в админ-панели, настраиваемые типы и приоритеты уведомлений.',
            ],
        ];

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
            'host'     => 'Хост',
            'port'     => 'Порт',
            'database' => 'База данных',
            'username' => 'Пользователь',
            'password' => 'Пароль',
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
            return back()->withErrors(['security' => 'Обнаружена попытка SQL инъекции'])->withInput();
        }

        // 1) Тест соединения БД
        $ok = $this->testConnection($host, $port, $db, $user, $pass, $err);
        if (!$ok) {
            return back()->withErrors(['database' => "Не удалось подключиться к БД: " . $err])->withInput();
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
            return back()->withErrors(['env' => 'Ошибка записи .env: ' . $e->getMessage()])->withInput();
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
            'name'     => 'Имя',
            'email'    => 'Email',
            'password' => 'Пароль',
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

            // Проверка обязательных таблиц (без них система нежизнеспособна)
            $missing = $this->verifyInstalledTables();
            if (!empty($missing)) {
                $output = trim(Artisan::output());
                return back()->withErrors([
                    'migrations' => 'Не найдены обязательные таблицы: ' . implode(', ', $missing),
                    'artisan'    => $output ?: 'Нет вывода Artisan',
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
                'migrate' => 'Ошибка миграции: ' . $e->getMessage(),
                'artisan' => $output ?: 'Нет вывода Artisan',
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

                User::create($userData);
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['user' => 'Не удалось создать администратора: ' . $e->getMessage()])->withInput();
        }

        session(['install.completed.admin' => true]);

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
            'license_key' => 'Лицензионный ключ',
            'promo_code' => 'Промокод',
        ]);

        if ($v->fails()) {
            return back()->withErrors($v)->withInput();
        }

        $licenseKey = $request->input('license_key');
        $promoCode = $request->input('promo_code');

        if (empty($licenseKey) && empty($promoCode)) {
            return back()->withErrors(['license' => 'Укажите лицензионный ключ или промокод'])->withInput();
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
                return back()->withErrors(['license_key' => 'Неверный формат лицензионного ключа'])->withInput();
            }
            session(['install_license_key' => strtoupper($licenseKey)]);
        }

        try {
            $envLicenseKey = $licenseKey ?: 'PENDING';
            $this->writeEnv(['LICENSE_KEY' => $envLicenseKey]);
        } catch (\Throwable $e) {
            return back()->withErrors(['env' => 'Ошибка записи лицензии в .env: ' . $e->getMessage()])->withInput();
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
                return back()->withErrors(['demo' => 'Ошибка установки демо-данных: ' . $e->getMessage()]);
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
            \Log::warning('Install finish error', ['error' => $e->getMessage()]);
            $this->pushInstallWarning('Не всё удалось завершить автоматически: ' . $e->getMessage() . '. Установка всё равно считается выполненной — проверьте настройки в админ-панели.');
        }

        $warnings = session('install.warnings', []);
        session()->forget('install.warnings');

        $countryCode = session('install_country_code', 'RU');

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
            'license'  => 'install.license',
            'demo'     => 'install.demo',
        ];

        return redirect()
            ->route($routeMap[$prerequisite] ?? 'install.welcome')
            ->with('install_notice', 'Сначала завершите предыдущий шаг установки.');
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

        return 'Некоторые опциональные модули не создали свои таблицы: ' . implode(', ', $missing) . '. Это ожидаемо, если соответствующие модули отключены.';
    }

    /** 📦 Установка демо-данных */
    private function installDemoData(): void
    {
        $userId = DB::table('users')->where('is_admin', true)->value('id');

        if (!$userId) {
            return;
        }

        // Демо-категории (колонка называется title, не name; template на
        // categories не существует — это отдельное поле только у News)
        $categoryIds = [];
        $categories = [
            ['title' => 'Новости', 'slug' => 'news', 'type' => 'news'],
            ['title' => 'Товары', 'slug' => 'products', 'type' => 'product'],
            ['title' => 'Услуги', 'slug' => 'services', 'type' => 'page'],
        ];

        foreach ($categories as $cat) {
            $id = DB::table('categories')->insertGetId([
                'title' => $cat['title'],
                'slug' => $cat['slug'],
                'type' => $cat['type'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $categoryIds[] = $id;
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
            $newsId = DB::table('news')->insertGetId([
                'title' => $news['title'],
                'content' => $news['content'],
                'slug' => $news['slug'],
                'published' => $news['published'],
                'template' => $news['template'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (!empty($categoryIds)) {
                DB::table('news_category')->insert([
                    'news_id' => $newsId,
                    'category_id' => $categoryIds[0],
                ]);
            }
        }

        // Демо-меню
        $menuId = DB::table('menus')->insertGetId([
            'title' => 'Главное меню',
            'position' => 'header',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('menu_items')->insert([
            [
                'menu_id' => $menuId,
                'title' => 'Главная',
                'url' => '/',
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'menu_id' => $menuId,
                'title' => 'Новости',
                'url' => '/news',
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
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
            \Log::warning('Failed to apply localization settings', [
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
            \Log::error('Failed to create subscription during install', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->pushInstallWarning('Не удалось оформить подписку/лицензию автоматически: ' . $e->getMessage());
        }
    }
}
