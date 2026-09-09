# Arena Phase 2 helper — prepares production checklist (run ON SERVER or locally to validate)
# Usage: .\scripts\prepare-production.ps1
# Does NOT deploy; runs safe local checks and prints reminders.

$root = Split-Path $PSScriptRoot -Parent
$backend = Join-Path $root "tournament-backend"

Write-Host "=== Arena Production Prep ===" -ForegroundColor Cyan
Write-Host ""

Set-Location $backend

if (-not (Test-Path ".env")) {
    Write-Host "Copy .env.example to .env and edit for production." -ForegroundColor Yellow
    Copy-Item ".env.example" ".env"
    php artisan key:generate
}

Write-Host "Running migrations (dry-run friendly)..." -ForegroundColor Yellow
php artisan migrate --force

Write-Host "Running tests..." -ForegroundColor Yellow
php artisan test
if ($LASTEXITCODE -ne 0) { exit 1 }

Write-Host ""
Write-Host "Production reminders:" -ForegroundColor Cyan
Write-Host "  1. Set APP_DEBUG=false, APP_ENV=production in .env"
Write-Host "  2. Set FRONTEND_URL and CORS_ALLOWED_ORIGINS to your live domain"
Write-Host "  3. Set MAIL_* for password reset"
Write-Host "  4. Edit assets/js/config.js — window.ARENA_API_BASE = your API URL"
Write-Host "  5. php artisan config:cache && php artisan route:cache"
Write-Host "  6. php artisan storage:link"
Write-Host "  7. Change admin@arena.gg / player@arena.gg passwords"
Write-Host ""
Write-Host "See DEPLOY.md and tournament-backend/production-checklist.md" -ForegroundColor Green
