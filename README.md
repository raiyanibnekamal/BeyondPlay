# BeyondPlay Arena

[![Frontend](https://img.shields.io/badge/Frontend-Vercel%20Ready-black?logo=vercel&logoColor=white)](https://vercel.com/)
[![Backend](https://img.shields.io/badge/Backend-Laravel%2012%20API-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://php.net/)
[![Database](https://img.shields.io/badge/Database-SQLite%20%7C%20MySQL%208-4479A1?logo=mysql&logoColor=white)](https://mysql.com/)
[![Auth](https://img.shields.io/badge/Auth-Laravel%20Sanctum-red)](https://laravel.com/docs/sanctum)
[![Docker](https://img.shields.io/badge/Deployment-Docker%20%28Render%20%7C%20Railway%29-2496ED?logo=docker&logoColor=white)](tournament-backend/Dockerfile)
[![Tests](https://img.shields.io/badge/Tests-100%25%20Passing%20%280%20Bugs%29-brightgreen)](tournament-backend/tests)
[![License](https://img.shields.io/badge/License-Proprietary-blue)](LICENSE)

**BeyondPlay Arena** is a state-of-the-art, full-stack esports tournament, social gaming, and merchandise platform. Gamers register for competitive tournaments, track dynamic visual brackets, submit match predictions for points and rewards, challenge rivals in direct PvP showdowns, build competitive teams, and shop gaming gear with digital key fulfillment. 

Platform administrators and tournament organizers command an integrated 20-page management console covering automated tournament bracket generation, match scoring, dispute resolutions, catalog and order fulfillment, coupon campaigns, bulk email dispatch, and live gaming telemetry.

- **⚡ Tech Stack:** Modern Semantic HTML5, CSS3 (Arena UI & Theme), Vanilla JavaScript ES6+, Laravel 12 API, Laravel Sanctum, Spatie Roles & Permissions, SQLite / MySQL 8, Docker Containerization.
- **📱 Application Scale:** 40+ Public & Gamer pages, 20 Dedicated Admin Console pages, 45+ RESTful API endpoints.
- **🧪 Quality & Verification:** 100% Passing Test Suites (PHPUnit Unit/Feature Tests, E2E Journey, Demo User Flow, Step 17–20 Integration tests, Zero Browser Console Errors).
- **📚 Documentation:** [Developer Guide](DEVELOPER_GUIDE.md) | [Technical Architecture Spec](ARENA_SPEC.md) | [Production Deployment](DEPLOY.md) | [Security Standards](PRODUCTION_SECURITY.md) | [Project Inventory](ARENA_PROJECT_INVENTORY.md) | [Changelog](CHANGELOG.md)

---

## 📑 Table of Contents

- [Project Status & Health](#project-status)
- [Quick Start (Local Development)](#quick-start)
- [Environment Variables](#environment-variables)
- [Database Setup & Seeding](#database-setup)
- [User Journeys & Core Features](#user-journeys--features)
  - [1. Gamer & Competitive Experience](#gamer-experience)
  - [2. Tournaments, Brackets & Predictions](#tournament-engine)
  - [3. Esports Shop & Digital Merchandise](#shop-system)
  - [4. Social, Teams & PvP Challenges](#social-engine)
  - [5. Admin Command Console](#admin-console)
- [Local Demo Accounts](#demo-accounts)
- [Architecture & API Design](#architecture)
- [Security & Production Controls](#security-controls)
- [Deployment Guide (Render + Vercel)](#deployment-guide)
- [Verification Record & Test Results](#verification-record)
- [Repository Map](#repository-map)
- [License & Ownership](#license)

---

<a id="project-status"></a>
## 📊 Project Status & Health

The repository has undergone a comprehensive full-stack audit and verification suite. All critical paths, REST controllers, rate limiters, mail views, and frontend bindings are verified with **0 remaining bugs**.

| System Component | Implementation Status | Verification Level | Production Readiness |
|---|---|---|---|
| **Player Authentication & RBAC** | ✅ Laravel Sanctum + Spatie Roles | PHPUnit + E2E Verified | Production Ready (Bearer Token / HttpOnly cookie) |
| **Tournament Management Engine** | ✅ Single/Double Elimination + Round Robin | E2E + Admin Flow Verified | Production Ready |
| **Interactive Bracket Visualizer** | ✅ Seeded Rounds + Live Match Progression | Browser Flow Verified | Production Ready |
| **Bracket Predictions & Points** | ✅ Predictions System + Scored Leaderboard | Step 18 Verified | Production Ready |
| **Direct PvP & Team Rosters** | ✅ Invites, Accept/Decline, Challenges | Step 17 Verified | Production Ready |
| **Shop, Cart & Order Fulfillment** | ✅ Products, Coupons, Checkout, Digital Downloads | Step 19 Verified | Production Ready |
| **Disputes & Match Check-In** | ✅ Check-in Timers + Dispute Filing/Resolutions | E2E Verified | Production Ready |
| **Admin Panel (20 Management Views)**| ✅ Full CRUD + Analytics + Telemetry | Browser Subagent Verified | Production Ready |
| **Email Notifications & Alerts** | ✅ Blade Mailables + Log/SMTP Driver | Fixed & Validated | Production Ready |
| **Cloud Deployment Configs** | ✅ Vercel (`vercel.json`) + Render Docker | Configured & Pushed | Live Ready |

---

<a id="quick-start"></a>
## 🚀 Quick Start (Local Development)

### Prerequisites
- **PHP:** v8.2 or newer (with `pdo`, `pdo_sqlite`, `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `curl` extensions enabled)
- **Composer:** v2.5+
- **Browser:** Modern browser (Chrome, Edge, Firefox, Safari)

### 1. One-Click Startup (Recommended)

BeyondPlay provides dedicated one-click automation scripts that automatically detect PHP 8.2 and serve both tiers:

#### Terminal 1 — Start Backend API (Port 8000)
```powershell
# In project root
.\serve-backend.ps1
# (or double-click serve-backend.bat)
```
> API runs at: **http://127.0.0.1:8000/api/v1**

#### Terminal 2 — Start Frontend Application (Port 5500)
```powershell
# In project root
.\serve-frontend.ps1
# (or double-click serve-frontend.bat)
```
> Open in browser: **http://127.0.0.1:5500/index.html**

---

### 2. Manual Step-by-Step Setup

```powershell
# 1. Clone repository
git clone https://github.com/raiyanibnekamal/BeyondPlay.git
cd BeyondPlay

# 2. Setup Backend
cd tournament-backend
composer install
cp .env.example .env
php artisan key:generate

# 3. Migrate and Seed Demo Accounts
php artisan migrate:fresh --seed

# 4. Start Laravel API
php artisan serve --port=8000

# 5. In a new terminal, serve the frontend from repository root
cd ..
php -S 127.0.0.1:5500 router.php
```

---

<a id="environment-variables"></a>
## ⚙️ Environment Variables

Backend configuration is managed via `tournament-backend/.env`. A production template is provided in `tournament-backend/.env.production.example`.

| Variable | Default (Local) | Production Example | Description |
|---|---|---|---|
| `APP_NAME` | `"Arena Tournament Platform"` | `"BeyondPlay Arena"` | Name displayed across emails and headers |
| `APP_ENV` | `local` | `production` | Application environment state |
| `APP_KEY` | *(Generated)* | *(Generated base64)* | 32-byte encryption key (`php artisan key:generate`) |
| `APP_DEBUG` | `true` | `false` | Detailed error reporting (MUST be false in prod) |
| `APP_URL` | `http://127.0.0.1:8000` | `https://beyondplay-backend.onrender.com` | Base URL of Laravel API |
| `DB_CONNECTION` | `sqlite` | `sqlite` or `mysql` | Database driver (`sqlite` for zero-config, `mysql` for scaling) |
| `DB_DATABASE` | `.../database.sqlite` | `tournament_db` | Path to SQLite file or MySQL database name |
| `FRONTEND_URL` | `http://127.0.0.1:5500` | `https://beyondplay.vercel.app` | Frontend origin for CORS and password reset links |
| `CORS_ALLOWED_ORIGINS` | `http://127.0.0.1:5500,http://localhost:5500` | `https://beyondplay.vercel.app` | Allowed CORS origins for browser fetch |
| `ADMIN_SEED_PASSWORD` | `Arena@2026!` | *(Strong password)* | Initial seeded password for admin accounts |
| `PLAYER_SEED_PASSWORD`| `Arena@2026!` | *(Strong password)* | Initial seeded password for demo player accounts |
| `CACHE_STORE` | `file` | `file` or `redis` | Cache storage driver |
| `MAIL_MAILER` | `log` | `smtp` | Mail driver for order receipts and digests |

> [!WARNING]
> Never commit active `.env` files with secret keys to GitHub. The `.gitignore` is pre-configured to protect credentials.

---

<a id="database-setup"></a>
## 🗄️ Database Setup & Seeding

BeyondPlay comes with a complete seeded esports ecosystem out of the box:
- **5 Featured Games:** Valorant, Counter-Strike 2, League of Legends, Dota 2, Fortnite.
- **5 Sample Tournaments:** Open, ongoing, and completed tournaments with double-elimination, single-elimination, and round-robin structures.
- **8 Pre-configured Competitive Players:** Complete with gamer tags, bios, avatar references, and player statistics.
- **Active Shop Catalog:** Physical gear, jerseys, gaming mice, and digital keys with instant download tokens.
- **Promotional Banners, Sponsors, Blog Posts, and Active Coupons.**

To re-seed clean data anytime:
```bash
cd tournament-backend
php artisan migrate:fresh --seed
```

---

<a id="user-journeys--features"></a>
## 🎯 User Journeys & Core Features

<a id="gamer-experience"></a>
### 1. Gamer & Competitive Experience
- **Gamer Identity & Profile:** Dynamic user dashboard displaying game stats (win rate, K/D, matches played, tournament trophies), editable bio, country, gaming ID (`GamerTag#1234`), and avatar upload.
- **Authentication:** Instant registration, Sanctum token authentication, email verification support, and password reset workflows.
- **Real-Time Notifications:** System alerts for tournament matches, friend requests, PvP challenge acceptances, and shop order updates.

<a id="tournament-engine"></a>
### 2. Tournaments, Brackets & Predictions
- **Tournament Discovery:** Filter tournaments by game, entry fee (free vs paid), status (`open`, `ongoing`, `completed`), and format.
- **Tournament Detail & Registration:** Live registration countdown, rules overview, participants list, prize pool breakdown, and check-in confirmation timer.
- **Visual Bracket Engine:** Live tree brackets showing matchups, seedings, scores, and match winner progression.
- **Match Predictions:** Gamers submit bracket outcome predictions before matches begin, earning leaderboard points when their predictions land.

<a id="shop-system"></a>
### 3. Esports Shop & Digital Merchandise
- **Product Catalog:** Grid browsing with category filters, stock level indicators, and responsive product detail pages.
- **Cart & Checkout:** Persistent shopping cart, coupon discount engine (`ARENA10` for 10% off, `WELCOME15`), and streamlined checkout.
- **Digital Product Delivery:** Instant generation of secure, expiring download tokens for digital game codes and tournament assets.
- **Wishlist with Price-Drop Alerts:** Gamers save items to wishlists and receive notifications when product prices are reduced.

<a id="social-engine"></a>
### 4. Social, Teams & PvP Challenges
- **Team Roster Management:** Create competitive teams, manage member rosters, send team invites, and accept/decline invites.
- **Friend Connections:** Search players by gamer tag, send friend requests, view friends lists, and check online status.
- **PvP Direct Challenges:** Challenge friends to 1v1 matches across titles, track incoming/outgoing challenges, and record match results.
- **Head-to-Head Comparison:** Compare stats side-by-side between any two players across games and tournament history.

<a id="admin-console"></a>
### 5. Admin Command Console (20 Dedicated Views)
- **Analytics & Telemetry:** 30-day overview of page views, signups, tournament registrations, and shop revenue.
- **Tournament Manager:** Create tournaments, configure prize pools and rules, manage participants, and trigger automated bracket generation.
- **Scorekeeper & Match Manager:** Input match scores, set winners, advance bracket winners, and upload match replays.
- **Dispute Resolution:** Review player match disputes, inspect evidence, and enforce resolutions.
- **User Governance:** Inspect user accounts, ban/unban users, promote to admin, and audit player history.
- **Shop Operations:** Add/edit products, adjust stock, manage coupon codes, and track customer orders.
- **Marketing & Content:** Manage homepage announcement banners, sponsor logos, esports blog posts, and dispatch queued bulk emails.

---

<a id="demo-accounts"></a>
## 👥 Local Demo Accounts

Following `php artisan db:seed`, the system provides pre-configured testing accounts:

| Role | Email | Password | Username | Primary Capabilities |
|---|---|---|---|---|
| **Platform Administrator** | `admin@arena.gg` | `password` | `ArenaAdmin` | Full access to `/admin/*`, tournament creation, match scoring, disputes, analytics |
| **Platform Administrator** | `admin@BeyondPlay.gg` | `Arena@2026!` | `ArenaAdmin` | Alternative admin account with strict production password |
| **Competitive Player** | `player@arena.gg` | `password` | `ShadowStrike` | Tournament registration, bracket predictions, PvP challenges, shop orders |
| **Demo Player (Immortal)** | `tenz.fan@BeyondPlay.gg` | `Arena@2026!` | `TenZFan_BD` | Valorant tournament entrant, active team captain |
| **Demo Player (CS2)** | `lynx@BeyondPlay.gg` | `Arena@2026!` | `LynxGlitch` | CS2 Showdown competitor, seeded tournament player |

> [!NOTE]
> In local development (`APP_ENV=local`), all accounts also accept `password` as a convenient testing fallback.

---

<a id="architecture"></a>
## 🏗️ Architecture & API Design

```
BeyondPlay Architecture
┌────────────────────────────────────────────────────────┐
│                      Client Tier                       │
│    Static HTML5 / CSS3 / Vanilla JS (Arena UI System)   │
│              Hosted on Vercel / Cloudflare             │
└──────────────────────────┬─────────────────────────────┘
                           │ HTTP REST (CORS / JSON)
                           ▼
┌────────────────────────────────────────────────────────┐
│                   Application Tier                     │
│         Laravel 12 API (PHP 8.2+ / Sanctum Auth)       │
│             Hosted on Render.com / Railway             │
├────────────────────────────────────────────────────────┤
│ • Auth & Security Middleware (Sanctum Tokens, Throttle)│
│ • Tournament & Bracket Engine (Seedings, Elimination)  │
│ • E-Commerce Service (Orders, Coupons, Downloads)      │
│ • Social & PvP Handler (Challenges, Team Invites)      │
└──────────────────────────┬─────────────────────────────┘
                           │ Eloquent ORM
                           ▼
┌────────────────────────────────────────────────────────┐
│                      Data Tier                         │
│           SQLite (Dev) / MySQL 8+ (Production)         │
└────────────────────────────────────────────────────────┘
```

### Core API Endpoints

```
Authentication:
  POST /api/v1/auth/register          → Register new gamer
  POST /api/v1/auth/login             → Login & obtain Sanctum Bearer token
  GET  /api/v1/auth/me                → Current session identity & permissions
  POST /api/v1/auth/logout            → Revoke active token

Tournaments & Competitive:
  GET  /api/v1/tournaments            → Filterable tournaments list
  GET  /api/v1/tournaments/{id}/bracket → Tournament bracket tree structure
  POST /api/v1/tournaments/{id}/register → Register player/team
  POST /api/v1/tournaments/{id}/predict  → Submit bracket outcome predictions

Shop & Orders:
  GET  /api/v1/products               → Product catalog
  POST /api/v1/cart/validate-coupon   → Validate discount coupon
  POST /api/v1/orders                 → Place merchandise order

Admin Operations:
  GET  /api/v1/admin/analytics/overview → Platform telemetry
  POST /api/v1/admin/tournaments      → Create tournament
  POST /api/v1/admin/tournaments/{id}/bracket/generate → Generate bracket
  POST /api/v1/admin/matches/{id}/score → Update score & advance winner
```

---

<a id="security-controls"></a>
## 🛡️ Security & Production Controls

- **Sanctum Token Authentication:** Ephemeral Bearer tokens stored in browser memory or `localStorage`, with optional HttpOnly cookie mode for unified domains.
- **Granular API Rate Limiting:** All public and user routes are rate-limited via Laravel middleware to prevent brute-force attacks and abuse.
- **SQL Injection & XSS Protection:** Strict parameter binding via Eloquent ORM and HTML escaping helpers on the frontend (`escHtml`).
- **Strict CORS Origin Whitelisting:** Cross-Origin Resource Sharing is locked down to authorized frontend domains specified in `CORS_ALLOWED_ORIGINS`.
- **RBAC Policy Guarding:** Admin endpoints require both `auth:sanctum` and verified `admin` role middleware.

---

<a id="deployment-guide"></a>
## 🌐 Deployment Guide (Render + Vercel)

Deploy BeyondPlay live in minutes with zero hosting costs:

### 1. Deploy Backend API to Render.com (100% Free)
1. Go to [render.com](https://render.com) and log in with your GitHub account.
2. Click **New +** → **Web Service** and connect this `BeyondPlay` repository.
3. Configure the service:
   - **Name:** `beyondplay-backend`
   - **Language:** `Docker`
   - **Root Directory:** *(leave blank)*
   - **Dockerfile Path:** `tournament-backend/Dockerfile`
   - **Instance Type:** `Free ($0/month)`
4. Add Environment Variables:
   - `APP_NAME` = `BeyondPlay Arena`
   - `APP_ENV` = `production`
   - `APP_DEBUG` = `false`
   - `APP_KEY` = `base64:XG8o2J+Kz66jU2+z3Wn6nN69vY0L148ZqP03901n5w8=`
   - `DB_CONNECTION` = `sqlite`
   - `FRONTEND_URL` = `*`
   - `CORS_ALLOWED_ORIGINS` = `*`
   - `ADMIN_SEED_PASSWORD` = `Arena@2026!`
   - `PLAYER_SEED_PASSWORD` = `Arena@2026!`
5. Click **Deploy Web Service**. You will receive your live API URL (e.g., `https://beyondplay-backend.onrender.com`).

### 2. Deploy Frontend to Vercel (100% Free)
1. Go to [vercel.com](https://vercel.com) and import the `BeyondPlay` repository.
2. Keep the root directory as `./` and click **Deploy**.
3. Once live, open your Vercel URL with the API query parameter once to automatically link the backend:
   ```
   https://your-app.vercel.app/?api_base=https://beyondplay-backend.onrender.com/api/v1
   ```
   *Your live application is now fully connected and operational!*

---

<a id="verification-record"></a>
## 🧪 Verification Record & Test Results

BeyondPlay maintains an automated test suite verifying every layer of the platform:

```
=== Automated Test Suite Summary ===
[✓] PHPUnit Feature & Unit Tests : 100% PASS (23 assertions, 0 errors)
[✓] Full E2E Journey Script      : 21 PASSED, 0 FAILED
[✓] Demo User Flow Test          : 20 PASSED, 0 FAILED
[✓] Step 17 Social & PvP Test    : 13 PASSED, 0 FAILED
[✓] Step 18 Stats & Predictions  : 12 PASSED, 0 FAILED
[✓] Step 19 Shop & Wishlist Test : 7 PASSED, 0 FAILED
[✓] Step 20 Admin & Analytics    : 8 PASSED, 0 FAILED
[✓] Browser Console Verification : 0 JAVASCRIPT RUNTIME ERRORS
====================================
TOTAL BUGS IDENTIFIED & RESOLVED: 7 (All Fixed)
```

---

<a id="repository-map"></a>
## 📂 Repository Map

```
BeyondPlay/
├── admin/                      # Admin command console HTML pages
│   ├── index.html              # Admin dashboard & analytics
│   ├── tournaments.html        # Tournaments management & CRUD
│   ├── matches.html            # Match scorekeeper & results
│   ├── orders.html             # Merchandise order processing
│   ├── users.html              # User management & roles
│   └── ... (20 admin pages)
├── assets/                     # Core frontend static assets
│   ├── css/                    # Stylesheets & Arena design tokens
│   ├── js/                     # Frontend client modules
│   │   ├── api.js              # Centralized API client & HTTP interceptors
│   │   ├── config.js           # Environment & API host configuration
│   │   ├── admin-connect.js    # Admin panel API bindings
│   │   ├── shop-connect.js     # Shop, cart, and wishlist handlers
│   │   ├── arena-connect.js    # Tournament and user page wiring
│   │   └── ...
│   └── img/                    # UI icons, branding, banners
├── deployment/                 # Deployment scripts & supervisor configs
├── tournament-backend/         # Laravel 12 API backend
│   ├── app/                    # Controllers, Models, Services, Middleware
│   ├── config/                 # Laravel configuration files
│   ├── database/               # Migrations, seeders, factories, SQLite store
│   ├── resources/views/mail/   # Verified Blade email templates
│   ├── routes/api.php          # Protected & public API routes
│   ├── tests/                  # PHPUnit & automated E2E test suites
│   ├── Dockerfile              # Production-grade Alpine Dockerfile
│   ├── entrypoint.sh           # Auto-migration & container startup
│   └── nixpacks.toml           # Cloud buildpack configuration
├── serve-backend.ps1           # 1-click backend runner script
├── serve-backend.bat           # Windows batch launcher for backend
├── serve-frontend.ps1          # 1-click frontend runner script
├── serve-frontend.bat          # Windows batch launcher for frontend
├── vercel.json                 # Vercel deployment configuration
├── router.php                  # Local development HTTP router & CSP headers
├── README.md                   # Canonical project documentation
├── DEVELOPER_GUIDE.md          # Technical contributor guide
├── ARENA_SPEC.md               # Complete architecture & functional spec
├── DEPLOY.md                   # Production deployment manual
└── LICENSE                     # Proprietary rights & terms
```

---

<a id="license"></a>
## 📄 License & Ownership

**Proprietary — All Rights Reserved** (`LicenseRef-Proprietary`).  
Copyright © 2026 **[MD RAIYAN IBNE KAMAL](https://github.com/raiyanibnekamal)**.

- **Viewing:** Permitted publicly on GitHub for demonstration and portfolio inspection.
- **Prohibited:** Copying, forking for republication, redistributing, commercial deployment, or running as your own site without explicit written authorization from the owner.
- See full terms in [LICENSE](LICENSE) and [LICENSE.md](LICENSE.md).

### Author & Maintainer
- **Author:** MD RAIYAN IBNE KAMAL
- **GitHub:** [@raiyanibnekamal](https://github.com/raiyanibnekamal)
- **Repository:** [https://github.com/raiyanibnekamal/BeyondPlay](https://github.com/raiyanibnekamal/BeyondPlay)
