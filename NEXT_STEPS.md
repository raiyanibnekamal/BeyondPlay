# Arena — Next Steps

**Current score:** 96/100 codebase · ~94 after deploy + QA (see [PRODUCTION_READINESS.md](PRODUCTION_READINESS.md))  
**Goal:** Go live safely, then optional Stripe / Pusher / enterprise hardening.

---

## Progress tracker

| Phase | Automated | You (manual) |
|-------|-----------|--------------|
| **1** Local verification | Run `.\scripts\verify-phase1.ps1` | Browser QA in [QA_CHECKLIST.md](QA_CHECKLIST.md) |
| **2** Production deploy | Run `.\scripts\prepare-production.ps1` on server | HTTPS, domain, SMTP, rotate passwords |
| **3** Post-launch | — | Logs, backups, cron |
| **4** Optional | — | Stripe, Pusher, etc. |

### Done in repo (agent / code)

- [x] PHPUnit: **15 tests** (auth, admin, dashboard, settings, shop, production APIs)
- [x] Admin **Settings** API + form wired (`GET/PUT /admin/settings`)
- [x] `scripts/verify-phase1.ps1` — runs tests + E2E when API is up
- [x] `scripts/prepare-production.ps1` — migrate + test + deploy reminders
- [ ] Phase 1 manual browser QA (your checklist)
- [ ] Phase 2 live server deploy
- [ ] Phase 3–4 optional features

---

## Phase 1 — Local verification (today, ~30–60 min)

### Quick run (recommended)

```powershell
# Terminal 1 — API
cd "d:\New folder\Tournament\tournament-backend"
php artisan serve

# Terminal 2 — verify + frontend
cd "d:\New folder\Tournament"
.\scripts\verify-phase1.ps1
.\serve-frontend.ps1
```

Open **http://127.0.0.1:5500** (not `file://`).

**Expected:** PHPUnit **15/15**; E2E **21/21** when API is running (retry after 60s if 429 on register).

### Manual steps (detail)

#### 1. Start the stack

```powershell
# Terminal 1 — API
cd "d:\New folder\Tournament\tournament-backend"
php artisan serve

# Terminal 2 — Frontend
cd "d:\New folder\Tournament"
.\serve-frontend.ps1
```

#### 2. Automated tests only

```powershell
cd "d:\New folder\Tournament\tournament-backend"
php artisan test
php tests/e2e_full_test.php   # API must be running
```

#### 3. Manual browser QA

Work through [QA_CHECKLIST.md](QA_CHECKLIST.md). Minimum paths:

| # | Flow | Pages |
|---|------|--------|
| 1 | Register player (password **8+** chars, new email, accept Terms) | `register.html` |
| 2 | Login admin → dashboard (live stats) | `login.html` → `admin/index.html` |
| 3 | Login player → profile | `login.html` → `my-profile.html` |
| 4 | Shop → cart → checkout → order | `shop.html` → `checkout.html` → `my-orders.html` |
| 5 | Tournament register + bracket | `tournament.html` → `bracket.html?id=1` |
| 6 | Predictions (`?id=` + bracket generated) | `bracket-prediction.html?id=1` |
| 7 | Friends, activity feed, stats, h2h | `friends.html`, `activity-feed.html`, etc. |
| 8 | Admin: settings save, user ban, products | `admin/settings.html`, `admin/users.html` |

---

## Phase 2 — Production deploy (~2–4 hours)

```powershell
.\scripts\prepare-production.ps1
```

Then follow:

1. **[DEPLOY.md](DEPLOY.md)** — hosting layout (frontend + API + MySQL).
2. **[tournament-backend/production-checklist.md](tournament-backend/production-checklist.md)** — env, migrations, cache, queue.

### Environment (API `.env`)

| Variable | Production value |
|----------|------------------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `LOG_LEVEL` | `error` |
| `FRONTEND_URL` | Your real site URL (e.g. `https://arena.yourdomain.com`) |
| `CORS_ALLOWED_ORIGINS` | Same frontend origin only (no `*`) |
| `DB_*` | Strong password, non-root DB user |
| `MAIL_*` | Real SMTP (not `log`) for reset password |

