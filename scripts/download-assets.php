<?php

/**
 * Улучшенный скрипт для скачивания ресурсов с использованием cURL
 */

$publicDir = __DIR__ . '/../public';
$assetsDir = $publicDir . '/assets';

// Создание структуры
$dirs = [
    $assetsDir . '/css',
    $assetsDir . '/css/font-awesome',
    $assetsDir . '/css/font-awesome/webfonts',
    $assetsDir . '/js',
    $assetsDir . '/fonts',
    $assetsDir . '/icons',
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

function downloadWithCurl($url, $output) {
    $ch = curl_init($url);
    $fp = fopen($output, 'wb');
    
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    fclose($fp);
    
    return $result && $httpCode === 200;
}

$resources = [
    // CSS
    ['https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css', $assetsDir . '/css/tailwind.min.css', 'Tailwind CSS'],
    ['https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism-tomorrow.min.css', $assetsDir . '/css/prism-tomorrow.min.css', 'Prism Theme'],
    ['https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', $assetsDir . '/css/swiper-bundle.min.css', 'Swiper CSS'],
    ['https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css', $assetsDir . '/css/bootstrap-icons.css', 'Bootstrap Icons'],
    ['https://cdn.jsdelivr.net/npm/remixicon@3.7.0/fonts/remixicon.css', $assetsDir . '/css/remixicon.css', 'Remix Icons'],
    ['https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/dist/tabler-icons.min.css', $assetsDir . '/css/tabler-icons.min.css', 'Tabler Icons'],
    ['https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css', $assetsDir . '/css/font-awesome/all.min.css', 'Font Awesome CSS'],
    
    // JS
    ['https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js', $assetsDir . '/js/alpine.min.js', 'Alpine.js'],
    ['https://cdn.jsdelivr.net/npm/lucide@0.344.0/dist/umd/lucide.min.js', $assetsDir . '/js/lucide.min.js', 'Lucide'],
    ['https://cdn.jsdelivr.net/npm/prismjs@1.29.0/prism.min.js', $assetsDir . '/js/prism.min.js', 'Prism Core'],
    ['https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-markup.min.js', $assetsDir . '/js/prism-markup.min.js', 'Prism Markup'],
    ['https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-html.min.js', $assetsDir . '/js/prism-html.min.js', 'Prism HTML'],
    ['https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-css.min.js', $assetsDir . '/js/prism-css.min.js', 'Prism CSS'],
    ['https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-javascript.min.js', $assetsDir . '/js/prism-javascript.min.js', 'Prism JS'],
    ['https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-php.min.js', $assetsDir . '/js/prism-php.min.js', 'Prism PHP'],
    ['https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', $assetsDir . '/js/swiper-bundle.min.js', 'Swiper JS'],
    
    // Fonts
    ['https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff2', $assetsDir . '/icons/bootstrap-icons.woff2', 'Bootstrap Font'],
    ['https://cdn.jsdelivr.net/npm/remixicon@3.7.0/fonts/remixicon.woff2', $assetsDir . '/icons/remixicon.woff2', 'Remix Font'],
    ['https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/dist/tabler-icons.woff2', $assetsDir . '/icons/tabler-icons.woff2', 'Tabler Font'],
    ['https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/webfonts/fa-solid-900.woff2', $assetsDir . '/css/font-awesome/webfonts/fa-solid-900.woff2', 'FA Solid'],
    ['https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/webfonts/fa-regular-400.woff2', $assetsDir . '/css/font-awesome/webfonts/fa-regular-400.woff2', 'FA Regular'],
    ['https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/webfonts/fa-brands-400.woff2', $assetsDir . '/css/font-awesome/webfonts/fa-brands-400.woff2', 'FA Brands'],
];

echo "🚀 Начинаем скачивание ресурсов...\n\n";

$success = 0;
$failed = 0;

foreach ($resources as $resource) {
    list($url, $output, $name) = $resource;
    
    echo "⬇️  {$name}... ";
    
    if (function_exists('curl_init')) {
        if (downloadWithCurl($url, $output)) {
            echo "✅\n";
            $success++;
        } else {
            echo "❌\n";
            $failed++;
        }
    } else {
        $content = @file_get_contents($url);
        if ($content !== false) {
            file_put_contents($output, $content);
            echo "✅\n";
            $success++;
        } else {
            echo "❌\n";
            $failed++;
        }
    }
}

// Обновление путей в CSS
echo "\n🔧 Обновление путей в CSS...\n";

$cssFiles = [
    [$assetsDir . '/css/bootstrap-icons.css', '/fonts/bootstrap-icons.woff2', '../icons/bootstrap-icons.woff2'],
    [$assetsDir . '/css/remixicon.css', '/fonts/remixicon.woff2', '../icons/remixicon.woff2'],
    [$assetsDir . '/css/tabler-icons.min.css', '/fonts/tabler-icons.woff2', '../icons/tabler-icons.woff2'],
];

foreach ($cssFiles as $file) {
    if (file_exists($file[0])) {
        $content = file_get_contents($file[0]);
        $content = preg_replace('/url\([^)]*' . preg_quote($file[1], '/') . '[^)]*\)/i', 'url(' . $file[2] . ')', $content);
        file_put_contents($file[0], $content);
    }
}

// Font Awesome
$faCss = $assetsDir . '/css/font-awesome/all.min.css';
if (file_exists($faCss)) {
    $content = file_get_contents($faCss);
    $content = preg_replace('/url\([^)]*webfonts\//i', 'url(webfonts/', $content);
    file_put_contents($faCss, $content);
}

echo "\n✅ Завершено! Успешно: {$success}, Ошибок: {$failed}\n";





