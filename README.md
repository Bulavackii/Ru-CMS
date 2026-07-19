# 💎 RU CMS — Модульная CMS для России и СНГ

> Современная модульная CMS на **Laravel 12** с архитектурой **HMVC**, встроенной безопасностью, системой подписок и централизованными обновлениями.

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/Laravel-12%2B-ff2d20?logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/TailwindCSS-UI-38bdf8?logo=tailwindcss&logoColor=white" alt="Tailwind">
  <img src="https://img.shields.io/badge/License-MIT-10b981" alt="License">
</p>

---

## ✨ Что это?

**RU CMS** — модульная система управления сайтом, где каждая функция — самостоятельный модуль (архитектура **HMVC**).  
Вы свободно подключаете/отключаете модули, создаёте свои и быстро собираете продукт под конкретные задачи.

**Подходит для:**
- 📰 Блогов и новостных порталов
- 💼 Сайтов компаний и организаций
- 📄 Лендингов и контент-страниц
- 🛒 Витрин товаров и простых магазинов
- 🛡 Клан-порталов и комьюнити-хабов

---

## 🚀 Ключевые возможности

### 🔒 Безопасность
- **2FA аутентификация** через Google Authenticator
- **Rate limiting** — защита от брутфорса
- **Автоматическая блокировка** подозрительных IP
- **Защита от SQL injection и XSS**
- **Content Security Policy (CSP)** — защита от XSS атак
- **Валидация сложности паролей**
- **Логирование** всех событий безопасности
- **API Rate Limiting** — защита API от перегрузки
- **Мониторинг ошибок** — централизованное отслеживание (Telegram уведомления)

### 💾 Автоматические бэкапы
- Ежедневное резервное копирование БД
- Еженедельное резервное копирование файлов
- Автоматическое сжатие и очистка старых бэкапов
- Загрузка в облако (S3, Yandex Object Storage)
- Управление через админ-панель

### ⚡ Производительность
- **Оптимизация БД** — составные индексы для частых запросов (ускорение в 2-5 раз)
- **Расширенное кэширование** — tagged cache, автоматическая инвалидация
- **Оптимизация запросов** — устранение N+1 проблем, eager loading
- **Lazy loading изображений** — ускорение загрузки страниц на 20-40%
- **Оптимизация изображений** — автоматические thumbnails, WebP, сжатие
- **Gzip сжатие** — уменьшение размера HTTP ответов

### 🔄 Централизованные обновления
- Автоматическая проверка обновлений через API
- Безопасная установка с проверкой целостности
- Автоматическое создание бэкапов
- Откат при ошибках

### 💳 Система подписок
- Гибкие тарифы (Basic, Pro, Enterprise)
- Промокоды со скидками
- Лицензионные ключи
- Ограничения по тарифам

### 💳 Платежные системы (РФ/СНГ)
- **ЮKassa (Яндекс.Касса)** — полная интеграция
- **СБП (Система быстрых платежей)** — QR-коды для оплаты
- **Тинькофф** — поддержка webhook
- **Сбербанк** — базовая поддержка
- Универсальная архитектура гейтвеев
- Обработка возвратов

### 🌍 Мультиязычность
- Поддержка русского и английского языков
- Адаптация для СНГ (Россия, Беларусь, Казахстан, Украина)
- Автоматическое определение языка
- Форматирование валют, дат, чисел

### 📦 Модульная архитектура
- Независимые модули (HMVC)
- Легкое подключение/отключение
- Создание собственных модулей
- **Команда `make:module`** — автоматическая генерация структуры модуля

### 🎨 UX/UI улучшения
- **Темная тема (Dark Mode)** — переключение темы, автоопределение системной темы
- **Дашборд аналитики** — графики посещаемости, популярный контент, интеграция с Яндекс.Метрикой
- **Web Push уведомления** — push-уведомления в браузере
- **Расширенная аналитика** — статистика по контенту, география посетителей

### 🐳 Docker поддержка
- Готовая конфигурация Docker
- Docker Compose для полной инфраструктуры
- Быстрое развертывание

