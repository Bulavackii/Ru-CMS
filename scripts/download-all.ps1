# Download all assets script
$ProgressPreference = 'SilentlyContinue'
$ErrorActionPreference = 'Continue'

Write-Host "Starting download..." -ForegroundColor Green

# Create directories
$dirs = @(
    "public\assets\css",
    "public\assets\css\font-awesome",
    "public\assets\css\font-awesome\webfonts",
    "public\assets\js",
    "public\assets\icons"
)

foreach ($dir in $dirs) {
    if (!(Test-Path $dir)) {
        New-Item -ItemType Directory -Force -Path $dir | Out-Null
    }
}

# CSS files
Write-Host "`nDownloading CSS..." -ForegroundColor Yellow
$cssFiles = @(
    @("https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css", "public\assets\css\tailwind.min.css", "Tailwind CSS"),
    @("https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism-tomorrow.min.css", "public\assets\css\prism-tomorrow.min.css", "Prism Theme"),
    @("https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css", "public\assets\css\swiper-bundle.min.css", "Swiper CSS"),
    @("https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css", "public\assets\css\bootstrap-icons.css", "Bootstrap Icons"),
    @("https://cdn.jsdelivr.net/npm/remixicon@3.7.0/fonts/remixicon.css", "public\assets\css\remixicon.css", "Remix Icons"),
    @("https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/dist/tabler-icons.min.css", "public\assets\css\tabler-icons.min.css", "Tabler Icons"),
    @("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css", "public\assets\css\font-awesome\all.min.css", "Font Awesome CSS")
)

foreach ($file in $cssFiles) {
    try {
        Write-Host "  Downloading $($file[2])..." -NoNewline
        Invoke-WebRequest -Uri $file[0] -OutFile $file[1] -UseBasicParsing | Out-Null
        Write-Host " OK" -ForegroundColor Green
    } catch {
        Write-Host " ERROR: $($_.Exception.Message)" -ForegroundColor Red
    }
}

# JavaScript files
Write-Host "`nDownloading JavaScript..." -ForegroundColor Yellow
$jsFiles = @(
    @("https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js", "public\assets\js\alpine.min.js", "Alpine.js"),
    @("https://cdn.jsdelivr.net/npm/lucide@0.344.0/dist/umd/lucide.min.js", "public\assets\js\lucide.min.js", "Lucide"),
    @("https://cdn.jsdelivr.net/npm/prismjs@1.29.0/prism.min.js", "public\assets\js\prism.min.js", "Prism Core"),
    @("https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-markup.min.js", "public\assets\js\prism-markup.min.js", "Prism Markup"),
    @("https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-html.min.js", "public\assets\js\prism-html.min.js", "Prism HTML"),
    @("https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-css.min.js", "public\assets\js\prism-css.min.js", "Prism CSS"),
    @("https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-javascript.min.js", "public\assets\js\prism-javascript.min.js", "Prism JS"),
    @("https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-php.min.js", "public\assets\js\prism-php.min.js", "Prism PHP"),
    @("https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js", "public\assets\js\swiper-bundle.min.js", "Swiper JS")
)

foreach ($file in $jsFiles) {
    try {
        Write-Host "  Downloading $($file[2])..." -NoNewline
        Invoke-WebRequest -Uri $file[0] -OutFile $file[1] -UseBasicParsing | Out-Null
        Write-Host " OK" -ForegroundColor Green
    } catch {
        Write-Host " ERROR: $($_.Exception.Message)" -ForegroundColor Red
    }
}

# Fonts
Write-Host "`nDownloading fonts..." -ForegroundColor Yellow
$fontFiles = @(
    @("https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff2", "public\assets\icons\bootstrap-icons.woff2", "Bootstrap Font"),
    @("https://cdn.jsdelivr.net/npm/remixicon@3.7.0/fonts/remixicon.woff2", "public\assets\icons\remixicon.woff2", "Remix Font"),
    @("https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/dist/tabler-icons.woff2", "public\assets\icons\tabler-icons.woff2", "Tabler Font"),
    @("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/webfonts/fa-solid-900.woff2", "public\assets\css\font-awesome\webfonts\fa-solid-900.woff2", "FA Solid"),
    @("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/webfonts/fa-regular-400.woff2", "public\assets\css\font-awesome\webfonts\fa-regular-400.woff2", "FA Regular"),
    @("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/webfonts/fa-brands-400.woff2", "public\assets\css\font-awesome\webfonts\fa-brands-400.woff2", "FA Brands")
)

foreach ($file in $fontFiles) {
    try {
        Write-Host "  Downloading $($file[2])..." -NoNewline
        Invoke-WebRequest -Uri $file[0] -OutFile $file[1] -UseBasicParsing | Out-Null
        Write-Host " OK" -ForegroundColor Green
    } catch {
        Write-Host " ERROR: $($_.Exception.Message)" -ForegroundColor Red
    }
}

Write-Host "`nAll files downloaded!" -ForegroundColor Green
Write-Host "Check folder: public\assets\" -ForegroundColor Cyan
