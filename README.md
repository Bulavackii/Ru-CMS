# 🛒 RuShop CMS

**RuShop CMS** — это модульная система управления сайтом (CMS), построенная на PHP 8.4+ и Laravel 12+, ориентированная на магазины и сайты для России и стран СНГ.

---

## 🚀 Основные возможности

- 🧩 Модульная архитектура: всё как отдельные модули
- ⚙️ GUI-интерфейс управления модулями (включение/отключение, установка ZIP)
- 🧑‍💼 Разделение ролей: обычный пользователь и администратор
- 🔍 Глобальный поиск по пользователям, категориям и товарам
- 🔐 Кастомная система авторизации и восстановления пароля
- 🎨 Адаптивная клиентская часть на Blade + Tailwind
- 🇷🇺 Русская локализация по умолчанию
- 📦 Установка модулей через ZIP-архивы
- 📈 Интеграции с российскими сервисами (в планах)

---

## 🗂 Структура проекта

Ru-CMS/ ├── app/ ├── modules/ │ ├── System/ │ ├── Users/ │ ├── Search/ │ └── ... ├── resources/ │ ├── views/ │ │ ├── auth/ │ │ ├── frontend/ │ │ └── layouts/ │ └── js/ ├── routes/ │ ├── web.php ├── database/ ├── public/ ├── storage/ ├── vite.config.js ├── composer.json └── package.json

---

## ⚙️ Установка проекта

```bash
# 1. Клонируй проект с GitHub
git clone https://github.com/ТВОЙ-АККАУНТ/Ru-CMS.git
cd Ru-CMS

# 2. Установи зависимости Laravel
composer install

# 3. Установи зависимости фронтенда
npm install

# 4. Создай файл окружения
cp .env.example .env

# 5. Сгенерируй ключ приложения
php artisan key:generate

# 6. Настрой .env для подключения к MySQL
# DB_DATABASE=rushop
# DB_USERNAME=...
# DB_PASSWORD=...

# 7. Примени миграции и сиды
php artisan migrate --seed

# 8. Запусти локальный сервер
php artisan serve

# 9. Запусти компиляцию фронтенда
npm run dev

👤 Пользователи по умолчанию:
| Роль         | Email               | Пароль    |
|--------------|---------------------|-----------|
| Администратор| `admin@example.com` | `123456`|
| Пользователь | `user@example.com`  | `123456`|

📦 Работа с модулями
Установка ZIP-модуля — через форму в /admin/modules

module.json должен содержать:
{
  "name": "Search",
  "version": "1.0.0",
  "active": false
}


🔐 Роуты авторизации
| URI             | Метод | Назначение                   |
|------------------|--------|------------------------------|
| `/login`         | GET    | Форма входа                 |
| `/register`      | GET    | Форма регистрации           |
| `/logout`        | POST   | Выход из аккаунта           |
| `/forgot-password` | GET  | Восстановление пароля       |
| `/reset-password/{token}` | GET | Форма сброса пароля    |

🧠 Команды разработчика
# Очистка кешей (на случай ошибок)
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan config:clear

# Повторное создание таблиц
php artisan migrate:fresh --seed

# Создание модели и миграции
php artisan make:model Product -m

# Создание модуля вручную
mkdir modules/ExampleModule
touch modules/ExampleModule/module.json


🔮 Планы на будущее
 React-админка с Vite

 Интеграции: Сбербанк, СДЭК, VK API

 SEO-модуль

 Модуль доставки и оплаты

 Редактор страниц и статей

📖 Лицензия
Проект распространяется под лицензией MIT. Используй, дорабатывай и предлагай pull requests!