### 🔄 CI/CD
- GitHub Actions workflow
- Автоматическое тестирование
- Code quality checks

---

## 🧩 Готовые модули

| Модуль | Описание |
|--------|----------|
| 📋 **Меню** | GUI-редактор меню до 3 уровней вложенности |
| 📰 **Новости** | CRUD, фильтры, поиск, SEO-мета, категории |
| 📰 **Импорт/Экспорт** | Массовый импорт или экспорт новостей |
| 🏷 **Категории** | Категоризация контента и товаров |
| 📄 **Страницы** | Контентные страницы с шаблонами |
| 🖼 **Слайдшоу** | Позиции (header/footer), оформление |
| 📁 **Файлы** | Загрузка/скачивание, категории |
| 🔍 **Поиск** | Глобальный поиск в админке |
| 🔔 **Уведомления** | Цвет, иконка, тип, позиция, Web Push |
| 💬 **Комментарии** | Модерация, вложенные комментарии, интеграция с Captcha |
| 👤 **Пользователи** | Роли, пароли, поиск |
| 💳 **Оплата** | Методы оплаты, интеграция с ЮKassa, СБП, Тинькофф |
| 📦 **Заказы** | История заказов, состав корзины |
| 🚚 **Доставка** | Способы доставки |
| 🎨 **Темы/Фрагменты** | Визуальный редактор |
| 🔐 **Captcha** | Изображения, слайдер, математика, вопросы |

---

## 📖 Установка

### Требования

- **PHP 8.5** (обязательно)
- **Laravel 12+**
- Composer 2.0+
- Node.js 18+ и npm
- MySQL 8.0+ или MariaDB 10.6+
- Nginx
- Расширения PHP: PDO, OpenSSL, Mbstring, Tokenizer, XML, Ctype, JSON, Fileinfo, Zip, GD или Imagick
- Redis (рекомендуется для production)

### Быстрая установка

Подробная инструкция по установке находится в файле [`docs/INSTALLATION.md`](docs/INSTALLATION.md).

Для автоматической установки на Ubuntu 24.04.3 LTS используйте скрипт:

```bash
sudo ./install-server.sh
```

Скрипт автоматически установит все необходимое программное обеспечение и настроит сервер.

### Быстрая установка

1. **Клонируйте репозиторий:**
```bash
git clone <repository-url> cms
cd cms
```

2. **Установите зависимости:**
```bash
composer install
npm install
```

3. **Запустите инсталлятор:**
Откройте в браузере: `http://your-domain.com/install`

Инсталлятор проведет вас через шаги:
- ✅ Приветствие (выбор языка RU/EN)
- ✅ Проверка требований
- ✅ Презентация возможностей
- ✅ Настройка базы данных
- ✅ Создание администратора
- ✅ Установка демо-данных (опционально)
- ✅ Завершение

4. **После установки:**

```bash
# Установите зависимость для 2FA
composer require pragmarx/google2fa

# Запустите миграции (если не были запущены автоматически)
php artisan migrate

# Очистите кеш
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

5. **Настройте обновления и бэкапы в `.env`:**
```env
UPDATE_SERVER_URL=https://updates.rucms.ru/api
LICENSE_KEY=your-license-key-here
APP_LOCALE=ru
APP_TIMEZONE=Europe/Moscow

# Redis (рекомендуется)
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Бэкапы
BACKUP_DATABASE_SCHEDULE=daily
BACKUP_FILES_SCHEDULE=weekly
BACKUP_RETENTION_DAYS=30
BACKUP_CLOUD_DRIVER=s3

# Web Push уведомления
VAPID_PUBLIC_KEY=your-vapid-public-key
VAPID_PRIVATE_KEY=your-vapid-private-key
VAPID_SUBJECT=https://your-domain.com

# Платежные системы
# ЮKassa
YOOKASSA_SHOP_ID=your-shop-id
YOOKASSA_SECRET_KEY=your-secret-key

