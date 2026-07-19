<?php

echo "Downloading files (robust method)...\n\n";

function downloadFile($url, $path, $name) {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Try multiple methods
    $methods = [
        function($url) use ($path) {
            // Method 1: file_get_contents with full context
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                        'Accept: */*',
                        'Accept-Language: en-US,en;q=0.9',
                        'Accept-Encoding: identity',
                        'Connection: keep-alive',
                        'Cache-Control: no-cache'
                    ],
                    'timeout' => 60,
                    'follow_location' => true,
                    'max_redirects' => 10,
                    'ignore_errors' => false
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'allow_self_signed' => false
                ]
            ];
            
            $context = stream_context_create($opts);
            $content = @file_get_contents($url, false, $context);
            
            if ($content !== false && strlen($content) > 100) {
                file_put_contents($path, $content);
                return true;
            }
            return false;
        },
        function($url) use ($path) {
            // Method 2: Try with allow_url_fopen workaround
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => 'User-Agent: PHP',
                    'timeout' => 60,
                    'follow_location' => 1
                ]
            ];
            $context = stream_context_create($opts);
            $content = @file_get_contents($url, false, $context);
            
            if ($content !== false && strlen($content) > 100) {
                file_put_contents($path, $content);
                return true;
            }
            return false;
        }
    ];
    
    foreach ($methods as $index => $method) {
        echo "  Method " . ($index + 1) . "... ";
        if ($method($url)) {
            $size = filesize($path);
            echo "OK (" . round($size / 1024, 2) . " KB)\n";
            return true;
        }
        echo "Failed\n";
    }
    
    return false;
}

$files = [
    [
        'urls' => [
            'https://unpkg.com/@tabler/icons-webfont@latest/dist/tabler-icons.woff2',
            'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.woff2',
            'https://cdn.skypack.dev/@tabler/icons-webfont@latest/dist/tabler-icons.woff2'
        ],
        'path' => 'public/assets/icons/tabler-icons.woff2',
        'name' => 'Tabler Icons Font',
        'minSize' => 100000 // ~100 KB minimum
    ],
    [
        'urls' => [
            'https://unpkg.com/@tabler/icons-webfont@latest/dist/tabler-icons.min.css',
            'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css',
            'https://cdn.skypack.dev/@tabler/icons-webfont@latest/dist/tabler-icons.min.css'
        ],
        'path' => 'public/assets/css/tabler-icons.min.css',
        'name' => 'Tabler Icons CSS',
        'minSize' => 1000 // ~1 KB minimum
    ],
    [
        'urls' => [
            'https://unpkg.com/prismjs@1.29.0/components/prism-html.min.js',
            'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-html.min.js',
            'https://cdn.skypack.dev/prismjs@1.29.0/components/prism-html.min.js'
        ],
        'path' => 'public/assets/js/prism-html.min.js',
        'name' => 'Prism HTML',
        'minSize' => 500 // ~0.5 KB minimum
    ]
];

foreach ($files as $file) {
    echo "Downloading {$file['name']}...\n";
    
    $downloaded = false;
    foreach ($file['urls'] as $urlIndex => $url) {
        if ($urlIndex > 0) {
            echo "  Trying alternative URL " . ($urlIndex + 1) . "...\n";
        }
        
        if (downloadFile($url, $file['path'], $file['name'])) {
            $size = filesize($file['path']);
            if ($size >= $file['minSize']) {
                echo "  ✓ {$file['name']} downloaded successfully: " . round($size / 1024, 2) . " KB\n\n";
                $downloaded = true;
                break;
            } else {
                echo "  ⚠️  File too small (" . round($size / 1024, 2) . " KB), trying next URL...\n";
                unlink($file['path']);
            }
        }
    }
    
    if (!$downloaded) {
        echo "  ❌ Failed to download {$file['name']} from all URLs\n";
        echo "  Please download manually:\n";
        foreach ($file['urls'] as $url) {
            echo "    - {$url}\n";
        }
        echo "\n";
    }
}

echo "Fixing CSS paths...\n";
require_once __DIR__ . '/fix-css-paths.php';

echo "\nVerifying files...\n";
require_once __DIR__ . '/check-file-sizes.php';

echo "\nDone!\n";





