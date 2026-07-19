<div align="center">

# 💎 RU CMS

### Модульная CMS для России и СНГ на Laravel 12

<sub>HMVC-архитектура · Локальные ассеты, без CDN · PostgreSQL · Встроенная безопасность</sub>

<br>

<img src="https://img.shields.io/badge/PHP-8.5-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.5">
<img src="https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 12">
<img src="https://img.shields.io/badge/PostgreSQL-only-336791?style=for-the-badge&logo=postgresql&logoColor=white" alt="PostgreSQL">
<img src="https://img.shields.io/badge/License-MIT-10b981?style=for-the-badge" alt="MIT License">

<br><br>

[Возможности](#-возможности) · [Модули](#-модули) · [Установка](#-установка) · [Команды](#-командная-строка) · [Структура](#-структура-проекта) · [FAQ](#-частые-проблемы)

</div>

<br>

> [!NOTE]
> **Философия проекта:** всё работает локально и офлайн-дружелюбно. Шрифты, иконки, JS-библиотеки, Swagger UI — всё вендорено в `public/assets/`, ни одного обращения к внешним CDN на публичных страницах[^1]. Единственная поддерживаемая СУБД — **PostgreSQL**[^2].

<br>

## 🧭 Что это

**RU CMS** — модульная система управления сайтом на архитектуре **HMVC**: каждая функция (новости, платежи, меню, SEO...) — независимый модуль в `modules/`. Модули можно свободно включать, отключать и создавать свои — командой [`make:module`](#-командная-строка).

<table>
<tr>
<td width="25%" align="center">📰<br><b>Блоги и новости</b></td>
<td width="25%" align="center">💼<br><b>Сайты компаний</b></td>
<td width="25%" align="center">🛒<br><b>Витрины и магазины</b></td>
<td width="25%" align="center">🛡<br><b>Комьюнити-порталы</b></td>
</tr>
</table>

<br>

## ✨ Возможности

<details open>
<summary><b>🔒 Безопасность</b></summary>
<br>

| | |
|---|---|
| 🔐 | 2FA-аутентификация (Google Authenticator) |
| 🚦 | Rate limiting и защита от брутфорса |
| 🚫 | Автоблокировка подозрительных IP |
| 🧪 | Защита от SQL-инъекций и XSS |
| 🛡️ | Content Security Policy (CSP), HSTS и другие security-заголовки |
| 🔑 | Валидация сложности паролей |
| 📋 | Аудит-лог событий безопасности |
| ⏱️ | Rate limiting для API |
| 📡 | Мониторинг ошибок с уведомлениями в Telegram |

</details>

<details>
<summary><b>⚡ Производительность</b></summary>
<br>

- Составные индексы БД под частые запросы
- Тегированное кэширование с автоинвалидацией
- Устранение N+1 через eager loading
- Lazy loading изображений, автоматические thumbnails и WebP
- Gzip-сжатие ответов

</details>

<details>
<summary><b>💾 Бэкапы и обновления</b></summary>
<br>

- Ежедневный бэкап БД, еженедельный — файлов (`php artisan backup:run`)
- Автосжатие и очистка старых копий, выгрузка в облако (S3 / Yandex Object Storage)
- Централизованная проверка обновлений с откатом при ошибке

</details>

<details>
<summary><b>💳 Платежи и подписки (РФ/СНГ)</b></summary>
<br>

- ЮKassa, СБП (QR-коды), Тинькофф, Сбербанк — единый интерфейс гейтвеев
- Обработка возвратов
- Тарифы Basic / Pro / Enterprise, промокоды, лицензионные ключи

</details>

<details>
<summary><b>🌍 Локализация</b></summary>
<br>

- Русский и английский интерфейс
- Пресеты для России, Беларуси, Казахстана, Украины
- Автоопределение языка, форматирование валют/дат/чисел под страну

</details>

<details>
<summary><b>🎨 Интерфейс</b></summary>
<br>

- Тёмная/светлая тема с автоопределением системной
- Дашборд аналитики (посещаемость, популярный контент, интеграция с Яндекс.Метрикой)
- Web Push-уведомления в браузере
- Локально захостенные шрифты (латиница + кириллица) и иконки — ничего не подгружается с CDN[^1]

</details>

<br>

## 🧩 Модули

<div align="center">

| | Модуль | Что делает |
|:---:|---|---|
| 📰 | **News** | CRUD новостей, фильтры, поиск, SEO-мета, категории, шаблоны |
| 📢 | **NewsIO** | Массовый импорт/экспорт новостей |
| 🗂️ | **Categories** | Категоризация контента и товаров |
| 📊 | **Menu** | GUI-редактор меню до 3 уровней вложенности |
| 🖼️ | **Slideshow** | Слайдшоу с позициями (header/footer) и оформлением |
| 📁 | **Files** | Загрузка, скачивание, категории файлов |
| 🔎 | **Search** | Глобальный поиск по всем модулям |
| 💬 | **Notifications** | Цвет, иконка, тип, позиция, Web Push |
| 💬 | **Comments** | Модерация, вложенные комментарии, интеграция с Captcha |
| 📝 | **Reviews** | Отзывы и оценки с модерацией |
| 📑 | **Messages** | Обращения и сообщения от пользователей |
| 👨‍💻 | **Users** | Роли, права доступа, управление пользователями |
| 💰 | **Payments** | Методы оплаты: ЮKassa, СБП, Тинькофф |
| 🚚 | **Delivery** | Способы доставки (российские службы) |
| 🎨 | **Visual** | Визуальный редактор тем и фрагментов |
| 🛡️ | **Captcha** | Картинка, слайдер, математика, вопросы |
| 🔍 | **Seo** | SEO-модуль (Yandex-first) для РФ/СНГ |
| 🌍 | **Localization** | Переводы, форматы даты/времени/валюты |
| ♿ | **Accessibility** | Настройки доступности |
| 💻 | **System** | Управление модулями и их состоянием |

</div>

<br>

## 📦 Установка

> [!TIP]
> Всё делается через графический мастер установки на `/install` — заполнять `.env` руками не нужно (кроме шага с клонированием и зависимостями).

### 1 · Требования

<table>
<tr><td><b>PHP</b></td><td><code>8.5</code>[^3]</td></tr>
<tr><td><b>СУБД</b></td><td>PostgreSQL[^2]</td></tr>
<tr><td><b>Composer</b></td><td><code>2.0+</code></td></tr>
<tr><td><b>Node.js</b></td><td><code>18+</code> и npm</td></tr>
<tr><td><b>Веб-сервер</b></td><td>Nginx (рекомендуется)</td></tr>
<tr><td><b>PHP-расширения</b></td><td><code>pdo_pgsql</code>, openssl, mbstring, tokenizer, xml, ctype, json, fileinfo, zip, gd/imagick</td></tr>
<tr><td><b>Redis</b></td><td>рекомендуется для production (кэш/очередь/сессии)</td></tr>
</table>

### 2 · Клонирование и зависимости

```bash
git clone https://github.com/Bulavackii/Ru-CMS.git cms
cd cms

composer install
npm install && npm run build
```

### 3 · Мастер установки

Откройте `http://your-domain.com/install` в браузере — дальше всё делается кликами:

```
🌍 Приветствие (страна/язык)  →  ✅ Проверка требований  →  💾 База данных
        →  👤 Администратор  →  🔑 Лицензия/промокод  →  📦 Демо-данные  →  🏁 Готово
```

На шаге «База данных» укажите хост/порт/имя базы/пользователя PostgreSQL — мастер сам протестирует соединение, сгенерирует `APP_KEY`, запишет `.env` и накатит все миграции[^4].

<details>
<summary><b>Что делать, если что-то пошло не так</b></summary>
<br>

```bash
# Права на запись
chmod -R 775 storage bootstrap/cache

# Проверить версию PHP и расширения
php -v
php -m | grep -E "pdo_pgsql|openssl|mbstring|zip"

# Сбросить кэш конфигурации
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

</details>

### 4 · После установки (production)

Донастройте в `.env`:

```env
# Redis — рекомендуется вместо file/sync
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Бэкапы
BACKUP_DATABASE_SCHEDULE=daily
BACKUP_FILES_SCHEDULE=weekly
BACKUP_RETENTION_DAYS=30
BACKUP_CLOUD_DRIVER=s3

# Web Push (см. `php artisan webpush:generate-keys`)
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
VAPID_SUBJECT=https://your-domain.com

# Платёжные системы
YOOKASSA_SHOP_ID=
YOOKASSA_SECRET_KEY=

# Мониторинг ошибок (Telegram)
MONITORING_TELEGRAM_BOT_TOKEN=
MONITORING_TELEGRAM_CHAT_ID=
```

<br>

## ⌨️ Командная строка

<div align="center">

| Команда | Что делает |
|---|---|
| `php artisan make:module {Name}` | Сгенерировать структуру нового модуля |
| `php artisan modules:sync` | Синхронизировать метаданные модулей с БД |
| `php artisan backup:run {all\|database\|files}` | Ручной запуск бэкапа |
| `php artisan webpush:generate-keys` | Сгенерировать VAPID-ключи для Web Push |
| `php artisan sitemap:generate` | Пересобрать `sitemap.xml` |
| `php artisan robots:generate` | Пересобрать `robots.txt` |
| `php artisan api:docs:generate` | Пересобрать Swagger-документацию API |
| `php artisan license:generate` | Сгенерировать лицензионный ключ |
| `php artisan cms:optimize` | Прогнать оптимизации (кэш конфигов/роутов/вью) |

</div>

<details>
<summary><b>🧑‍💻 Примеры использования сервисов из кода</b></summary>
<br>

```php
// 🔐 Безопасность — 2FA
$secret = app('security')->generate2FASecret();
app('security')->verify2FACode($secret, $code);

// ⚡ Кэш с тегами
app('cacheService')->rememberMenu('header', fn () => Menu::where('position', 'header')->get(), 3600);

// 🖼️ Оптимизация изображений
app('imageOptimizer')->optimize('path/to/image.jpg', [
    'max_width' => 1920, 'quality' => 85, 'thumbnail' => true, 'webp' => true,
]);

// 💳 Платежи
$gateway = app(\Modules\Payments\Services\PaymentGatewayService::class);
$result = $gateway->createPayment($order, $paymentMethod);

// 🌍 Локализация
echo __t('welcome.message');
echo format_currency(1000); // «1 000,00 ₽»
```

</details>

<details>
<summary><b>🎨 Создание собственного шаблона новости</b></summary>
<br>

1. Создайте `resources/views/frontend/templates/your-template.blade.php`
2. Добавьте ключ в `$templateKeys` в `app/Http/Controllers/Frontend/HomeController.php`
3. Добавьте подпись в `$customLabels` в `modules/News/Controllers/Admin/NewsController.php`
4. Выберите шаблон при создании новости в админке

```blade
{{-- resources/views/frontend/templates/release.blade.php --}}
<div class="max-w-screen-xl mx-auto px-4 my-12">
    <h2 class="text-3xl font-extrabold text-center mb-10">🚀 Релизы</h2>
    {{-- вывод записей --}}
</div>
```

</details>

<br>

## 🗂 Структура проекта

```text
cms/
├── app/                     Ядро приложения
│   ├── Http/                Контроллеры, Middleware, Requests
│   ├── Services/            Security, Update, Subscription, Monitoring, WebPush…
│   ├── Models/               Eloquent-модели
│   └── Providers/           Service Providers
├── modules/                 Модули HMVC (Views/Routes/Controllers/Providers)
│   ├── News/  Payments/  Menu/  Seo/  Localization/  …
├── database/
│   └── migrations/          Единое место ВСЕХ миграций — и ядра, и модулей[^4]
├── resources/                Blade-шаблоны, CSS, JS
├── public/assets/            Локальные шрифты, иконки, JS-библиотеки (без CDN)[^1]
├── routes/                   web.php / api.php / console.php
├── config/                   Конфигурация приложения
└── docs/                     Подробная документация (см. ниже)
```

<br>

## 🩹 Частые проблемы

<details>
<summary><b>Инсталлятор не открывается / 500 при установке</b></summary>
<br>

```bash
chmod -R 775 storage bootstrap/cache
php -v   # должна быть 8.5
php -m | grep pdo_pgsql
```

</details>

<details>
<summary><b>Ошибка подключения к PostgreSQL на шаге «База данных»</b></summary>
<br>

Проверьте, что база создана заранее и пользователь имеет права `SELECT/INSERT/UPDATE/DELETE/CREATE/ALTER`. Хост/порт по умолчанию — `127.0.0.1:5432`.

</details>

<details>
<summary><b>Не применяются переводы/локаль</b></summary>
<br>

```bash
php artisan cache:clear
php artisan config:clear
```

</details>

<br>

## 📚 Документация

<div align="center">

| | |
|---|---|
| 📘 | [`docs/DEVELOPER_GUIDE.md`](docs/DEVELOPER_GUIDE.md) — руководство разработчика |
| 🚀 | [`docs/INSTALLATION.md`](docs/INSTALLATION.md) — установка на выделенный сервер |
| 🔑 | [`docs/LICENSE_GENERATION_GUIDE.md`](docs/LICENSE_GENERATION_GUIDE.md) — генерация лицензий |
| 🧩 | [`docs/modules/`](docs/modules/) — документация по каждому модулю |

</div>

<br>

## 📄 Лицензия

Проект распространяется по лицензии **MIT** — свободное использование и модификация.

<br>

---

<br>

### Примечания

[^1]: **CDN-независимость.** Все шрифты (латиница + кириллица), Font Awesome/Lucide/Tabler-иконки, Alpine.js, Swagger UI вендорены локально в `public/assets/` через `local_css()`/`local_js()`/`local_font_css()` (`app/helpers.php`). Единственные внешние интеграции — опциональные и явно согласуемые пользователем (Яндекс.Карты открываются только по клику, Яндекс.Метрика включается через конфиг).

[^2]: **Почему только PostgreSQL.** Мастер установки (`/install`) сознательно предлагает только PostgreSQL — открытую СУБД без вендор-лока. Миграции написаны через Laravel Schema Builder (не сырой SQL), поэтому корректно работают и на PostgreSQL в проде, и на SQLite при локальном тестировании.

[^3]: **PHP 8.5.** Требование действительно строгое (не 8.2/8.3) — так зафиксировано в `composer.json` (`"php": "^8.5"`) и проверяется мастером установки на шаге «Требования».

[^4]: **Миграции — в одном месте.** Все 70+ миграций (и ядра, и каждого модуля) лежат в `database/migrations/` и подхватываются Laravel автоматически — никакой ручной регистрации путей по модулям не требуется. Создавая миграцию для нового модуля, кладите её сразу туда: `php artisan make:migration create_your_table`.

<br>

<div align="center">
<sub>Сделано с любовью к скорости, аккуратности и локальному хостингу 🇷🇺</sub>
</div>
