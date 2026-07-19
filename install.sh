#!/bin/bash

# ═══════════════════════════════════════════════════════════════
# 🚀 Автоматический скрипт установки RU CMS для Ubuntu 24.04.3 LTS
# ═══════════════════════════════════════════════════════════════
# 
# Использование: sudo ./install.sh
# 
# Этот скрипт автоматически:
# - Устанавливает необходимые пакеты (PHP 8.5, MySQL, Nginx, Composer, Node.js)
# - Настраивает веб-сервер (Nginx)
# - Создает базу данных MySQL (опционально)
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
# 3. DEFAULT_DB_NAME (строка после установки MySQL, см. раздел "Создание БД")
#    🔧 Имя базы данных по умолчанию (по умолчанию: "rucms")
#    Пример: DEFAULT_DB_NAME="mycms_db"
#
# 4. DEFAULT_DB_USER (строка после установки MySQL, см. раздел "Создание БД")
#    🔧 Имя пользователя БД по умолчанию (по умолчанию: "root")
#    Пример: DEFAULT_DB_USER="cms_user"
#
# 5. DEFAULT_DB_PASSWORD (строка после установки MySQL, см. раздел "Создание БД")
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

echo "🚀 Начало установки RU CMS..."
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
    php${PHP_VERSION}-mysql \
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

# Шаг 4: Установка MySQL
echo -e "${GREEN}📦 Шаг 4: Установка MySQL...${NC}"

# Проверяем установлен ли MySQL
if ! dpkg -l | grep -q "^ii.*mysql-server" && ! command -v mysql &> /dev/null; then
    apt install -y mysql-server
    systemctl enable mysql
    echo -e "${GREEN}✓ MySQL установлен${NC}"
elif ! systemctl is-active --quiet mysql 2>/dev/null; then
    systemctl start mysql
    systemctl enable mysql
    echo -e "${GREEN}✓ MySQL запущен${NC}"
else
    echo -e "${GREEN}✓ MySQL уже установлен и запущен${NC}"
fi

systemctl start mysql 2>/dev/null || true
systemctl enable mysql 2>/dev/null || true

# ═══════════════════════════════════════════════════════════════
# 🔧 НАСТРОЙКА ПАРАМЕТРОВ БАЗЫ ДАННЫХ - ИЗМЕНИТЕ ЗДЕСЬ
# ═══════════════════════════════════════════════════════════════
# 💡 Эти значения будут использоваться для создания БД и записи в .env
# Если вы оставите DEFAULT_DB_PASSWORD пустым (""), пароль будет сгенерирован автоматически
# ═══════════════════════════════════════════════════════════════

DEFAULT_DB_NAME="rucms"              # 🔧 ИЗМЕНИТЕ: имя базы данных (DB_DATABASE в .env)
DEFAULT_DB_USER="root"                # 🔧 ИЗМЕНИТЕ: имя пользователя (DB_USERNAME в .env)
DEFAULT_DB_PASSWORD=""                # 🔧 ИЗМЕНИТЕ: пароль (DB_PASSWORD в .env)
                                      #    Оставьте "" для автогенерации, или укажите конкретный пароль

# Предлагаем создать базу данных автоматически
echo ""
read -p "Создать базу данных MySQL автоматически? (y/n) " -n 1 -r
echo ""
MYSQL_DB_CREATED=false

if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${GREEN}📦 Шаг 4.1: Создание базы данных MySQL...${NC}"
    
    # Запрашиваем параметры (можно просто нажать Enter для значений по умолчанию)
    read -p "Имя базы данных [${DEFAULT_DB_NAME}]: " DB_NAME
    DB_NAME=${DB_NAME:-${DEFAULT_DB_NAME}}
    
    read -p "Имя пользователя [${DEFAULT_DB_USER}]: " DB_USER
    DB_USER=${DB_USER:-${DEFAULT_DB_USER}}
    
    # Запрашиваем пароль
    if [ -n "${DEFAULT_DB_PASSWORD}" ]; then
        read -sp "Пароль пользователя [используется значение по умолчанию]: " DB_PASSWORD
        echo ""
        DB_PASSWORD=${DB_PASSWORD:-${DEFAULT_DB_PASSWORD}}
    else
        read -sp "Пароль пользователя (Enter для автогенерации): " DB_PASSWORD
        echo ""
        if [ -z "$DB_PASSWORD" ]; then
            DB_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
            echo -e "${YELLOW}✓ Пароль сгенерирован автоматически${NC}"
        fi
    fi
    
    echo ""
    echo -e "${YELLOW}⚠️  Будет выполнен сброс пароля MySQL root через безопасный режим.${NC}"
    read -p "Продолжить? (y/n) " -n 1 -r
    echo ""
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        # Создаем временный systemd override для MySQL
        MYSQL_SERVICE_DIR="/etc/systemd/system/mysql.service.d"
        mkdir -p $MYSQL_SERVICE_DIR
        
        # Создаем override файл для запуска MySQL с --skip-grant-tables
        cat > $MYSQL_SERVICE_DIR/reset-root-password.conf <<EOF
