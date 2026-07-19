<?php

/**
 * Скачивание недостающих файлов
 */

echo "Скачивание недостающих файлов...\n\n";

function downloadFile($url, $output, $name) {
    echo "Скачивание $name... ";
    
    // Пробуем разные методы
    $success = false;
    
    // Метод 1: file_get_contents
    if (ini_get('allow_url_fopen')) {
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
        if ($content !== false && strlen($content) > 0) {
            $dir = dirname($output);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($output, $content);
            $success = true;
        }
    }
    
    // Метод 2: cURL
    if (!$success && function_exists('curl_init')) {
        $ch = curl_init($url);
        $fp = fopen($output, 'wb');
        
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        if (curl_exec($ch)) {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode === 200) {
                $success = true;
            }
        }
        
        curl_close($ch);
        fclose($fp);
    }
    
    if ($success && file_exists($output) && filesize($output) > 0) {
        echo "OK (" . number_format(filesize($output) / 1024, 2) . " KB)\n";
        return true;
    } else {
        echo "FAILED\n";
        if (file_exists($output)) {
            unlink($output);
        }
        return false;
    }
}

$baseDir = __DIR__ . '/../public/assets';

// Список файлов для скачивания с альтернативными URL
$files = [
    [
        'name' => 'Tabler Icons CSS',
        'urls' => [
            'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/dist/tabler-icons.min.css',
            'https://unpkg.com/@tabler/icons-webfont@2.47.0/dist/tabler-icons.min.css',
            'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css',
        ],
        'output' => $baseDir . '/css/tabler-icons.min.css',
    ],
    [
        'name' => 'Tabler Icons Font',
        'urls' => [
            'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/dist/tabler-icons.woff2',
            'https://unpkg.com/@tabler/icons-webfont@2.47.0/dist/tabler-icons.woff2',
            'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.woff2',
        ],
        'output' => $baseDir . '/icons/tabler-icons.woff2',
    ],
    [
        'name' => 'Prism HTML',
        'urls' => [
            'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-html.min.js',
            'https://unpkg.com/prismjs@1.29.0/components/prism-html.min.js',
            'https://cdn.jsdelivr.net/npm/prismjs@latest/components/prism-html.min.js',
            'https://unpkg.com/prismjs@latest/components/prism-html.min.js',
        ],
        'output' => $baseDir . '/js/prism-html.min.js',
    ],
];

foreach ($files as $file) {
    $downloaded = false;
    foreach ($file['urls'] as $url) {
        if (downloadFile($url, $file['output'], $file['name'])) {
            $downloaded = true;
            break;
        }
    }
    
    if (!$downloaded) {
        echo "  Все URL для {$file['name']} не сработали\n";
    }
}

echo "\nГотово!\n";





