# Serve Arena static frontend over HTTP (fixes file:// CORS / manifest errors).
# Usage: .\serve-frontend.ps1
# Then open: http://127.0.0.1:5500/index.html

$port = 5500
$root = $PSScriptRoot

Write-Host "Arena frontend: http://127.0.0.1:$port/" -ForegroundColor Cyan
Write-Host "Open http://127.0.0.1:$port/index.html (not file://)" -ForegroundColor Yellow
Write-Host "Keep tournament-backend running: php artisan serve (API on :8000)" -ForegroundColor Gray
Write-Host "Press Ctrl+C to stop.`n"

Set-Location $root
php -S "127.0.0.1:$port" -t $root router.php
