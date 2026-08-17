# 🚀 Установка Nexum Core: Пошаговая инструкция

## 🔽 1. Клонирование и начальная установка

### 📦 Клонируем репозиторий
```bash
git clone https://github.com/Bulavackii/Ru-CMS.git
cd Ru-CMS
```

### 📦 Устанавливаем зависимости PHP
```bash
composer install
```

### 📦 Устанавливаем JS-зависимости (React + Tailwind + Vite)
```bash
npm install
```

### ⚙️ Создаём .env-файл и настраиваем подключение к БД
```bash
cp .env.example .env
php artisan key:generate
```


## 🧰 2. Настройка прав и линков

### 🔗 Символическая ссылка для публичных файлов (обложки, загрузки и т.п.)
```bash
php artisan storage:link
```

### 🛠 Убедись, что в php.ini заданы лимиты:
```
upload_max_filesize = 100M
post_max_size = 100M
```


## 📂 3. Миграции и сиды

### 🛠 Основные миграции ядра и начальных таблиц
```bash
php artisan migrate
```

### 🌱 Посев пользователей и прочих данных (если есть)
```bash
php artisan db:seed
```

### ▶️ Или по конкретным путям, если требуется:
```bash
php artisan migrate --path=modules/Slideshow/Database/Migrations
php artisan migrate --path=modules/Menu/Database/Migrations
php artisan migrate --path=database/migrations/2025_05_012_100000_create_file_categories_table.php
php artisan migrate --path=database/migrations/2025_05_12_100100_create_files_table.php
```


## ⭐ 4. Альтернатива: одна команда для миграции и посева

### 🛎️ Выполняет миграции и сиды за один шаг :
```bash
php artisan migrate --seed
```

php artisan key:generate

<!-- Регистрирует все модули в системе из папки modules -->
php artisan modules:sync 

<!-- Автогенерация sitemap.xml в папку public -->
php artisan sitemap:generate

<!-- Автогенерация robots.txt в папку public -->
php artisan robots:generate
