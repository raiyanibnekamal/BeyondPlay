# Arena Tournament Platform — Developer Guide

> **Purpose:** Single source of truth for humans and AI assistants. Read this before changing code.  
> **Last updated:** June 2026  
> **Related docs:** `README.md` (quick start), `ARENA_SPEC.md` (technical spec), `LICENSE.md` (ownership), `NEXT_STEPS.md` (deploy)

---

## 1. Project overview

**Arena** is an esports tournament platform:

| Layer | Stack | Location |
|-------|--------|----------|
| Frontend | Custom HTML/CSS/JS (Arena UI) | `Tournament/` root (`*.html`, `assets/`, `admin/`) |
| Backend | Laravel 12 + Sanctum + Spatie Permission | `tournament-backend/` |
| Database | **MySQL** `tournament_db` (XAMPP) — SQLite optional | `.env` → `DB_CONNECTION` |

**Architecture:** Browser → static pages + `api.js` → `http://HOST:8000/api/v1` → Laravel → MySQL.

**Auth:** Sanctum Bearer token — `localStorage` (`arena_auth_token`) in local dev; optional **HttpOnly cookie** in production (`ARENA_HTTPONLY_AUTH_COOKIE=true` + `ARENA_USE_COOKIE_AUTH` in `config.js`).

**CORS (local):** Set in `.env`:

```env
FRONTEND_URL=http://127.0.0.1:5500
CORS_ALLOWED_ORIGINS=http://127.0.0.1:5500,http://localhost:5500
```

If missing, `APP_ENV=local` still allows `:5500` origins. Production denies all origins until these are set.

---

## 1b. Functionality & scope (MVP)

### In scope — implemented

| Area | Backend API | Frontend wired |
|------|-------------|----------------|
| Auth (register, login, logout, reset) | Yes | `login.html`, `register.html`, `forgot-password.html`, `reset-password.html` |
| Tournaments (list, detail, register, bracket, predictions) | Yes | `tournament.html`, `tournament-details.html`, `bracket.html`, `bracket-prediction.html` |
| Shop (products, cart, checkout, orders, wishlist, coupons) | Yes | `shop.html`, `cart.html`, `checkout.html`, `wishlist.html`, `my-orders.html` |
| User dashboard | Yes | `my-profile.html`, `my-tournaments.html`, `my-notifications.html`, `user-connect.js` |
| Social (friends, challenges, activity, stats, h2h) | Yes | Partial — API live; some pages mix API + static layout |
| Admin panel | Yes | `admin-connect.js` + `admin-connect-extra.js` on 20 admin pages |
| Content (blog, banners, sponsors) | Yes | `blog.html`, `index.html` banners; admin CRUD |
| Contact & newsletter | Yes | `contact.html` |
| Disputes & check-in | Yes | `dispute.html`, `checkin.html` |
| Settings | Yes | `admin/settings.html` |

### Out of scope / optional (not required for MVP)

| Item | Status |
|------|--------|
| Stripe / card payments | Not built — orders via API checkout |
| Pusher / live scores | Optional — `api.initEcho` hook only |
| Full-text site search | `search.html` static |
| Playwright in CI | Manual E2E script only |
| Enterprise WAF / pen-test | Ops / infrastructure |

### Demo users (after `db:seed`)

| ID | Email | Username | Role |
|----|-------|----------|------|
| 1 | `admin@arena.gg` | ArenaAdmin | admin |
| 2 | `player@arena.gg` | ShadowStrike | user |

Passwords: printed once at seed (or set `ADMIN_SEED_PASSWORD` / `PLAYER_SEED_PASSWORD` in `.env`). **Not** hardcoded as `password`.

---

## 2. How to run locally

### Requirements

- PHP 8.2+, Composer
- XAMPP (MySQL) or SQLite
- Node not required (static frontend)

### Backend

