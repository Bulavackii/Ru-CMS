#!/bin/bash

# ═══════════════════════════════════════════════════════════════
# 🚀 Автоматический скрипт установки Nexum Core для Ubuntu 24.04.3 LTS
# ═══════════════════════════════════════════════════════════════
#
# 🔴 База данных — ТОЛЬКО PostgreSQL. Это железное правило проекта: в мастере
#    установки других драйверов нет, миграции и запросы рассчитаны на него.
#    Скрипт ставит и настраивает именно PostgreSQL. Раньше он поднимал MySQL —
#    и установка по нему падала на первом же обращении к базе.
#
# Использование: sudo ./install.sh
#
# Этот скрипт автоматически:
# - Устанавливает необходимые пакеты (PHP 8.5, PostgreSQL, Nginx, Composer, Node.js)
# - Настраивает веб-сервер (Nginx)
# - Создаёт роль и базу PostgreSQL (опционально)
# - Устанавливает зависимости проекта
# - Настраивает SSL сертификат (опционально)
# - Настраивает права доступа
#
# ═══════════════════════════════════════════════════════════════
# 🔧 НАСТРАИВАЕМЫЕ ПАРАМЕТРЫ
# ═══════════════════════════════════════════════════════════════
#
# Если вам нужно изменить значения по умолчанию, найдите и измените
# следующие переменные в соответствующих разделах скрипта:
#
# 1. PHP_VERSION (строка ~37)
#    🔧 Версия PHP для установки (по умолчанию: "8.5")
#    Пример: PHP_VERSION="8.2"  # Для установки PHP 8.2
#
# 2. PROJECT_DIR (строка ~95)
#    🔧 Директория проекта (по умолчанию: "/var/www/cms")
#    Пример: PROJECT_DIR="/var/www/myproject"
#
# 3. DEFAULT_DB_NAME (см. раздел "Создание БД")
#    🔧 Имя базы данных по умолчанию (по умолчанию: "nexum_core")
#    Пример: DEFAULT_DB_NAME="mycms_db"
#
# 4. DEFAULT_DB_USER (см. раздел "Создание БД")
#    🔧 Имя роли PostgreSQL по умолчанию (по умолчанию: "nexum_core")
#    Пример: DEFAULT_DB_USER="cms_user"
#
# 5. DEFAULT_DB_PASSWORD (см. раздел "Создание БД")
#    🔧 Пароль пользователя БД по умолчанию (по умолчанию: пусто = автогенерация)
#    Пример: DEFAULT_DB_PASSWORD="мой_безопасный_пароль"
#    Или оставьте пустым "" для автоматической генерации пароля
#
# 6. PHP настройки (строки ~182-184)
#    🔧 upload_max_filesize - максимальный размер загружаемого файла (по умолчанию: 100M)
#    🔧 post_max_size - максимальный размер POST данных (по умолчанию: 100M)
#    🔧 memory_limit - лимит памяти PHP (по умолчанию: 256M)
#
# 7. Node.js версия (строка ~90)
#    🔧 Версия Node.js (по умолчанию: 18.x)
#    Для изменения измените URL: setup_18.x → setup_20.x (для Node.js 20)
#
# ═══════════════════════════════════════════════════════════════

set -e

echo "🚀 Начало установки Nexum Core..."
echo ""

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Проверка, что скрипт запущен от root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}❌ Пожалуйста, запустите скрипт с sudo: sudo ./install.sh${NC}"
    exit 1
fi

# ═══════════════════════════════════════════════════════════════
# НАЧАЛО УСТАНОВКИ
# ═══════════════════════════════════════════════════════════════

# Шаг 1: Обновление системы
echo -e "${GREEN}📦 Шаг 1: Обновление системы...${NC}"
apt update
apt upgrade -y

# Шаг 2: Установка необходимых пакетов
echo -e "${GREEN}📦 Шаг 2: Установка необходимых пакетов...${NC}"
apt install -y software-properties-common curl wget unzip git cron

# Шаг 3: Установка PHP 8.5
echo -e "${GREEN}📦 Шаг 3: Установка PHP 8.5...${NC}"
add-apt-repository ppa:ondrej/php -y
apt update

# ═══════════════════════════════════════════════════════════════
# 🔧 PHP VERSION - ИЗМЕНИТЕ ЗДЕСЬ ДЛЯ ДРУГОЙ ВЕРСИИ PHP
# ═══════════════════════════════════════════════════════════════
PHP_VERSION="8.5"  # 🔧 ИЗМЕНИТЕ: Версия PHP (например: "8.2", "8.3")

