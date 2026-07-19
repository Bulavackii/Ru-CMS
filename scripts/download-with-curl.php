<?php

if (!function_exists('curl_init')) {
    die("cURL extension is not available!\n");
}

echo "Downloading files with cURL...\n\n";

$files = [
    [
        'url' => 'https://unpkg.com/@tabler/icons-webfont@latest/dist/tabler-icons.woff2',
        'path' => 'public/assets/icons/tabler-icons.woff2',
        'name' => 'Tabler Icons Font'
    ],
    [
        'url' => 'https://unpkg.com/@tabler/icons-webfont@latest/dist/tabler-icons.min.css',
        'path' => 'public/assets/css/tabler-icons.min.css',
        'name' => 'Tabler Icons CSS'
    ],
    [
        'url' => 'https://unpkg.com/prismjs@1.29.0/components/prism-html.min.js',
        'path' => 'public/assets/js/prism-html.min.js',
        'name' => 'Prism HTML'
    ]
];

foreach ($files as $file) {
    echo "Downloading {$file['name']}...\n";
    
    $dir = dirname($file['path']);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $ch = curl_init($file['url']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);
    
    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($content === false || $httpCode !== 200 || !empty($error)) {
        echo "  ❌ Failed: HTTP {$httpCode}" . ($error ? " - {$error}" : "") . "\n";
        
        // Try alternative URLs
        $altUrls = [];
        if (strpos($file['url'], 'tabler-icons.woff2') !== false) {
            $altUrls = [
                'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.woff2',
                'https://cdn.skypack.dev/@tabler/icons-webfont@latest/dist/tabler-icons.woff2'
            ];
        } elseif (strpos($file['url'], 'tabler-icons.min.css') !== false) {
            $altUrls = [
                'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css',
                'https://cdn.skypack.dev/@tabler/icons-webfont@latest/dist/tabler-icons.min.css'
            ];
        } elseif (strpos($file['url'], 'prism-html') !== false) {
            $altUrls = [
                'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-html.min.js',
                'https://cdn.skypack.dev/prismjs@1.29.0/components/prism-html.min.js'
            ];
        }
        
        $downloaded = false;
        foreach ($altUrls as $altUrl) {
            echo "  Trying alternative: {$altUrl}\n";
            $ch = curl_init($altUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2
            ]);
            
            $content = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($content !== false && $httpCode === 200) {
                echo "  ✓ Downloaded from alternative URL\n";
                $downloaded = true;
                break;
            }
        }
        
        if (!$downloaded) {
            echo "  ❌ All URLs failed for {$file['name']}\n\n";
            continue;
        }
    } else {
        echo "  ✓ Downloaded successfully\n";
    }
    
    file_put_contents($file['path'], $content);
    $size = filesize($file['path']);
    echo "  Size: " . round($size / 1024, 2) . " KB\n\n";
}

echo "Fixing CSS paths...\n";
require_once __DIR__ . '/fix-css-paths.php';

echo "\nVerifying files...\n";
require_once __DIR__ . '/check-file-sizes.php';

echo "\nDone!\n";





