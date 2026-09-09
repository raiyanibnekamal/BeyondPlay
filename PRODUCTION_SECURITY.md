# Arena — production security fixes (verification)

## Critical fixes applied

| # | Item | Status |
|---|------|--------|
| 1 | `.env` in `.gitignore` + `SECURITY_GIT.md` purge instructions + `.env.production.example` | Done |
| 2 | `assets/js/config.js` — production API URL + localhost guard | Done |
| 3 | `.env.example` — `APP_DEBUG=false`, `APP_ENV=production`, `LOG_LEVEL` note | Done |
| 4 | Seed passwords from `ADMIN_SEED_PASSWORD` / random + console warning | Done |
| 5 | `SESSION_ENCRYPT=true` in `.env.example` | Done |
| 6 | CORS empty fallback (no `*`) + `supports_credentials` when origins set | Done |

## High-priority fixes applied

| # | Item | Status |
|---|------|--------|
| 7 | `throttle:60,1` on authenticated routes; tighter limits on register/order/bulk-email | Done |
| 8 | `deployment/supervisor/arena-worker.conf` | Done |
| 9 | `deployment/scripts/backup-db.sh` | Done |
| 10 | HttpOnly cookie auth (`ARENA_HTTPONLY_AUTH_COOKIE`) + CSP in `router.php` | Done |

## Pre-launch checklist (you must run on the server)

- [ ] Purge `.env` from git history if it was ever committed (`SECURITY_GIT.md`)
- [ ] `php artisan key:generate` and set strong `DB_PASSWORD`
- [ ] Set `assets/js/config.js` → `ARENA_API_BASE` to real API URL (not `yourdomain.com` placeholder)
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=error`
- [ ] `FRONTEND_URL` + `CORS_ALLOWED_ORIGINS` = production domain only
- [ ] `SESSION_ENCRYPT=true`, `APP_KEY` set (sessions invalidate once — users re-login)
- [ ] Run `php artisan db:seed` only once; save printed passwords; rotate or delete seed users
- [ ] `php artisan config:cache` && `php artisan route:cache` (no errors)
- [ ] Enable Supervisor queue worker (`DEPLOY.md`)
- [ ] Schedule daily `backup-db.sh` cron
- [ ] Enable `ARENA_HTTPONLY_AUTH_COOKIE=true` when HTTPS + `SESSION_DOMAIN` configured

## Still optional / post-MVP

- Stripe payments, Pusher realtime, Playwright CI
- Migrate fully off Bearer tokens in API responses when cookie mode is stable everywhere

## Verify locally

```bash
cd tournament-backend
php artisan test
php artisan config:cache
php artisan route:cache
```
