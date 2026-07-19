<?php

/**
 * Проверка всех скачанных ресурсов
 */

echo "Проверка всех ресурсов...\n\n";

$baseDir = __DIR__ . '/../public/assets';
$errors = [];
$success = [];

// Ожидаемые файлы
$expectedFiles = [
    // CSS
    'css/tailwind.min.css' => 'Tailwind CSS',
    'css/prism-tomorrow.min.css' => 'Prism Theme',
    'css/swiper-bundle.min.css' => 'Swiper CSS',
    'css/bootstrap-icons.css' => 'Bootstrap Icons CSS',
    'css/remixicon.css' => 'Remix Icons CSS',
    'css/tabler-icons.min.css' => 'Tabler Icons CSS',
    'css/font-awesome/all.min.css' => 'Font Awesome CSS',
    
    // JavaScript
    'js/alpine.min.js' => 'Alpine.js',
    'js/lucide.min.js' => 'Lucide',
    'js/prism.min.js' => 'Prism Core',
    'js/prism-markup.min.js' => 'Prism Markup',
    'js/prism-html.min.js' => 'Prism HTML',
    'js/prism-css.min.js' => 'Prism CSS',
    'js/prism-javascript.min.js' => 'Prism JS',
    'js/prism-php.min.js' => 'Prism PHP',
    'js/swiper-bundle.min.js' => 'Swiper JS',
    'js/chart.min.js' => 'Chart.js',
    'js/sortable.min.js' => 'SortableJS',
    
    // Fonts
    'icons/bootstrap-icons.woff2' => 'Bootstrap Icons Font',
    'icons/remixicon.woff2' => 'Remix Icons Font',
    'icons/tabler-icons.woff2' => 'Tabler Icons Font',
    'css/font-awesome/webfonts/fa-solid-900.woff2' => 'FA Solid Font',
    'css/font-awesome/webfonts/fa-regular-400.woff2' => 'FA Regular Font',
    'css/font-awesome/webfonts/fa-brands-400.woff2' => 'FA Brands Font',
];

foreach ($expectedFiles as $file => $name) {
    $fullPath = $baseDir . '/' . $file;
    if (file_exists($fullPath)) {
        $size = filesize($fullPath);
        if ($size > 0) {
            $success[] = "$name ($file) - " . number_format($size / 1024, 2) . " KB";
        } else {
            $errors[] = "$name ($file) - файл пустой!";
        }
    } else {
        $errors[] = "$name ($file) - файл отсутствует!";
    }
}

echo "Успешно найдено: " . count($success) . " файлов\n";
echo "Ошибок: " . count($errors) . "\n\n";

if (count($success) > 0) {
    echo "✓ Найденные файлы:\n";
    foreach ($success as $item) {
        echo "  $item\n";
    }
}

if (count($errors) > 0) {
    echo "\n✗ Проблемы:\n";
    foreach ($errors as $item) {
        echo "  $item\n";
    }
} else {
    echo "\n✓ Все файлы на месте!\n";
}

