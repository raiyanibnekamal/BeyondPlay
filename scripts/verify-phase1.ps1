# Arena Phase 1 verification (NEXT_STEPS.md)
# Usage: .\scripts\verify-phase1.ps1
# Prerequisite: API running in another terminal — php artisan serve

$ErrorActionPreference = "Stop"
$root = Split-Path $PSScriptRoot -Parent
$backend = Join-Path $root "tournament-backend"

Write-Host "=== Arena Phase 1 Verification ===" -ForegroundColor Cyan
Write-Host ""

Set-Location $backend

Write-Host "[1/2] PHPUnit..." -ForegroundColor Yellow
php artisan test
if ($LASTEXITCODE -ne 0) {
    Write-Host "PHPUnit FAILED" -ForegroundColor Red
    exit 1
}
Write-Host "PHPUnit OK" -ForegroundColor Green
Write-Host ""

Write-Host "[2/2] E2E API script (requires php artisan serve on :8000)..." -ForegroundColor Yellow
$health = $null
try {
    $health = Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/v1/tournaments" -UseBasicParsing -TimeoutSec 3
} catch {
    Write-Host "SKIP E2E: API not reachable at http://127.0.0.1:8000" -ForegroundColor DarkYellow
    Write-Host "  Start: cd tournament-backend; php artisan serve" -ForegroundColor DarkYellow
    Write-Host ""
    Write-Host "Phase 1 automated (tests only): PASS" -ForegroundColor Green
    exit 0
}

php tests/e2e_full_test.php
if ($LASTEXITCODE -ne 0) {
    Write-Host "E2E FAILED (429 throttle? wait 60s and retry)" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "Phase 1 automated: PASS (PHPUnit + E2E)" -ForegroundColor Green
Write-Host "Next: manual browser QA — see QA_CHECKLIST.md" -ForegroundColor Cyan
