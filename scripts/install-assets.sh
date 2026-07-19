#!/bin/bash

# Скрипт для установки локальных ресурсов (CSS, JS, шрифты, иконки)
# Использование: ./scripts/install-assets.sh

set -e

# Цвета для вывода
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Базовые директории
PUBLIC_DIR="public"
ASSETS_DIR="${PUBLIC_DIR}/assets"
CSS_DIR="${ASSETS_DIR}/css"
JS_DIR="${ASSETS_DIR}/js"
FONTS_DIR="${ASSETS_DIR}/fonts"
ICONS_DIR="${ASSETS_DIR}/icons"
FA_DIR="${CSS_DIR}/font-awesome"
FA_WEBFONTS_DIR="${FA_DIR}/webfonts"

echo -e "${GREEN}🚀 Начинаем установку локальных ресурсов...${NC}"

# Создание структуры директорий
echo -e "${YELLOW}📁 Создание структуры директорий...${NC}"
mkdir -p "${CSS_DIR}"
mkdir -p "${JS_DIR}"
mkdir -p "${FONTS_DIR}"
mkdir -p "${ICONS_DIR}"
mkdir -p "${FA_DIR}"
mkdir -p "${FA_WEBFONTS_DIR}"

# Функция для скачивания файла
download_file() {
    local url=$1
    local output=$2
    local description=$3
    
    echo -e "${YELLOW}⬇️  Скачивание: ${description}...${NC}"
    
    if command -v curl &> /dev/null; then
        curl -L -o "${output}" "${url}" || {
            echo -e "${RED}❌ Ошибка при скачивании ${description}${NC}"
            return 1
        }
    elif command -v wget &> /dev/null; then
        wget -O "${output}" "${url}" || {
            echo -e "${RED}❌ Ошибка при скачивании ${description}${NC}"
            return 1
        }
    else
        echo -e "${RED}❌ Не найдены curl или wget. Установите один из них.${NC}"
        return 1
    fi
    
    echo -e "${GREEN}✅ ${description} установлен${NC}"
}

# ===== CSS =====
echo -e "\n${GREEN}📦 Установка CSS библиотек...${NC}"

# Tailwind CSS 2.2.19
download_file \
    "https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" \
    "${CSS_DIR}/tailwind.min.css" \
    "Tailwind CSS"

# Prism.js
download_file \
    "https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism-tomorrow.min.css" \
    "${CSS_DIR}/prism-tomorrow.min.css" \
    "Prism.js Theme"

# Swiper
download_file \
    "https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" \
    "${CSS_DIR}/swiper-bundle.min.css" \
    "Swiper CSS"

# Bootstrap Icons
download_file \
    "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" \
    "${CSS_DIR}/bootstrap-icons.css" \
    "Bootstrap Icons CSS"

# Remix Icons
download_file \
    "https://cdn.jsdelivr.net/npm/remixicon@3.7.0/fonts/remixicon.css" \
    "${CSS_DIR}/remixicon.css" \
    "Remix Icons CSS"

# Tabler Icons
download_file \
    "https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/dist/tabler-icons.min.css" \
    "${CSS_DIR}/tabler-icons.min.css" \
    "Tabler Icons CSS"

# Font Awesome
download_file \
    "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" \
    "${FA_DIR}/all.min.css" \
    "Font Awesome CSS"

# ===== JavaScript =====
echo -e "\n${GREEN}📦 Установка JavaScript библиотек...${NC}"

# Alpine.js
download_file \
    "https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js" \
    "${JS_DIR}/alpine.min.js" \
    "Alpine.js"

# Lucide
download_file \
    "https://cdn.jsdelivr.net/npm/lucide@0.344.0/dist/umd/lucide.min.js" \
    "${JS_DIR}/lucide.min.js" \
    "Lucide Icons"

# Prism.js
download_file \
    "https://cdn.jsdelivr.net/npm/prismjs@1.29.0/prism.min.js" \
    "${JS_DIR}/prism.min.js" \
    "Prism.js Core"

# Prism.js Components
download_file \
    "https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-markup.min.js" \
    "${JS_DIR}/prism-markup.min.js" \
    "Prism.js Markup"

