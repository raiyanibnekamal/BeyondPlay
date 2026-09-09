# Arena — Pre go-live QA checklist

Pre go-live verification for the Arena platform.

## Environment

- [ ] `APP_ENV=production`, `APP_DEBUG=false` on API
- [ ] `LOG_LEVEL=error`
- [ ] HTTPS on frontend and API
- [ ] `CORS_ALLOWED_ORIGINS` set to production domain only (local: `http://127.0.0.1:5500`)
- [ ] `FRONTEND_URL` matches live site URL (local: `http://127.0.0.1:5500`)
- [ ] `.env` not committed to git
- [ ] Seed passwords rotated or seed users removed (`admin@arena.gg`, `player@arena.gg`)
- [ ] `SESSION_ENCRYPT=true` on API (users re-login after deploy)

## Automated

```bash
cd tournament-backend
php artisan test    # 15 tests
php artisan serve   # separate terminal
php tests/e2e_full_test.php   # 21 checks when API up; retry if 429 on register
```

Last CI-local run: **15/15 PHPUnit** passed. Run `.\scripts\verify-phase1.ps1` for tests + E2E.

Automated wiring verified: dashboard stats, bracket predictions (`?id=`), blog API list, checkout cart summary.

## Browser (http://127.0.0.1:5500 — not file://)

Password for new accounts: minimum 8 characters.

- [ ] Register new player
- [ ] Login admin → `admin/index.html`
- [ ] Login player → `my-profile.html`
- [ ] Shop: browse → cart → checkout → order in DB
- [ ] Wishlist add/remove
- [ ] Tournament register, bracket, point table
- [ ] Friends / activity feed (Load More) / stats / head-to-head compare
- [ ] My tournaments → Create Tournament (draft)
- [ ] Forgot password → reset link in log → `reset-password.html`
- [ ] Admin: users ban/promote, tournaments create/edit, products add/edit/delete, match score, coupons, contact inbox

## Ops

- [ ] `php artisan migrate --force` (production, not fresh)
- [ ] `php artisan storage:link`
- [ ] Supervisor queue worker (`deployment/supervisor/arena-worker.conf`)
- [ ] Daily DB backup (`deployment/scripts/backup-db.sh`)
- [ ] Cron: `* * * * * php artisan schedule:run`

## Optional (documented, not required for MVP)

- [ ] Pusher/Echo realtime (`BROADCAST_*`, `PUSHER_*`) — see DEVELOPER_GUIDE.md
- [ ] Stripe/payments — not implemented (H2)
