# 🔑 Гайд по генерации лицензий и промокодов

Подробное руководство для разработчиков по работе с системой лицензирования CMS.

---

## 📋 Содержание

1. [Настройка доступа](#настройка-доступа)
2. [Генерация лицензионных ключей](#генерация-лицензионных-ключей)
3. [Генерация промокодов](#генерация-промокодов)
4. [Программное создание подписок](#программное-создание-подписок)
5. [Проверка и валидация](#проверка-и-валидация)
6. [Управление подписками](#управление-подписками)
7. [Примеры использования](#примеры-использования)
8. [Часто задаваемые вопросы](#часто-задаваемые-вопросы)

---

## 🔐 Настройка доступа

Для доступа к командам генерации лицензий необходимо включить режим разработчика.

### Вариант 1: Через .env файл (рекомендуется)

Добавьте в файл `.env`:

```env
DEVELOPER_MODE=true
```

### Вариант 2: Через специальный ключ

```env
DEVELOPER_KEY=your-secret-key-here
```

И в `config/app.php`:

```php
'developer_key' => env('DEVELOPER_KEY'),
```

### Вариант 3: Через файл (для локальной разработки)

Создайте файл `.developer` в корне проекта:

```bash
touch .developer
```

**⚠️ Важно:** Файл `.developer` должен быть добавлен в `.gitignore` для безопасности.

---

## 🔑 Генерация лицензионных ключей

### Базовая команда

```bash
php artisan license:generate license
```

Генерирует лицензионный ключ с параметрами по умолчанию:
- Тариф: `basic`
- Срок действия: `12 месяцев`

### Полная команда с параметрами

```bash
php artisan license:generate license --plan=pro --months=24
```

#### Параметры команды:

| Параметр | Тип | По умолчанию | Описание |
|----------|-----|--------------|----------|
| `type` | `license` | обязательный | Тип генерации (license или promo) |
| `--plan` | `basic\|pro\|enterprise` | `basic` | Тарифный план |
| `--months` | `integer` | `12` | Срок действия в месяцах |

### Примеры генерации лицензий

#### 1. Базовая лицензия на 1 год

```bash
php artisan license:generate license --plan=basic --months=12
```

**Вывод:**
```
🔑 Генерация лицензионного ключа...

┌─────────────────────┬──────────────────────────────────────┐
│ Параметр            │ Значение                              │
├─────────────────────┼──────────────────────────────────────┤
│ Лицензионный ключ   │ A1B2C3D4-E5F6G7H8-I9J0K1L2-M3N4O5P6 │
│ Тариф               │ basic                                │
│ Срок действия       │ 2026-01-15 14:30:00                 │
│ Месяцев             │ 12                                   │
└─────────────────────┴──────────────────────────────────────┘

💾 Сохраните этот ключ для выдачи клиентам.
📋 Ключ будет активирован при установке CMS.
✅ Ключ сохранен в: storage/app/licenses.txt
```

#### 2. Pro лицензия на 2 года

```bash
php artisan license:generate license --plan=pro --months=24
```

#### 3. Enterprise лицензия на 6 месяцев

```bash
php artisan license:generate license --plan=enterprise --months=6
```

#### 4. Базовая лицензия на 3 месяца (тестовая)

```bash
php artisan license:generate license --plan=basic --months=3
```

### Формат лицензионного ключа

Лицензионный ключ имеет формат: `XXXX-XXXX-XXXX-XXXX`

Где `X` - это символы `A-Z` и цифры `0-9`.

**Примеры:**
- `A1B2C3D4-E5F6G7H8-I9J0K1L2-M3N4O5P6`
- `F8E7D6C5-B4A39281-76543210-FEDCBA98`
- `12345678-ABCDEFGH-IJKLMNOP-QRSTUVWX`

### Сохранение ключей

Все сгенерированные ключи автоматически сохраняются в файл:
```
storage/app/licenses.txt
```

Формат записи:
```
A1B2C3D4-E5F6G7H8-I9J0K1L2-M3N4O5P6 | Plan: pro | Expires: 2026-01-15 | Generated: 2025-01-15 14:30:00
```

---

## 🎟️ Генерация промокодов

### Базовая команда

```bash
php artisan license:generate promo
```

Генерирует промокод с параметрами по умолчанию:
- Код: случайный (8 символов)
- Тип скидки: `percentage`
- Значение скидки: `10%`
- Лимит использования: без ограничений
- Срок действия: без ограничений

### Полная команда с параметрами

```bash
php artisan license:generate promo \
  --code=SUMMER2025 \
  --name="Летняя скидка" \
  --discount-type=percentage \
  --discount-value=25 \
  --usage-limit=100 \
  --expires-at=2025-08-31
```

#### Параметры команды:

| Параметр | Тип | По умолчанию | Описание |
|----------|-----|--------------|----------|
| `type` | `promo` | обязательный | Тип генерации |
| `--code` | `string` | случайный | Код промокода (если не указан, генерируется автоматически) |
| `--name` | `string` | `null` | Название промокода |
| `--discount-type` | `percentage\|fixed` | `percentage` | Тип скидки |
| `--discount-value` | `float` | `10` | Значение скидки |
| `--usage-limit` | `integer` | `null` (без ограничений) | Лимит использования |
| `--expires-at` | `Y-m-d` | `null` (без ограничений) | Дата истечения |

### Примеры генерации промокодов

#### 1. Промокод со скидкой 25%

```bash
php artisan license:generate promo \
  --code=WELCOME25 \
  --name="Приветственная скидка" \
  --discount-type=percentage \
  --discount-value=25
```

**Вывод:**
```
🎟️ Промокод успешно создан!

┌─────────────────────┬──────────────────────┐
│ Параметр            │ Значение              │
├─────────────────────┼──────────────────────┤
│ Код                 │ WELCOME25             │
│ Название            │ Приветственная скидка │
│ Тип скидки          │ 25%                   │
│ Лимит использования │ Без ограничений       │
│ Истекает            │ Никогда               │
└─────────────────────┴──────────────────────┘

💾 Промокод сохранен в базу данных.
📋 Клиенты смогут использовать его при установке CMS.
```

#### 2. Промокод с фиксированной скидкой 500 рублей

```bash
php artisan license:generate promo \
  --code=SAVE500 \
  --name="Скидка 500 рублей" \
  --discount-type=fixed \
  --discount-value=500
```

#### 3. Промокод с ограничением использования (100 раз)

```bash
php artisan license:generate promo \
  --code=LIMITED100 \
  --name="Ограниченная акция" \
  --discount-type=percentage \
  --discount-value=15 \
  --usage-limit=100
```

#### 4. Промокод с датой истечения

```bash
php artisan license:generate promo \
  --code=NEWYEAR2025 \
  --name="Новогодняя акция" \
  --discount-type=percentage \
  --discount-value=30 \
  --expires-at=2025-02-01
```

#### 5. Промокод со всеми параметрами

```bash
php artisan license:generate promo \
  --code=BLACKFRIDAY \
  --name="Черная пятница 2025" \
  --discount-type=percentage \
  --discount-value=50 \
  --usage-limit=500 \
  --expires-at=2025-11-30
```

### Типы скидок

#### Percentage (Процентная скидка)

Скидка рассчитывается как процент от стоимости тарифа.

**Пример:**
- Тариф Pro: 2990 руб.
- Промокод: 25%
- Итоговая цена: 2990 - (2990 × 0.25) = 2242.5 руб.

```bash
php artisan license:generate promo \
  --code=PRO25 \
  --discount-type=percentage \
  --discount-value=25
```

#### Fixed (Фиксированная скидка)

Скидка вычитается из стоимости тарифа.

**Пример:**
- Тариф Pro: 2990 руб.
- Промокод: 500 руб.
- Итоговая цена: 2990 - 500 = 2490 руб.

```bash
php artisan license:generate promo \
  --code=SAVE500 \
  --discount-type=fixed \
  --discount-value=500
```

---

## 💻 Программное создание подписок

### Использование SubscriptionService

```php
use App\Services\SubscriptionService;

$subscriptionService = app(SubscriptionService::class);

// Создание подписки
$result = $subscriptionService->createSubscription([
    'user_id' => 1,
    'plan' => 'pro',
    'starts_at' => now(),
    'duration' => 12, // месяцев
    'promo_code_id' => 5, // опционально
]);
```

### Прямое создание через DB

```php
use Illuminate\Support\Facades\DB;
use App\Services\SubscriptionService;

$subscriptionService = app(SubscriptionService::class);

$licenseKey = $subscriptionService->generateLicenseKey();

DB::table('subscriptions')->insert([
    'user_id' => 1,
    'plan' => 'pro',
    'license_key' => $licenseKey,
    'starts_at' => now(),
    'expires_at' => now()->addMonths(12),
    'is_active' => true,
    'created_at' => now(),
    'updated_at' => now(),
]);
```

### Создание подписки с конкретным ключом

```php
use Illuminate\Support\Facades\DB;

DB::table('subscriptions')->insert([
    'user_id' => 1,
    'plan' => 'enterprise',
    'license_key' => 'A1B2C3D4-E5F6G7H8-I9J0K1L2-M3N4O5P6', // ваш ключ
    'starts_at' => now(),
    'expires_at' => now()->addYears(2),
    'is_active' => true,
    'notes' => 'Корпоративная лицензия для компании X',
    'created_at' => now(),
    'updated_at' => now(),
]);
```

### Массовое создание подписок

```php
use Illuminate\Support\Facades\DB;
use App\Services\SubscriptionService;

$subscriptionService = app(SubscriptionService::class);

$users = [1, 2, 3, 4, 5]; // ID пользователей
$subscriptions = [];

foreach ($users as $userId) {
    $subscriptions[] = [
        'user_id' => $userId,
        'plan' => 'basic',
        'license_key' => $subscriptionService->generateLicenseKey(),
        'starts_at' => now(),
        'expires_at' => now()->addMonths(6),
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ];
}

DB::table('subscriptions')->insert($subscriptions);
```

---

## ✅ Проверка и валидация

### Проверка лицензионного ключа

```php
use App\Services\SubscriptionService;

$subscriptionService = app(SubscriptionService::class);

$isValid = $subscriptionService->validateLicenseKey('A1B2C3D4-E5F6G7H8-I9J0K1L2-M3N4O5P6');

if ($isValid) {
    echo "Лицензия действительна";
} else {
    echo "Лицензия недействительна или истекла";
}
```

### Проверка активной подписки

```php
use App\Services\SubscriptionService;

$subscriptionService = app(SubscriptionService::class);

$hasActive = $subscriptionService->hasActiveSubscription();

if ($hasActive) {
    echo "У пользователя есть активная подписка";
}
```

### Получение информации о лицензии

```php
use App\Services\SubscriptionService;

$subscriptionService = app(SubscriptionService::class);

$licenseInfo = $subscriptionService->getLicenseInfo();

if ($licenseInfo) {
    echo "Тариф: " . $licenseInfo['plan_info']['name'] . "\n";
    echo "Осталось дней: " . $licenseInfo['days_left'] . "\n";
    echo "Истекает: " . $licenseInfo['formatted_expires_at'] . "\n";
    echo "Статус: " . ($licenseInfo['is_expired'] ? 'Истекла' : 'Активна') . "\n";
}
```

### Получение текущей подписки

```php
use App\Services\SubscriptionService;

$subscriptionService = app(SubscriptionService::class);

$subscription = $subscriptionService->getCurrentSubscription();

if ($subscription) {
    echo "Лицензионный ключ: " . $subscription->license_key . "\n";
    echo "Тариф: " . $subscription->plan . "\n";
    echo "Истекает: " . $subscription->expires_at . "\n";
}
```

---

## 🛠️ Управление подписками

### Продление подписки

```php
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$subscriptionId = 1; // ID подписки
$additionalMonths = 12; // месяцев

DB::table('subscriptions')
    ->where('id', $subscriptionId)
    ->update([
        'expires_at' => Carbon::parse(
            DB::table('subscriptions')->where('id', $subscriptionId)->value('expires_at')
        )->addMonths($additionalMonths),
        'updated_at' => now(),
    ]);
```

### Изменение тарифа

```php
use Illuminate\Support\Facades\DB;

DB::table('subscriptions')
    ->where('id', 1)
    ->update([
        'plan' => 'pro',
        'updated_at' => now(),
    ]);
```

### Деактивация подписки

```php
use Illuminate\Support\Facades\DB;

DB::table('subscriptions')
    ->where('id', 1)
    ->update([
        'is_active' => false,
        'updated_at' => now(),
    ]);
```

### Активация подписки

```php
use Illuminate\Support\Facades\DB;

DB::table('subscriptions')
    ->where('id', 1)
    ->update([
        'is_active' => true,
        'updated_at' => now(),
    ]);
```

### Поиск подписки по ключу

```php
use Illuminate\Support\Facades\DB;

$subscription = DB::table('subscriptions')
    ->where('license_key', 'A1B2C3D4-E5F6G7H8-I9J0K1L2-M3N4O5P6')
    ->first();

if ($subscription) {
    echo "Найдена подписка для пользователя ID: " . $subscription->user_id;
}
```

---

## 📝 Примеры использования

### Пример 1: Генерация лицензии для нового клиента

```bash
# Генерируем Pro лицензию на 1 год
php artisan license:generate license --plan=pro --months=12

# Копируем сгенерированный ключ
# Отправляем клиенту ключ для использования при установке
```

### Пример 2: Создание промокода для акции

```bash
# Создаем промокод "Черная пятница" со скидкой 50%
php artisan license:generate promo \
  --code=BLACKFRIDAY2025 \
  --name="Черная пятница 2025" \
  --discount-type=percentage \
  --discount-value=50 \
  --usage-limit=1000 \
  --expires-at=2025-12-01
```

### Пример 3: Программное создание тестовой подписки

```php
// В Tinker или в коде
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\DB;

$service = app(SubscriptionService::class);

$userId = DB::table('users')->where('is_admin', true)->value('id');

$service->createSubscription([
    'user_id' => $userId,
    'plan' => 'basic',
    'starts_at' => now(),
    'duration' => 3, // 3 месяца для теста
]);
```

### Пример 4: Проверка лицензии перед установкой

```php
use App\Services\SubscriptionService;

$subscriptionService = app(SubscriptionService::class);
$licenseKey = 'A1B2C3D4-E5F6G7H8-I9J0K1L2-M3N4O5P6';

if ($subscriptionService->validateLicenseKey($licenseKey)) {
    // Лицензия валидна, можно продолжать установку
    echo "Лицензия подтверждена";
} else {
    // Лицензия недействительна
    echo "Ошибка: Лицензия недействительна или истекла";
}
```

### Пример 5: Массовая генерация лицензий

```bash
#!/bin/bash
# Скрипт для генерации 10 лицензий

for i in {1..10}; do
    echo "Генерация лицензии #$i..."
    php artisan license:generate license --plan=basic --months=12
    echo ""
done
```

### Пример 6: Проверка всех истекающих подписок

```php
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$expiringSoon = DB::table('subscriptions')
    ->where('is_active', true)
    ->where('expires_at', '>', now())
    ->where('expires_at', '<=', now()->addDays(30))
    ->get();

foreach ($expiringSoon as $subscription) {
    $daysLeft = Carbon::parse($subscription->expires_at)->diffInDays(now());
    echo "Подписка ID {$subscription->id} истекает через {$daysLeft} дней\n";
}
```

---

## ❓ Часто задаваемые вопросы

### Как сгенерировать лицензию для конкретного клиента?

1. Определите тариф и срок действия
2. Выполните команду:
   ```bash
   php artisan license:generate license --plan=pro --months=12
   ```
3. Скопируйте сгенерированный ключ
4. Отправьте ключ клиенту для использования при установке

### Можно ли использовать один ключ на нескольких установках?

**Нет**, каждый лицензионный ключ уникален и предназначен для одной установки. При попытке использовать ключ повторно система определит, что он уже активирован.

### Как продлить лицензию клиента?

1. Найдите подписку по лицензионному ключу или ID пользователя
2. Обновите дату `expires_at`:
   ```php
   DB::table('subscriptions')
       ->where('license_key', 'KEY-HERE')
       ->update(['expires_at' => now()->addMonths(12)]);
   ```

### Как создать промокод с неограниченным использованием?

Просто не указывайте параметр `--usage-limit`:

```bash
php artisan license:generate promo --code=UNLIMITED --discount-value=20
```

### Можно ли изменить тариф существующей подписки?

Да, обновите поле `plan` в таблице `subscriptions`:

```php
DB::table('subscriptions')
    ->where('id', $subscriptionId)
    ->update(['plan' => 'pro']);
```

### Как проверить, сколько раз использован промокод?

```php
$promo = DB::table('promo_codes')
    ->where('code', 'PROMOCODE')
    ->first();

echo "Использовано: {$promo->used_count} из " . ($promo->usage_limit ?? '∞');
```

### Где хранятся сгенерированные ключи?

Все ключи сохраняются в:
- **База данных**: таблица `subscriptions`
- **Файл**: `storage/app/licenses.txt` (для удобства)

### Как отключить режим разработчика?

Удалите или установите в `false` в `.env`:

```env
DEVELOPER_MODE=false
```

Или удалите файл `.developer` если использовали его.

### Можно ли создать лицензию с конкретным ключом?

Да, создайте подписку программно с нужным ключом:

```php
DB::table('subscriptions')->insert([
    'user_id' => 1,
    'plan' => 'pro',
    'license_key' => 'YOUR-CUSTOM-KEY-HERE',
    'starts_at' => now(),
    'expires_at' => now()->addMonths(12),
    'is_active' => true,
    'created_at' => now(),
    'updated_at' => now(),
]);
```

### Как проверить все активные подписки?

```php
$activeSubscriptions = DB::table('subscriptions')
    ->where('is_active', true)
    ->where('expires_at', '>', now())
    ->get();

foreach ($activeSubscriptions as $sub) {
    echo "Ключ: {$sub->license_key}, Тариф: {$sub->plan}, Истекает: {$sub->expires_at}\n";
}
```

---

## 🔒 Безопасность

### Рекомендации

1. **Никогда не коммитьте `.env` файл** с `DEVELOPER_MODE=true`
2. **Добавьте `.developer` в `.gitignore`**
3. **Храните файл `storage/app/licenses.txt` в безопасном месте**
4. **Не передавайте лицензионные ключи по незащищенным каналам**
5. **Используйте HTTPS для передачи ключей клиентам**

### .gitignore

Убедитесь, что в `.gitignore` есть:

```
.env
.developer
storage/app/licenses.txt
```

---

## 📞 Поддержка

Если у вас возникли вопросы или проблемы:

1. Проверьте логи: `storage/logs/laravel.log`
2. Убедитесь, что режим разработчика включен
3. Проверьте права доступа к базе данных
4. Убедитесь, что миграции выполнены

---

**Последнее обновление:** 2025-01-15  
**Версия CMS:** 1.0.0

