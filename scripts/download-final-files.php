<?php

echo "Downloading final missing files...\n\n";

$files = [
    [
        'url' => 'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.woff2',
        'path' => 'public/assets/icons/tabler-icons.woff2',
        'name' => 'Tabler Icons Font'
    ],
    [
        'url' => 'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css',
        'path' => 'public/assets/css/tabler-icons.min.css',
        'name' => 'Tabler Icons CSS'
    ],
    [
        'url' => 'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-html.min.js',
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
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ],
            'timeout' => 30,
            'follow_location' => true
        ]
    ]);
    
    $content = @file_get_contents($file['url'], false, $context);
    
    if ($content === false) {
        echo "  ❌ Failed to download {$file['name']}\n";
        echo "  Trying alternative URL...\n";
        
        // Alternative URLs
        $altUrls = [];
        if (strpos($file['url'], 'tabler-icons.woff2') !== false) {
            $altUrls = [
                'https://unpkg.com/@tabler/icons-webfont@latest/dist/tabler-icons.woff2',
                'https://cdn.skypack.dev/@tabler/icons-webfont@latest/dist/tabler-icons.woff2'
            ];
        } elseif (strpos($file['url'], 'tabler-icons.min.css') !== false) {
            $altUrls = [
                'https://unpkg.com/@tabler/icons-webfont@latest/dist/tabler-icons.min.css',
                'https://cdn.skypack.dev/@tabler/icons-webfont@latest/dist/tabler-icons.min.css'
            ];
        } elseif (strpos($file['url'], 'prism-html') !== false) {
            $altUrls = [
                'https://unpkg.com/prismjs@1.29.0/components/prism-html.min.js',
                'https://cdn.skypack.dev/prismjs@1.29.0/components/prism-html.min.js'
            ];
        }
        
        $downloaded = false;
        foreach ($altUrls as $altUrl) {
            $content = @file_get_contents($altUrl, false, $context);
            if ($content !== false) {
                echo "  ✓ Downloaded from alternative URL\n";
                $downloaded = true;
                break;
            }
        }
        
        if (!$downloaded) {
            echo "  ❌ All URLs failed for {$file['name']}\n\n";
            continue;
        }
    }
    
    file_put_contents($file['path'], $content);
    $size = filesize($file['path']);
    echo "  ✓ {$file['name']} downloaded: " . round($size / 1024, 2) . " KB\n\n";
}

echo "Fixing CSS paths...\n";
require_once __DIR__ . '/fix-css-paths.php';

echo "\nDone!\n";