```powershell
cd tournament-backend
composer install
# .env: DB_CONNECTION=mysql, DB_DATABASE=tournament_db, DB_USERNAME=root, DB_PASSWORD=
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

API: `http://127.0.0.1:8000/api/v1`

### Frontend (required — do not open `file://`)

```powershell
cd Tournament
.\serve-frontend.ps1
```

Open: `http://127.0.0.1:5500/index.html`

`api.js` resolves API as `http://<same-host-as-page>:8000/api/v1`.

### Queue (bulk email)

```powershell
php artisan queue:work
```

### Test accounts (after seed)

See **Section 1b** — passwords are shown in the terminal when you run `php artisan db:seed`.

---

## 3. Repository structure

```
Tournament/
├── index.html, login.html, register.html, …     # 41 public/user pages
├── admin/                                       # 20 admin HTML pages
├── assets/
│   ├── css/, img/, fonts/
│   └── js/
│       ├── api.js              # API client
│       ├── arena-connect.js    # Public pages + auth forms
│       ├── user-connect.js     # User dashboard pages
│       ├── admin-connect.js    # Admin API wiring
│       ├── validation.js       # Form validation (excludes auth forms)
│       ├── cart.js, bracket.js, main.js
├── serve-frontend.ps1 / .bat
├── README.md
├── LICENSE.md
├── DEVELOPER_GUIDE.md          # this file
├── ARENA_SPEC.md
└── tournament-backend/
    ├── app/Http/Controllers/
    ├── app/Services/
    ├── database/migrations/     # 36 migrations
    ├── database/seeders/DatabaseSeeder.php
    ├── routes/api.php
    └── tests/                  # step17–20, e2e, register_test
```

---

## 4. Build progress (Steps 01–20)

### Phase 1 — Frontend (Steps 01–12) — DONE

| Step | Topic | Status |
|------|--------|--------|
| 01 | Arena branding and UI system | Done |
| 02 | Content and copy aligned to Arena | Done |
| 03–12 | New pages (auth, dashboard, admin HTML, shop, social, etc.) | Done (HTML/CSS) |

**Note:** Many pages are still **static mock data** in HTML; only wired pages load from API (see Section 6).

### Phase 2 — Backend (Steps 13–20) — DONE (API)

| Step | Topic | Status |
|------|--------|--------|
| 13 | Laravel 12, migrations, Sanctum, packages | Done |
| 14 | Models, seeders | Done |
| 15 | Auth API, admin middleware | Done |
| 16 | Tournaments, matches, teams, brackets, disputes | Done |
| 17 | Friends, PvP, achievements, notifications, broadcast event | Done |
| 18 | Player stats, predictions, game suggestions admin | Done |
| 19 | Shop, orders, coupons, wishlist, mailables | Done |
| 20 | Banners, sponsors, contact, newsletter, analytics, bulk email, weekly digest | Done |

### Phase 3 — Frontend ↔ API integration — MOSTLY DONE

Core flows use `arena-connect.js`, `user-connect.js`, `admin-connect.js`, and `admin-connect-extra.js`. Marketing pages (`gallery.html`, `team.html` cards, etc.) may still show static demo content when API data is not bound.

---

## 5. Backend API inventory

Run: `php artisan route:list --path=api/v1`

### Implemented route groups

| Group | Prefix | Auth |
|-------|--------|------|
| Auth | `/auth/register`, `/login`, `/logout`, `/me`, forgot/reset password | Public + Sanctum |
| Tournaments | list, show, register, withdraw, bracket, matches, standings, rules, history, predict | Mixed |
| Matches | show, checkin, dispute, replay | Mixed |
| Teams | CRUD, invite, show | Sanctum |
| Games | list, show, suggest, my suggestions | Mixed |
| Social | friends, PvP challenges | Sanctum |
| User | stats, achievements, tournaments, notifications, activity-feed, orders, profile update | Sanctum |
| Shop | products, orders, cart coupon, wishlist | Mixed |
| Public | banners/active, sponsors, contact, newsletter | Public |
| Admin | users, games, coupons, posts, contact-messages, tournaments, matches, disputes, products, orders, banners, sponsors, settings, stats, notifications, bulk-email, analytics | Sanctum + `admin` |
| Stats | player, head-to-head | Public / auth |

