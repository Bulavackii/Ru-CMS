# 📚 Документация по модулям RU CMS

Этот раздел содержит подробное описание всех модулей системы RU CMS.

---

## 📋 Список модулей

### 🔧 Системные модули

- **[System](SYSTEM.md)** — Системный модуль для управления модулями
- **[Install](INSTALL.md)** — Модуль установки CMS
- **[Users](USERS.md)** — Управление пользователями, ролями и правами доступа

### 🌍 Контентные модули

- **[News](NEWS.md)** — Управление новостями с фильтрами, поиском и SEO
- **[Categories](CATEGORIES.md)** — Категоризация контента и товаров
- **[Menu](MENU.md)** — GUI-редактор меню до 3 уровней вложенности
- **[Files](FILES.md)** — Управление файлами и медиа-библиотекой
- **[Slideshow](SLIDESHOW.md)** — Слайдшоу для header/footer

### 💼 Функциональные модули

- **[Payments](PAYMENTS.md)** — Интеграция платежных систем (ЮKassa, СБП, Тинькофф)
- **[Delivery](DELIVERY.md)** — Управление способами доставки

### 🎨 Визуальные модули

- **[Slideshow](SLIDESHOW.md)** — Слайдшоу для header/footer
- **[Visual](VISUAL.md)** — Визуальный редактор тем и фрагментов

### 🔍 Служебные модули

- **[Search](SEARCH.md)** — Глобальный поиск в админке
- **[SEO](SEO.md)** — SEO оптимизация (Yandex-first)
- **[Localization](LOCALIZATION.md)** — Мультиязычность и локализация
- **[Notifications](NOTIFICATIONS.md)** — Система уведомлений с Web Push
- **[Comments](COMMENTS.md)** — Система комментариев с модерацией
- **[Reviews](REVIEWS.md)** — Система отзывов
- **[Messages](MESSAGES.md)** — Система внутренних сообщений
- **[Captcha](CAPTCHA.md)** — Система защиты от ботов
- **[Accessibility](ACCESSIBILITY.md)** — Настройки доступности

### 📊 Дополнительные модули

- **[NewsIO](NEWSIO.md)** — Импорт и экспорт новостей

---

## 🏗️ Архитектура модулей

Все модули следуют единой архитектуре HMVC:

```
modules/ModuleName/
├── Controllers/          # Контроллеры (Admin/Frontend/Api)
├── Models/               # Eloquent модели
├── Views/                # Blade шаблоны
├── Routes/               # Маршруты (web.php, api.php)
├── Migrations/           # Миграции БД
├── Providers/            # Service Providers
├── Services/             # Бизнес-логика
├── Config/               # Конфигурация модуля
├── Lang/                 # Переводы
└── module.json           # Метаданные модуля
```

---

## 🔌 Подключение модулей

Модули автоматически подключаются через файл `module.json`:

```json
{
  "name": "ModuleName",
  "title": "Название модуля",
  "version": "1.0.0",
  "active": true,
  "priority": 50,
  "description": "Описание модуля",
  "providers": [
    "Modules\\ModuleName\\Providers\\ModuleServiceProvider"
  ]
}
```

Приоритет загрузки:
- **Меньше = загружается раньше**
- Системные модули обычно имеют priority < 10
- Функциональные модули: 10-100
- Визуальные модули: > 100

---

## 📖 Подробнее

Для получения подробной информации о каждом модуле, см. соответствующий файл документации.

---

**Версия:** 2.0  
**Дата:** 2025-01-28

