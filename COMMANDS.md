Проектный план RuShop CMS

1. Создание базового ядра CMS

Настройка проекта:

composer create-project laravel/laravel rushop-cms
cd rushop-cms
composer require laravel/sanctum

Конфигурация базы данных:
Редактируем файл:
/config/database.php

Инициализация React + Vite + TailwindCSS:

npm install react react-dom vite @vitejs/plugin-react tailwindcss postcss autoprefixer
npm install -D tailwindcss
npx tailwindcss init -p

2. Разработка модуля системы и управления модулями

Структура модулей:

/modules/System/
├── Config/config.php
├── Controllers/Admin/ModuleController.php
├── Models/Module.php
├── Views/admin/
├── Routes/web.php
├── Migrations/
├── Lang/ru/
├── module.json

Команда для миграций:

php artisan make:migration create_modules_table --path=/modules/System/Migrations

3. Реализация GUI админки (React + Tailwind)

Расположение React приложения:

/resources/views/admin

Сборка приложения:

npm run build

4. Создание базовых модулей (Shop, Cart, Checkout, Payments)

Пример структуры для модуля Shop:

/modules/Shop/
├── Config/config.php
├── Controllers/Frontend/ProductController.php
├── Controllers/Admin/ProductAdminController.php
├── Models/Product.php
├── Views/frontend/product.blade.php
├── Routes/web.php
├── Migrations/create_products_table.php
├── Seeders/ProductSeeder.php
├── Lang/ru/messages.php
├── module.json

Создание миграций и сидеров:

php artisan make:migration create_products_table --path=/modules/Shop/Migrations
php artisan make:seeder ProductSeeder --path=/modules/Shop/Seeders

5. Реализация магазина модулей и загрузки ZIP

Реализация загрузчика модулей:

composer require maatwebsite/excel

Контроллер и представления:

/modules/System/Controllers/Admin/ModuleUploadController.php
/modules/System/Views/admin/upload-module.blade.php

6. Интеграция российских сервисов

Добавление интеграций через Composer:

composer require yoomoney/yookassa-sdk-php
composer require tinkoff/tinkoff-sdk
composer require guzzlehttp/guzzle

Структура интеграций (пример платежей):

/modules/Payments/
├── Config/config.php
├── Controllers/PaymentsController.php
├── Models/Payment.php
├── Migrations/create_payments_table.php
├── module.json

7. Оптимизация и тестирование

Использование PHPUnit:

composer require --dev phpunit/phpunit
php artisan make:test ShopTest

Линтинг и проверка кода:

composer require --dev friendsofphp/php-cs-fixer
./vendor/bin/php-cs-fixer fix

8. Запуск первой версии CMS

Финальные шаги:

npm run build
php artisan migrate --seed
php artisan serve

Проект готов к работе на:
http://localhost:8000

composer require laravel/breeze --dev
php artisan breeze:install blade
npm install && npm run dev
php artisan migrate


в php.ini достаточные лимиты (upload_max_filesize, post_max_size)