download_file \
    "https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-html.min.js" \
    "${JS_DIR}/prism-html.min.js" \
    "Prism.js HTML"

download_file \
    "https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-css.min.js" \
    "${JS_DIR}/prism-css.min.js" \
    "Prism.js CSS"

download_file \
    "https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-javascript.min.js" \
    "${JS_DIR}/prism-javascript.min.js" \
    "Prism.js JavaScript"

download_file \
    "https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-php.min.js" \
    "${JS_DIR}/prism-php.min.js" \
    "Prism.js PHP"

# Swiper JS
download_file \
    "https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" \
    "${JS_DIR}/swiper-bundle.min.js" \
    "Swiper JS"

# ===== Шрифты иконок =====
echo -e "\n${GREEN}📦 Установка шрифтов иконок...${NC}"

# Bootstrap Icons Font
download_file \
    "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff2" \
    "${ICONS_DIR}/bootstrap-icons.woff2" \
    "Bootstrap Icons Font"

# Remix Icons Font
download_file \
    "https://cdn.jsdelivr.net/npm/remixicon@3.7.0/fonts/remixicon.woff2" \
    "${ICONS_DIR}/remixicon.woff2" \
    "Remix Icons Font"

# Tabler Icons Font
download_file \
    "https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/dist/tabler-icons.woff2" \
    "${ICONS_DIR}/tabler-icons.woff2" \
    "Tabler Icons Font"

# Font Awesome Webfonts
echo -e "${YELLOW}⬇️  Скачивание Font Awesome webfonts...${NC}"
download_file \
    "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/webfonts/fa-solid-900.woff2" \
    "${FA_WEBFONTS_DIR}/fa-solid-900.woff2" \
    "Font Awesome Solid"

download_file \
    "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/webfonts/fa-regular-400.woff2" \
    "${FA_WEBFONTS_DIR}/fa-regular-400.woff2" \
    "Font Awesome Regular"

download_file \
    "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/webfonts/fa-brands-400.woff2" \
    "${FA_WEBFONTS_DIR}/fa-brands-400.woff2" \
    "Font Awesome Brands"

# Обновление путей в CSS файлах иконок
echo -e "\n${YELLOW}🔧 Обновление путей к шрифтам в CSS файлах...${NC}"

# Bootstrap Icons
if [ -f "${CSS_DIR}/bootstrap-icons.css" ]; then
    sed -i.bak "s|url(.*bootstrap-icons.woff2)|url(../icons/bootstrap-icons.woff2)|g" "${CSS_DIR}/bootstrap-icons.css"
    rm -f "${CSS_DIR}/bootstrap-icons.css.bak"
fi

# Remix Icons
if [ -f "${CSS_DIR}/remixicon.css" ]; then
    sed -i.bak "s|url(.*remixicon.woff2)|url(../icons/remixicon.woff2)|g" "${CSS_DIR}/remixicon.css"
    rm -f "${CSS_DIR}/remixicon.css.bak"
fi

# Tabler Icons
if [ -f "${CSS_DIR}/tabler-icons.min.css" ]; then
    sed -i.bak "s|url(.*tabler-icons.woff2)|url(../icons/tabler-icons.woff2)|g" "${CSS_DIR}/tabler-icons.min.css"
    rm -f "${CSS_DIR}/tabler-icons.min.css.bak"
fi

# Font Awesome
if [ -f "${FA_DIR}/all.min.css" ]; then
    sed -i.bak "s|url(.*webfonts/|url(webfonts/|g" "${FA_DIR}/all.min.css"
    rm -f "${FA_DIR}/all.min.css.bak"
fi

echo -e "\n${GREEN}✅ Установка завершена!${NC}"
echo -e "${GREEN}📁 Все ресурсы находятся в: ${ASSETS_DIR}${NC}"
echo -e "\n${YELLOW}⚠️  Следующие шаги:${NC}"
echo -e "1. Обновите views, заменив CDN ссылки на функции local_css() и local_js()"
echo -e "2. Проверьте работу сайта"
echo -e "3. Настройте кэширование статических файлов в веб-сервере"





