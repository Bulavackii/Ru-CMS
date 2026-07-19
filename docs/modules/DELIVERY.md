# 🚚 Модуль Delivery (Доставка)

**Версия:** 1.0.0  
**Приоритет:** 11

---

## Описание

Управление способами доставки с поддержкой российских служб доставки (СДЭК, Boxberry, Почта России).

## Основные возможности

- ✅ Управление способами доставки
- ✅ Интеграция с СДЭК
- ✅ Интеграция с Boxberry
- ✅ Интеграция с Почтой России
- ✅ Расчет стоимости доставки
- ✅ Расчет сроков доставки
- ✅ API для расчета доставки

## Структура

```
modules/Delivery/
├── Controllers/
│   ├── Admin/DeliveryController.php
│   └── Api/DeliveryApiController.php
├── Models/
│   └── DeliveryMethod.php            # Модель способа доставки
├── Services/
│   ├── DeliveryServiceInterface.php  # Интерфейс сервиса доставки
│   ├── CdekService.php              # СДЭК
│   ├── BoxberryService.php          # Boxberry
│   ├── PochtaService.php            # Почта России
│   └── DeliveryCalculatorService.php # Калькулятор доставки
└── Views/
    └── admin/                         # Админ-панель
```

## Использование

### Создание способа доставки

```php
use Modules\Delivery\Models\DeliveryMethod;

$method = DeliveryMethod::create([
    'name' => 'СДЭК',
    'code' => 'cdek',
    'is_active' => true,
    'price' => 300.00,
    'free_from' => 5000.00, // Бесплатно от суммы
    'settings' => [
        'api_key' => 'your_api_key',
        'sender_city' => 'Москва',
    ],
]);
```

### Расчет стоимости доставки

```php
use Modules\Delivery\Services\DeliveryCalculatorService;

$calculator = app(DeliveryCalculatorService::class);

$cost = $calculator->calculate(
    $deliveryMethod,
    $orderWeight, // вес в граммах
    $orderTotal,  // сумма заказа
    $fromCity,    // город отправления
    $toCity       // город назначения
);
```

### Использование сервисов доставки

```php
use Modules\Delivery\Services\CdekService;

$cdek = new CdekService($deliveryMethod);
$result = $cdek->calculateDelivery([
    'from' => 'Москва',
    'to' => 'Санкт-Петербург',
    'weight' => 1000, // граммы
]);
```

## API

### Расчет доставки
```
POST /api/delivery/calculate
{
    "method_id": 1,
    "weight": 1000,
    "total": 5000,
    "from_city": "Москва",
    "to_city": "Санкт-Петербург"
}
```

## Поддерживаемые службы доставки

- **СДЭК** - через API
- **Boxberry** - через API
- **Почта России** - расчет по тарифам
- **Самовывоз** - фиксированная стоимость или бесплатно
- **Курьерская доставка** - настраиваемая стоимость

## Миграции

Таблицы:
- `delivery_methods` - способы доставки

---

**Версия:** 1.0.0  
**Последнее обновление:** 2025-01-28