### Frontend

Edit **[assets/js/config.js](assets/js/config.js)**:

```javascript
window.ARENA_API_BASE = "https://api.yourdomain.com/api/v1";
```

### Server commands (first deploy)

```bash
cd tournament-backend
composer install --no-dev --optimize-autoloader
cp .env.example .env   # then edit .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force   # first time only
php artisan storage:link
php artisan config:cache
php artisan route:cache
```

### Security immediately after seed

- Change passwords for `admin@arena.gg` and `player@arena.gg`.
- Do not leave default `password` on a public server.

### Process manager

- **Queue:** `php artisan queue:work` (Supervisor) if using bulk email / notifications queue.
- **Cron:** `* * * * * php /path/to/artisan schedule:run`

### HTTPS

- Force HTTPS on frontend and API.
- Confirm API CORS allows only your frontend domain.

---

## Phase 3 — Post-launch (first week)

| Task | Why |
|------|-----|
| Monitor `storage/logs/laravel.log` | Catch 500s early |
| Run `composer audit` monthly | Dependency security |
| Backup MySQL daily | Tournaments + orders data |
| Re-run `.\scripts\verify-phase1.ps1` after each deploy | Regression check |
| Enable GitHub Actions | Push triggers `.github/workflows/arena-tests.yml` |

---

## Phase 4 — Optional improvements (not required for MVP)

### Raise score toward 95–96

| Item | Effort | Status |
|------|--------|--------|
| Admin settings API | Done | `admin/settings.html` |
| More PHPUnit tests | Done | 15 tests |
| Browser E2E in CI (Playwright) | High | Not started |
| Wire remaining static admin rows | Low | Partial |

### Commercial / enterprise

| Item | Notes |
|------|--------|
| **Stripe** | Real payments on checkout |
| **Pusher / Echo** | Live notifications |
| **HttpOnly cookies** | Replace `localStorage` tokens |
| **2FA / email verify** | New auth flows |
| **Pen-test / WAF** | Infrastructure |

---

## Quick reference

| Doc / script | Purpose |
|--------------|---------|
| [PRODUCTION_READINESS.md](PRODUCTION_READINESS.md) | Score breakdown |
| [QA_CHECKLIST.md](QA_CHECKLIST.md) | Pre go-live browser tests |
| [DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md) | Architecture, APIs |
| [LICENSE.md](LICENSE.md) | Copyright and ownership |
| [DEPLOY.md](DEPLOY.md) | Hosting |
| `scripts/verify-phase1.ps1` | Phase 1 automation |
| `scripts/prepare-production.ps1` | Phase 2 prep |

### Test accounts (local seed only)

| ID | Email | Username | Role |
|----|-------|----------|------|
| 1 | `admin@arena.gg` | ArenaAdmin | Admin |
| 2 | `player@arena.gg` | ShadowStrike | Player |

Passwords are printed **once** when you run `php artisan db:seed` (or set `ADMIN_SEED_PASSWORD` / `PLAYER_SEED_PASSWORD` in `.env`).

**CORS:** `.env` must include `FRONTEND_URL=http://127.0.0.1:5500` and `CORS_ALLOWED_ORIGINS` for API calls from the frontend.

---

## Suggested order (checklist)

- [ ] Run `.\scripts\verify-phase1.ps1` (15 tests + E2E)
- [ ] Phase 1: Manual browser QA ([QA_CHECKLIST.md](QA_CHECKLIST.md))
- [ ] Phase 2: `.\scripts\prepare-production.ps1` + live `.env` + HTTPS + `config.js`
- [ ] Phase 2: Rotate seed passwords on production
- [ ] Phase 2: Smoke test on live URL
- [ ] Phase 3: Logs, backups, cron, queue
- [ ] Phase 4 (optional): Stripe / Pusher / Playwright CI

**When Phase 1–2 are done → live MVP (~94/100 operational).**