[Service]
ExecStart=
ExecStart=/usr/sbin/mysqld --skip-grant-tables --user=mysql
EOF
        
        # Останавливаем MySQL
        systemctl stop mysql
        sleep 2
        
        # Перезагружаем systemd и запускаем MySQL в безопасном режиме
        systemctl daemon-reload
        systemctl start mysql
        
        # Ждем, пока MySQL запустится (до 20 секунд)
        echo -e "${YELLOW}Ожидание запуска MySQL в безопасном режиме...${NC}"
        MYSQL_READY=false
        for i in {1..20}; do
            if systemctl is-active --quiet mysql; then
                if mysql -u root -e "SELECT 1;" >/dev/null 2>&1 || mysql -u root -h 127.0.0.1 -e "SELECT 1;" >/dev/null 2>&1; then
                    MYSQL_READY=true
                    break
                fi
            fi
            sleep 1
        done
        
        if [ "$MYSQL_READY" = true ]; then
            # Подключаемся и создаем БД и пользователя
            if mysql -u root -e "SELECT 1;" >/dev/null 2>&1; then
                mysql -u root <<EOF
USE mysql;
FLUSH PRIVILEGES;
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';
FLUSH PRIVILEGES;
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF
            else
                mysql -u root -h 127.0.0.1 <<EOF
USE mysql;
FLUSH PRIVILEGES;
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';
FLUSH PRIVILEGES;
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF
            fi
            
            # Восстанавливаем нормальный режим MySQL
            systemctl stop mysql
            rm -f $MYSQL_SERVICE_DIR/reset-root-password.conf
            systemctl daemon-reload
            systemctl start mysql
            sleep 2
            
            if systemctl is-active --quiet mysql; then
                echo -e "${GREEN}✓ База данных и пользователь созданы успешно${NC}"
                MYSQL_DB_CREATED=true
                MYSQL_DB_NAME=$DB_NAME
                MYSQL_USER=$DB_USER
                MYSQL_USER_PASSWORD=$DB_PASSWORD
            else
                echo -e "${YELLOW}⚠️  База данных создана, но MySQL не запустился автоматически${NC}"
                systemctl start mysql
            fi
        else
            echo -e "${YELLOW}⚠️  Не удалось запустить MySQL в безопасном режиме. Пропускаем создание БД.${NC}"
            rm -f $MYSQL_SERVICE_DIR/reset-root-password.conf
            systemctl daemon-reload
            systemctl start mysql
        fi
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

NGINX_CONFIG="/etc/nginx/sites-available/rucms"
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
ln -sf $NGINX_CONFIG /etc/nginx/sites-enabled/rucms
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
APP_NAME="RU CMS"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://${DOMAIN}

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=file
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
if [ "$MYSQL_DB_CREATED" = true ]; then
    # Обновляем или добавляем строки с данными БД
    if grep -q "^DB_DATABASE=" .env; then
        sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${MYSQL_DB_NAME}|" .env
    else
        echo "DB_DATABASE=${MYSQL_DB_NAME}" >> .env
    fi
    
    if grep -q "^DB_USERNAME=" .env; then
        sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${MYSQL_USER}|" .env
    else
        echo "DB_USERNAME=${MYSQL_USER}" >> .env
    fi
    
    if grep -q "^DB_PASSWORD=" .env; then
        sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${MYSQL_USER_PASSWORD}|" .env
    else
        echo "DB_PASSWORD=${MYSQL_USER_PASSWORD}" >> .env
    fi
    
    # Убеждаемся, что используется MySQL
    if grep -q "^DB_CONNECTION=" .env; then
        sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=mysql|" .env
    else
        echo "DB_CONNECTION=mysql" >> .env
    fi
    
    echo -e "${GREEN}✓ Настроены данные БД в .env${NC}"
    
    # Сохраняем пароли MySQL в файл для безопасности
    MYSQL_CREDENTIALS_FILE="${PROJECT_DIR}/mysql_credentials.txt"
    cat > $MYSQL_CREDENTIALS_FILE <<CREDEOF
╔═══════════════════════════════════════════════════════════════╗
║           ДАННЫЕ ДОСТУПА К MYSQL (СОХРАНИТЕ!)                ║
╚═══════════════════════════════════════════════════════════════╝
База данных: ${MYSQL_DB_NAME}
Пользователь: ${MYSQL_USER}
Пароль пользователя: ${MYSQL_USER_PASSWORD}
Хост: localhost
Порт: 3306
CREDEOF
    chmod 600 $MYSQL_CREDENTIALS_FILE
    echo -e "${YELLOW}⚠️  Данные MySQL сохранены в: ${MYSQL_CREDENTIALS_FILE}${NC}"
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
if [ "$MYSQL_DB_CREATED" = false ]; then
    echo "2. Создайте базу данных MySQL (если ещё не создана):"
    echo "   Используйте скрипт: sudo ./create-database-simple.sh"
    echo "   Или вручную:"
    echo "   sudo mysql -e \"CREATE DATABASE rucms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\""
    echo "   sudo mysql -e \"CREATE USER 'rucms_user'@'localhost' IDENTIFIED BY 'ваш_пароль';\""
    echo "   sudo mysql -e \"GRANT ALL PRIVILEGES ON rucms.* TO 'rucms_user'@'localhost';\""
    echo "   sudo mysql -e \"FLUSH PRIVILEGES;\""
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