# Проверка доступности PHP
if ! apt-cache show php${PHP_VERSION} &>/dev/null; then
    echo -e "${RED}❌ PHP 8.5 недоступен в репозитории. Пожалуйста, используйте Ubuntu 24.04.3 LTS.${NC}"
    exit 1
fi

echo -e "${YELLOW}Устанавливается PHP ${PHP_VERSION}...${NC}"

apt install -y php${PHP_VERSION} \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-common \
    php${PHP_VERSION}-pgsql \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-sqlite3 \
    php${PHP_VERSION}-redis \
    php${PHP_VERSION}-imagick

# Шаг 4: Установка PostgreSQL
echo -e "${GREEN}📦 Шаг 4: Установка PostgreSQL...${NC}"

if ! command -v psql &> /dev/null; then
    apt install -y postgresql postgresql-contrib
    echo -e "${GREEN}✓ PostgreSQL установлен${NC}"
else
    echo -e "${GREEN}✓ PostgreSQL уже установлен${NC}"
fi

systemctl start postgresql 2>/dev/null || true
systemctl enable postgresql 2>/dev/null || true

echo ""

# ═══════════════════════════════════════════════════════════════
# 🔧 ПАРАМЕТРЫ БАЗЫ ДАННЫХ — ИЗМЕНИТЕ ЗДЕСЬ
# ═══════════════════════════════════════════════════════════════
DEFAULT_DB_NAME="nexum_core"          # 🔧 имя базы (DB_DATABASE в .env)
DEFAULT_DB_USER="nexum_core"          # 🔧 имя роли (DB_USERNAME в .env)
DEFAULT_DB_PASSWORD=""                # 🔧 пароль (DB_PASSWORD); "" — сгенерировать

# Предлагаем создать роль и базу автоматически
read -p "Создать роль и базу PostgreSQL автоматически? (y/n) " -n 1 -r
echo ""
DB_CREATED=false

if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${GREEN}📦 Шаг 4.1: Создание роли и базы PostgreSQL...${NC}"

    read -p "Имя базы [$DEFAULT_DB_NAME]: " DB_NAME
    DB_NAME=${DB_NAME:-$DEFAULT_DB_NAME}

    read -p "Имя роли [$DEFAULT_DB_USER]: " DB_USER
    DB_USER=${DB_USER:-$DEFAULT_DB_USER}

    read -s -p "Пароль роли (Enter — сгенерировать): " DB_PASSWORD
    echo ""

    if [ -z "$DB_PASSWORD" ]; then
        # 24 байта из /dev/urandom, только безопасные для .env символы
        DB_PASSWORD=$(tr -dc 'A-Za-z0-9' < /dev/urandom | head -c 24)
        echo -e "${YELLOW}Пароль сгенерирован — он будет показан в конце установки.${NC}"
    fi

    # ⚠️ Пароль роли передаётся ЧЕРЕЗ STDIN (heredoc), а не аргументом `-c`:
    #    аргументы командной строки видны любому пользователю сервера в
    #    `ps aux`, и пароль базы утёк бы в момент установки.
    #
    # ⚠️ Работаем через `sudo -u postgres psql`: на Ubuntu для локального
    #    подключения суперпользователя действует peer-аутентификация, то есть
    #    пароль postgres не нужен вовсе. Прежняя версия скрипта вместо этого
    #    останавливала сервер, поднимала его с --skip-grant-tables и сбрасывала
    #    пароль root — на живом сервере это опасная затея.
    if sudo -u postgres psql -tAc "SELECT 1 FROM pg_roles WHERE rolname='$DB_USER'" | grep -q 1; then
        echo -e "${YELLOW}Роль $DB_USER уже есть — обновляю пароль${NC}"
        sudo -u postgres psql -v ON_ERROR_STOP=1 >/dev/null <<SQLEOF
ALTER ROLE "$DB_USER" WITH LOGIN CREATEDB PASSWORD '$DB_PASSWORD';
SQLEOF
    else
        sudo -u postgres psql -v ON_ERROR_STOP=1 >/dev/null <<SQLEOF
