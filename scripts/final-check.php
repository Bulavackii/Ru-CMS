<?php

/**
 * Финальная проверка всех замен CDN на локальные ресурсы
 */

echo "=== ФИНАЛЬНАЯ ПРОВЕРКА ===\n\n";

$errors = [];
$warnings = [];

// Проверка файлов
$requiredFiles = [
    'css/tailwind.min.css',
    'css/prism-tomorrow.min.css',
    'css/swiper-bundle.min.css',
    'css/bootstrap-icons.css',
    'css/remixicon.css',
    'css/tabler-icons.min.css',
    'css/font-awesome/all.min.css',
    'js/alpine.min.js',
    'js/lucide.min.js',
    'js/prism.min.js',
    'js/prism-markup.min.js',
    'js/prism-html.min.js',
    'js/prism-css.min.js',
    'js/prism-javascript.min.js',
    'js/prism-php.min.js',
    'js/swiper-bundle.min.js',
    'js/chart.min.js',
    'js/sortable.min.js',
    'icons/bootstrap-icons.woff2',
    'icons/remixicon.woff2',
    'icons/tabler-icons.woff2',
    'css/font-awesome/webfonts/fa-solid-900.woff2',
    'css/font-awesome/webfonts/fa-regular-400.woff2',
    'css/font-awesome/webfonts/fa-brands-400.woff2',
];

$baseDir = __DIR__ . '/../public/assets';
$found = 0;
$missing = [];

foreach ($requiredFiles as $file) {
    $path = $baseDir . '/' . $file;
    if (file_exists($path) && filesize($path) > 0) {
        $found++;
    } else {
        $missing[] = $file;
    }
}

echo "Файлы: {$found}/" . count($requiredFiles) . " найдено\n";
if (count($missing) > 0) {
    echo "Отсутствуют:\n";
    foreach ($missing as $file) {
        echo "  - $file\n";
    }
}

// Проверка CDN ссылок в views
echo "\n=== Проверка CDN ссылок ===\n";

$cdns = [
    'resources/views' => [],
    'modules' => [],
];

function checkDir($dir, &$results) {
    $files = glob($dir . '/*.blade.php');
    foreach ($files as $file) {
        $content = file_get_contents($file);
        if (preg_match('/https?:\/\/(cdn\.jsdelivr|cdnjs\.cloudflare|fonts\.googleapis|fonts\.bunny|unpkg\.com)/i', $content, $matches)) {
            $results[] = ['file' => $file, 'match' => $matches[0]];
        }
    }
    $dirs = glob($dir . '/*', GLOB_ONLYDIR);
    foreach ($dirs as $subdir) {
        checkDir($subdir, $results);
    }
}

checkDir(__DIR__ . '/../resources/views', $cdns['resources/views']);
checkDir(__DIR__ . '/../modules', $cdns['modules']);

$totalCdn = count($cdns['resources/views']) + count($cdns['modules']);

echo "Найдено CDN ссылок: $totalCdn\n";

// Исключения (опциональные онлайн шрифты и Swagger UI)
$allowed = [
    'fonts.googleapis.com', // опциональные онлайн шрифты
    'fonts.bunny.net',      // опциональные онлайн шрифты
    'unpkg.com/swagger',    // Swagger UI для API документации
];

$critical = [];
foreach ($cdns['resources/views'] as $item) {
    $isAllowed = false;
    foreach ($allowed as $allow) {
        if (strpos($item['match'], $allow) !== false) {
            $isAllowed = true;
            break;
        }
    }
    if (!$isAllowed) {
        $critical[] = $item;
    }
}

foreach ($cdns['modules'] as $item) {
    $isAllowed = false;
    foreach ($allowed as $allow) {
        if (strpos($item['match'], $allow) !== false) {
            $isAllowed = true;
            break;
        }
    }
    if (!$isAllowed) {
        $critical[] = $item;
    }
}

if (count($critical) > 0) {
    echo "\n⚠️  КРИТИЧЕСКИЕ CDN ссылки (не опциональные):\n";
    foreach ($critical as $item) {
        echo "  - " . str_replace(__DIR__ . '/../', '', $item['file']) . "\n";
        echo "    " . $item['match'] . "\n";
    }
} else {
    echo "\n✅ Все критичные CDN ссылки заменены!\n";
    echo "   (Остались только опциональные онлайн шрифты и Swagger UI)\n";
}

echo "\n=== ИТОГ ===\n";
echo "Файлы ресурсов: " . ($found === count($requiredFiles) ? "✅ Все на месте" : "⚠️  Не хватает " . (count($requiredFiles) - $found)) . "\n";
echo "CDN ссылки: " . (count($critical) === 0 ? "✅ Все заменены" : "⚠️  Осталось " . count($critical) . " критичных") . "\n";





