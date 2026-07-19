<?php

namespace Modules\Install\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Services\SecurityService;
use App\Services\SubscriptionService;

class InstallController extends Controller
{
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
        // Получаем предустановленные страны из конфига Localization
        $presetCountries = config('localization.preset_countries', []);
        
        // Если конфиг недоступен, используем базовые языки
        if (empty($presetCountries)) {
            $presetCountries = [
                'RU' => [
                    'name' => 'Россия',
                    'native_name' => 'Россия',
                    'flag' => '🇷🇺',
                    'locale' => 'ru',
                    'timezone' => 'Europe/Moscow',
                ],
                'US' => [
                    'name' => 'США',
                    'native_name' => 'United States',
                    'flag' => '🇺🇸',
                    'locale' => 'en',
                    'timezone' => 'America/New_York',
                ],
            ];
        }

        // Сохранение выбранной страны в сессии
        if ($request->has('country_code')) {
            $countryCode = strtoupper($request->get('country_code'));
            
            // Проверяем, что страна существует в предустановленных
            if (isset($presetCountries[$countryCode])) {
                $country = $presetCountries[$countryCode];
                session([
                    'install_country_code' => $countryCode,
                    'install_locale' => $country['locale'] ?? 'ru',
                    'install_timezone' => $country['timezone'] ?? 'Europe/Moscow',
                ]);
                
                // Устанавливаем локаль для интерфейса установки
                app()->setLocale($country['locale'] ?? 'ru');
            }
        } else {
            // Загружаем из сессии или используем по умолчанию
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
            'PDO'                       => extension_loaded('pdo'),
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
                'icon' => '🧩',
                'title' => 'Модульная архитектура',
                'description' => 'HMVC архитектура с независимыми модулями. Легко подключайте, отключайте и создавайте собственные модули.',
                'highlight' => true,
            ],
            [
                'icon' => '🔒',
                'title' => 'Безопасность',
                'description' => '2FA аутентификация, защита от SQL injection и XSS, rate limiting, автоматическая блокировка подозрительных IP, аудит-лог действий.',
                'highlight' => true,
            ],
            [
                'icon' => '⚡',
                'title' => 'Производительность',
                'description' => 'Оптимизация БД, расширенное кэширование, оптимизация изображений, Gzip сжатие, устранение N+1 проблем.',
            ],
            [
                'icon' => '🌍',
                'title' => 'Мультиязычность',
                'description' => 'Поддержка русского и английского языков. Автоматическое определение языка, форматирование валют и дат для РФ/СНГ.',
            ],
            [
                'icon' => '💾',
                'title' => 'Автоматические бэкапы',
                'description' => 'Ежедневное резервное копирование БД, еженедельное копирование файлов, загрузка в облако, управление через админ-панель.',
            ],
            [
                'icon' => '🔄',
                'title' => 'Централизованные обновления',
                'description' => 'Автоматическая проверка обновлений, безопасная установка с проверкой целостности, автоматические бэкапы перед обновлением.',
            ],
            [
                'icon' => '💳',
                'title' => 'Система подписок',
                'description' => 'Гибкие тарифы (Basic, Pro, Enterprise), промокоды со скидками, лицензионные ключи, ограничения по тарифам.',
            ],
            [
                'icon' => '📊',
                'title' => 'Аналитика',
                'description' => 'Отслеживание просмотров, статистика посетителей, популярный контент, интеграция с Яндекс.Метрикой, графики и отчеты.',
            ],
            [
                'icon' => '🔌',
                'title' => 'REST API',
                'description' => 'Полноценный REST API с JWT аутентификацией, Swagger документация, версионирование API, rate limiting.',
            ],
            [
                'icon' => '📱',
                'title' => 'Адаптивный дизайн',
                'description' => 'Современный интерфейс на TailwindCSS, работает на всех устройствах. Темная тема, настраиваемые темы и фрагменты.',
            ],
            [
                'icon' => '💬',
                'title' => 'Комментарии и модерация',
                'description' => 'Система комментариев с модерацией, вложенные комментарии, интеграция с Captcha, система голосования.',
            ],
            [
                'icon' => '🔔',
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
            return view('Install::database');
        }

