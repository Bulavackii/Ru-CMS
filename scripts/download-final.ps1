$ProgressPreference = 'SilentlyContinue'
$ErrorActionPreference = 'Continue'

Write-Host "Downloading final files..." -ForegroundColor Cyan
Write-Host ""

$files = @(
    @{
        Name = "Tabler Icons Font"
        Url = "https://unpkg.com/@tabler/icons-webfont@latest/dist/tabler-icons.woff2"
        Path = "public\assets\icons\tabler-icons.woff2"
        MinSize = 100000
    },
    @{
        Name = "Tabler Icons CSS"
        Url = "https://unpkg.com/@tabler/icons-webfont@latest/dist/tabler-icons.min.css"
        Path = "public\assets\css\tabler-icons.min.css"
        MinSize = 1000
    },
    @{
        Name = "Prism HTML"
        Url = "https://unpkg.com/prismjs@1.29.0/components/prism-html.min.js"
        Path = "public\assets\js\prism-html.min.js"
        MinSize = 500
    }
)

$altUrls = @{
    "Tabler Icons Font" = @(
        "https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.woff2",
        "https://cdn.skypack.dev/@tabler/icons-webfont@latest/dist/tabler-icons.woff2"
    )
    "Tabler Icons CSS" = @(
        "https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css",
        "https://cdn.skypack.dev/@tabler/icons-webfont@latest/dist/tabler-icons.min.css"
    )
    "Prism HTML" = @(
        "https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-html.min.js",
        "https://cdn.skypack.dev/prismjs@1.29.0/components/prism-html.min.js"
    )
}

foreach ($file in $files) {
    Write-Host "Downloading $($file.Name)..." -ForegroundColor Yellow
    
    $urls = @($file.Url) + $altUrls[$file.Name]
    $downloaded = $false
    
    foreach ($url in $urls) {
        try {
            $dir = Split-Path -Path $file.Path -Parent
            if (-not (Test-Path $dir)) {
                New-Item -ItemType Directory -Path $dir -Force | Out-Null
            }
            
            Invoke-WebRequest -Uri $url -OutFile $file.Path -UseBasicParsing -TimeoutSec 60
            
            $size = (Get-Item $file.Path).Length
            if ($size -ge $file.MinSize) {
                Write-Host "  OK: $([math]::Round($size/1KB, 2)) KB" -ForegroundColor Green
                $downloaded = $true
                break
            } else {
                Write-Host "  File too small ($([math]::Round($size/1KB, 2)) KB), trying next URL..." -ForegroundColor Yellow
                Remove-Item $file.Path -Force
            }
        } catch {
            Write-Host "  Failed: $($_.Exception.Message)" -ForegroundColor Red
            if (Test-Path $file.Path) {
                Remove-Item $file.Path -Force
            }
        }
    }
    
    if (-not $downloaded) {
        Write-Host "  ERROR: Could not download $($file.Name)" -ForegroundColor Red
        Write-Host "  Please download manually from:" -ForegroundColor Yellow
        foreach ($url in $urls) {
            Write-Host "    - $url" -ForegroundColor Gray
        }
    }
    
    Write-Host ""
}

Write-Host "Fixing CSS paths..." -ForegroundColor Cyan
php scripts/fix-css-paths.php

Write-Host ""
Write-Host "Verifying files..." -ForegroundColor Cyan
php scripts/check-file-sizes.php

Write-Host ""
Write-Host "Done!" -ForegroundColor Green





