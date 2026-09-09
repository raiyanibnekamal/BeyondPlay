# Arena Tournament Platform — Complete Project Inventory

> **Purpose:** Every asset, page, route, model, script, and config in one place.  
> **Use with:** `ARENA_FULL_AUDIT.md` (gaps & scores), `DEVELOPER_GUIDE.md` (how to run)  
> **Last updated:** June 2026

---

## Table of contents

1. [Project summary](#1-project-summary)
2. [Repository tree](#2-repository-tree)
3. [All HTML pages (63)](#3-all-html-pages-63)
4. [Frontend JavaScript (22 files)](#4-frontend-javascript-22-files)
5. [Frontend CSS & assets](#5-frontend-css--assets)
6. [Backend API routes (~100)](#6-backend-api-routes-100)
7. [Backend models (32)](#7-backend-models-32)
8. [Database tables (46)](#8-database-tables-46)
9. [Controllers (41)](#9-controllers-41)
10. [Services (10)](#10-services-10)
11. [Middleware & auth](#11-middleware--auth)
12. [Seed data](#12-seed-data)
13. [Tests](#13-tests)
14. [Deployment & scripts](#14-deployment--scripts)
15. [Documentation files](#15-documentation-files)
16. [Environment variables](#16-environment-variables)
17. [Third-party packages](#17-third-party-packages)
18. [Page → script → API map](#18-page--script--api-map)

---

## 1. Project summary

| Metric | Value |
|--------|-------|
| Project name | Arena Tournament Platform |
| Frontend pages | 63 HTML (42 public + 21 admin) |
| API endpoints | ~100 under `/api/v1` |
| Eloquent models | 32 |
| DB migrations | 37 files → ~46 tables |
| Controllers | 41 |
| Service classes | 10 |
| Custom JS modules | 18 (+ jQuery, app.min.js) |
| PHPUnit tests | 15 (all passing) |
| Default local URLs | Frontend `:5500`, API `:8000` |

---

## 2. Repository tree

```
Tournament/
├── *.html                          # 42 public/user pages
├── admin/                            # 21 admin pages + admin.js + admin.css
├── assets/
│   ├── css/                        # style.css, arena-ui.css, app.min.css, fontawesome
│   ├── js/                         # api.js, *-connect.js, cart.js, arena-ui.js, …
│   ├── img/                        # logos, favicons, backgrounds, product images
│   └── fonts/                      # warPriest, theme fonts
├── deployment/
│   ├── scripts/                    # apply-arena-ui, fix-interconnectivity, backup-db
│   └── supervisor/                 # arena-worker.conf
├── scripts/                        # prepare-production.ps1, verify-phase1.ps1
├── .github/workflows/              # arena-tests.yml
├── serve-frontend.ps1 / .bat
├── router.php                      # PHP built-in server + security headers
├── README.md, DEVELOPER_GUIDE.md, ARENA_FULL_AUDIT.md, …
└── tournament-backend/
    ├── app/
    │   ├── Http/Controllers/       # 41 controllers
    │   ├── Http/Middleware/        # 3 custom
    │   ├── Http/Requests/          # Form requests
    │   ├── Models/                 # 32 models
    │   ├── Services/               # 10 services
    │   └── Mail/                   # Mailables
    ├── bootstrap/app.php
    ├── config/                     # arena.php, cors.php, sanctum.php, …
    ├── database/migrations/        # 37 migrations
    ├── database/seeders/           # DatabaseSeeder.php
    ├── routes/api.php
    └── tests/                      # PHPUnit + e2e scripts
```

---

## 3. All HTML pages (63)

### 3.1 Public — Auth (4)

| Page | Title area | Connect scripts | API |
|------|------------|-----------------|-----|
| `login.html` | Login | arena-connect | `POST /auth/login` |
| `register.html` | Register | arena-connect | `POST /auth/register` |
| `forgot-password.html` | Forgot password | arena-connect | `POST /auth/forgot-password` |
| `reset-password.html` | Reset password | arena-connect | `POST /auth/reset-password` |

### 3.2 Public — Marketing (7)

| Page | API wired |
|------|-----------|
| `index.html` | Banners, sponsors (arena-connect) |
| `about.html` | Sponsors |
| `contact.html` | Contact form POST |
| `gallery.html` | Static only |
| `service-details.html` | Static only |
| `terms.html` | Static legal text |
| `error.html` | Static 404 |

### 3.3 Public — Tournaments (9)

| Page | Connect script | API |
|------|----------------|-----|
| `tournament.html` | arena-connect | `GET /tournaments` |
| `tournament-details.html` | arena-connect | `GET /tournaments/{slug}`, register |
| `tournament-rules.html` | — | **Not wired** (static HTML) |
| `tournament-history.html` | user-connect | `GET /tournaments/history` |
| `bracket.html` | tournament-connect | `GET /tournaments/{id}/bracket` |
| `bracket-prediction.html` | tournament-connect | predict + leaderboard |
| `point-table.html` | tournament-connect | `GET /tournaments/{id}/standings` |
| `checkin.html` | arena-connect | `POST /matches/{id}/checkin` |
| `dispute.html` | arena-connect | `POST /matches/{id}/dispute` |

### 3.4 Public — Shop (6)

| Page | Connect script | API |
|------|----------------|-----|
| `shop.html` | arena-connect | `GET /products` |
| `shop-details.html` | shop-connect | `GET /products/{slug}` |
| `cart.html` | cart.js (localStorage) | — |
| `checkout.html` | checkout-connect | `POST /orders`, coupon validate |
| `wishlist.html` | shop-connect | wishlist API |
| `my-orders.html` | user-connect | `GET /user/orders` |

### 3.5 User dashboard (5)

| Page | Connect script | API |
|------|----------------|-----|
| `my-profile.html` | user-connect | profile GET/PUT, avatar |
| `my-tournaments.html` | user-connect | user tournaments + create |
| `my-notifications.html` | user-connect | notifications |
| `achievements.html` | user-connect | achievements |
| `stats.html` | user-connect | user stats |

### 3.6 Social / stats (3)

| Page | Connect script |
|------|----------------|
| `friends.html` | user-connect |
| `activity-feed.html` | user-connect |
| `head-to-head.html` | user-connect |

### 3.7 Games / teams / players (7)

| Page | Connect script |
|------|----------------|
| `game.html` | arena-connect |
| `game-details.html` | arena-connect |
| `suggest-game.html` | arena-connect |
| `team.html` | team-connect |
| `team-details.html` | team-connect |
| `player-profile.html` | player-connect |
| `bracket.html` | (listed above) |

### 3.8 Blog (2)

| Page | Connect script |
|------|----------------|
| `blog.html` | blog-connect |
| `blog-details.html` | blog-connect |

### 3.9 Admin panel (21)

All in `admin/` — load `admin-connect.js`, `admin-connect-extra.js`, `admin.js`, `admin.css`.

| Page | Primary function |
|------|------------------|
| `admin/index.html` | Dashboard stats |
| `admin/users.html` | User list, ban, promote |
| `admin/tournaments.html` | Tournament list |
| `admin/tournament-edit.html` | Create/edit tournament, rules, generate bracket |
| `admin/matches.html` | Match list |
| `admin/match-edit.html` | Score, replay |
| `admin/disputes.html` | Resolve disputes |
| `admin/game-suggestions.html` | Approve/reject suggestions |
| `admin/games.html` | Game CRUD |
| `admin/products.html` | Product CRUD |
| `admin/orders.html` | Order status |
| `admin/coupons.html` | Coupon CRUD |
| `admin/blog.html` | Blog post list |
| `admin/blog-edit.html` | Blog editor |
| `admin/banners.html` | Banner CRUD |
| `admin/sponsors.html` | Sponsor CRUD |
| `admin/settings.html` | Site settings |
| `admin/notifications.html` | Send user notifications |
| `admin/bulk-email.html` | Bulk email |
| `admin/analytics.html` | Analytics overview |
| `admin/contact-messages.html` | Contact inbox |

### 3.10 Missing page (referenced in docs)

| Page | Status |
|------|--------|
| `search.html` | **Does not exist** |

---

## 4. Frontend JavaScript (22 files)

### 4.1 Core

| File | Lines (approx) | Purpose |
|------|----------------|---------|
| `config.js` | ~30 | `ARENA_API_BASE`, cookie auth flag, prod guard |
| `api.js` | ~800+ | Full REST client for `/api/v1` |
| `arena-escape.js` | ~50 | XSS helpers (`escHtml`, `safeHref`) |

### 4.2 Connect scripts (API wiring)

| File | Pages served |
|------|--------------|
| `arena-connect.js` | All 42 public pages |
| `user-connect.js` | 10 dashboard/social pages |
| `shop-connect.js` | shop-details, wishlist |
| `checkout-connect.js` | checkout |
| `tournament-connect.js` | bracket, bracket-prediction, point-table |
| `blog-connect.js` | blog, blog-details |
| `player-connect.js` | player-profile |
| `team-connect.js` | team, team-details |
| `admin-connect.js` | Admin tables/forms |
| `admin-connect-extra.js` | Admin dashboard, settings, analytics |

### 4.3 UI / client-only

| File | Purpose |
|------|---------|
| `arena-ui.js` | Preloader, navbar layout, scroll perf, titles, checkout UI |
| `arena-auth-guard.js` | Redirect if not logged in (16 pages) |
| `cart.js` | localStorage cart (`arena_cart`), badge |
| `bracket.js` | Bracket/prediction UI rendering |
| `checkin.js` | Check-in countdown UI |
| `validation.js` | Client validation (skips auth forms) |
| `main.js` | Theme: sliders, WOW, mobile menu |
| `app.min.js` | Vendor bundle |
| `vendor/jquery-3.7.1.min.js` | jQuery |

### 4.4 Admin

| File | Purpose |
|------|---------|
| `admin/admin.js` | Admin shell: sidebar, tables UI |

### 4.5 `api.js` namespaces

| Namespace | Methods (summary) |
|-----------|-------------------|
| `api.auth` | register, login, logout, me, forgotPassword, resetPassword |
| `api.tournaments` | list, show, register, withdraw, bracket, matches, standings, rules, history, predict, create |
| `api.matches` | show, checkin, dispute, replay |
| `api.teams` | list, show, create, update, invite |
| `api.games` | list, show, suggest, mySuggestions |
| `api.shop` | products, orders, wishlist, validateCoupon |
| `api.user` | profile, stats, achievements, tournaments, notifications, activity, orders |
| `api.social` | friends, challenges |
| `api.stats` | player, headToHead, me |
| `api.blog` | posts, comments |
| `api.public` | banners, sponsors, contact, newsletter, lookupUser |
| `api.admin` | Full admin CRUD (~40 methods) |
| `api.initEcho` | Optional Pusher hook |

**Note:** No `return stub` fake responses — offline returns `{ ok: false, offline: true }`.

---

## 5. Frontend CSS & assets

### 5.1 CSS

| File | Purpose |
|------|---------|
| `assets/css/style.css` | Main Arena/gaming theme (minified) |
| `assets/css/arena-ui.css` | Custom overrides: navbar, perf, breadcrumbs, checkout |
| `assets/css/app.min.css` | Bootstrap/vendor bundle |
| `assets/css/fontawesome.min.css` | Icons |
| `admin/admin.css` | Admin panel dark theme |

### 5.2 Images (key)

| Path | Use |
|------|-----|
| `assets/img/logo.svg` | Header/footer logo (green mark + wordmark) |
| `assets/img/logo-icon.svg` | Icon only |
| `assets/img/favicons/*` | PWA / browser icons |
| `assets/img/bg/*` | Hero, breadcrumb, footer backgrounds |
| `assets/img/normal/*`, `product/*`, `game/*` | Page content |

### 5.3 Fonts

| Font | Use |
|------|-----|
| Rajdhani | Titles |
| Poppins | Body |
| Goldman | Logo-style text |
| warPriest / warPriest3d | Gaming display |
| Font Awesome 6 Pro | Icons |

---

## 6. Backend API routes (~100)

Base: `http://HOST:8000/api/v1`

### 6.1 Auth — `/auth`

| Method | Path | Auth |
|--------|------|------|
| POST | `auth/register` | Public (throttle 20/min) |
| POST | `auth/login` | Public |
| POST | `auth/forgot-password` | Public (throttle 5/min) |
| POST | `auth/reset-password` | Public |
| POST | `auth/logout` | Sanctum |
| GET | `auth/me` | Sanctum |

### 6.2 Public

| Method | Path |
|--------|------|
| GET | `tournaments`, `tournaments/history`, `tournaments/{slug}` |
| GET | `tournaments/{id}/rules`, `bracket`, `matches`, `standings` |
| GET | `tournaments/{id}/predictions` (leaderboard) |
| GET | `stats/player/{userId}`, `stats/head-to-head` |
| GET | `users/lookup` |
| GET | `matches/{id}`, `matches/{id}/replay` |
| GET | `teams`, `teams/{slug}` |
| GET | `games`, `games/{slug}` |
| GET | `products`, `products/{slug}` |
| GET | `banners/active`, `sponsors` |
| GET | `posts`, `posts/{slug}` |
| POST | `contact`, `newsletter/subscribe` |
| GET | `orders/{order}/download/{product}` (signed URL) |

### 6.3 Authenticated user

| Method | Path |
|--------|------|
| POST | `tournaments` (create draft) |
| POST/DELETE | `tournaments/{id}/register`, `withdraw` |
| POST | `matches/{id}/checkin`, `dispute` |
| POST/PUT | `teams`, `teams/{id}`, `teams/{id}/invite` |
| POST | `games/suggest` |
| GET | `games/suggestions/mine` |
| GET | `user/stats`, `achievements`, `tournaments`, `notifications`, `activity-feed`, `orders` |
| PUT | `user/profile` |
| POST | `user/avatar` |
| POST | `tournaments/{id}/predict` |
| PUT | `user/notifications/read` |
| GET/POST/PUT/DELETE | `friends/*`, `challenges/*` |
| POST | `cart/validate-coupon`, `orders` |
| GET | `orders/{id}` |
| GET/POST/DELETE | `wishlist/{productId}` |
| POST | `posts/{id}/comments` |

### 6.4 Admin — `/admin` (Sanctum + admin middleware)

| Resource | CRUD / actions |
|----------|----------------|
| tournaments | index, store, update, destroy, saveRules, generateBracket |
| matches | index, updateScore, addReplay |
| disputes | index, resolve |
| game-suggestions | index, approve, reject |
| banners, sponsors, products, coupons, games, posts | Full CRUD |
| orders | index, updateStatus |
| users | index, updateStatus, promote |
| contact-messages | index, markRead |
| settings | show, update |
| analytics | overview, tournaments |
| bulk-email | send |
| notifications | stats, send, approveComment, deleteComment |
| posts | publish |

---

## 7. Backend models (32)

| Model | Table | Domain |
|-------|-------|--------|
| User | users | Auth, profile |
| Game | games | Game catalog |
| GameSuggestion | game_suggestions | User suggestions |
| Tournament | tournaments | Events |
| TournamentRule | tournament_rules | Per-tournament rules |
| TournamentRegistration | tournament_registrations | Signups |
| Team | teams | Teams |
| TeamMember | team_members | Roster |
| GameMatch | matches | Bracket matches |
| TournamentCheckin | tournament_checkins | Check-ins |
| MatchReplay | match_replays | VOD links |
| Dispute | disputes | Match disputes |
| BracketPrediction | bracket_predictions | User picks |
| PointTable | point_table | Standings rows |
| PlayerStat | player_stats | Aggregated stats |
| Product | products | Shop |
| Coupon | coupons | Discounts |
| Order | orders | Purchases |
| OrderItem | order_items | Line items |
| Wishlist | wishlists | Saved products |
| BlogPost | blog_posts | CMS |
| Comment | comments | Blog comments |
| Friend | friends | Social graph |
| PvpChallenge | pvp_challenges | 1v1 challenges |
| Achievement | achievements | Badge definitions |
| UserAchievement | user_achievements | Earned badges |
| Notification | notifications | In-app alerts |
| Banner | banners | Site announcements |
| Sponsor | sponsors | Partners |
| NewsletterSubscriber | newsletter_subscribers | Email list |
| ContactMessage | contact_messages | Inbox |
| PageView | page_views | Analytics |
| SiteSetting | site_settings | Key-value config |

---

## 8. Database tables (46)

**Laravel / package:** users, password_reset_tokens, sessions, cache, cache_locks, jobs, job_batches, failed_jobs, personal_access_tokens, permissions, roles, model_has_*, role_has_permissions

**Domain (31):** games, game_suggestions, tournaments, tournament_rules, teams, team_members, tournament_registrations, matches, tournament_checkins, match_replays, disputes, bracket_predictions, point_table, player_stats, products, coupons, orders, order_items, wishlists, blog_posts, comments, friends, pvp_challenges, achievements, user_achievements, notifications, banners, sponsors, newsletter_subscribers, contact_messages, page_views, site_settings

---

## 9. Controllers (41)

**Public/API:** ActivityFeedController, BannerController, CartController, ContactController, FriendController, GameController, MatchController, NewsletterController, NotificationController, OrderController, PostController, PredictionController, ProductController, PvpChallengeController, SponsorController, StatsController, TeamController, TournamentController, UserDashboardController, UserProfileController, WishlistController

**Auth:** AuthController, PasswordResetController

**Admin (17):** AdminNotificationController, AdminSettingsController, AnalyticsController, BannerController, BlogPostController, BulkEmailController, ContactMessageController, CouponController, DisputeController, GameController, GameSuggestionController, MatchController, OrderController, ProductController, SponsorController, TournamentController, UserController

---

## 10. Services (10)

| Service | Responsibility |
|---------|----------------|
| BracketService | Single-elimination bracket generation |
| OrderService | Order creation, items, digital delivery |
| CouponService | Validate/apply coupons |
| DigitalDownloadService | Signed download URLs |
| PredictionService | Store user predictions |
| PredictionScoringService | Score predictions after tournament |
| PointTableService | Standings calculation |
| PlayerStatsService | Player stat aggregation |
| AchievementService | Award achievements |
| NotificationService | Create/send notifications |

---

## 11. Middleware & auth

| Middleware | Role |
|------------|------|
| `auth:sanctum` | API token / cookie auth |
| `admin` | AdminMiddleware — Spatie admin role OR `users.role = admin` |
| `InjectBearerFromCookie` | Reads `arena_auth` HttpOnly cookie |
| `LogPageView` | Logs `X-Arena-Page` for analytics |
| Throttle | Per-route rate limits |

**Frontend auth storage:**
- Dev: `localStorage.arena_auth_token`
- Prod option: HttpOnly cookie + `ARENA_USE_COOKIE_AUTH` in config.js

---

## 12. Seed data

`DatabaseSeeder` creates:

| Entity | Count |
|--------|-------|
| Roles | admin, user (Spatie) |
| Users | admin@arena.gg, player@arena.gg |
| Games | 3 |
| Tournaments | 2 |
| Products | 5 |
| Coupon | ARENA10 |
| Banner | 1 |
| Sponsor | 1 |
| Achievements | 3 |

Passwords: from `ADMIN_SEED_PASSWORD` / `PLAYER_SEED_PASSWORD` or random (printed once).

---

## 13. Tests

### PHPUnit (`php artisan test`)

| File | Tests |
|------|-------|
| AuthApiTest | register, login, me |
| AdminAccessTest | player denied, admin allowed |
| DashboardApiTest | admin stats |
| ProductionApiTest | password rules, lookup, history, h2h |
| SettingsApiTest | admin settings CRUD |
| ShopOrderApiTest | create order, public products |
| ExampleTest | placeholder |

**Result at inventory time:** 15 passed, 44 assertions

### Standalone scripts (require `php artisan serve`)

| Script | Coverage |
|--------|----------|
| `tests/e2e_full_test.php` | Full user journey (~21 checks) |
| `tests/register_test.php` | Registration |
| `tests/demo_user_flow_test.php` | Demo flow |
| `tests/step17_social_test.php` | Friends, notifications |
| `tests/step18_stats_test.php` | Stats, predictions |
| `tests/step19_shop_test.php` | Shop |
| `tests/step20_final_test.php` | Banners, contact |

---

## 14. Deployment & scripts

| File | Purpose |
|------|---------|
| `serve-frontend.ps1` | Local HTTP server :5500 |
| `serve-frontend.bat` | Wrapper |
| `router.php` | CSP, X-Frame-Options, etc. |
| `DEPLOY.md` | Production deploy guide |
| `deployment/scripts/apply-arena-ui.ps1` | Inject arena-ui.css/js sitewide |
| `deployment/scripts/apply-arena-ui.js` | Node variant |
| `deployment/scripts/fix-interconnectivity.ps1` | Footer links, auth guard injection |
| `deployment/scripts/backup-db.sh` | MySQL backup |
| `deployment/supervisor/arena-worker.conf` | Queue worker |
| `scripts/prepare-production.ps1` | Pre-deploy checks |
| `scripts/verify-phase1.ps1` | Phase 1 verification |
| `.github/workflows/arena-tests.yml` | CI PHPUnit |

---

## 15. Documentation files

| File | Description |
|------|-------------|
| `README.md` | Quick start |
| `DEVELOPER_GUIDE.md` | Full dev reference |
| `ARENA_SPEC.md` | Technical specification |
| `ARENA_FULL_AUDIT.md` | Audit scores & gaps |
| `ARENA_PROJECT_INVENTORY.md` | This file |
| `PRODUCTION_READINESS.md` | Scorecard (~96/100) |
| `PRODUCTION_SECURITY.md` | Security checklist |
| `DEPLOY.md` | Deploy steps |
| `QA_CHECKLIST.md` | Manual QA |
| `NEXT_STEPS.md` | Post-MVP priorities |
| `ARENA_PRODUCTION_FIX_PROMPT.md` | AI fix prompts |
| `LICENSE.md` | License / ownership |
| `SECURITY_GIT.md` | Git security notes |
| `tournament-backend/production-checklist.md` | Backend deploy |

---

## 16. Environment variables

Key `.env` keys (`tournament-backend/.env.example`):

| Group | Variables |
|-------|-----------|
| App | APP_NAME, APP_ENV, APP_KEY, APP_DEBUG, APP_URL |
| DB | DB_CONNECTION, DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD |
| Session | SESSION_DRIVER, SESSION_LIFETIME, SESSION_ENCRYPT |
| Queue/Cache | QUEUE_CONNECTION, CACHE_STORE |
| CORS | FRONTEND_URL, CORS_ALLOWED_ORIGINS |
| Auth | ARENA_HTTPONLY_AUTH_COOKIE, SANCTUM_STATEFUL_DOMAINS |
| Seed | ADMIN_SEED_PASSWORD, PLAYER_SEED_PASSWORD |
| Mail | MAIL_* |
| Broadcast | BROADCAST_CONNECTION, PUSHER_* |

Frontend `assets/js/config.js`:
- `ARENA_API_BASE` — production API URL
- `ARENA_USE_COOKIE_AUTH` — cookie vs Bearer

---

## 17. Third-party packages

### Backend (composer)

| Package | Use |
|---------|-----|
| laravel/framework 12 | Core |
| laravel/sanctum | API auth |
| spatie/laravel-permission | Roles |
| intervention/image | Image upload |
| laravel/scout | Search hook |
| pusher/pusher-php-server | Broadcasting |

### Frontend (bundled in theme)

| Library | Use |
|---------|-----|
| Bootstrap 5 | Layout |
| jQuery 3.7 | DOM / theme |
| Swiper | Sliders |
| WOW.js | Scroll animations (disabled by arena-ui) |
| Font Awesome 6 Pro | Icons |

---

## 18. Page → script → API map

### Sitewide (all 63 pages)

```
config.js → api.js → arena-escape.js → arena-ui.js → main.js
```

### Public pages additionally

```
arena-connect.js → cart.js → validation.js
```

### Protected pages additionally

```
arena-auth-guard.js (16 pages)
user-connect.js OR shop-connect OR tournament-connect OR …
```

### Admin pages

```
config.js → api.js → arena-escape.js → admin-connect.js → admin-connect-extra.js → admin.js
```

### Quick reference

| User action | Page | API endpoint |
|-------------|------|--------------|
| Register | register.html | POST /auth/register |
| Login | login.html | POST /auth/login |
| Browse tournaments | tournament.html | GET /tournaments |
| Join tournament | tournament-details.html | POST /tournaments/{id}/register |
| View bracket | bracket.html?id=1 | GET /tournaments/{id}/bracket |
| Add to cart | shop.html | localStorage only |
| Checkout | checkout.html | POST /orders |
| Admin login | login.html → admin/ | Same auth + role check |
| Ban user | admin/users.html | PUT /admin/users/{id}/status |
| Generate bracket | admin/tournament-edit.html | POST /admin/tournaments/{id}/generate-bracket |

---

## What this project does NOT have

| Item | Status |
|------|--------|
| Stripe / PayPal | Not built |
| Real tournament entry fee collection | Not built |
| Pusher live UI | Hook only |
| search.html / search API | Missing |
| Email verification flow | Not built |
| OAuth (Google, Discord) | Not built |
| Docker / K8s | Not in repo |
| Playwright / Cypress CI | Not in repo |
| Double-elim / round-robin engines | DB field only |
| Server-side admin page protection | API-only |
| Mobile native app | Not in scope |

---

*Complete inventory for Arena Tournament Platform. For gaps and fix priority, see `ARENA_FULL_AUDIT.md` Section 8.*
