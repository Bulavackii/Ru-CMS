# 💳 Модуль Payments (Платежи)

**Версия:** 1.0.0  
**Приоритет:** 10

---

## Описание

Модуль интеграции платежных систем с поддержкой ЮKassa, СБП, Тинькофф и других российских платежных систем.

## Основные возможности

- ✅ Управление методами оплаты
- ✅ Интеграция с ЮKassa
- ✅ Интеграция с СБП (Система быстрых платежей)
- ✅ Интеграция с Тинькофф
- ✅ Управление заказами
- ✅ Webhook обработка
- ✅ Статистика заказов
- ✅ Экспорт заказов в CSV

## Структура

```
modules/Payments/
├── Controllers/
│   ├── Admin/
│   │   ├── PaymentController.php  # Управление методами оплаты
│   │   └── OrderController.php    # Управление заказами
│   └── Frontend/
│       └── CartController.php     # Корзина
├── Models/
│   ├── PaymentMethod.php          # Методы оплаты
│   ├── Order.php                  # Заказы
│   └── OrderItem.php              # Элементы заказа
├── Gateways/
│   ├── YooKassaGateway.php        # ЮKassa
│   ├── SBPGateway.php             # СБП
│   ├── TinkoffGateway.php         # Тинькофф
│   └── ...                        # Другие гейтвеи
├── Services/
│   └── PaymentGatewayService.php  # Сервис для работы с гейтвеями
└── Views/
    ├── admin/                     # Админ-панель
    └── public/                    # Публичная часть
```

## Использование

### Создание метода оплаты

```php
use Modules\Payments\Models\PaymentMethod;

$method = PaymentMethod::create([
    'name' => 'ЮKassa',
    'gateway' => 'yookassa',
    'is_active' => true,
    'settings' => [
        'shop_id' => 'your_shop_id',
        'secret_key' => 'your_secret_key',
    ],
]);
```

### Создание заказа

```php
use Modules\Payments\Models\Order;
use Modules\Payments\Models\OrderItem;

$order = Order::create([
    'user_id' => auth()->id(),
    'status' => 'pending',
    'total' => 1000.00,
    'payment_method_id' => 1,
]);

OrderItem::create([
    'order_id' => $order->id,
    'name' => 'Товар',
    'price' => 1000.00,
    'quantity' => 1,
]);
```

### Инициализация платежа

```php
use Modules\Payments\Services\PaymentGatewayService;

$gatewayService = app(PaymentGatewayService::class);
$paymentMethod = PaymentMethod::find(1);
$gateway = $gatewayService->getGateway($paymentMethod->gateway, $paymentMethod);

$paymentData = $gateway->createPayment($order, [
    'return_url' => route('payments.success', $order),
    'fail_url' => route('payments.fail', $order),
]);
```

## Поддерживаемые платежные системы

### ЮKassa
- Полная интеграция API
- Поддержка карт, электронных кошельков
- Возвраты и частичные возвраты

### СБП (Система быстрых платежей)
- QR-коды для оплаты
- Мгновенные переводы
- Поддержка всех банков СБП

### Тинькофф
- Интернет-эквайринг
- Поддержка карт
- Интеграция с кассой

## Маршруты

### Админка
- `GET /admin/payments` - список методов оплаты
- `POST /admin/payments` - создание метода
- `PUT /admin/payments/{id}` - обновление метода
- `DELETE /admin/payments/{id}` - удаление метода

- `GET /admin/orders` - список заказов
- `GET /admin/orders/{id}` - просмотр заказа
- `PUT /admin/orders/{id}/status` - изменение статуса
- `GET /admin/orders/export/csv` - экспорт в CSV
- `GET /admin/orders/stats` - статистика

### Публичные
- `POST /orders/{order}/payment/initiate` - инициализация платежа
- `GET /payments/success/{order}` - успешная оплата
- `GET /payments/fail/{order}` - ошибка оплаты
- `POST /payment/webhook/{gateway}` - webhook от платежной системы

## Статусы заказа

- `pending` - Ожидает оплаты
- `paid` - Оплачен
- `processing` - В обработке
- `shipped` - Отправлен
- `delivered` - Доставлен
- `cancelled` - Отменен
- `refunded` - Возвращен

## Создание нового платежного гейтвея

1. Создайте класс в `modules/Payments/Gateways/`:

```php
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
}
```

2. Зарегистрируйте в `PaymentGatewayService`:

```php
return match($code) {
    'mygateway' => new MyGateway($paymentMethod),
    // ...
};
```

## Миграции

Таблицы:
- `payment_methods` - методы оплаты
- `orders` - заказы
- `order_items` - элементы заказов

---

**Версия:** 1.0.0  
**Последнее обновление:** 2025-01-28




