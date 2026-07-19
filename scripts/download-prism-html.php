<?php

echo "Downloading Prism HTML component...\n\n";

$urls = [
    'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-html.min.js',
    'https://unpkg.com/prismjs@1.29.0/components/prism-html.min.js',
    'https://cdn.skypack.dev/prismjs@1.29.0/components/prism-html.min.js'
];

$dir = 'public/assets/js';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$downloaded = false;

foreach ($urls as $index => $url) {
    echo "Trying URL " . ($index + 1) . ": $url\n";
    
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept: */*'
            ],
            'timeout' => 30,
            'follow_location' => true,
            'max_redirects' => 5
        ]
    ];
    
    $context = stream_context_create($opts);
    $content = @file_get_contents($url, false, $context);
    
    if ($content !== false && strlen($content) > 100) {
        // Check if it's not just a Skypack wrapper
        if (strpos($content, 'export') === false && strpos($content, 'Skypack') === false) {
            file_put_contents('public/assets/js/prism-html.min.js', $content);
            $size = filesize('public/assets/js/prism-html.min.js');
            echo "✓ Downloaded: " . round($size / 1024, 2) . " KB\n";
            $downloaded = true;
            break;
        } else {
            echo "  ⚠️  Got Skypack wrapper, trying next URL...\n";
        }
    } else {
        echo "  ❌ Failed\n";
    }
}

if (!$downloaded) {
    echo "\nTrying direct GitHub raw content...\n";
    
    // Try GitHub raw content
    $githubUrl = 'https://raw.githubusercontent.com/PrismJS/prism/v1.29.0/components/prism-html.min.js';
    $content = @file_get_contents($githubUrl, false, $context);
    
    if ($content !== false && strlen($content) > 100) {
        file_put_contents('public/assets/js/prism-html.min.js', $content);
        $size = filesize('public/assets/js/prism-html.min.js');
        echo "✓ Downloaded from GitHub: " . round($size / 1024, 2) . " KB\n";
        $downloaded = true;
    }
}

if (!$downloaded) {
    echo "\n❌ Could not download Prism HTML component\n";
    echo "Please download manually from:\n";
    echo "  https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-html.min.js\n";
    exit(1);
}

echo "\nVerifying content...\n";
$content = file_get_contents('public/assets/js/prism-html.min.js');
if (strpos($content, 'Prism') !== false || strpos($content, 'html') !== false || strpos($content, 'markup') !== false) {
    echo "✓ Content looks valid\n";
} else {
    echo "⚠️  Content might be invalid, please verify manually\n";
}

echo "\nDone!\n";





