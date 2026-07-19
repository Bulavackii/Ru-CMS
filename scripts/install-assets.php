<?php

/**
 * Скрипт для установки локальных ресурсов (CSS, JS, шрифты, иконки)
 * PHP версия для Windows и кроссплатформенного использования
 * 
 * Использование: php scripts/install-assets.php
 */

// Цвета для консоли (Windows и Unix)
function colorize($text, $color = 'green') {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        return $text; // Windows не поддерживает ANSI цвета в стандартной консоли
    }
    
    $colors = [
        'green' => "\033[0;32m",
        'yellow' => "\033[1;33m",
        'red' => "\033[0;31m",
        'nc' => "\033[0m",
    ];
    
    return ($colors[$color] ?? '') . $text . $colors['nc'];
}

// Базовые директории
$publicDir = __DIR__ . '/../public';
$assetsDir = $publicDir . '/assets';
$cssDir = $assetsDir . '/css';
$jsDir = $assetsDir . '/js';
$fontsDir = $assetsDir . '/fonts';
$iconsDir = $assetsDir . '/icons';
$faDir = $cssDir . '/font-awesome';
$faWebfontsDir = $faDir . '/webfonts';

echo colorize("🚀 Начинаем установку локальных ресурсов...\n", 'green');

// Создание структуры директорий
echo colorize("📁 Создание структуры директорий...\n", 'yellow');
$dirs = [$cssDir, $jsDir, $fontsDir, $iconsDir, $faDir, $faWebfontsDir];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Функция для скачивания файла
function downloadFile($url, $output, $description) {
    echo colorize("⬇️  Скачивание: {$description}...\n", 'yellow');
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
            'timeout' => 30,
            'follow_location' => true,
        ],
    ]);
    
    $content = @file_get_contents($url, false, $context);
    
    if ($content === false) {
        echo colorize("❌ Ошибка при скачивании {$description}\n", 'red');
        return false;
    }
    
    $dir = dirname($output);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    file_put_contents($output, $content);
    echo colorize("✅ {$description} установлен\n", 'green');
    return true;
}

// Ресурсы для скачивания
$resources = [
    // CSS
    [
        'url' => 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css',
        'output' => $cssDir . '/tailwind.min.css',
        'description' => 'Tailwind CSS',
    ],
    [
        'url' => 'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism-tomorrow.min.css',
        'output' => $cssDir . '/prism-tomorrow.min.css',
        'description' => 'Prism.js Theme',
    ],
    [
        'url' => 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
        'output' => $cssDir . '/swiper-bundle.min.css',
        'description' => 'Swiper CSS',
    ],
    [
        'url' => 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css',
        'output' => $cssDir . '/bootstrap-icons.css',
        'description' => 'Bootstrap Icons CSS',
    ],
    [
        'url' => 'https://cdn.jsdelivr.net/npm/remixicon@3.7.0/fonts/remixicon.css',
        'output' => $cssDir . '/remixicon.css',
        'description' => 'Remix Icons CSS',
    ],
    [
        'url' => 'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/dist/tabler-icons.min.css',
        'output' => $cssDir . '/tabler-icons.min.css',
        'description' => 'Tabler Icons CSS',
    ],
    [
        'url' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css',
        'output' => $faDir . '/all.min.css',
        'description' => 'Font Awesome CSS',
    ],
    
    // JavaScript
    [
        'url' => 'https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js',
        'output' => $jsDir . '/alpine.min.js',
        'description' => 'Alpine.js',
    ],
    [
        'url' => 'https://cdn.jsdelivr.net/npm/lucide@0.344.0/dist/umd/lucide.min.js',
        'output' => $jsDir . '/lucide.min.js',
        'description' => 'Lucide Icons',
    ],
    [
        'url' => 'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/prism.min.js',
        'output' => $jsDir . '/prism.min.js',
        'description' => 'Prism.js Core',
    ],
    [
        'url' => 'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-markup.min.js',
        'output' => $jsDir . '/prism-markup.min.js',
        'description' => 'Prism.js Markup',
    ],
    [
        'url' => 'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-html.min.js',
        'output' => $jsDir . '/prism-html.min.js',
        'description' => 'Prism.js HTML',
    ],
    [
        'url' => 'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-css.min.js',
        'output' => $jsDir . '/prism-css.min.js',
        'description' => 'Prism.js CSS',
    ],
    [
        'url' => 'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-javascript.min.js',
        'output' => $jsDir . '/prism-javascript.min.js',
        'description' => 'Prism.js JavaScript',
    ],
    [
        'url' => 'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-php.min.js',
        'output' => $jsDir . '/prism-php.min.js',
        'description' => 'Prism.js PHP',
    ],
    [
        'url' => 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
        'output' => $jsDir . '/swiper-bundle.min.js',
        'description' => 'Swiper JS',
    ],
    
    // Шрифты иконок
    [
        'url' => 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff2',
        'output' => $iconsDir . '/bootstrap-icons.woff2',
        'description' => 'Bootstrap Icons Font',
    ],
    [
        'url' => 'https://cdn.jsdelivr.net/npm/remixicon@3.7.0/fonts/remixicon.woff2',
        'output' => $iconsDir . '/remixicon.woff2',
        'description' => 'Remix Icons Font',
    ],
    [
        'url' => 'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/dist/tabler-icons.woff2',
        'output' => $iconsDir . '/tabler-icons.woff2',
        'description' => 'Tabler Icons Font',
    ],
    [
        'url' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/webfonts/fa-solid-900.woff2',
        'output' => $faWebfontsDir . '/fa-solid-900.woff2',
        'description' => 'Font Awesome Solid',
    ],
    [
        'url' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/webfonts/fa-regular-400.woff2',
        'output' => $faWebfontsDir . '/fa-regular-400.woff2',
        'description' => 'Font Awesome Regular',
    ],
    [
        'url' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/webfonts/fa-brands-400.woff2',
        'output' => $faWebfontsDir . '/fa-brands-400.woff2',
        'description' => 'Font Awesome Brands',
    ],
];