# Мониторинг (Telegram)
MONITORING_TELEGRAM_BOT_TOKEN=your-bot-token
MONITORING_TELEGRAM_CHAT_ID=your-chat-id
```

6. **Установите дополнительные зависимости:**
```bash
composer require intervention/image
```

7. **Сгенерируйте VAPID ключи для Web Push:**
```bash
php artisan webpush:generate-keys
# Или используйте библиотеку minishlink/web-push
composer require minishlink/web-push
vendor/bin/generate-vapid-keys
```

8. **Запустите миграции:**
```bash
php artisan migrate
```

---

## 🛠️ Использование

### Бэкапы

```bash
# Ручной запуск
php artisan backup:run all
php artisan backup:run database
php artisan backup:run files

# Через админ-панель
/admin/backups
```

### Безопасность

```php
use App\Services\SecurityService;

$security = app('security');

// Генерация 2FA секрета
$secret = $security->generate2FASecret();

// Проверка 2FA кода
if ($security->verify2FACode($secret, $code)) {
    // Код верный
}
```

### Кэширование

```php
use App\Services\CacheService;

$cache = app('cacheService');

// Кэширование с тегами
$menu = $cache->rememberMenu('header', function() {
    return Menu::where('position', 'header')->get();
}, 3600);

// Инвалидация
$cache->invalidateMenu('header');
```

### Оптимизация изображений

```php
use App\Services\ImageOptimizationService;

$optimizer = app('imageOptimizer');
$result = $optimizer->optimize('path/to/image.jpg', [
    'max_width' => 1920,
    'max_height' => 1080,
    'quality' => 85,
    'thumbnail' => true,
    'webp' => true,
]);
```

### Обновления

```php
use App\Services\UpdateService;

$updates = app('updates');
$info = $updates->checkForUpdates();

if ($info['available']) {
    // Есть обновление
}
```

### Подписки

```php
use App\Services\SubscriptionService;

$subscription = app('subscription');

// Проверка подписки
if ($subscription->hasActiveSubscription()) {
    // Подписка активна
}

// Применение промокода
$result = $subscription->applyPromoCode('PROMO2024', 'pro');
```

### Локализация

```php
// Переводы
echo __t('welcome.message');

// Форматирование валюты
echo format_currency(1000); // "1 000,00 ₽"

// Форматирование даты
echo format_date(now()); // "27.01.2025"
```

### Платежные системы

```php
use Modules\Payments\Services\PaymentGatewayService;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\PaymentMethod;

$gatewayService = app(PaymentGatewayService::class);
$paymentMethod = PaymentMethod::where('code', 'yookassa')->first();

// Создание платежа
$result = $gatewayService->createPayment($order, $paymentMethod);

if ($result['success']) {
    // Редирект на страницу оплаты
    return redirect($result['confirmation_url']);
}
```

### Web Push уведомления

```php
use App\Services\NotificationService;

$notificationService = app(NotificationService::class);

// Отправить уведомление с Web Push
$notificationService->create([
    'user_id' => auth()->id(),
    'type' => 'info',
    'title' => 'Новое уведомление',
    'message' => 'У вас новое сообщение',
    'send_web_push' => true, // Отправить Web Push
]);
```

### Мониторинг

```php
use App\Services\MonitoringService;

$monitoring = app(MonitoringService::class);

// Отправить ошибку в Telegram
$monitoring->reportError(new \Exception('Ошибка'), [
    'context' => 'Дополнительная информация',
]);
```

### Docker

```bash
# Запуск всей инфраструктуры
docker-compose up -d

# Остановка
docker-compose down

# Просмотр логов
docker-compose logs -f app
```

---

## 🎨 Создание шаблонов

1. Создайте файл: `resources/views/frontend/templates/your-template.blade.php`
2. Добавьте в массив `$templateKeys` в `routes/web.php`
3. Добавьте в `$customLabels` в `modules/News/Controllers/Admin/NewsController.php`
4. Выберите шаблон при создании новости

Пример:
```blade
{{-- resources/views/frontend/templates/release.blade.php --}}
<div class="max-w-screen-xl mx-auto px-4 my-12">
  <h2 class="text-3xl font-extrabold text-center mb-10">🚀 Релизы</h2>
  {{-- Вывод записей --}}