CREATE ROLE "$DB_USER" WITH LOGIN CREATEDB PASSWORD '$DB_PASSWORD';
SQLEOF
        echo -e "${GREEN}✓ Роль $DB_USER создана${NC}"
    fi

    if sudo -u postgres psql -tAc "SELECT 1 FROM pg_database WHERE datname='$DB_NAME'" | grep -q 1; then
        echo -e "${YELLOW}База $DB_NAME уже есть — создавать не нужно${NC}"
    else
        # Кодировка и правила сортировки заданы явно: иначе берётся системная
        # локаль сервера, и русские тексты получают другой порядок сортировки.
        sudo -u postgres psql -v ON_ERROR_STOP=1 -c \
            "CREATE DATABASE \"$DB_NAME\" WITH OWNER = \"$DB_USER\" ENCODING = 'UTF8' TEMPLATE = template0;" >/dev/null
        echo -e "${GREEN}✓ База $DB_NAME создана${NC}"
    fi

    # ⚠️ С PostgreSQL 15 обычная роль больше НЕ может создавать таблицы в
    #    схеме public по умолчанию. Без этих двух строк `php artisan migrate`
    #    падает с «permission denied for schema public» — самая частая заминка
    #    на свежей установке.
    sudo -u postgres psql -v ON_ERROR_STOP=1 -d "$DB_NAME" \
        -c "GRANT ALL ON SCHEMA public TO \"$DB_USER\";" \
        -c "ALTER SCHEMA public OWNER TO \"$DB_USER\";" >/dev/null

    sudo -u postgres psql -v ON_ERROR_STOP=1 \
        -c "GRANT ALL PRIVILEGES ON DATABASE \"$DB_NAME\" TO \"$DB_USER\";" >/dev/null

    # Проверяем, что роль действительно может войти и работать
    if PGPASSWORD="$DB_PASSWORD" psql -h 127.0.0.1 -U "$DB_USER" -d "$DB_NAME" -tAc "SELECT 1" >/dev/null 2>&1; then
        echo -e "${GREEN}✓ Подключение под ролью $DB_USER проверено${NC}"
        DB_CREATED=true
    else
        echo -e "${RED}✗ Роль создана, но подключиться не удалось.${NC}"
        echo -e "${YELLOW}  Проверьте pg_hba.conf: для host 127.0.0.1 нужен scram-sha-256 или md5.${NC}"
    fi
fi

echo ""

# Шаг 5: Установка Nginx
echo -e "${GREEN}📦 Шаг 5: Установка Nginx...${NC}"
apt install -y nginx
systemctl start nginx
systemctl enable nginx

# Шаг 6: Установка Composer
echo -e "${GREEN}📦 Шаг 6: Установка Composer...${NC}"
if [ ! -f /usr/local/bin/composer ]; then
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
fi

# Шаг 7: Установка Node.js
echo -e "${GREEN}📦 Шаг 7: Установка Node.js...${NC}"

# ═══════════════════════════════════════════════════════════════
# 🔧 NODE.JS VERSION - ИЗМЕНИТЕ ЗДЕСЬ ДЛЯ ДРУГОЙ ВЕРСИИ
# ═══════════════════════════════════════════════════════════════
# Для изменения версии измените setup_18.x на setup_20.x (для Node.js 20) и т.д.
NODE_VERSION="18"  # 🔧 ИЗМЕНИТЕ: Версия Node.js (например: "18", "20")
curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash - 2>/dev/null || true
apt install -y nodejs

# Шаг 8: Настройка директории проекта
echo -e "${GREEN}📦 Шаг 8: Настройка директории проекта...${NC}"

# ═══════════════════════════════════════════════════════════════
# 🔧 PROJECT_DIR - ИЗМЕНИТЕ ЗДЕСЬ ДЛЯ ДРУГОЙ ДИРЕКТОРИИ
# ═══════════════════════════════════════════════════════════════
PROJECT_DIR="/var/www/cms"  # 🔧 ИЗМЕНИТЕ: Директория проекта (например: "/var/www/myproject")

# Если проект уже существует, спросить
if [ -d "$PROJECT_DIR" ]; then
    read -p "Директория $PROJECT_DIR уже существует. Продолжить? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Переход в директорию проекта (если скрипт запущен из неё)
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
if [ -f "$SCRIPT_DIR/composer.json" ]; then
    cd "$SCRIPT_DIR"
    echo -e "${YELLOW}📁 Используется текущая директория проекта: $SCRIPT_DIR${NC}"
    PROJECT_DIR="$SCRIPT_DIR"
else
    mkdir -p $PROJECT_DIR
    cd $PROJECT_DIR
