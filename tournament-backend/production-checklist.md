# Arena Production Deployment Checklist

## Before first deploy

- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Generate new `APP_KEY`: `php artisan key:generate`
- [ ] Set `DB_PASSWORD` to a strong password (never empty)
- [ ] Set `FRONTEND_URL` to your real domain in `.env`
- [ ] Set `CORS_ALLOWED_ORIGINS` to your frontend domain(s)
- [ ] Configure `MAIL_*` for real email (not `log` driver)
- [ ] Set `QUEUE_CONNECTION=database` or `redis`
- [ ] Run: `php artisan migrate --force`
- [ ] Run: `php artisan db:seed --force` (first time only)
- [ ] Rotate or delete seed users (`admin@arena.gg` id:1, `player@arena.gg` id:2) — passwords printed once at `db:seed`
- [ ] Run: `php artisan config:cache`
- [ ] Run: `php artisan route:cache`
- [ ] Run: `php artisan storage:link`
- [ ] Start queue worker: `php artisan queue:work` (or supervisor)
- [ ] Set up HTTPS on your web server
- [ ] Restrict MySQL user permissions (not root)
- [ ] Set `window.ARENA_API_BASE` in `assets/js/config.js` to your API URL

## Security

- [ ] Confirm `APP_DEBUG=false` (broken route returns JSON, not HTML stack trace)
- [ ] Confirm CORS only allows your frontend domain
- [ ] Confirm Sanctum `expiration` is `10080` (7 days)
- [ ] Run: `composer audit`

## Monitoring

- [ ] Set up log rotation for `storage/logs/`
- [ ] Cron: `* * * * * php artisan schedule:run`

## Smoke tests

```bash
php artisan test
php artisan serve
php tests/e2e_full_test.php
```

See also `../QA_CHECKLIST.md` and `../DEPLOY.md`.
