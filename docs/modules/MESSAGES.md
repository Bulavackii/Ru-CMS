# 📑 Модуль Messages (Сообщения)

**Версия:** 1.0.0  
**Приоритет:** 8

---

## Описание

Обработка сообщений и обращений от пользователей. Система внутренних сообщений и обратной связи.

## Основные возможности

- ✅ Обратная связь от пользователей
- ✅ Внутренние сообщения
- ✅ Вложения к сообщениям
- ✅ Статусы сообщений (новое, прочитано, отвечено)
- ✅ Уведомления о новых сообщениях

## Структура

```
modules/Messages/
├── Controllers/
│   └── Admin/MessageController.php
├── Models/
│   ├── Message.php                   # Модель сообщения
│   └── MessageAttachment.php        # Вложения
└── Views/
    └── admin/                         # Админ-панель
```

## Использование

### Создание сообщения

```php
use Modules\Messages\Models\Message;

$message = Message::create([
    'name' => 'Иван Иванов',
    'email' => 'ivan@example.com',
    'phone' => '+7 (999) 123-45-67',
    'subject' => 'Вопрос',
    'message' => 'Текст сообщения',
    'status' => 'new', // new, read, replied
]);
```

### Получение сообщений

```php
// Все сообщения
$messages = Message::orderByDesc('created_at')->get();

// Новые сообщения
$newMessages = Message::where('status', 'new')->get();

// По статусу
$readMessages = Message::where('status', 'read')->get();
```

## Статусы сообщений

- `new` - Новое
- `read` - Прочитано
- `replied` - Отвечено

## Миграции

Таблицы:
- `messages` - сообщения
- `message_attachments` - вложения к сообщениям

---

**Версия:** 1.0.0  
**Последнее обновление:** 2025-01-28