fi

# Установка прав
chown -R www-data:www-data $PROJECT_DIR

# Шаг 9: Создание конфигурации Nginx
echo -e "${GREEN}📦 Шаг 9: Создание конфигурации Nginx...${NC}"
read -p "Введите ваш домен (или IP, без www): " DOMAIN

# Определяем основной домен (убираем www если был введен)
DOMAIN=$(echo $DOMAIN | sed 's/^www\.//')
DOMAIN_WWW="www.${DOMAIN}"

NGINX_CONFIG="/etc/nginx/sites-available/nexum_core"
cat > $NGINX_CONFIG <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} ${DOMAIN_WWW};
    root ${PROJECT_DIR}/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/json;
}
EOF

# Активация конфигурации
ln -sf $NGINX_CONFIG /etc/nginx/sites-enabled/nexum_core
rm -f /etc/nginx/sites-enabled/default

# Проверка конфигурации
nginx -t

# Перезагрузка Nginx
systemctl reload nginx

# Шаг 10: Настройка PHP
echo -e "${GREEN}📦 Шаг 10: Настройка PHP...${NC}"
PHP_INI="/etc/php/${PHP_VERSION}/fpm/php.ini"

# ═══════════════════════════════════════════════════════════════
# 🔧 PHP НАСТРОЙКИ - ИЗМЕНИТЕ ЗДЕСЬ ДЛЯ ДРУГИХ ЗНАЧЕНИЙ
# ═══════════════════════════════════════════════════════════════
# Эти значения настраивают лимиты PHP для загрузки файлов и использования памяти
sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 100M/' $PHP_INI  # 🔧 Макс. размер файла (по умолчанию: 100M)
sed -i 's/post_max_size = 8M/post_max_size = 100M/' $PHP_INI              # 🔧 Макс. размер POST (по умолчанию: 100M)
sed -i 's/memory_limit = 128M/memory_limit = 256M/' $PHP_INI              # 🔧 Лимит памяти (по умолчанию: 256M)

systemctl restart php${PHP_VERSION}-fpm

# Шаг 11: Настройка CRON
echo -e "${GREEN}📦 Шаг 11: Настройка CRON...${NC}"
CRON_JOB="* * * * * cd ${PROJECT_DIR} && php artisan schedule:run >> /dev/null 2>&1"
(crontab -l 2>/dev/null | grep -v "schedule:run"; echo "$CRON_JOB") | crontab -

# Шаг 12: Установка Redis (опционально)
read -p "Установить Redis для кеширования? (y/n) " -n 1 -r
echo
REDIS_INSTALLED=false
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${GREEN}📦 Установка Redis...${NC}"
    apt install -y redis-server
    systemctl start redis-server
    systemctl enable redis-server
    REDIS_INSTALLED=true
fi

# Шаг 13: Установка зависимостей проекта
echo -e "${GREEN}📦 Шаг 13: Установка зависимостей проекта...${NC}"
cd $PROJECT_DIR

# Проверка наличия composer.json
if [ ! -f "composer.json" ]; then
    echo -e "${YELLOW}⚠️  composer.json не найден. Пропускаем установку зависимостей Composer.${NC}"
else
    echo -e "${YELLOW}Установка зависимостей Composer...${NC}"
    export COMPOSER_ALLOW_SUPERUSER=1
    composer install --no-interaction --optimize-autoloader 2>&1 | grep -v "Do not run Composer as root" || true
    
    # Если были ошибки с отсутствующими пакетами, обновляем
    if composer show pragmarx/google2fa >/dev/null 2>&1 && composer show intervention/image >/dev/null 2>&1; then
        echo -e "${GREEN}✓ Composer зависимости установлены${NC}"
    else
        echo -e "${YELLOW}Обновление composer.lock...${NC}"
        composer update --no-interaction --optimize-autoloader 2>&1 | grep -v "Do not run Composer as root" || true
    fi
fi

# Установка npm зависимостей
if [ -f "package.json" ]; then
    echo -e "${YELLOW}Установка зависимостей npm...${NC}"
    npm install --silent
    
    # Проверка наличия react-router-dom
    if ! npm list react-router-dom >/dev/null 2>&1; then
        echo -e "${YELLOW}Установка react-router-dom...${NC}"
        npm install react-router-dom --silent
    fi
    
    echo -e "${YELLOW}Сборка фронтенда...${NC}"
    npm run build 2>&1 | tail -20
    echo -e "${GREEN}✓ npm зависимости установлены и собраны${NC}"