</div>
```

---

## 🔧 Настройка

### Безопасность

В `app/Services/SecurityService.php`:
- `$maxLoginAttempts` — максимальное количество попыток (по умолчанию: 5)
- `$lockoutDuration` — длительность блокировки в секундах (по умолчанию: 900)

### Локализация

В `config/localization.php` настраиваются:
- Поддерживаемые страны СНГ
- Форматы дат и чисел
- Валюты

### Тарифы

Система поддерживает 3 тарифа:
- **Basic** (990₽/мес) — до 10 модулей, базовая поддержка
- **Pro** (2990₽/мес) — неограниченные модули, приоритетная поддержка
- **Enterprise** (9990₽/мес) — все возможности + персональный менеджер

---

## 📁 Структура проекта

```
cms/
├── app/                    # Ядро приложения
│   ├── Http/              # Контроллеры, Middleware, Requests
│   ├── Services/          # Сервисы (Security, Update, Subscription, Monitoring, WebPush)
│   ├── Models/            # Eloquent модели
│   └── Providers/         # Service Providers
├── modules/               # Модули HMVC
│   ├── News/              # Модуль новостей
│   ├── Menu/              # Модуль меню
│   ├── Comments/          # Модуль комментариев
│   ├── Payments/          # Модуль платежей
│   │   └── Gateways/      # Платежные гейтвеи (ЮKassa, СБП)
│   ├── Localization/      # Модуль локализации
│   └── ...                # Другие модули
├── database/              # Миграции и сидеры
├── resources/             # Views, CSS, JS
│   └── js/                # JavaScript (webpush.js)
├── routes/                # Маршруты
├── config/                # Конфигурация
│   ├── webpush.php        # Настройки Web Push
│   └── monitoring.php     # Настройки мониторинга
└── public/                # Публичные файлы
    └── sw.js              # Service Worker для Web Push
```

---

## 🐛 Troubleshooting

### Ошибка при установке

1. Проверьте права на папки:
```bash
chmod -R 775 storage bootstrap/cache
```

2. Проверьте версию PHP:
```bash
php -v  # Должна быть 8.5
```

3. Проверьте расширения PHP:
```bash
php -m | grep -E "pdo|openssl|mbstring|zip"
```

### Ошибка с обновлениями

1. Проверьте настройки в `.env`:
```env
UPDATE_SERVER_URL=https://updates.rucms.ru/api
LICENSE_KEY=your-license-key
```

2. Проверьте доступность сервера обновлений

### Проблемы с локализацией

1. Очистите кеш:
```bash
php artisan cache:clear
php artisan config:clear
```

---

## 📝 Лицензия

MIT License — свободное использование и модификация.

---

## 📞 Поддержка

- **GitHub:** [Ваш репозиторий]
- **Email:** support@rucms.ru
- **Документация:** См. комментарии в коде

---

## 🆕 Что нового в версии 2.0

- ✅ **Интеграция российских платежных систем** (ЮKassa, СБП)
- ✅ **Web Push уведомления** — push-уведомления в браузере
- ✅ **Темная тема** — переключение темы, автоопределение
- ✅ **Оптимизация производительности** — индексы БД, кэширование, lazy loading
- ✅ **Модуль комментариев** — с модерацией и интеграцией Captcha
- ✅ **Мониторинг ошибок** — централизованное отслеживание с Telegram уведомлениями
- ✅ **Дашборд аналитики** — графики, статистика, интеграция с Яндекс.Метрикой
- ✅ **Content Security Policy** — защита от XSS атак
- ✅ **Команда make:module** — автоматическая генерация модулей

---

**Версия:** 2.0.0  
**Дата:** 2025-01-28  
**Laravel:** 12.x  
**PHP:** 8.5

---

## 📚 Документация

Полная документация находится в папке [`docs/`](docs/):

- **[Инструкция по установке](docs/INSTALLATION.md)** — пошаговая инструкция для Ubuntu 24.04.3 LTS
- **[Руководство разработчика](docs/DEVELOPER_GUIDE.md)** — подробная документация для разработчиков
- **[Описание модулей](docs/modules/)** — документация по каждому модулю
