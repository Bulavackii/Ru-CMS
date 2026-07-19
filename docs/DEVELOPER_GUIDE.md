# 👨‍💻 Руководство разработчика RU CMS

**Версия:** 2.0  
**Дата:** 2025-01-27

---

## 📋 Содержание

1. [Архитектура системы](#архитектура-системы)
2. [Создание модуля](#создание-модуля)
3. [Создание плагина](#создание-плагина)
4. [Работа с API](#работа-с-api)
5. [Кастомизация](#кастомизация)
6. [Конфигурация](#конфигурация)
7. [Безопасность](#безопасность)
8. [Тестирование](#тестирование)

---

## 🏗️ Архитектура системы

### HMVC (Hierarchical Model-View-Controller)

RU CMS использует модульную архитектуру HMVC, где каждый модуль - это независимое приложение.

```
modules/
├── ModuleName/
│   ├── Controllers/
│   │   ├── Admin/          # Контроллеры для админки
│   │   └── Frontend/       # Контроллеры для фронтенда
│   ├── Models/              # Eloquent модели
│   ├── Views/               # Blade шаблоны
│   │   ├── admin/          # Шаблоны админки
│   │   └── frontend/       # Шаблоны фронтенда
│   ├── Routes/
│   │   └── web.php         # Маршруты модуля
│   ├── Migrations/         # Миграции БД
│   ├── Providers/
│   │   └── ModuleServiceProvider.php
│   └── module.json         # Метаданные модуля
```

### Структура модуля

Каждый модуль должен содержать:

1. **module.json** - метаданные модуля
2. **ServiceProvider** - регистрация модуля
3. **Routes** - маршруты модуля
4. **Controllers** - логика обработки запросов
5. **Models** - работа с данными
6. **Views** - представления
7. **Migrations** - структура БД

---

## 🧩 Создание модуля

### Быстрый способ: Использование команды make:module

```bash
# Автоматическая генерация структуры модуля
php artisan make:module MyModule
```

Команда автоматически создаст:
- ✅ Структуру директорий
- ✅ `module.json` с метаданными
- ✅ `ServiceProvider`
- ✅ Базовые контроллеры (Admin и Frontend)
- ✅ Примеры маршрутов
- ✅ Базовые views
- ✅ Пример миграции

### Ручной способ: Создание структуры

```bash
# Создать директорию модуля
mkdir -p modules/MyModule/{Controllers/{Admin,Frontend},Models,Views/{admin,frontend},Routes,Migrations,Providers}
```

### Шаг 2: Создание module.json

Создайте файл `modules/MyModule/module.json`:

```json
{
  "name": "MyModule",
  "title": "Мой модуль",
  "version": "1.0.0",
  "active": true,
  "priority": 50,
  "description": "Описание модуля",
  "providers": [
    "Modules\\MyModule\\Providers\\MyModuleServiceProvider"
  ]
}
```

**Параметры:**
- `name` - имя модуля (должно совпадать с директорией)
- `title` - отображаемое название
- `version` - версия модуля
- `active` - активен ли модуль
- `priority` - приоритет загрузки (меньше = загружается раньше)
- `description` - описание модуля
- `providers` - массив Service Providers

### Шаг 3: Создание ServiceProvider

Создайте файл `modules/MyModule/Providers/MyModuleServiceProvider.php`:

```php
<?php

namespace Modules\MyModule\Providers;

use Illuminate\Support\ServiceProvider;

class MyModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Регистрация сервисов, биндингов и т.д.
    }

    public function boot(): void
    {
        $modulePath = base_path('modules/MyModule');

        // Загрузка маршрутов
        if (file_exists($modulePath . '/Routes/web.php')) {
            $this->loadRoutesFrom($modulePath . '/Routes/web.php');
        }

        // Загрузка представлений
        if (is_dir($modulePath . '/Views')) {
            $this->loadViewsFrom($modulePath . '/Views', 'MyModule');
        }

        // Загрузка миграций
        if (is_dir($modulePath . '/Migrations')) {
            $this->loadMigrationsFrom($modulePath . '/Migrations');
        }

        // Загрузка переводов (если есть)
        if (is_dir($modulePath . '/Lang')) {
            $this->loadTranslationsFrom($modulePath . '/Lang', 'MyModule');
        }
    }
}
```

### Шаг 4: Регистрация модуля

Добавьте в `bootstrap/app.php`:

```php
$app->register(Modules\MyModule\Providers\MyModuleServiceProvider::class);
```

### Шаг 5: Создание контроллера

Создайте файл `modules/MyModule/Controllers/Admin/MyModuleController.php`:

```php
<?php

namespace Modules\MyModule\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MyModuleController extends Controller
{
    public function index()
    {
        return view('MyModule::admin.index');
    }
}
```

### Шаг 6: Создание маршрутов

Создайте файл `modules/MyModule/Routes/web.php`:

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\MyModule\Controllers\Admin\MyModuleController;

Route::prefix('admin/mymodule')
    ->middleware(['web', 'auth', 'admin'])
    ->name('admin.mymodule.')
    ->group(function () {
        Route::get('/', [MyModuleController::class, 'index'])->name('index');
    });
```

### Шаг 7: Создание миграции

Создайте файл `modules/MyModule/Migrations/2025_01_27_000000_create_mymodule_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mymodule_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mymodule_items');
    }
};
```

### Шаг 8: Создание модели

Создайте файл `modules/MyModule/Models/MyModuleItem.php`:

```php
<?php

namespace Modules\MyModule\Models;

use Illuminate\Database\Eloquent\Model;

class MyModuleItem extends Model
{
    protected $fillable = [
        'title',
        'content',
    ];
}
```

### Шаг 9: Создание представления

Создайте файл `modules/MyModule/Views/admin/index.blade.php`:

```blade
@extends('layouts.admin')

@section('title', 'Мой модуль')

@section('content')
<div class="space-y-6">
    <h1 class="text-3xl font-bold">Мой модуль</h1>
    <p>Содержимое модуля</p>
</div>
@endsection
```

---

## 🔌 Создание плагина

Плагины - это расширения, которые могут добавлять функциональность к существующим модулям.

### Структура плагина

```
plugins/
└── MyPlugin/
    ├── plugin.json
    ├── PluginServiceProvider.php
    └── ...
```

### Создание plugin.json

```json
{
  "name": "MyPlugin",
  "version": "1.0.0",
  "description": "Описание плагина",
  "target_module": "News",
  "providers": [
    "Plugins\\MyPlugin\\PluginServiceProvider"
  ]
}
```

### Регистрация плагина

Плагины регистрируются через систему модулей или вручную в `AppServiceProvider`.

---

## 🔌 Работа с API

### Swagger документация

API документация доступна по адресу:
- `/api/docs` — интерактивная документация Swagger
- `/api-docs.json` — JSON схема OpenAPI

### Аутентификация

API использует JWT токены для аутентификации.

#### Получение токена

```bash
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

Ответ:
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "name": "User",
    "email": "user@example.com"
  }
}
```

#### Использование токена

```bash
GET /api/v1/news
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

### Endpoints API

#### Новости

- `GET /api/v1/news` - список новостей
- `GET /api/v1/news/{id}` - одна новость
- `POST /api/v1/news` - создать новость
- `PUT /api/v1/news/{id}` - обновить новость
- `DELETE /api/v1/news/{id}` - удалить новость

#### Пользователи

- `GET /api/v1/users` - список пользователей
- `GET /api/v1/users/{id}` - один пользователь

### Создание API endpoint в модуле

```php
<?php

namespace Modules\MyModule\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MyModuleApiController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => [],
        ]);
    }
}
```

Добавьте маршрут в `modules/MyModule/Routes/api.php`:

```php
Route::prefix('api/v1/mymodule')
    ->middleware(['api', 'auth:api'])
    ->group(function () {
        Route::get('/', [MyModuleApiController::class, 'index']);
    });
```

---

## 🎨 Кастомизация

### Создание темы

1. Создайте директорию: `resources/themes/MyTheme/`
2. Скопируйте структуру из `resources/views/frontend/`
3. Настройте тему в админ-панели

### Кастомизация админ-панели

Шаблоны админ-панели находятся в `resources/views/layouts/admin.blade.php`

### Темная тема

Админ-панель поддерживает темную тему:
- Переключатель темы в header
- Автоопределение системной темы
- Сохранение выбора в localStorage
- Используйте классы `dark:` для темной темы

### Добавление своих стилей

```bash
# Добавить в resources/css/app.css
# Или создать отдельный файл и подключить в vite.config.js
```

### Web Push уведомления

Добавьте компонент подписки в шаблон:
```blade
<x-webpush-subscribe :userId="auth()->id()" />
```

Компонент автоматически:
- Проверяет поддержку браузера
- Запрашивает разрешение
- Подписывает/отписывает пользователя

---

## ⚙️ Конфигурация

### Создание конфига модуля

Создайте файл `modules/MyModule/Config/mymodule.php`:

```php
<?php

return [
    'setting1' => env('MYMODULE_SETTING1', 'default'),
    'setting2' => env('MYMODULE_SETTING2', 'default'),
];
```

Использование:

```php
config('mymodule.setting1')
```

### Переменные окружения

Все конфиденциальные данные должны быть в `.env`:

```env
MYMODULE_API_KEY=ваш-ключ
MYMODULE_SECRET=ваш-секрет
```

---

## 🔒 Безопасность

### Валидация данных

```php
$request->validate([
    'title' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
]);
```

### Защита от CSRF

Все формы автоматически защищены CSRF токеном через `@csrf` в Blade.

### Защита маршрутов

```php
Route::middleware(['auth', 'admin'])->group(function () {
    // Защищенные маршруты
});
```

### Проверка прав доступа

```php
// Проверка роли
if (auth()->user()->hasRole('admin')) {
    // Доступ разрешен
}

// Проверка разрешения
if (auth()->user()->hasPermission('edit.news')) {
    // Доступ разрешен
}
```

---

## 🧪 Тестирование

### Создание теста

```php
<?php

namespace Tests\Feature\MyModule;

use Tests\TestCase;

class MyModuleTest extends TestCase
{
    public function test_module_works()
    {
        $response = $this->get('/admin/mymodule');
        $response->assertStatus(200);
    }
}
```

### Запуск тестов

```bash
php artisan test
php artisan test --filter MyModuleTest
```

## 💳 Работа с платежными системами

### Создание платежного гейтвея

1. Создайте класс гейтвея в `modules/Payments/Gateways/`:

```php
<?php

namespace Modules\Payments\Gateways;

use Modules\Payments\Gateways\AbstractPaymentGateway;
use Modules\Payments\Models\Order;

class MyGateway extends AbstractPaymentGateway
{
    protected function getGatewayCode(): string
    {
        return 'mygateway';
    }

    public function createPayment(Order $order, array $options = []): array
    {
        // Реализация создания платежа
    }

    public function handleWebhook(array $data): bool
    {
        // Обработка webhook
    }

    // ... другие методы
}
```

2. Зарегистрируйте гейтвей в `PaymentGatewayService`:

```php
return match($code) {
    'yookassa' => new YooKassaGateway($paymentMethod),
    'sbp' => new SBPGateway($paymentMethod),
    'mygateway' => new MyGateway($paymentMethod), // Добавить здесь
    default => null,
};
```

## 📱 Web Push уведомления

### Отправка уведомления

```php
use App\Services\WebPushService;

$webPush = app(WebPushService::class);

$payload = [
    'title' => 'Заголовок',
    'body' => 'Текст уведомления',
    'icon' => '/favicon.svg',
    'data' => ['url' => '/some-page'],
];

// Отправить всем подпискам
$webPush->broadcast($payload);

// Отправить конкретному пользователю
$webPush->broadcast($payload, $userId);
```

## 📊 Аналитика

### Использование AnalyticsService

```php
use App\Services\AnalyticsService;

$analytics = app(AnalyticsService::class);

// Отслеживание просмотра
$analytics->trackView($news, auth()->id());

// Получение статистики
$stats = $analytics->getPeriodStats($start, $end);

// Популярный контент
$popular = $analytics->getPopularContent('Modules\News\Models\News', 10);
```

## 🔍 Мониторинг

### Использование MonitoringService

```php
use App\Services\MonitoringService;

$monitoring = app(MonitoringService::class);

// Отправить ошибку
$monitoring->reportError($exception, ['context' => 'Дополнительная информация']);

// Отправить в Telegram
$monitoring->sendTelegramNotification('Сообщение');
```

---

## 📚 Полезные ресурсы

- **Laravel документация:** https://laravel.com/docs
- **Blade документация:** https://laravel.com/docs/blade
- **Eloquent документация:** https://laravel.com/docs/eloquent

---

## 🛠️ Полезные команды

### Генерация модуля
```bash
php artisan make:module ModuleName
```

### Генерация VAPID ключей для Web Push
```bash
php artisan webpush:generate-keys
```

### Генерация лицензий
```bash
php artisan license:generate license --plan=pro --months=12
```

### Оптимизация производительности
```bash
php artisan cms:optimize
```

### Генерация API документации
```bash
php artisan api:docs:generate
```

---

**Версия руководства:** 2.0  
**Дата обновления:** 2025-01-27