        // POST
        $v = Validator::make($request->all(), [
            'host'       => ['required','string','max:255'],
            'port'       => ['required','numeric'],
            'database'   => ['required','string','max:191'],
            'username'   => ['required','string','max:191'],
            'password'   => ['nullable','string','max:191'],
            'connection' => ['sometimes','in:mysql,pgsql,sqlite,sqlsrv'],
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

        $conn = $request->input('connection', 'mysql');
        $host = $request->input('host');
        $port = $request->input('port');
        $db   = $request->input('database');
        $user = $request->input('username');
        $pass = $request->input('password');

        // Проверка на SQL injection
        if ($this->securityService->detectSqlInjection($host . $db . $user)) {
            return back()->withErrors(['security' => 'Обнаружена попытка SQL инъекции'])->withInput();
        }

        // 1) Тест соединения БД
        $ok = $this->testConnection($conn, $host, $port, $db, $user, $pass, $err);
        if (!$ok) {
            return back()->withErrors(['database' => "Не удалось подключиться к БД: ".$err])->withInput();
        }

        // 2) Запись .env
        try {
            // Получаем выбранную страну из сессии
            $countryCode = session('install_country_code', 'RU');
            $locale = session('install_locale', 'ru');
            $timezone = session('install_timezone', 'Europe/Moscow');
            
            // Генерируем APP_KEY если его нет
            $appKey = config('app.key');
            if (empty($appKey) || $appKey === '') {
                $appKey = 'base64:'.base64_encode(random_bytes(32));
            }
            
            $this->writeEnv([
                'APP_URL'          => rtrim($request->getSchemeAndHttpHost(), '/'),
                'APP_KEY'          => $appKey,
                'APP_LOCALE'       => $locale,
                'APP_TIMEZONE'     => $timezone,
                'LOCALIZATION_DEFAULT_COUNTRY' => $countryCode,
                'DB_CONNECTION'    => $conn,
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
            return back()->withErrors(['env' => 'Ошибка записи .env: '.$e->getMessage()])->withInput();
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

        return redirect()->route('install.admin');
    }

    /** 👤 Создание администратора + миграции */
    public function admin(Request $request)
    {
        if ($request->isMethod('get')) {
            return view('Install::admin');
        }

        // POST
        $v = Validator::make($request->all(), [
            'name'     => ['required','string','max:191'],
            'email'    => ['required','email','max:191'],
            'password' => ['required','string','min:8','max:191'],
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
            // 1) Core миграции
            try { Artisan::call('session:table'); } catch (\Throwable $e) {}
            Artisan::call('migrate', ['--force' => true]);

            // 2) Миграции модулей
            foreach ($this->moduleMigrationPaths() as $path) {
                Artisan::call('migrate', [
                    '--force' => true,
                    '--path'  => $path,
                ]);
            }

            // 3) Проверка таблиц
            $missing = $this->verifyInstalledTables();
            if (!empty($missing)) {
                $output = trim(Artisan::output());
                return back()->withErrors([
                    'migrations' => 'Не найдены таблицы: ' . implode(', ', $missing),
                    'artisan'    => $output ?: 'Нет вывода Artisan',
                ])->withInput();
            }
        } catch (\Throwable $e) {
            $output = trim(Artisan::output());
            return back()->withErrors([
                'migrate' => 'Ошибка миграции: '.$e->getMessage(),
                'artisan' => $output ?: 'Нет вывода Artisan',
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ])->withInput();
        }

        try {
            // 4) Создание администратора
            $exists = DB::table('users')->where('email', $request->email)->exists();
            if (!$exists) {
                // Получаем настройки из сессии установки
                $countryCode = session('install_country_code', 'RU');
                $locale = session('install_locale', 'ru');
                
                // Базовые данные администратора
                $userData = [
                    'name'        => $request->name,
                    'email'       => $request->email,
                    'password'    => Hash::make($request->password),
                    'is_admin'    => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
                
                // Добавляем поля локализации, если они существуют в таблице
                if (Schema::hasColumn('users', 'country_code')) {
                    $userData['country_code'] = $countryCode;
                }
                if (Schema::hasColumn('users', 'locale')) {
                    $userData['locale'] = $locale;
                }
                
                DB::table('users')->insert($userData);
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['user' => 'Не удалось создать администратора: '.$e->getMessage()])->withInput();
        }

        // Сохранение выбора демо-данных
        session(['install_demo_data' => $request->boolean('demo_data', false)]);

        return redirect()->route('install.license');
    }

    /** 🔑 Ввод лицензионного ключа или промокода */
    public function license(Request $request)
    {
        if ($request->isMethod('get')) {
            return view('Install::license');
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

        // Должен быть указан либо лицензионный ключ, либо промокод
        if (empty($licenseKey) && empty($promoCode)) {
            return back()->withErrors(['license' => 'Укажите лицензионный ключ или промокод'])->withInput();
        }

        // Если указан промокод, проверяем его
        if (!empty($promoCode)) {
            $promoResult = $this->subscriptionService->applyPromoCode($promoCode, 'basic');
            if (!$promoResult['success']) {
                return back()->withErrors(['promo_code' => $promoResult['message']])->withInput();
            }
            session(['install_promo_code' => $promoCode]);
            session(['install_promo_id' => $promoResult['promo_id']]);
        }

        // Если указан лицензионный ключ, проверяем его формат
        if (!empty($licenseKey)) {
            // Проверка формата лицензионного ключа (XXXX-XXXX-XXXX-XXXX)
            if (!preg_match('/^[A-Z0-9]{8}-[A-Z0-9]{8}-[A-Z0-9]{8}-[A-Z0-9]{8}$/i', $licenseKey)) {
                return back()->withErrors(['license_key' => 'Неверный формат лицензионного ключа'])->withInput();
            }
            session(['install_license_key' => strtoupper($licenseKey)]);
        }

        // Сохранение лицензии в .env
        try {
            $envLicenseKey = $licenseKey ?: 'PENDING'; // Если промокод, лицензия будет создана позже
            $this->writeEnv([
                'LICENSE_KEY' => $envLicenseKey,
            ]);
        } catch (\Throwable $e) {
            return back()->withErrors(['env' => 'Ошибка записи лицензии в .env: '.$e->getMessage()])->withInput();
        }

        return redirect()->route('install.demo');
    }

    /** 📦 Установка демо-данных */
    public function demo(Request $request)
    {
        if ($request->isMethod('get')) {
            $installDemo = session('install_demo_data', false);
            return view('Install::demo', compact('installDemo'));
        }

        // POST - установка демо-данных
        if ($request->boolean('install_demo')) {
            try {
                $this->installDemoData();
            } catch (\Throwable $e) {
                return back()->withErrors(['demo' => 'Ошибка установки демо-данных: '.$e->getMessage()]);
            }
        }

        return redirect()->route('install.finish');
    }

    /** 🏁 Завершение */
    public function finish()
    {
        try {
            // Создание подписки на основе лицензии или промокода
            $this->createSubscriptionFromInstall();

            // Применение выбранной страны/языка из установки
            $this->applyLocalizationSettings();

            File::put(storage_path('install.lock'), 'Installed at ' . now()->toDateTimeString());
            
            // Очистка кеша
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
        } catch (\Throwable $e) {
            // не фатально, но логируем
            \Log::warning('Install finish error', ['error' => $e->getMessage()]);
        }
        
        return view('Install::finish');
    }

    /**
     * Применение настроек локализации после установки
     */
    private function applyLocalizationSettings(): void
    {
        try {
            $countryCode = session('install_country_code', 'RU');
            $presetCountries = config('localization.preset_countries', []);
            
            if (!isset($presetCountries[$countryCode])) {
                return; // Страна не найдена в предустановленных
            }

            $countryData = $presetCountries[$countryCode];

            // Проверяем, существует ли таблица countries (модуль Localization установлен)
            if (!Schema::hasTable('countries')) {
                return; // Модуль Localization не установлен
            }

            // Создаём или обновляем страну в базе данных
            $country = DB::table('countries')->where('code', $countryCode)->first();
            
            if (!$country) {
                // Создаём страну
                DB::table('countries')->insert([
                    'code' => $countryCode,
                    'name' => $countryData['name'] ?? $countryCode,
                    'native_name' => $countryData['native_name'] ?? $countryData['name'] ?? $countryCode,
                    'flag' => $countryData['flag'] ?? '🌍',
                    'currency_code' => $countryData['currency_code'] ?? 'USD',
                    'currency_symbol' => $countryData['currency_symbol'] ?? '$',
                    'locale' => $countryData['locale'] ?? 'ru',
                    'timezone' => $countryData['timezone'] ?? 'UTC',
                    'date_format' => $countryData['date_format'] ?? 'd.m.Y',
                    'time_format' => $countryData['time_format'] ?? 'H:i',
                    'decimal_separator' => $countryData['decimal_separator'] ?? '.',
                    'thousands_separator' => $countryData['thousands_separator'] ?? ',',
                    'decimal_places' => $countryData['decimal_places'] ?? 2,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                // Обновляем существующую страну, делаем её активной
                DB::table('countries')
                    ->where('code', $countryCode)
                    ->update([
                        'active' => true,
                        'locale' => $countryData['locale'] ?? $country->locale ?? 'ru',
                        'timezone' => $countryData['timezone'] ?? $country->timezone ?? 'UTC',
                        'updated_at' => now(),
                    ]);
            }

            // Если есть таблица localization_settings, создаём базовые настройки
            if (Schema::hasTable('localization_settings')) {
                $countryId = DB::table('countries')->where('code', $countryCode)->value('id');
                
                if ($countryId) {
                    // Базовые переводы из seeder
                    $translations = [
                        'RU' => ['welcome' => 'Добро пожаловать', 'home' => 'Главная'],
                        'KZ' => ['welcome' => 'Қош келдіңіз', 'home' => 'Басты'],
                        'US' => ['welcome' => 'Welcome', 'home' => 'Home'],
                        'GB' => ['welcome' => 'Welcome', 'home' => 'Home'],
                        'DE' => ['welcome' => 'Willkommen', 'home' => 'Startseite'],
                        'FR' => ['welcome' => 'Bienvenue', 'home' => 'Accueil'],
                        'IT' => ['welcome' => 'Benvenuto', 'home' => 'Casa'],
                    ];

                    $countryTranslations = $translations[$countryCode] ?? $translations['RU'];
                    
                    // Создаём базовые настройки, если их нет
                    $existing = DB::table('localization_settings')
                        ->where('country_id', $countryId)
                        ->where('key', 'welcome_message')
                        ->exists();
                    
                    if (!$existing) {
                        DB::table('localization_settings')->insert([
                            'country_id' => $countryId,
                            'key' => 'welcome_message',
                            'value' => $countryTranslations['welcome'] ?? 'Welcome',
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

            // Обновляем .env с финальными настройками
            $this->writeEnv([
                'LOCALIZATION_DEFAULT_COUNTRY' => $countryCode,
                'APP_LOCALE' => $countryData['locale'] ?? 'ru',
                'APP_TIMEZONE' => $countryData['timezone'] ?? 'UTC',
            ]);

        } catch (\Throwable $e) {
            \Log::warning('Failed to apply localization settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Создание подписки на основе лицензии или промокода из установки
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

            // Если есть лицензионный ключ, создаем подписку с ним
            if ($licenseKey) {
                // Проверяем, не существует ли уже подписка с таким ключом
                $existing = DB::table('subscriptions')
                    ->where('license_key', $licenseKey)
                    ->first();

                if ($existing) {
                    // Если ключ уже используется, просто обновляем .env
                    $this->writeEnv(['LICENSE_KEY' => $licenseKey]);
                    return;
                }

                // Создаем новую подписку с указанным ключом
                DB::table('subscriptions')->insert([
                    'user_id' => $userId,
                    'plan' => 'basic', // По умолчанию basic, можно изменить позже
                    'license_key' => $licenseKey,
                    'starts_at' => now(),
                    'expires_at' => now()->addYear(), // По умолчанию 1 год
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Обновляем .env с реальным ключом
                $this->writeEnv(['LICENSE_KEY' => $licenseKey]);
            } 
            // Если есть промокод, создаем подписку с применением промокода
            elseif ($promoCode && $promoId) {
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

                // Активируем промокод
                $this->subscriptionService->activatePromoCode($promoId, $userId);

                // Обновляем .env с сгенерированным ключом
                $this->writeEnv(['LICENSE_KEY' => $licenseKey]);
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to create subscription during install', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
    // ─────────────────────────────────────────────────────────────────────

    private function testConnection(
        string $connection,
        string $host,
        string $port,
        string $db,
        string $user,
        ?string $pass,
        ?string &$err = null
    ): bool {
        $tmp = [
            'driver'   => $connection,
            'host'     => $host,
            'port'     => $port,
            'database' => $db,
            'username' => $user,
            'password' => $pass,
            'charset'  => 'utf8mb4',
            'collation'=> 'utf8mb4_unicode_ci',
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
            try { DB::purge('__install__'); } catch (\Throwable $e) {}
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
                // Создаем минимальный .env файл если .env.example отсутствует
                File::put($envPath, "APP_NAME=\"RU CMS\"\nAPP_ENV=local\nAPP_KEY=\nAPP_DEBUG=true\n");
            }
        }

        $content = File::get($envPath);

        foreach ($pairs as $key => $value) {
            $value = (string) $value;
            // Для APP_KEY не используем кавычки, если он уже начинается с base64:
            if ($key === 'APP_KEY' && str_starts_with($value, 'base64:')) {
                $line = $key.'='.$value;
            } else {
                // Экранируем кавычки и другие специальные символы
                $escapedValue = str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value);
                $line = $key.'="'.$escapedValue.'"';
            }
            $pattern = "/^{$key}=.*$/m";
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $line, $content);
            } else {
                $content .= PHP_EOL.$line;
            }
        }

        @File::copy($envPath, $envPath.'.bak');
        $tmp = $envPath.'.tmp';
        File::put($tmp, $content);
        @rename($tmp, $envPath);
    }

    private function moduleMigrationPaths(): array
    {
        $paths = [];
        $base = base_path('modules');

        if (!is_dir($base)) return $paths;

        foreach (scandir($base) as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            $moduleBase = $base . DIRECTORY_SEPARATOR . $dir;
            if (!is_dir($moduleBase)) continue;

            foreach (['Migrations', 'Database' . DIRECTORY_SEPARATOR . 'Migrations'] as $sub) {
                $full = $moduleBase . DIRECTORY_SEPARATOR . $sub;
                if (is_dir($full)) {
                    $rel = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $full);
                    $paths[] = $rel;
                }
            }
        }

        return array_values(array_unique($paths));
    }

    private function verifyInstalledTables(): array
    {
        $required = [
            'migrations', 'users', 'sessions',
            'news', 'categories', 'menus', 'files',
            'modules', 'subscriptions', 'promo_codes', 'security_logs',
        ];

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

    /** 📦 Установка демо-данных */
    private function installDemoData(): void
    {
        $userId = DB::table('users')->where('is_admin', true)->value('id');
        
        if (!$userId) {
            return;
        }

        // Демо-категории
        $categoryIds = [];
        $categories = [
            ['name' => 'Новости', 'slug' => 'news', 'template' => 'default'],
            ['name' => 'Товары', 'slug' => 'products', 'template' => 'products'],
            ['name' => 'Услуги', 'slug' => 'services', 'template' => 'ourworks'],
        ];

        foreach ($categories as $cat) {
            $id = DB::table('categories')->insertGetId([
                'name' => $cat['name'],
                'slug' => $cat['slug'],
                'template' => $cat['template'],
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

            // Привязка к категории
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
}