### Not built (post-MVP)

| Feature | Notes |
|---------|--------|
| Stripe / payment gateway | Checkout creates orders in DB only |
| Pusher realtime | Config placeholders only |
| Public site search API | No `/search` endpoint |

---

## 6. Frontend integration matrix

### JS modules

| File | Role |
|------|------|
| `api.js` | All HTTP; `request()` + optional no-op fallbacks; 401 → `login.html` |
| `arena-connect.js` | Banners, sponsors, login, register, forgot password, tournaments list/detail, shop snippet, games count, contact, suggest-game, checkin, dispute, header auth |
| `user-connect.js` | `my-profile`, `my-tournaments`, `my-orders`, `my-notifications`, `achievements` — requires login |
| `admin-connect.js` | Admin gate; users, products, orders, tournaments, matches, disputes, coupons, contact inbox, bulk email |
| `admin-connect-extra.js` | Dashboard stats, banners, sponsors, blog, games, tournament edit, settings, notifications |
| `validation.js` | Validates forms; **delegates** `#login-form`, `#register-form`, `#profile-form`, etc. |
| `cart.js` | Local cart (not fully synced with API checkout) |
| `bracket.js` | Bracket UI helper (needs tournament id from URL) |
| `main.js` | Theme (sliders, `.ajax-contact` mailer) — **auth forms must NOT use class `ajax-contact`** |

### Pages with API wiring (approximate)

| Page | Wired? | Via |
|------|--------|-----|
| `index.html`, `about.html` | Partial | banners, sponsors |
| `login.html`, `register.html`, `forgot-password.html` | Yes | arena-connect |
| `tournament.html`, `tournament-details.html` | Partial | list, detail, register button |
| `shop.html` | Partial | product list overlay |
| `contact.html` | Partial | contact submit |
| `suggest-game.html` | Partial | game suggest |
| `checkin.html`, `dispute.html` | Partial | if form/button ids present |
| `my-profile.html` | Yes | user-connect |
| `my-tournaments.html`, `my-orders.html`, `my-notifications.html` | Yes | user-connect |
| `admin/*.html` (20 pages) | Yes (core tables/forms) | admin-connect + admin-connect-extra |
| `checkout.html`, `blog.html`, `bracket-prediction.html` | Yes | checkout-connect, blog-connect, tournament-connect |
| `gallery.html`, `search.html`, some listing cards | Partial / static | Demo layout only |

### `api.js`

All public methods call `request()` against `/api/v1`. If the API is unreachable, `request()` returns `{ ok: false, offline: true, ... }` with a setup hint (no fake success).

Some backend routes may still return 404 until implemented; connect scripts should surface API errors instead of silent placeholders.

**Fixed (June 2026):** Removed duplicate client definitions that overwrote real `listBanners`, `listSponsors`, `analyticsOverview` at end of `admin` object.

---

## 7. Database