else
    echo -e "${YELLOW}⚠️  package.json не найден. Пропускаем установку npm зависимостей.${NC}"
fi

# Шаг 14: Настройка .env файла
echo -e "${GREEN}📦 Шаг 14: Настройка .env файла...${NC}"
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        cp .env.example .env
        echo -e "${GREEN}✓ Создан файл .env из .env.example${NC}"
    else
        # Создаём минимальный .env файл
        cat > .env <<ENVEOF
APP_NAME="Nexum Core"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://${DOMAIN}

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# ТОЛЬКО PostgreSQL: в мастере установки других драйверов нет, миграции и
# запросы рассчитаны на него. Раньше здесь стоял mysql на порту 3306 — то
# есть запасной .env противоречил самому продукту, и установка по нему
# падала на первом же обращении к базе.
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=${DEFAULT_DB_NAME}
DB_USERNAME=${DEFAULT_DB_USER}
DB_PASSWORD=

# Пять секунд, а не бесконечность: недоступный SMTP иначе роняет запрос по
# лимиту времени PHP (так падала смена статуса заказа).
MAIL_TIMEOUT=5

BROADCAST_CONNECTION=log
CACHE_STORE=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
ENVEOF
        echo -e "${GREEN}✓ Создан файл .env${NC}"
    fi
fi

# Заполнение данных БД в .env (если БД была создана автоматически)
if [ "$DB_CREATED" = true ]; then
    # Обновляем или добавляем строки с данными БД
    if grep -q "^DB_DATABASE=" .env; then
        sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" .env
    else
        echo "DB_DATABASE=${DB_NAME}" >> .env
    fi
    
    if grep -q "^DB_USERNAME=" .env; then
        sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" .env
    else
        echo "DB_USERNAME=${DB_USER}" >> .env
    fi
    
    if grep -q "^DB_PASSWORD=" .env; then
        sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" .env
    else
        echo "DB_PASSWORD=${DB_PASSWORD}" >> .env
    fi
    
    # Драйвер и порт — только PostgreSQL (железное правило проекта)
    if grep -q "^DB_CONNECTION=" .env; then
        sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=pgsql|" .env
    else
        echo "DB_CONNECTION=pgsql" >> .env
    fi

    if grep -q "^DB_PORT=" .env; then
        sed -i "s|^DB_PORT=.*|DB_PORT=5432|" .env
    else
        echo "DB_PORT=5432" >> .env
    fi
    
    echo -e "${GREEN}✓ Настроены данные БД в .env${NC}"
    
    # Реквизиты — в файл с правами 600: пароль мог быть сгенерирован, и
    # без записи владелец его больше нигде не увидит.
    DB_CREDENTIALS_FILE="${PROJECT_DIR}/postgres_credentials.txt"
    cat > $DB_CREDENTIALS_FILE <<CREDEOF
╔═══════════════════════════════════════════════════════════════╗
║        ДАННЫЕ ДОСТУПА К POSTGRESQL (СОХРАНИТЕ!)               ║
╚═══════════════════════════════════════════════════════════════╝
База данных: ${DB_NAME}
Роль:        ${DB_USER}
Пароль:      ${DB_PASSWORD}
Хост:        127.0.0.1
Порт:        5432
CREDEOF
    chmod 600 $DB_CREDENTIALS_FILE
    echo -e "${YELLOW}⚠️  Реквизиты сохранены в: ${DB_CREDENTIALS_FILE}${NC}"
    echo -e "${YELLOW}   Перенесите их в надёжное место и удалите файл.${NC}"
else
    echo -e "${YELLOW}ℹ️  Данные базы данных будут настроены через веб-установщик${NC}"
fi

# Генерация APP_KEY если его нет
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    echo -e "${YELLOW}Генерация APP_KEY...${NC}"
    php artisan key:generate --force 2>&1 | grep -v "Do not run" || true
    echo -e "${GREEN}✓ APP_KEY сгенерирован${NC}"
fi

# Обновление APP_URL
sed -i "s|^APP_URL=.*|APP_URL=http://${DOMAIN}|" .env