// Скачивание ресурсов
echo colorize("\n📦 Установка CSS библиотек...\n", 'green');
$cssCount = 0;
foreach ($resources as $resource) {
    if (strpos($resource['output'], '/css/') !== false) {
        downloadFile($resource['url'], $resource['output'], $resource['description']);
        $cssCount++;
    }
}

echo colorize("\n📦 Установка JavaScript библиотек...\n", 'green');
$jsCount = 0;
foreach ($resources as $resource) {
    if (strpos($resource['output'], '/js/') !== false) {
        downloadFile($resource['url'], $resource['output'], $resource['description']);
        $jsCount++;
    }
}

echo colorize("\n📦 Установка шрифтов иконок...\n", 'green');
$fontCount = 0;
foreach ($resources as $resource) {
    if (strpos($resource['output'], '/icons/') !== false || strpos($resource['output'], '/webfonts/') !== false) {
        downloadFile($resource['url'], $resource['output'], $resource['description']);
        $fontCount++;
    }
}

// Обновление путей в CSS файлах
echo colorize("\n🔧 Обновление путей к шрифтам в CSS файлах...\n", 'yellow');

// Bootstrap Icons
$bootstrapCss = $cssDir . '/bootstrap-icons.css';
if (file_exists($bootstrapCss)) {
    $content = file_get_contents($bootstrapCss);
    $content = preg_replace('/url\([^)]*bootstrap-icons\.woff2\)/i', 'url(../icons/bootstrap-icons.woff2)', $content);
    file_put_contents($bootstrapCss, $content);
}

// Remix Icons
$remixCss = $cssDir . '/remixicon.css';
if (file_exists($remixCss)) {
    $content = file_get_contents($remixCss);
    $content = preg_replace('/url\([^)]*remixicon\.woff2\)/i', 'url(../icons/remixicon.woff2)', $content);
    file_put_contents($remixCss, $content);
}

// Tabler Icons
$tablerCss = $cssDir . '/tabler-icons.min.css';
if (file_exists($tablerCss)) {
    $content = file_get_contents($tablerCss);
    $content = preg_replace('/url\([^)]*tabler-icons\.woff2\)/i', 'url(../icons/tabler-icons.woff2)', $content);
    file_put_contents($tablerCss, $content);
}

// Font Awesome
$faCss = $faDir . '/all.min.css';
if (file_exists($faCss)) {
    $content = file_get_contents($faCss);
    $content = preg_replace('/url\([^)]*webfonts\//i', 'url(webfonts/', $content);
    file_put_contents($faCss, $content);
}

echo colorize("\n✅ Установка завершена!\n", 'green');
echo colorize("📁 Все ресурсы находятся в: {$assetsDir}\n", 'green');
echo colorize("\n⚠️  Следующие шаги:\n", 'yellow');
echo "1. Обновите views, заменив CDN ссылки на функции local_css() и local_js()\n";
echo "2. Проверьте работу сайта\n";
echo "3. Настройте кэширование статических файлов в веб-сервере\n";





