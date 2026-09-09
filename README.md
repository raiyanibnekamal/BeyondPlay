# Arena — Esports Tournament Platform

**Private proprietary software.** Copyright © 2026 [MD RAIYAN IBNE KAMAL](https://github.com/raiyanibnekamal). All rights reserved. See [LICENSE.md](LICENSE.md).

Original full-stack esports tournament platform: custom HTML/CSS/JS frontend and Laravel 12 API (`tournament-backend/`).

**Documentation:** [DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md) · [ARENA_SPEC.md](ARENA_SPEC.md) · [LICENSE.md](LICENSE.md) · [NEXT_STEPS.md](NEXT_STEPS.md)

## Quick start

### Backend

```bash
cd tournament-backend
composer install
cp .env.example .env
php artisan key:generate
```

**MySQL (XAMPP):** start Apache/MySQL in XAMPP, create DB `tournament_db` (or run `CREATE DATABASE tournament_db;`), set `DB_CONNECTION=mysql` in `.env`, then:

```bash
php artisan migrate:fresh --seed
php artisan serve
```

**SQLite (no XAMPP):** set `DB_CONNECTION=sqlite` in `.env`, then `php artisan migrate --seed` and `php artisan serve`.

API base URL: `http://127.0.0.1:8000/api/v1`

After `php artisan db:seed`, the console prints **one-time** passwords for:

- Admin: `admin@arena.gg`
- Player: `player@arena.gg`

Optional: set `ADMIN_SEED_PASSWORD` and `PLAYER_SEED_PASSWORD` in `.env` before seeding.

### Frontend

**Do not double-click HTML files** (`file:///...`). Browsers block manifests, SVG backgrounds, and API calls on the `file://` protocol (you will see CORS / `origin 'null'` errors).

Serve the project root over HTTP, then open the site in the browser:

```powershell
# Terminal 1 — API (from tournament-backend)
php artisan serve

# Terminal 2 — static frontend (from Tournament project root)
.\serve-frontend.ps1
```

Open **http://127.0.0.1:5500/index.html** (not `file:///D:/...`).

Alternative: `php -S 127.0.0.1:5500 -t .` from the `Tournament` folder, or VS Code **Live Server** on port 5500.

Local API: `http://127.0.0.1:8000/api/v1` (auto from `api.js` when using `:5500`). Production: set `ARENA_API_BASE` in `assets/js/config.js`.

For page analytics, the frontend sends `X-Arena-Page` on each request (via `arena-connect.js`).

### Queue (bulk email)

```bash
php artisan queue:work
```

### Weekly digest

Scheduled Mondays at 08:00 (see `routes/console.php`). Manual run:

```bash
php artisan arena:weekly-digest
```

## Environment variables (backend)

| Variable | Purpose |
|----------|---------|
| `APP_DEBUG` | Set `false` in production |
| `APP_URL` | Laravel app URL |
| `DB_*` | Database (MySQL `tournament_db` via XAMPP; SQLite optional) |
| `MAIL_*` | Order confirmations, digests, bulk email |
| `QUEUE_CONNECTION` | `database` or `sync` for dev |
| `FRONTEND_URL` | Password reset links; production site URL |
| `CORS_ALLOWED_ORIGINS` | Dev: `http://127.0.0.1:5500,http://localhost:5500` — Prod: your domain only (deny-all if unset) |
| `FRONTEND_URL` | `http://127.0.0.1:5500` (dev) or production site URL |

`.env` is listed in `.gitignore` — never commit secrets.

## Email (password reset, orders)

| Environment | Setup |
|-------------|--------|
| **Local dev** | `MAIL_MAILER=log` — reset links appear in `tournament-backend/storage/logs/laravel.log` |
| **Dev inbox** | [Mailtrap](https://mailtrap.io) — set `MAIL_MAILER=smtp` and Mailtrap credentials in `.env` |
| **Production** | Real SMTP (`MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`) |

Set `FRONTEND_URL=http://127.0.0.1:5500` (dev) or `https://yourdomain.com` (prod). Reset emails link to `{FRONTEND_URL}/reset-password.html?token=...&email=...`.

Avatar uploads require `php artisan storage:link` once (serves `storage/app/public`).

## Production deployment

See **[DEPLOY.md](DEPLOY.md)** for server setup, queue/cron, and post-deploy checks.  
Before go-live, run **[QA_CHECKLIST.md](QA_CHECKLIST.md)** and **[NEXT_STEPS.md](NEXT_STEPS.md)**. Optional later: Stripe payments, Pusher realtime.

## Automated tests

```bash
cd tournament-backend
php artisan test
php tests/e2e_full_test.php   # manual smoke (API must be running)
```

## API overview

- **Auth:** `/auth/register`, `/auth/login`, `/auth/logout`, `/auth/me`
- **Tournaments:** list, detail, register, bracket, matches, standings, predictions
- **Social:** friends, PvP challenges, notifications, activity feed
- **Shop:** products, coupons, orders, wishlist
- **Public:** `/banners/active`, `/sponsors`, `/contact`, `/newsletter/subscribe`
- **Admin:** users, games, coupons, blog, contact inbox, tournaments, matches, disputes, products, orders, banners, sponsors, settings, stats, notifications, bulk email, analytics

Full route list: `php artisan route:list --path=api/v1`

## Frontend pages

- Public: `index.html`, `tournament.html`, `tournament-details.html`, `shop.html`, `login.html`, `register.html`, …
- User dashboard: `my-profile.html`, `my-tournaments.html`, `my-orders.html`, …
- Admin: `admin/*.html` (20 pages)

## JS integration

- `assets/js/config.js` — API URL + cookie-auth flag (load before `api.js`)
- `assets/js/api.js` — API client (Bearer local / HttpOnly cookie in prod)
- `assets/js/arena-connect.js` — Public pages, auth, tournaments, shop, contact
- `assets/js/user-connect.js` — User dashboard pages
- `assets/js/admin-connect.js` + `admin-connect-extra.js` — Full admin panel
- `assets/js/checkout-connect.js`, `blog-connect.js`, `tournament-connect.js`
- `assets/js/cart.js`, `assets/js/validation.js`, `assets/js/bracket.js`

**Scope:** Full MVP (auth, tournaments, shop, admin, social). Not included: Stripe, Pusher, site search API. See [DEVELOPER_GUIDE.md — Section 1b](DEVELOPER_GUIDE.md#1b-functionality--scope-mvp).

401 responses on authenticated routes redirect to `login.html`.

## Project structure

```
Tournament/
├── assets/           # CSS, JS, images
├── admin/            # Admin HTML pages
├── *.html            # Public & user pages
├── tournament-backend/
│   ├── app/
│   ├── database/migrations/
│   └── routes/api.php
├── ARENA_SPEC.md
├── LICENSE.md
└── NEXT_STEPS.md
```

## API tests

With `php artisan serve` running:

```bash
cd tournament-backend
php tests/e2e_full_test.php
php tests/step20_final_test.php
```

## Security

See **[DEVELOPER_GUIDE.md — Section 8](DEVELOPER_GUIDE.md#8-security)** for full details (auth, admin guard, rate limits, gaps, production checklist).

Quick summary:

- Sanctum + bcrypt + admin middleware + API rate limits
- Security pass documented in [PRODUCTION_SECURITY.md](PRODUCTION_SECURITY.md)
- Production: HTTPS, CORS domain lock, `APP_DEBUG=false`, rotate seed passwords

## License

**Proprietary — all rights reserved.** This repository is private. You may not copy, redistribute, or reuse this codebase without written permission from **MD RAIYAN IBNE KAMAL** ([@raiyanibnekamal](https://github.com/raiyanibnekamal)). See [LICENSE.md](LICENSE.md).
