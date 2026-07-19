<?php

echo "=== ФИНАЛЬНАЯ ПРОВЕРКА ПРОЕКТА ===\n\n";

$issues = [];
$ok = [];

// 1. Проверка всех ресурсов
echo "1. Проверка ресурсов...\n";
require_once __DIR__ . '/verify-assets.php';
echo "\n";

// 2. Проверка CDN ссылок в Blade файлах
echo "2. Проверка CDN ссылок в Blade файлах...\n";
$bladeFiles = [
    'resources/views/layouts/frontend.blade.php',
    'resources/views/layouts/admin.blade.php',
    'resources/views/layouts/frontend-install.blade.php',
    'resources/views/layouts/app.blade.php',
    'modules/Visual/Resources/views/admin/fragments/editor.blade.php',
];

$cdnPatterns = [
    '/https?:\/\/(cdn|unpkg|jsdelivr|fonts\.googleapis|fonts\.bunny|cdn\.tailwindcss)\./i',
];

foreach ($bladeFiles as $file) {
    if (!file_exists($file)) {
        $issues[] = "Файл не найден: $file";
        continue;
    }
    
    $content = file_get_contents($file);
    
    // Исключения (опциональные пользовательские шрифты)
    $allowedPatterns = [
        '/fonts\.googleapis\.com.*fontProvider.*google/i',
        '/fonts\.bunny\.net.*fontProvider.*bunny/i',
    ];
    
    $hasAllowed = false;
    foreach ($allowedPatterns as $pattern) {
        if (preg_match($pattern, $content)) {
            $hasAllowed = true;
            break;
        }
    }
    
    foreach ($cdnPatterns as $pattern) {
        if (preg_match($pattern, $content)) {
            // Проверяем, не является ли это опциональным шрифтом
            if (!$hasAllowed || !preg_match('/fontProvider|font_name/i', $content)) {
                $matches = [];
                preg_match_all($pattern, $content, $matches);
                foreach ($matches[0] as $match) {
                    // Пропускаем опциональные шрифты
                    if (strpos($match, 'fonts.googleapis.com') !== false || 
                        strpos($match, 'fonts.bunny.net') !== false) {
                        continue;
                    }
                    $issues[] = "CDN ссылка в $file: $match";
                }
            }
        }
    }
    
    if (!preg_match('/cdn\.|unpkg\.|jsdelivr\.|cdn\.tailwindcss/i', $content) || $hasAllowed) {
        $ok[] = "✓ $file - без проблемных CDN";
    }
}

// 3. Проверка TinyMCE
echo "3. Проверка TinyMCE...\n";
$tinymcePath = 'public/admin/tinymce/tinymce.min.js';
if (file_exists($tinymcePath)) {
    $size = filesize($tinymcePath);
    if ($size > 1000) {
        $ok[] = "✓ TinyMCE найден локально: " . round($size / 1024, 2) . " KB";
    } else {
        $issues[] = "TinyMCE слишком маленький: " . round($size / 1024, 2) . " KB";
    }
} else {
    $issues[] = "TinyMCE не найден: $tinymcePath";
}

// 4. Проверка модуля Visual
echo "4. Проверка модуля Visual...\n";
$visualEditor = 'modules/Visual/Resources/views/admin/fragments/editor.blade.php';
if (file_exists($visualEditor)) {
    $content = file_get_contents($visualEditor);
    
    // Проверяем использование локальных ресурсов
    if (preg_match('/local_css|local_js|asset\([\'"]admin\/tinymce/i', $content)) {
        $ok[] = "✓ Модуль Visual использует локальные ресурсы";
    } else {
        $issues[] = "Модуль Visual может использовать внешние ресурсы";
    }
    
    // Проверяем, что нет прямых CDN ссылок
    if (preg_match('/https?:\/\/(cdn|unpkg|jsdelivr)\./i', $content)) {
        $issues[] = "Модуль Visual содержит CDN ссылки";
    } else {
        $ok[] = "✓ Модуль Visual без CDN ссылок";
    }
}

// 5. Проверка CSP
echo "5. Проверка CSP политики...\n";
$cspFile = 'app/Http/Middleware/ContentSecurityPolicy.php';
if (file_exists($cspFile)) {
    $content = file_get_contents($cspFile);
    if (preg_match("/script-src.*'self'.*'unsafe-inline'/i", $content) &&
        !preg_match("/https?:\/\/(cdn|unpkg|jsdelivr)/i", $content)) {
        $ok[] = "✓ CSP политика настроена правильно (без внешних CDN)";
    } else {
        $issues[] = "CSP политика может разрешать внешние CDN";
    }
}

// Итоги
echo "\n=== РЕЗУЛЬТАТЫ ===\n\n";

if (empty($issues)) {
    echo "✅ ВСЕ ПРОВЕРКИ ПРОЙДЕНЫ!\n\n";
    echo "Успешные проверки:\n";
    foreach ($ok as $msg) {
        echo "  $msg\n";
    }
} else {
    echo "⚠️  НАЙДЕНЫ ПРОБЛЕМЫ:\n\n";
    foreach ($issues as $issue) {
        echo "  ❌ $issue\n";
    }
    echo "\nУспешные проверки:\n";
    foreach ($ok as $msg) {
        echo "  $msg\n";
    }
}

echo "\n=== КОНЕЦ ПРОВЕРКИ ===\n";





