$ProgressPreference = 'SilentlyContinue'
$ErrorActionPreference = 'Continue'

Write-Host "Downloading Tabler Icons Font..." -ForegroundColor Cyan

$urls = @(
    'https://unpkg.com/@tabler/icons-webfont@3.36.0/dist/fonts/tabler-icons.woff2',
    'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.36.0/dist/fonts/tabler-icons.woff2',
    'https://unpkg.com/@tabler/icons-webfont@latest/dist/fonts/tabler-icons.woff2'
)

$downloaded = $false

foreach ($url in $urls) {
    try {
        Write-Host "Trying: $url" -ForegroundColor Yellow
        
        $dir = "public\assets\icons"
        if (-not (Test-Path $dir)) {
            New-Item -ItemType Directory -Path $dir -Force | Out-Null
        }
        
        Invoke-WebRequest -Uri $url -OutFile "public\assets\icons\tabler-icons.woff2" -UseBasicParsing -TimeoutSec 60
        
        $size = (Get-Item "public\assets\icons\tabler-icons.woff2").Length
        if ($size -gt 10000) {
            Write-Host "OK: $([math]::Round($size/1KB, 2)) KB" -ForegroundColor Green
            $downloaded = $true
            break
        } else {
            Write-Host "File too small: $([math]::Round($size/1KB, 2)) KB" -ForegroundColor Yellow
            Remove-Item "public\assets\icons\tabler-icons.woff2" -Force -ErrorAction SilentlyContinue
        }
    } catch {
        Write-Host "Failed: $($_.Exception.Message)" -ForegroundColor Red
    }
}

if (-not $downloaded) {
    Write-Host ""
    Write-Host "Could not download Tabler Icons Font automatically." -ForegroundColor Red
    Write-Host "Please download manually from one of these URLs:" -ForegroundColor Yellow
    foreach ($url in $urls) {
        Write-Host "  - $url" -ForegroundColor Gray
    }
    Write-Host ""
    Write-Host "Or install via npm:" -ForegroundColor Yellow
    Write-Host "  npm install @tabler/icons-webfont" -ForegroundColor Gray
    Write-Host "  Then copy: node_modules/@tabler/icons-webfont/dist/fonts/tabler-icons.woff2" -ForegroundColor Gray
    Write-Host "  To: public/assets/icons/tabler-icons.woff2" -ForegroundColor Gray
}