### Current config

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tournament_db
DB_USERNAME=root
DB_PASSWORD=
```

### Switch back to SQLite

Comment MySQL lines, set `DB_CONNECTION=sqlite`, run `php artisan migrate:fresh --seed`.

### Main tables (migrations)

`users`, `games`, `tournaments`, `tournament_registrations`, `teams`, `team_members`, `matches`, `disputes`, `tournament_checkins`, `point_table`, `player_stats`, `products`, `orders`, `coupons`, `wishlists`, `friends`, `pvp_challenges`, `achievements`, `notifications`, `blog_posts`, `comments`, `banners`, `sponsors`, `contact_messages`, `newsletter_subscribers`, `page_views`, Spatie `roles/permissions`, Sanctum `personal_access_tokens`.

### Important model note

- Match model: `App\Models\GameMatch` → table `matches` (not `GameMatch` table name).

---

## 8. Security

Summary of what is **implemented today** vs what is **still required for production**. Use this when auditing or before go-live.

### 8.1 Authentication & passwords

| Control | Implementation |
|---------|----------------|
| API authentication | **Laravel Sanctum** — Bearer token on protected routes (`auth:sanctum`) |
| Password storage | **Bcrypt** via Eloquent (`User` model casts / `Hash::check` on login) |
| Registration rules | `RegisterRequest`: unique email/username, password min 8 + `confirmed` |
| Login errors | Generic message (does not reveal whether email exists) |
| Banned accounts | Login rejected when `user.status === 'banned'` |
| Logout | Deletes **current** access token only (`currentAccessToken()->delete()`) |
| Token location (frontend) | Local: `localStorage` (`arena_auth_token`). Prod option: HttpOnly cookie (`arena_auth`) |
| Registration password | Min **8** characters + confirmation (no symbol rule) |

**Files:** `app/Http/Controllers/Auth/AuthController.php`, `app/Http/Requests/Auth/*`, `config/sanctum.php`

### 8.2 Authorization (access control)

| Area | Rule |
|------|------|
| All `/api/v1/admin/*` | `auth:sanctum` + **`admin` middleware** (`AdminMiddleware`) |
| Admin check | Spatie role `admin` **or** `users.role === 'admin'` |
| Team update / invite | Captain only → **403** if not captain |
| Match check-in | User must be registered for tournament → **403** |
| User-scoped data | Orders, notifications, profile, etc. tied to `$request->user()` |

**Files:** `app/Http/Middleware/AdminMiddleware.php`, `bootstrap/app.php` (middleware alias), `routes/api.php` (admin group)

**Gap:** Admin **HTML** pages (`admin/*.html`) are public static files. Security is on the **API only** — without a valid admin token, admin API calls return 403.

### 8.3 Rate limiting (anti abuse)

Defined in `routes/api.php`:

| Endpoint group | Limit |
|----------------|--------|
| `POST /auth/register`, `/login` | **20 / minute** |
| `POST /auth/forgot-password`, `/reset-password` | **5 / minute** |
| Authenticated routes (`auth:sanctum`) | **60 / minute** per user |
| `POST /tournaments/{id}/register` | **10 / minute** |
| `POST /orders` | **15 / minute** |
| `POST /friends/request/{userId}` | **20 / minute** |
| `POST /contact`, `/newsletter/subscribe` | **10 / minute** |
| `POST /games/suggest` | **5 / minute** |
| `POST /matches/{id}/dispute` | **10 / minute** |
| `POST /admin/bulk-email` | **3 / minute** |

Returns **429** when exceeded.

### 8.4 Input validation & API safety

- **Form Requests** for auth, tournaments, disputes, teams, game suggestions.
- Controllers use `$request->validate()` for many write operations.
- **Eloquent** ORM for DB access (avoid raw SQL with unsanitized user input).
- **API errors as JSON** for `api/*` routes (`bootstrap/app.php` → `shouldRenderJsonWhen`).
- **Signed URL** on digital order download: `orders/{order}/download/{product}` uses `signed` middleware.

### 8.5 Secrets & configuration

| Item | Status |
|------|--------|
| `.env` in `.gitignore` | Yes — do not commit credentials |
| `APP_KEY` | Required for encryption/signed URLs |
| Default seed passwords | Random or `ADMIN_SEED_PASSWORD` env — **rotate before go-live** |
| `APP_DEBUG` | `true` in local dev — must be **`false` in production** |

### 8.6 Frontend security (partial)

| Control | Status |
|---------|--------|
| Bearer header on authenticated API calls | Yes (`api.js` → `buildHeaders`) |
| 401 handling | Clears token, redirects to `login.html` |
| Auth forms isolated from theme mailer | No `ajax-contact` on login/register/profile |
| Client-side validation | `validation.js` (length, email, password match, terms) |
| XSS when rendering API data | **Partial** — use `esc()` everywhere (e.g. `user-connect.js`); audit any `innerHTML` |

### 8.7 Remaining production gaps

| Risk | Status |
|------|--------|
| HTTPS / HSTS | Configure on web server before go-live |
| Strict CORS | Set `CORS_ALLOWED_ORIGINS` + `FRONTEND_URL`; production defaults to **deny all** if unset |
| HttpOnly cookie tokens | Available via `ARENA_HTTPONLY_AUTH_COOKIE` + `ARENA_USE_COOKIE_AUTH`; local dev uses Bearer in `localStorage` |
| 2FA / email verification | Not built |
| Security headers | `router.php` + `serve-frontend.ps1` send `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy` |
| WAF / IDS | Infrastructure — not in repo |
| PCI / Stripe | Checkout is API/manual order flow; no card processor (see `PRODUCTION_READINESS.md`) |
| Penetration test | Run `composer audit` + external review before high-traffic launch |
| Password reset email | Requires real `MAIL_*` in production |
| Realtime (Pusher) | Optional; `api.initEcho` hook only |

**Implemented since earlier docs:** Full admin API surface, security hardening (`PRODUCTION_SECURITY.md`), CORS local dev fallback, `config.js` production guard, 15 PHPUnit tests, GitHub Actions (`.github/workflows/arena-tests.yml`).

### 8.8 Production security checklist

Before deploying:

1. Set `APP_DEBUG=false`, `APP_ENV=production`, strong unique `APP_KEY`.
2. Use **HTTPS** for frontend and API; enforce redirect HTTP → HTTPS.
3. Configure **CORS** `allowed_origins` to your real domain only (not `*`).
4. Change all default passwords; remove or protect seed accounts.
5. Set real **`MAIL_*`**, **`QUEUE_*`**, database credentials via env (never in git).
6. Run MySQL with least-privilege DB user (not `root` with empty password).
7. Run `php artisan config:cache` and `route:cache` in production.
8. Keep `tournament-backend/storage` and `bootstrap/cache` non-writable by web root where possible.
9. Review JS that sets `innerHTML` with user-generated content — escape or use `textContent`.
10. Add frontend **admin gate** (redirect non-admin away from `admin/` after login).
11. Plan **token refresh** or short-lived tokens if sessions are long-lived.
12. Schedule dependency updates (`composer audit`, npm if added later).

### 8.9 Security-related files (quick reference)

| Topic | Path |
|-------|------|
| API routes & throttles | `tournament-backend/routes/api.php` |
| Admin guard | `app/Http/Middleware/AdminMiddleware.php` |
| Auth logic | `app/Http/Controllers/Auth/AuthController.php` |
| Password reset | `app/Http/Controllers/Auth/PasswordResetController.php` |
| Sanctum config | `config/sanctum.php` |
| Roles / permissions | Spatie migrations + `DatabaseSeeder` |
| Frontend token | `assets/js/api.js` (`AUTH_TOKEN_KEY`, `handleUnauthorized`) |
| Form hardening | `assets/js/validation.js` (`DELEGATED_FORM_IDS`) |

---

## 9. Known problems & gotchas

### Critical / frequent

| Issue | Symptom | Fix |
|-------|---------|-----|
| Opening HTML via `file://` | CORS, manifest errors, API fails | Use `serve-frontend.ps1` → `http://127.0.0.1:5500` |
| Backend not running | "Cannot reach the API" in forms | `php artisan serve` |
| MySQL stopped in XAMPP | 500 / connection refused | Start MySQL in XAMPP |
| `ajax-contact` on auth forms | Register/login does nothing | Forms must **not** use class `ajax-contact` (see `register.html`) |
| `main.js` hijacks `.ajax-contact` | Fake contact validation on wrong forms | Keep auth forms without that class |
| Register without Terms checkbox | Client-side block | Check Terms on register page |
| Throttle on register | 429 after many attempts | `throttle:20,1` on `/auth/register` — wait or retry E2E |

### Integration gaps

| Issue | Details |
|-------|---------|
| Admin pages | Core flows wired (users, tournaments, products, orders, disputes); some tables still show seed/demo rows until API load runs |
| Checkout | `checkout-connect.js` + order API; no Stripe |
| Bracket/point-table | Use `?id=` / `tournament_id` query params with bracket API |
| Blog | DB tables exist; limited public/admin API |
| Realtime notifications | Echo/Pusher optional (`BROADCAST_*`) |
| Password reset UI | `forgot-password.html` + API; email needs production `MAIL_*` |
| Admin login | Same `login.html`; `admin-connect.js` `requireAdmin()` gates `admin/` |
| Production score | See `PRODUCTION_READINESS.md` (~88/100 MVP) |
| Duplicate HTML minified | Many `.html` files are 1–3 lines (minified) — hard to edit by hand |

### Backend / ops

| Issue | Details |
|-------|---------|
| `php artisan db:show` on some XAMPP builds | `performance_schema` error — ignore; app DB works |
| `migrate:fresh` wipes data | Document before running in production |
| Bulk email | Requires `queue:work` if `QUEUE_CONNECTION=database` |
| Weekly digest | `php artisan arena:weekly-digest`; scheduled Mondays in `routes/console.php` |

### Historical

- XAMPP MySQL InnoDB corruption once forced SQLite; project switched back to MySQL `tournament_db`.
- Migration order: `matches` must exist before `tournament_checkins`.

---

## 10. Testing

| Script | Command | Covers |
|--------|---------|--------|
| Register | `php tests/register_test.php` | POST `/auth/register` |
| Step 17 | `php tests/step17_social_test.php` | Friends, notifications |
| Step 18 | `php tests/step18_stats_test.php` | Stats, predictions |
| Step 19 | `php tests/step19_shop_test.php` | Shop flow |
| Step 20 | `php tests/step20_final_test.php` | Banners, contact, etc. |
| E2E | `php tests/e2e_full_test.php` | Register → tournament → shop → admin |

Requires `php artisan serve` on port 8000.

---

## 11. Recommended next work (priority)

Use this section when planning tasks or prompting AI.

### P0 — Must fix for production feel

1. Wire **checkout** → `POST /orders` + coupon validation.
2. **Admin role guard** on frontend: redirect `admin@arena.gg` to `admin/index.html` after login.
3. Implement **admin users API** + wire `admin/users.html`.
4. Wire remaining **admin** pages: tournaments, matches, disputes, game-suggestions, banners, sponsors, orders (CRUD UI).
5. Remove or implement all **optional `api.js` fallbacks** used by pages you ship.

### P1 — Core product

6. **Bracket** + **point-table** pages → tournament id from query + API bracket/standings.
7. **Friends**, **activity-feed**, **wishlist** pages → existing social/shop APIs.
8. **Team** pages → wire `teams` API (backend exists).
9. **Blog** public + admin APIs + `blog.html` / `admin/blog.html`.
10. **Admin coupons** API + admin UI.

### P2 — Polish

11. Configure **Pusher/Echo** for realtime notifications.
12. **Avatar upload** endpoint + `my-profile.html`.
13. **Contact messages** admin inbox (`contact_messages` table).
14. **Payment** integration (Stripe/PayPal) — currently test/manual payment fields.
15. Split minified HTML or add build step for maintainability.
16. PHPUnit Feature tests (only standalone `tests/*.php` scripts exist today).
17. Production: `APP_DEBUG=false`, CORS origins, HTTPS, proper `MAIL_*`, Redis queue.

---

## 12. AI assistant checklist

When the user asks for a change, check:

1. **Is it frontend, backend, or both?**
2. **Does the API route exist?** (`routes/api.php` or `route:list`)
3. **Is the endpoint live?** Search `api.js` for `request(` vs `apiFallback(` for that method.
4. **Is the page wired?** Search `arena-connect.js`, `user-connect.js`, `admin-connect.js`.
5. **Auth:** Does it need `arena_auth_token`? Admin needs `admin` role (Spatie).
6. **Forms:** Never add `ajax-contact` to login/register/profile forms.
7. **Serve:** Remind user if error looks like `file://` or connection refused.
8. **DB:** MySQL `tournament_db` — re-seed loses data (`migrate:fresh --seed`).
9. **Security:** Read **Section 8** before auth/admin/payment changes; never commit `.env`.

### AI assistant brief

```
Read DEVELOPER_GUIDE.md and ARENA_SPEC.md Section [X].
Task: [describe]
Constraints: minimal diff, match existing patterns; wire real API routes when they exist.
Verify: php tests/e2e_full_test.php or relevant step test.
```

---

## 13. Key file reference

| Task | Files |
|------|--------|
| Add API route | `tournament-backend/routes/api.php`, new Controller |
| Auth | `Auth/AuthController.php`, `RegisterRequest.php` |
| Admin middleware | `AdminMiddleware.php`, `bootstrap/app.php` |
| Frontend API call | `assets/js/api.js` |
| Page behavior | `arena-connect.js` / `user-connect.js` / `admin-connect.js` |
| Form validation | `assets/js/validation.js` — `DELEGATED_FORM_IDS` |
| Seed data | `database/seeders/DatabaseSeeder.php` |
| Bracket logic | `app/Services/BracketService.php` |
| Notifications | `app/Services/NotificationService.php` |
| Security audit | **Section 8**, `routes/api.php`, `AdminMiddleware.php` |

---

## 15. api.js — all production endpoints wired

Optional features not in MVP (may use `apiFallback` in `api.js` until backend exists):

| Feature | Notes |
|---------|--------|
| H2 Stripe/payments | Orders use `payment_method` text field; see Section 11 |
| H1 Pusher realtime | `api.initEcho` on `my-notifications.html` when `BROADCAST_DRIVER=pusher` + keys set |

New routes (production pass): `GET/POST /posts`, admin blog/games, `PUT /admin/users/{id}/promote`, `POST /admin/notifications/send`, `GET /admin/stats`.

---

## 14. Changelog (maintainer notes)

| Date | Change |
|------|--------|
| 2026-06 | Steps 13–20 backend completed |
| 2026-06 | MySQL `tournament_db` active (was SQLite during XAMPP issues) |
| 2026-06 | Auth forms fixed (`ajax-contact` removed, arena-connect capture) |
| 2026-06 | `user-connect.js`, `admin-connect.js`, e2e tests added |
| 2026-06 | Admin product/order API + partial admin-connect |
| 2026-06 | `PUT /user/profile` added; duplicate api.js admin client methods removed |
| 2026-06 | This `DEVELOPER_GUIDE.md` created |
| 2026-06 | **Section 8 — Security** added (implemented vs gaps + production checklist) |
| 2026-06 | Original Arena platform — full-stack release |
| 2026-06 | Production fixes A1–A3, B1–B2, C1, D partial, E1 teams, `DEPLOY.md`, `config/cors.php` |
| 2026-06 | C2–C4 user pages, D5 coupons, D6 contact inbox, `shop-connect.js`, `tournament-connect.js` |
| 2026-06 | F1 `arena-escape.js`, F2 avatar upload, F3 reset-password wiring, G2 PHPUnit Feature tests |
| 2026-06-03 | HTML script corruption fix, `ProductionApiTest`, CI workflow, `PRODUCTION_READINESS.md`, tournament-history/h2h wiring |
| 2026-06 | Full ARENA pass: E2 blog, D1–D4 admin-extra, E1 teams, promote, `QA_CHECKLIST.md` — all prompts complete |

---

*Update this file when you add routes, wire pages, or resolve known issues so AI and developers stay aligned.*
