# Serve BeyondPlay Arena Laravel Backend on port 8000
# Usage: .\serve-backend.ps1
# API URL: http://127.0.0.1:8000/api/v1

$port = 8000
$root = $PSScriptRoot
$backendDir = Join-Path $root "tournament-backend"

$php82 = (Get-Command php -All -ErrorAction SilentlyContinue | Where-Object { 
    try { [int](& $_.Source -r "echo PHP_VERSION_ID;") -ge 80200 } catch { $false } 
} | Select-Object -First 1).Source

if (-not $php82) {
    $wingetPhp = "$env:LOCALAPPDATA\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"
    if (Test-Path $wingetPhp) {
        $php82 = $wingetPhp
    } else {
        $php82 = "php"
    }
}

Write-Host "BeyondPlay Backend: http://127.0.0.1:$port/api/v1" -ForegroundColor Cyan
Write-Host "Using PHP binary: $php82" -ForegroundColor Gray
Write-Host "Press Ctrl+C to stop.`n"

Set-Location $backendDir
& $php82 artisan serve --host=127.0.0.1 --port=$port