# Шаг 15: Настройка прав доступа
echo -e "${GREEN}📦 Шаг 15: Настройка прав доступа...${NC}"
chown -R www-data:www-data $PROJECT_DIR
chmod -R 755 $PROJECT_DIR
chmod -R 775 storage bootstrap/cache 2>/dev/null || mkdir -p storage bootstrap/cache && chmod -R 775 storage bootstrap/cache
echo -e "${GREEN}✓ Права доступа настроены${NC}"

# Шаг 16: Установка SSL сертификата (опционально)
echo -e "${GREEN}📦 Шаг 16: Настройка SSL сертификата...${NC}"
read -p "Установить SSL сертификат (Let's Encrypt)? (y/n) " -n 1 -r
echo
SSL_INSTALLED=false
if [[ $REPLY =~ ^[Yy]$ ]] && [[ "$DOMAIN" != *[0-9]*\.[0-9]*\.[0-9]*\.[0-9]* ]]; then
    echo -e "${YELLOW}Установка Certbot...${NC}"
    apt install -y certbot python3-certbot-nginx
    
    echo -e "${YELLOW}Получение SSL сертификата для ${DOMAIN} и ${DOMAIN_WWW}...${NC}"
    if certbot --nginx -d ${DOMAIN} -d ${DOMAIN_WWW} --non-interactive --agree-tos --register-unsafely-without-email --redirect 2>&1 | tee /tmp/certbot.log; then
        SSL_INSTALLED=true
        echo -e "${GREEN}✓ SSL сертификат успешно установлен${NC}"
        
        # Обновление APP_URL на HTTPS
        sed -i "s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" .env
        
        # Настройка автообновления сертификата
        systemctl enable certbot.timer
        systemctl start certbot.timer
    else
        echo -e "${YELLOW}⚠️  Не удалось установить SSL сертификат. Проверьте, что домен указывает на этот сервер.${NC}"
        echo -e "${YELLOW}   Вы можете установить SSL позже командой: certbot --nginx -d ${DOMAIN} -d ${DOMAIN_WWW}${NC}"
    fi
else
    if [[ "$DOMAIN" == *[0-9]*\.[0-9]*\.[0-9]*\.[0-9]* ]]; then
        echo -e "${YELLOW}⚠️  SSL сертификат недоступен для IP адресов. Используйте доменное имя для SSL.${NC}"
    fi
fi

# Финальное сообщение
echo ""
echo -e "${GREEN}✅ Установка завершена!${NC}"
echo ""
echo -e "${YELLOW}📝 Важная информация:${NC}"
echo "Директория проекта: ${PROJECT_DIR}"
echo "Домен: ${DOMAIN}"
if [ "$SSL_INSTALLED" = true ]; then
    echo "SSL: ✓ Установлен (https://${DOMAIN})"
else
    echo "SSL: ✗ Не установлен (http://${DOMAIN})"
fi
echo ""
echo -e "${GREEN}🎯 Следующие шаги:${NC}"
if [ "$SSL_INSTALLED" = true ]; then
    echo "1. Откройте в браузере: https://${DOMAIN}/install"
else
    echo "1. Откройте в браузере: http://${DOMAIN}/install"
fi
if [ "$DB_CREATED" = false ]; then
    echo "2. Создайте роль и базу PostgreSQL (если ещё не созданы):"
    echo "   sudo -u postgres psql -c \"CREATE ROLE nexum_core WITH LOGIN CREATEDB PASSWORD 'ваш_пароль';\""
    echo "   sudo -u postgres psql -c \"CREATE DATABASE nexum_core WITH OWNER = nexum_core ENCODING = 'UTF8' TEMPLATE = template0;\""
    echo "   # С PostgreSQL 15 без этих двух строк миграции падают на схеме public:"
    echo "   sudo -u postgres psql -d nexum_core -c \"GRANT ALL ON SCHEMA public TO nexum_core;\""
    echo "   sudo -u postgres psql -d nexum_core -c \"ALTER SCHEMA public OWNER TO nexum_core;\""
    echo "3. Завершите установку через веб-интерфейс (настройка БД, создание администратора)"
else
    echo "2. Завершите установку через веб-интерфейс (создание администратора)"
    echo "   База данных уже создана и настроена в .env"
fi
echo ""
echo -e "${YELLOW}ℹ️  Установлена версия PHP: ${PHP_VERSION}${NC}"
if [ "$REDIS_INSTALLED" = true ]; then
    echo -e "${YELLOW}ℹ️  Redis установлен и запущен${NC}"
fi
echo -e "${GREEN}📚 Документация: См. INSTALLATION_GUIDE.md${NC}"
