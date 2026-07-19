$ProgressPreference = 'SilentlyContinue'
$ErrorActionPreference = 'Continue'

Write-Host "Downloading Prism HTML component..." -ForegroundColor Cyan

$urls = @(
    'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-html.min.js',
    'https://unpkg.com/prismjs@1.29.0/components/prism-html.min.js',
    'https://raw.githubusercontent.com/PrismJS/prism/v1.29.0/components/prism-html.min.js'
)

$downloaded = $false

foreach ($url in $urls) {
    try {
        Write-Host "Trying: $url" -ForegroundColor Yellow
        
        $response = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 30
        $content = $response.Content
        
        # Check if it's not a Skypack wrapper
        if ($content -notmatch 'Skypack' -and $content -notmatch 'export \* from' -and $content.Length -gt 100) {
            $dir = "public\assets\js"
            if (-not (Test-Path $dir)) {
                New-Item -ItemType Directory -Path $dir -Force | Out-Null
            }
            
            [System.IO.File]::WriteAllText("$dir\prism-html.min.js", $content, [System.Text.Encoding]::UTF8)
            
            $size = (Get-Item "$dir\prism-html.min.js").Length
            Write-Host "OK: $([math]::Round($size/1KB, 2)) KB" -ForegroundColor Green
            
            # Verify content
            if ($content -match 'Prism|html|markup') {
                Write-Host "Content verified: looks valid" -ForegroundColor Green
            }
            
            $downloaded = $true
            break
        } else {
            Write-Host "Got wrapper/empty file, trying next..." -ForegroundColor Yellow
        }
    } catch {
        Write-Host "Failed: $($_.Exception.Message)" -ForegroundColor Red
    }
}

if (-not $downloaded) {
    Write-Host ""
    Write-Host "Could not download automatically." -ForegroundColor Red
    Write-Host "Please download manually from:" -ForegroundColor Yellow
    Write-Host "  https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-html.min.js" -ForegroundColor Gray
    exit 1
}

Write-Host ""
Write-Host "Done!" -ForegroundColor Green





