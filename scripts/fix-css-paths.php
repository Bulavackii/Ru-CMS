<?php

/**
 * Обновление путей к шрифтам в CSS файлах
 */

echo "Обновление путей в CSS файлах...\n";

// Bootstrap Icons
$bootstrapCss = __DIR__ . '/../public/assets/css/bootstrap-icons.css';
if (file_exists($bootstrapCss)) {
    $content = file_get_contents($bootstrapCss);
    $content = preg_replace('/url\([^)]*fonts\/bootstrap-icons\.woff2[^)]*\)/i', 'url(../icons/bootstrap-icons.woff2)', $content);
    file_put_contents($bootstrapCss, $content);
    echo "✓ Bootstrap Icons CSS обновлен\n";
}

// Remix Icons
$remixCss = __DIR__ . '/../public/assets/css/remixicon.css';
if (file_exists($remixCss)) {
    $content = file_get_contents($remixCss);
    $content = preg_replace('/url\([^)]*fonts\/remixicon\.woff2[^)]*\)/i', 'url(../icons/remixicon.woff2)', $content);
    file_put_contents($remixCss, $content);
    echo "✓ Remix Icons CSS обновлен\n";
}

// Tabler Icons
$tablerCss = __DIR__ . '/../public/assets/css/tabler-icons.min.css';
if (file_exists($tablerCss)) {
    $content = file_get_contents($tablerCss);
    $content = preg_replace('/url\([^)]*fonts\/tabler-icons\.woff2[^)]*\)/i', 'url(../icons/tabler-icons.woff2)', $content);
    file_put_contents($tablerCss, $content);
    echo "✓ Tabler Icons CSS обновлен\n";
}

// Font Awesome
$faCss = __DIR__ . '/../public/assets/css/font-awesome/all.min.css';
if (file_exists($faCss)) {
    $content = file_get_contents($faCss);
    $content = preg_replace('/url\([^)]*webfonts\//i', 'url(webfonts/', $content);
    file_put_contents($faCss, $content);
    echo "✓ Font Awesome CSS обновлен\n";
}

echo "\nГотово!\n";





