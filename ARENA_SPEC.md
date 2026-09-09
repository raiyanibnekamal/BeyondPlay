# Arena Platform — Technical Specification
### Full-stack esports tournament platform | Custom frontend + Laravel API

> **Proprietary specification** for the Arena project: features, database schema, API design, admin panel, and implementation notes. Application code in this repository is original work for Arena.

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Implementation Status](#2-implementation-status)
3. [Frontend Architecture](#3-frontend-architecture)
4. [Page Catalog](#4-page-catalog)
5. [Release Checklist](#5-release-checklist)
6. [Backend Overview](#6-backend-overview)
7. [Backend Architecture — Laravel + MySQL](#7-backend-architecture--laravel--mysql)
8. [Database Schema](#8-database-schema)
9. [API Endpoint Design](#9-api-endpoint-design)
10. [Admin Panel Specification](#10-admin-panel-specification)
11. [User Panel Specification](#11-user-panel-specification)
12. [Feature Implementation Order](#12-feature-implementation-order)
13. [Security Checklist](#13-security-checklist)
14. [File & Folder Structure](#14-file--folder-structure)
15. [Tech Stack Summary](#15-tech-stack-summary)

---

## 1. Project Overview

**Project Name:** Arena
**Type:** Full-Stack Esports & Gaming Tournament Platform
**Goal:** A fully functional, original tournament website with public-facing pages, a user registration/profile system, a tournament bracket + scoring system, a shop with checkout, social features, a notification system, and a separate admin dashboard to manage everything.

**Two distinct panels:**
- **Public / User Panel** — what visitors and registered players see
- **Admin Panel** — private dashboard to manage tournaments, players, blog, shop, scores, announcements, and sponsors

**Tech Stack:**
| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, Vanilla JavaScript, Bootstrap 5, Swiper.js |
| Backend | Laravel (PHP) |
| Database | MySQL |
| Auth | Laravel Sanctum (API tokens) + session-based for admin |
| Real-time | Laravel Broadcasting + Pusher (for live scores + live chat) |
| Email | Laravel Mailable + SMTP (Mailtrap for dev) |
| Storage | Laravel Storage (local disk → S3 in production) |

---

## 2. Implementation Status

Arena is a **shipped full-stack MVP**: public HTML/CSS/JS frontend, Laravel 12 API (`tournament-backend/`), MySQL/SQLite, and Sanctum auth.

| Layer | Status |
|---|---|
| Public pages (tournaments, shop, blog, social, brackets) | Built; core flows API-wired — see `DEVELOPER_GUIDE.md` §1b |
| Auth (login, register, password reset) | Live API |
| User dashboard (`my-*`, profile, notifications) | Live API |
| Admin panel (`admin/`) | Live API for core flows (tournaments, disputes, products, banners, analytics, settings) |
| Tests | PHPUnit feature tests + `tests/e2e_full_test.php` |

**Run locally:** `php artisan serve` (API :8000) + `.\serve-frontend.ps1` (frontend :5500). See `README.md`.

---

## 3. Frontend Architecture

| Module | Role |
|---|---|
| `assets/js/config.js` | Production `ARENA_API_BASE` |
| `assets/js/api.js` | HTTP client for `/api/v1` |
| `assets/js/arena-connect.js` | Public pages (auth, tournaments, shop, contact) |
| `assets/js/user-connect.js` | Logged-in user dashboard |
| `assets/js/admin-connect.js` + `admin-connect-extra.js` | Admin panel |
| `assets/js/cart.js`, `validation.js`, `bracket.js` | Cart, forms, bracket UI |
| `assets/js/main.js` | Sliders, mobile menu, scroll helpers |

Branding, copy, and layout are **Arena-specific**. Optional client methods without a backend route yet are documented in `DEVELOPER_GUIDE.md` (Section 6).

---

## 4. Page Catalog

The following pages are part of the Arena design system (shared header, footer, and CSS). Most exist in the repository; integration depth varies by page.

### 4.0 Public site (core)

| Area | Examples |
|---|---|
| Marketing | `index.html`, `about.html`, `contact.html`, `gallery.html` |
| Tournaments | `tournament.html`, `tournament-details.html`, `bracket.html`, `point-table.html`, `tournament-rules.html` |
| Players & games | `team.html`, `team-details.html`, `game.html`, `game-details.html`, `player-profile.html` |
| Shop | `shop.html`, `shop-details.html`, `cart.html`, `checkout.html`, `wishlist.html` |
| Auth & account | `login.html`, `register.html`, `forgot-password.html`, `reset-password.html`, `my-profile.html`, `my-tournaments.html`, `my-orders.html`, `my-notifications.html` |
| Social & stats | `friends.html`, `activity-feed.html`, `achievements.html`, `stats.html`, `head-to-head.html`, `tournament-history.html`, `suggest-game.html` |
| Match flow | `checkin.html`, `dispute.html`, `bracket-prediction.html` |

---

## 5. Release Checklist

Pre-production verification (see also `QA_CHECKLIST.md`, `PRODUCTION_READINESS.md`, `NEXT_STEPS.md`):

- [ ] API and frontend served over HTTPS with correct `config.js` / `.env`
- [ ] Seed passwords rotated; `APP_DEBUG=false`
- [ ] CORS and `FRONTEND_URL` set for production domain
- [ ] Manual browser QA on auth, tournament register, checkout, admin flows
- [ ] Queue worker for bulk email if used

---

## 6. Backend Overview

The Laravel API in `tournament-backend/` implements auth, tournaments, shop, social features, admin tools, and analytics. Database schema, routes, and controllers are defined in Sections 7–11 below.

---

## 7. Backend Architecture — Laravel + MySQL

### Laravel Project Setup

```bash
# Create new Laravel project
composer create-project laravel/laravel tournament-backend

cd tournament-backend

# Install required packages
composer require laravel/sanctum                  # API authentication
composer require spatie/laravel-permission        # Role & permission management
composer require intervention/image               # Image upload processing
composer require laravel/scout                    # Search functionality
composer require pusher/pusher-php-server         # Real-time (live scores + chat)

# Copy env
cp .env.example .env
php artisan key:generate

# Publish Sanctum config
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### `.env` Configuration

```env
APP_NAME="Tournament Platform"
APP_ENV=local
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tournament_db
DB_USERNAME=root
DB_PASSWORD=your_password

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_user
MAIL_PASSWORD=your_mailtrap_pass
MAIL_FROM_ADDRESS="no-reply@arena.com"
MAIL_FROM_NAME="Arena Platform"

BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_id
PUSHER_APP_KEY=your_key
PUSHER_APP_SECRET=your_secret
PUSHER_APP_CLUSTER=ap2

FILESYSTEM_DISK=local
```

---

## 8. Database Schema

### Complete Table List (29 tables)

| # | Table | Purpose |
|---|---|---|
| 1 | `users` | Registered users |
| 2 | `games` | Available games |
| 3 | `game_suggestions` | User-submitted game requests |
| 4 | `tournaments` | Tournament events |
| 5 | `tournament_rules` | Per-tournament rules |
| 6 | `teams` | Player teams |
| 7 | `team_members` | Team membership |
| 8 | `tournament_registrations` | Who registered for which tournament |
| 9 | `tournament_checkins` | Pre-match check-in records |
| 10 | `matches` | Individual matches |
| 11 | `match_replays` | VOD/replay links for completed matches |
| 12 | `disputes` | Match dispute reports |
| 13 | `bracket_predictions` | User bracket predictions |
| 14 | `point_table` | Tournament standings |
| 15 | `player_stats` | Per-player per-game statistics |
| 16 | `products` | Shop products |
| 17 | `coupons` | Discount coupon codes |
| 18 | `orders` | Shop orders |
| 19 | `order_items` | Individual items in an order |
| 20 | `wishlists` | User product wishlists |
| 21 | `blog_posts` | Blog articles |
| 22 | `comments` | Comments on blog/tournament posts |
| 23 | `friends` | Friend relationships |
| 24 | `pvp_challenges` | 1v1 challenge requests |
| 25 | `achievements` | Achievement/badge definitions |
| 26 | `user_achievements` | Awarded badges per user |
| 27 | `notifications` | In-site notifications |
| 28 | `banners` | Homepage announcements |
| 29 | `sponsors` | Sponsor logos |
| 30 | `newsletter_subscribers` | Email newsletter list |
| 31 | `contact_messages` | Contact form submissions |

---

### Table Definitions

#### `users`
```sql
CREATE TABLE users (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username            VARCHAR(50) UNIQUE NOT NULL,
    email               VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at   TIMESTAMP NULL,
    password            VARCHAR(255) NOT NULL,
    avatar              VARCHAR(255) NULL,
    bio                 TEXT NULL,
    country             VARCHAR(100) NULL,
    gaming_id           VARCHAR(100) NULL,
    role                ENUM('user','admin') DEFAULT 'user',
    status              ENUM('active','banned') DEFAULT 'active',
    remember_token      VARCHAR(100) NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL
);
```

#### `games`
```sql
CREATE TABLE games (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150) NOT NULL,
    slug            VARCHAR(150) UNIQUE NOT NULL,
    cover_image     VARCHAR(255) NULL,
    logo            VARCHAR(255) NULL,
    description     TEXT NULL,
    genre           VARCHAR(100) NULL,
    status          ENUM('active','inactive') DEFAULT 'active',
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

#### `game_suggestions`
```sql
CREATE TABLE game_suggestions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    name            VARCHAR(150) NOT NULL,
    genre           VARCHAR(100) NULL,
    description     TEXT NULL,
    cover_image_url VARCHAR(500) NULL,
    status          ENUM('pending','approved','rejected') DEFAULT 'pending',
    rejection_reason TEXT NULL,
    reviewed_by     BIGINT UNSIGNED NULL,
    reviewed_at     TIMESTAMP NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id)
);
```
> When admin approves a suggestion, a trigger or Laravel Observer automatically inserts a new row into the `games` table with the suggested data.

#### `tournaments`
```sql
CREATE TABLE tournaments (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    game_id                 BIGINT UNSIGNED NOT NULL,
    name                    VARCHAR(200) NOT NULL,
    slug                    VARCHAR(200) UNIQUE NOT NULL,
    description             TEXT NULL,
    cover_image             VARCHAR(255) NULL,
    entry_fee               DECIMAL(8,2) DEFAULT 0.00,
    prize_pool              DECIMAL(10,2) DEFAULT 0.00,
    max_participants        INT UNSIGNED DEFAULT 16,
    current_participants    INT UNSIGNED DEFAULT 0,
    format                  ENUM('single_elimination','double_elimination','round_robin') DEFAULT 'single_elimination',
    status                  ENUM('draft','open','ongoing','completed','cancelled') DEFAULT 'draft',
    registration_start      DATETIME NULL,
    registration_end        DATETIME NULL,
    start_date              DATETIME NOT NULL,
    end_date                DATETIME NULL,
    checkin_minutes_before  SMALLINT UNSIGNED DEFAULT 30,
    created_by              BIGINT UNSIGNED NOT NULL,
    created_at              TIMESTAMP NULL,
    updated_at              TIMESTAMP NULL,
    FOREIGN KEY (game_id) REFERENCES games(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

#### `tournament_rules`
```sql
CREATE TABLE tournament_rules (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tournament_id   BIGINT UNSIGNED NOT NULL UNIQUE,
    format_details  TEXT NULL,
    schedule_info   TEXT NULL,
    scoring_rules   TEXT NULL,
    code_of_conduct TEXT NULL,
    dispute_policy  TEXT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id)
);
```

#### `teams`
```sql
CREATE TABLE teams (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    slug        VARCHAR(150) UNIQUE NOT NULL,
    logo        VARCHAR(255) NULL,
    captain_id  BIGINT UNSIGNED NOT NULL,
    status      ENUM('active','inactive') DEFAULT 'active',
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    FOREIGN KEY (captain_id) REFERENCES users(id)
);
```

#### `team_members`
```sql
CREATE TABLE team_members (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id     BIGINT UNSIGNED NOT NULL,
    user_id     BIGINT UNSIGNED NOT NULL,
    role        ENUM('captain','member') DEFAULT 'member',
    joined_at   TIMESTAMP NULL,
    FOREIGN KEY (team_id) REFERENCES teams(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_member (team_id, user_id)
);
```

#### `tournament_registrations`
```sql
CREATE TABLE tournament_registrations (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tournament_id   BIGINT UNSIGNED NOT NULL,
    user_id         BIGINT UNSIGNED NOT NULL,
    team_id         BIGINT UNSIGNED NULL,
    status          ENUM('pending','confirmed','disqualified') DEFAULT 'pending',
    registered_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (team_id) REFERENCES teams(id),
    UNIQUE KEY unique_registration (tournament_id, user_id)
);
```

#### `tournament_checkins`
```sql
CREATE TABLE tournament_checkins (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id        BIGINT UNSIGNED NOT NULL,
    user_id         BIGINT UNSIGNED NOT NULL,
    checked_in_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (match_id) REFERENCES matches(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_checkin (match_id, user_id)
);
```

#### `matches`
```sql
CREATE TABLE matches (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tournament_id   BIGINT UNSIGNED NOT NULL,
    round           TINYINT UNSIGNED NOT NULL,
    match_number    SMALLINT UNSIGNED NOT NULL,
    team1_id        BIGINT UNSIGNED NULL,
    team2_id        BIGINT UNSIGNED NULL,
    team1_score     SMALLINT UNSIGNED DEFAULT 0,
    team2_score     SMALLINT UNSIGNED DEFAULT 0,
    winner_id       BIGINT UNSIGNED NULL,
    status          ENUM('scheduled','live','completed') DEFAULT 'scheduled',
    scheduled_at    DATETIME NULL,
    completed_at    DATETIME NULL,
    stream_url      VARCHAR(500) NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id),
    FOREIGN KEY (team1_id) REFERENCES teams(id),
    FOREIGN KEY (team2_id) REFERENCES teams(id),
    FOREIGN KEY (winner_id) REFERENCES teams(id)
);
```

#### `match_replays`
```sql
CREATE TABLE match_replays (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id        BIGINT UNSIGNED NOT NULL UNIQUE,
    vod_url         VARCHAR(500) NOT NULL,
    platform        VARCHAR(100) NULL,
    duration_minutes SMALLINT UNSIGNED NULL,
    added_by        BIGINT UNSIGNED NOT NULL,
    created_at      TIMESTAMP NULL,
    FOREIGN KEY (match_id) REFERENCES matches(id),
    FOREIGN KEY (added_by) REFERENCES users(id)
);
```

#### `disputes`
```sql
CREATE TABLE disputes (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id        BIGINT UNSIGNED NOT NULL,
    reported_by     BIGINT UNSIGNED NOT NULL,
    issue_type      ENUM('cheating','connection_problem','score_error','other') NOT NULL,
    description     TEXT NOT NULL,
    evidence_url    VARCHAR(500) NULL,
    status          ENUM('open','under_review','resolved') DEFAULT 'open',
    resolution_note TEXT NULL,
    resolved_by     BIGINT UNSIGNED NULL,
    resolved_at     TIMESTAMP NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (match_id) REFERENCES matches(id),
    FOREIGN KEY (reported_by) REFERENCES users(id),
    FOREIGN KEY (resolved_by) REFERENCES users(id)
);
```

#### `bracket_predictions`
```sql
CREATE TABLE bracket_predictions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tournament_id   BIGINT UNSIGNED NOT NULL,
    user_id         BIGINT UNSIGNED NOT NULL,
    predictions     JSON NOT NULL,
    score           SMALLINT UNSIGNED DEFAULT 0,
    scored_at       TIMESTAMP NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_prediction (tournament_id, user_id)
);
```
> `predictions` JSON format: `{"match_1": team_id, "match_2": team_id, ...}`

#### `point_table`
```sql
CREATE TABLE point_table (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tournament_id   BIGINT UNSIGNED NOT NULL,
    team_id         BIGINT UNSIGNED NOT NULL,
    matches_played  SMALLINT UNSIGNED DEFAULT 0,
    wins            SMALLINT UNSIGNED DEFAULT 0,
    losses          SMALLINT UNSIGNED DEFAULT 0,
    draws           SMALLINT UNSIGNED DEFAULT 0,
    points          SMALLINT UNSIGNED DEFAULT 0,
    goal_difference SMALLINT DEFAULT 0,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id),
    FOREIGN KEY (team_id) REFERENCES teams(id),
    UNIQUE KEY unique_entry (tournament_id, team_id)
);
```

#### `player_stats`
```sql
CREATE TABLE player_stats (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    game_id         BIGINT UNSIGNED NOT NULL,
    matches_played  INT UNSIGNED DEFAULT 0,
    wins            INT UNSIGNED DEFAULT 0,
    losses          INT UNSIGNED DEFAULT 0,
    kills           INT UNSIGNED DEFAULT 0,
    deaths          INT UNSIGNED DEFAULT 0,
    total_score     BIGINT UNSIGNED DEFAULT 0,
    tournament_wins INT UNSIGNED DEFAULT 0,
    win_streak      SMALLINT UNSIGNED DEFAULT 0,
    best_streak     SMALLINT UNSIGNED DEFAULT 0,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (game_id) REFERENCES games(id),
    UNIQUE KEY unique_player_game (user_id, game_id)
);
```
> Computed columns: `kd_ratio = kills / NULLIF(deaths,0)`, `win_rate = wins / NULLIF(matches_played,0) * 100`, `avg_score = total_score / NULLIF(matches_played,0)` — calculate these in PHP/Eloquent, not in DB.

#### `products`
```sql
CREATE TABLE products (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(200) NOT NULL,
    slug            VARCHAR(200) UNIQUE NOT NULL,
    description     TEXT NULL,
    price           DECIMAL(10,2) NOT NULL,
    sale_price      DECIMAL(10,2) NULL,
    stock           INT UNSIGNED DEFAULT 0,
    category        VARCHAR(100) NULL,
    type            ENUM('physical','digital') DEFAULT 'physical',
    digital_file    VARCHAR(255) NULL,
    image           VARCHAR(255) NULL,
    status          ENUM('active','inactive') DEFAULT 'active',
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

#### `coupons`
```sql
CREATE TABLE coupons (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(50) UNIQUE NOT NULL,
    type            ENUM('percentage','fixed') NOT NULL,
    value           DECIMAL(8,2) NOT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0.00,
    max_uses        INT UNSIGNED NULL,
    used_count      INT UNSIGNED DEFAULT 0,
    expires_at      DATETIME NULL,
    status          ENUM('active','inactive') DEFAULT 'active',
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

#### `orders`
```sql
CREATE TABLE orders (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    coupon_id       BIGINT UNSIGNED NULL,
    subtotal        DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    total_amount    DECIMAL(10,2) NOT NULL,
    status          ENUM('pending','paid','shipped','delivered','cancelled') DEFAULT 'pending',
    payment_method  VARCHAR(50) NULL,
    payment_id      VARCHAR(200) NULL,
    shipping_name   VARCHAR(150) NULL,
    shipping_address TEXT NULL,
    shipping_city   VARCHAR(100) NULL,
    shipping_phone  VARCHAR(20) NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (coupon_id) REFERENCES coupons(id)
);
```

#### `order_items`
```sql
CREATE TABLE order_items (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id    BIGINT UNSIGNED NOT NULL,
    product_id  BIGINT UNSIGNED NOT NULL,
    quantity    SMALLINT UNSIGNED NOT NULL,
    unit_price  DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);
```

#### `wishlists`
```sql
CREATE TABLE wishlists (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED NOT NULL,
    product_id  BIGINT UNSIGNED NOT NULL,
    added_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    UNIQUE KEY unique_wishlist (user_id, product_id)
);
```
> When a product's `sale_price` is set or `price` drops, a Laravel Observer fires and sends an email to all users who have that product in their wishlist.

#### `blog_posts`
```sql
CREATE TABLE blog_posts (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    author_id       BIGINT UNSIGNED NOT NULL,
    title           VARCHAR(300) NOT NULL,
    slug            VARCHAR(300) UNIQUE NOT NULL,
    content         LONGTEXT NULL,
    excerpt         TEXT NULL,
    cover_image     VARCHAR(255) NULL,
    category        VARCHAR(100) NULL,
    status          ENUM('draft','published') DEFAULT 'draft',
    published_at    DATETIME NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (author_id) REFERENCES users(id)
);
```

#### `comments`
```sql
CREATE TABLE comments (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             BIGINT UNSIGNED NOT NULL,
    commentable_type    VARCHAR(100) NOT NULL,
    commentable_id      BIGINT UNSIGNED NOT NULL,
    parent_id           BIGINT UNSIGNED NULL,
    content             TEXT NOT NULL,
    status              ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (parent_id) REFERENCES comments(id)
);
```

#### `friends`
```sql
CREATE TABLE friends (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requester_id    BIGINT UNSIGNED NOT NULL,
    receiver_id     BIGINT UNSIGNED NOT NULL,
    status          ENUM('pending','accepted','declined','blocked') DEFAULT 'pending',
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (requester_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id),
    UNIQUE KEY unique_friendship (requester_id, receiver_id)
);
```

#### `pvp_challenges`
```sql
CREATE TABLE pvp_challenges (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    challenger_id   BIGINT UNSIGNED NOT NULL,
    challenged_id   BIGINT UNSIGNED NOT NULL,
    game_id         BIGINT UNSIGNED NOT NULL,
    message         VARCHAR(300) NULL,
    status          ENUM('pending','accepted','declined','expired') DEFAULT 'pending',
    expires_at      DATETIME NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (challenger_id) REFERENCES users(id),
    FOREIGN KEY (challenged_id) REFERENCES users(id),
    FOREIGN KEY (game_id) REFERENCES games(id)
);
```

#### `achievements`
```sql
CREATE TABLE achievements (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150) NOT NULL,
    description     VARCHAR(300) NOT NULL,
    icon            VARCHAR(255) NULL,
    condition_type  VARCHAR(100) NOT NULL,
    condition_value INT UNSIGNED NOT NULL,
    created_at      TIMESTAMP NULL
);
```
> `condition_type` examples: `tournament_wins`, `matches_played`, `friends_count`, `predictions_correct`

#### `user_achievements`
```sql
CREATE TABLE user_achievements (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    achievement_id  BIGINT UNSIGNED NOT NULL,
    earned_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (achievement_id) REFERENCES achievements(id),
    UNIQUE KEY unique_badge (user_id, achievement_id)
);
```

#### `notifications`
```sql
CREATE TABLE notifications (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    type            VARCHAR(100) NOT NULL,
    title           VARCHAR(200) NOT NULL,
    message         TEXT NOT NULL,
    link            VARCHAR(500) NULL,
    is_read         BOOLEAN DEFAULT FALSE,
    created_at      TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```
> `type` examples: `friend_request`, `match_checkin_reminder`, `dispute_resolved`, `game_suggestion_approved`, `wishlist_price_drop`, `pvp_challenge`, `tournament_open`

#### `banners`
```sql
CREATE TABLE banners (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(200) NOT NULL,
    message         TEXT NOT NULL,
    link            VARCHAR(500) NULL,
    type            ENUM('info','warning','success','danger') DEFAULT 'info',
    is_active       BOOLEAN DEFAULT TRUE,
    starts_at       DATETIME NULL,
    ends_at         DATETIME NULL,
    created_by      BIGINT UNSIGNED NOT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

#### `sponsors`
```sql
CREATE TABLE sponsors (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    logo        VARCHAR(255) NOT NULL,
    website_url VARCHAR(500) NULL,
    tier        ENUM('title','gold','silver','bronze') DEFAULT 'silver',
    is_active   BOOLEAN DEFAULT TRUE,
    sort_order  TINYINT UNSIGNED DEFAULT 0,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL
);
```

#### `newsletter_subscribers`
```sql
CREATE TABLE newsletter_subscribers (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(255) UNIQUE NOT NULL,
    status      ENUM('active','unsubscribed') DEFAULT 'active',
    created_at  TIMESTAMP NULL
);
```

#### `contact_messages`
```sql
CREATE TABLE contact_messages (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    email       VARCHAR(255) NOT NULL,
    subject     VARCHAR(300) NULL,
    message     TEXT NOT NULL,
    is_read     BOOLEAN DEFAULT FALSE,
    created_at  TIMESTAMP NULL
);
```

---

## 9. API Endpoint Design

All API routes use prefix `/api/v1/`. Authentication uses Laravel Sanctum bearer tokens.

### Authentication Routes
```
POST   /api/v1/auth/register            Register new user
POST   /api/v1/auth/login               Login, returns token
POST   /api/v1/auth/logout              Invalidate token (auth required)
POST   /api/v1/auth/forgot-password     Send reset email
POST   /api/v1/auth/reset-password      Reset with token
GET    /api/v1/auth/me                  Get logged-in user profile (auth required)
```

### User Routes (auth required)
```
GET    /api/v1/user/profile             Get own profile
PUT    /api/v1/user/profile             Update profile
POST   /api/v1/user/avatar              Upload avatar
GET    /api/v1/user/tournaments         My registered tournaments
GET    /api/v1/user/orders              My order history
GET    /api/v1/user/notifications       My notifications (paginated)
PUT    /api/v1/user/notifications/read  Mark all as read
GET    /api/v1/user/achievements        My earned badges
GET    /api/v1/user/stats               My player stats (filter by game_id)
GET    /api/v1/user/activity-feed       Activity feed from friends
```

### Tournament Routes (public + auth)
```
GET    /api/v1/tournaments                      List all tournaments (public)
GET    /api/v1/tournaments/{slug}               Single tournament detail (public)
GET    /api/v1/tournaments/{id}/rules           Tournament rules (public)
POST   /api/v1/tournaments/{id}/register        Register for tournament (auth)
DELETE /api/v1/tournaments/{id}/register        Withdraw registration (auth)
GET    /api/v1/tournaments/{id}/bracket         Get bracket data (public)
GET    /api/v1/tournaments/{id}/matches         Get matches list (public)
GET    /api/v1/tournaments/{id}/standings       Get point table (public)
POST   /api/v1/tournaments/{id}/predict         Submit bracket prediction (auth)
GET    /api/v1/tournaments/{id}/predictions     Get prediction leaderboard (public)
GET    /api/v1/tournaments/history              Past completed tournaments (public)
```

### Match Routes
```
GET    /api/v1/matches/{id}             Single match details (public)
GET    /api/v1/matches/{id}/replay      Match VOD/replay info (public)
POST   /api/v1/matches/{id}/checkin     Player check-in (auth)
POST   /api/v1/matches/{id}/dispute     File a dispute (auth)
```

### Team Routes
```
GET    /api/v1/teams                    List teams (public)
GET    /api/v1/teams/{slug}             Single team (public)
POST   /api/v1/teams                    Create team (auth)
PUT    /api/v1/teams/{id}               Update team (auth, captain only)
POST   /api/v1/teams/{id}/invite        Invite member (auth, captain only)
```

### Game Routes
```
GET    /api/v1/games                    List games (public)
GET    /api/v1/games/{slug}             Single game (public)
POST   /api/v1/games/suggest            Submit game suggestion (auth)
GET    /api/v1/games/suggestions/mine   My submitted suggestions (auth)
```

### Stats Routes
```
GET    /api/v1/stats/player/{user_id}   Public player stats (public)
GET    /api/v1/stats/head-to-head       Compare two players: ?player1=id&player2=id (public)
```

### Social Routes (auth required)
```
GET    /api/v1/friends                  My friend list
GET    /api/v1/friends/requests         Incoming friend requests
POST   /api/v1/friends/request/{user_id}    Send friend request
PUT    /api/v1/friends/request/{id}/accept  Accept friend request
PUT    /api/v1/friends/request/{id}/decline Decline friend request
DELETE /api/v1/friends/{user_id}        Remove friend

POST   /api/v1/challenges               Send PvP challenge
GET    /api/v1/challenges/incoming      Incoming challenges
PUT    /api/v1/challenges/{id}/accept   Accept challenge
PUT    /api/v1/challenges/{id}/decline  Decline challenge
```

### Shop Routes
```
GET    /api/v1/products                 List products (public)
GET    /api/v1/products/{slug}          Single product (public)
POST   /api/v1/cart/validate-coupon     Validate coupon code (auth)
POST   /api/v1/orders                   Create order (auth)
GET    /api/v1/orders/{id}              Order detail (auth, own order only)
POST   /api/v1/wishlist/{product_id}    Add to wishlist (auth)
DELETE /api/v1/wishlist/{product_id}    Remove from wishlist (auth)
GET    /api/v1/wishlist                 My wishlist (auth)
```

### Blog Routes
```
GET    /api/v1/posts                    List posts (public)
GET    /api/v1/posts/{slug}             Single post (public)
POST   /api/v1/posts/{id}/comments      Submit comment (auth)
```

### Contact & Newsletter
```
POST   /api/v1/contact                  Submit contact message (public)
POST   /api/v1/newsletter/subscribe     Subscribe (public)
```

### Admin Routes (admin role required)
```
Prefix: /api/v1/admin/

--- Dashboard ---
GET    /admin/stats                         Overview counts + recent activity

--- Users ---
GET    /admin/users                         List all users
PUT    /admin/users/{id}/ban                Ban a user
PUT    /admin/users/{id}/promote            Promote to admin

--- Tournaments ---
GET    /admin/tournaments                   List all tournaments
POST   /admin/tournaments                   Create tournament
PUT    /admin/tournaments/{id}              Update tournament
DELETE /admin/tournaments/{id}              Delete tournament
POST   /admin/tournaments/{id}/rules        Save tournament rules
POST   /admin/tournaments/{id}/generate-bracket   Generate bracket

--- Matches ---
GET    /admin/matches                       List all matches
PUT    /admin/matches/{id}/score            Update match score + winner
POST   /admin/matches/{id}/replay           Add VOD/replay link

--- Game Suggestions ---
GET    /admin/game-suggestions              List pending suggestions
PUT    /admin/game-suggestions/{id}/approve Approve (auto-creates game)
PUT    /admin/game-suggestions/{id}/reject  Reject with reason (sends notification)

--- Games ---
GET    /admin/games                         List games
POST   /admin/games                         Create game
PUT    /admin/games/{id}                    Update game
DELETE /admin/games/{id}                    Delete game

--- Disputes ---
GET    /admin/disputes                      List open disputes
PUT    /admin/disputes/{id}/resolve         Resolve dispute with note

--- Shop ---
GET    /admin/products                      List products
POST   /admin/products                      Create product
PUT    /admin/products/{id}                 Update product
DELETE /admin/products/{id}                 Delete product
GET    /admin/orders                        List orders
PUT    /admin/orders/{id}/status            Update order status
GET    /admin/coupons                       List coupons
POST   /admin/coupons                       Create coupon
PUT    /admin/coupons/{id}                  Update coupon
DELETE /admin/coupons/{id}                  Delete/disable coupon

--- Blog ---
GET    /admin/posts                         List blog posts
POST   /admin/posts                         Create post
PUT    /admin/posts/{id}                    Update post
PUT    /admin/posts/{id}/publish            Publish / unpublish
DELETE /admin/posts/{id}                    Delete post
PUT    /admin/comments/{id}/approve         Approve comment
DELETE /admin/comments/{id}                 Delete comment

--- Communication ---
POST   /admin/notifications/send            Send notification to specific user
POST   /admin/bulk-email                    Send bulk email to all users
GET    /admin/messages                      Contact messages
PUT    /admin/messages/{id}/read            Mark as read

--- Banners ---
GET    /admin/banners                       List banners
POST   /admin/banners                       Create banner
PUT    /admin/banners/{id}                  Update banner
DELETE /admin/banners/{id}                  Delete banner

--- Sponsors ---
GET    /admin/sponsors                      List sponsors
POST   /admin/sponsors                      Add sponsor
PUT    /admin/sponsors/{id}                 Update sponsor
DELETE /admin/sponsors/{id}                 Remove sponsor

--- Analytics ---
GET    /admin/analytics/overview            Visitor count, signups, popular pages
GET    /admin/analytics/tournaments         Tournament participation trends
```

---

## 10. Admin Panel Specification

### Layout
The admin panel has its own layout, completely separate from the public site.

```
┌─────────────┬────────────────────────────────────┐
│   SIDEBAR   │         MAIN CONTENT AREA           │
│             │                                    │
│  Logo       │  Page Title + Breadcrumb           │
│  ─────────  │  ─────────────────────────────     │
│  Dashboard  │  Stats cards / Tables / Forms      │
│  Tournam..  │                                    │
│  Matches    │                                    │
│  Games      │                                    │
│  ► Suggest. │                                    │
│  Players    │                                    │
│  Blog       │                                    │
│  Shop       │                                    │
│  ► Coupons  │                                    │
│  Disputes   │                                    │
│  Banners    │                                    │
│  Sponsors   │                                    │
│  Bulk Email │                                    │
│  Analytics  │                                    │
│  Users      │                                    │
│  Settings   │                                    │
│  ─────────  │                                    │
│  Logout     │                                    │
└─────────────┴────────────────────────────────────┘
```

### Dashboard (`admin/index.html`)
Stat cards at top:
- Total Users | Active Tournaments | Matches Today | Total Orders | Unread Disputes | Pending Game Suggestions

Below: Recent registrations, Recent orders, Recent disputes.

### Game Suggestions (`admin/game-suggestions.html`)
- Table: Game Name | Genre | Submitted By | Date | Status | Actions
- Actions: View Details | Approve | Reject (opens modal to enter reason)
- On Approve: backend creates game record automatically, sends "approved" notification to user

### Disputes (`admin/disputes.html`)
- Table: Match | Reported By | Type | Status | Date | Actions
- Actions: View Details | Mark Under Review | Resolve (opens modal to enter resolution note)
- On Resolve: backend sends notification to disputing user

### Banners (`admin/banners.html`)
- Table: Title | Type | Active | Start | End | Actions
- Create/Edit form: Title, Message, Type (info/warning/success/danger), Link, Start date, End date
- Toggle active/inactive instantly

### Sponsors (`admin/sponsors.html`)
- Grid view of sponsor logos with tier labels
- Upload logo, add name + website URL, assign tier, drag to reorder

### Coupons (`admin/coupons.html`)
- Table: Code | Type | Value | Uses | Expires | Status | Actions
- Create form: Code, Type (% or fixed), Value, Min order, Max uses, Expiry date

### Bulk Email (`admin/bulk-email.html`)
- Compose form: Subject, Body (rich text editor)
- Target: All Users / Only Active / Only Subscribers
- Preview before send
- Confirmation dialog before sending

### Analytics (`admin/analytics.html`)
- Cards: Total Visitors (30 days) | New Signups | Tournament Registrations | Orders
- Line chart: Daily visitors over time
- Bar chart: Most viewed pages
- Pie chart: Device breakdown (mobile/desktop)

---

## 11. User Panel Specification

### Navigation additions (public site header)
When user is NOT logged in:
```html
<a href="login.html" class="th-btn">Login</a>
<a href="register.html" class="th-btn style2">Register</a>
```

When user IS logged in (after backend integration):
```html
<span class="notification-bell">🔔 <span class="badge">3</span></span>
<span>Welcome, [Username]</span>
<a href="my-profile.html">My Profile</a>
<a href="my-tournaments.html">My Tournaments</a>
<a href="my-orders.html">My Orders</a>
<a href="activity-feed.html">Activity</a>
<a href="#" id="logoutBtn">Logout</a>
```

### Tournament Registration Flow
1. User visits `tournament.html` → sees open tournaments
2. Clicks tournament → `tournament-details.html`
3. Sees "Register Now" button (requires login)
4. After login → clicks Register → solo or team selection
5. Confirmation → tournament appears in `my-tournaments.html`
6. Before match: check-in reminder notification → user visits `checkin.html`

### Game Suggestion Flow
1. User clicks "Suggest a Game" → `suggest-game.html`
2. Fills form: name, genre, description, cover image URL
3. Submits → status shows "Pending"
4. Admin reviews in `admin/game-suggestions.html`
5. Admin approves → game added to `games` table → user gets "Approved" notification
6. Admin rejects → user gets "Rejected" notification with reason shown in `suggest-game.html`

### Shop + Cart Flow (frontend localStorage → backend in Phase 2)
1. User browses `shop.html`
2. Clicks "Add to Cart" → item saved to `localStorage`
3. Cart icon badge updates
4. In `cart.html`: enters optional coupon code → validated via API
5. Checkout → `checkout.html` with order form
6. After backend: form submits to API, order created in DB, confirmation email sent

### Wishlist Price Drop Notification Flow
1. User adds product to wishlist
2. Admin reduces `sale_price` on a product
3. Laravel Observer fires: queries `wishlists` for all users with that product
4. Sends email to each user + creates in-site notification: "Price dropped on [Product Name]!"

---

## 12. Feature Implementation Order

### Completed (MVP)

- Frontend: public site, auth pages, user dashboard, admin panel, `api.js` + connect scripts
- Backend: Sanctum auth, tournaments, shop, social, admin APIs, migrations, seed data
- Quality: PHPUnit + E2E script, CI workflow, production docs

### Optional next (post-MVP)

- Stripe payments, Pusher live scores, HttpOnly cookie auth
- Admin: full users/blog/coupons CRUD where UI exists but API is partial
- Playwright CI, hardened CSP, full avatar upload pipeline

---

## 13. Security Checklist

### Frontend
- [ ] Never store JWT token in localStorage — use httpOnly cookie via backend
- [ ] Sanitize all user-generated content before displaying (prevent XSS)
- [ ] All forms have CSRF protection (Laravel handles this automatically)
- [ ] Admin panel pages check role before rendering (JavaScript gate + backend gate)
- [ ] Game suggestion form: validate image URL is a real URL before submitting

### Backend (Laravel)
- [ ] All routes behind authentication use `auth:sanctum` middleware
- [ ] Admin routes use `role:admin` middleware via Spatie Permission
- [ ] All user input validated with Laravel Form Request classes
- [ ] Images validated by MIME type and size before saving
- [ ] SQL injection prevented — use Eloquent ORM, never raw string queries
- [ ] Passwords hashed with `bcrypt` (Laravel default)
- [ ] Rate limiting on auth endpoints (`throttle:5,1` — 5 attempts per minute)
- [ ] Rate limiting on game suggestion endpoint (prevent spam submissions)
- [ ] Rate limiting on dispute submissions (1 per match per user)
- [ ] Email verification required before tournament registration
- [ ] API returns generic error messages (never expose stack traces in production)
- [ ] Coupon codes validated server-side (never trust frontend-only validation)
- [ ] Digital product download links must be protected (signed URLs with expiry)
- [ ] Bulk email: only admin can trigger, rate limited to prevent abuse
- [ ] `.env` file never committed to Git (add to `.gitignore`)
- [ ] `APP_DEBUG=false` in production
- [ ] `APP_ENV=production` in production

---

## 14. File & Folder Structure

### Frontend (current + all new pages)
```
Tournament/
├── assets/
│   ├── css/
│   │   ├── app.min.css
│   │   ├── fontawesome.min.css
│   │   ├── style.css
│   │   └── admin.css              ← NEW (admin panel styles)
│   ├── js/
│   │   ├── vendor/
│   │   ├── app.min.js
│   │   ├── main.js                ← MODIFIED (remove DevTools block)
│   │   ├── cart.js                ← NEW (localStorage cart)
│   │   ├── validation.js          ← NEW (form validation)
│   │   └── api.js                 ← NEW (fetch wrapper for backend calls)
│   ├── img/
│   └── fonts/
│
├── admin/                         ← NEW folder (20 pages)
│   ├── index.html
│   ├── tournaments.html
│   ├── tournament-edit.html
│   ├── matches.html
│   ├── match-edit.html
│   ├── players.html
│   ├── games.html
│   ├── game-suggestions.html      ← NEW
│   ├── blog.html
│   ├── blog-edit.html
│   ├── products.html
│   ├── orders.html
│   ├── users.html
│   ├── disputes.html              ← NEW
│   ├── notifications.html         ← NEW
│   ├── banners.html               ← NEW
│   ├── sponsors.html              ← NEW
│   ├── coupons.html               ← NEW
│   ├── bulk-email.html            ← NEW
│   ├── analytics.html             ← NEW
│   └── settings.html
│
│── index.html                     ← MODIFIED
├── tournament.html                ← MODIFIED
├── tournament-details.html        ← MODIFIED
├── tournament-rules.html          ← NEW
├── bracket.html                   ← NEW
├── bracket-prediction.html        ← NEW
├── checkin.html                   ← NEW
├── dispute.html                   ← NEW
├── team.html                      ← MODIFIED
├── team-details.html              ← MODIFIED
├── game.html                      ← MODIFIED
├── game-details.html              ← MODIFIED
├── suggest-game.html              ← NEW
├── shop.html                      ← MODIFIED (coupon field)
├── shop-details.html              ← MODIFIED (wishlist bell)
├── cart.html                      ← MODIFIED (coupon input)
├── checkout.html                  ← MODIFIED
├── wishlist.html                  ← MODIFIED
├── blog.html                      ← MODIFIED
├── blog-details.html              ← MODIFIED
├── gallery.html                   ← MODIFIED
├── point-table.html               ← MODIFIED
├── tournament-history.html        ← NEW
├── about.html                     ← MODIFIED (sponsors section)
├── contact.html                   ← MODIFIED
├── search.html                    ← NEW
├── login.html                     ← NEW
├── register.html                  ← NEW
├── forgot-password.html           ← NEW
├── reset-password.html            ← NEW
├── my-profile.html                ← NEW
├── my-tournaments.html            ← NEW
├── my-orders.html                 ← NEW
├── my-notifications.html          ← NEW
├── player-profile.html            ← NEW (public)
├── friends.html                   ← NEW
├── activity-feed.html             ← NEW
├── achievements.html              ← NEW
├── stats.html                     ← NEW
├── head-to-head.html              ← NEW
├── service-details.html
└── error.html                     ← MODIFIED
```

### Backend (Laravel)
```
tournament-backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   ├── AuthController.php
│   │   │   │   └── PasswordResetController.php
│   │   │   ├── Admin/
│   │   │   │   ├── TournamentController.php
│   │   │   │   ├── MatchController.php
│   │   │   │   ├── GameSuggestionController.php
│   │   │   │   ├── DisputeController.php
│   │   │   │   ├── BannerController.php
│   │   │   │   ├── SponsorController.php
│   │   │   │   ├── CouponController.php
│   │   │   │   ├── BulkEmailController.php
│   │   │   │   ├── AnalyticsController.php
│   │   │   │   ├── ProductController.php
│   │   │   │   ├── OrderController.php
│   │   │   │   ├── BlogController.php
│   │   │   │   ├── UserController.php
│   │   │   │   └── GameController.php
│   │   │   ├── TournamentController.php
│   │   │   ├── MatchController.php
│   │   │   ├── TeamController.php
│   │   │   ├── GameController.php
│   │   │   ├── GameSuggestionController.php
│   │   │   ├── ProductController.php
│   │   │   ├── OrderController.php
│   │   │   ├── WishlistController.php
│   │   │   ├── BlogController.php
│   │   │   ├── FriendController.php
│   │   │   ├── PvpChallengeController.php
│   │   │   ├── AchievementController.php
│   │   │   ├── NotificationController.php
│   │   │   ├── StatsController.php
│   │   │   ├── DisputeController.php
│   │   │   ├── ContactController.php
│   │   │   └── NewsletterController.php
│   │   ├── Middleware/
│   │   │   └── AdminMiddleware.php
│   │   └── Requests/
│   │       ├── Auth/RegisterRequest.php
│   │       ├── TournamentRequest.php
│   │       ├── GameSuggestionRequest.php
│   │       ├── DisputeRequest.php
│   │       ├── CouponRequest.php
│   │       ├── ProductRequest.php
│   │       └── OrderRequest.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Tournament.php
│   │   ├── TournamentRule.php
│   │   ├── Match.php
│   │   ├── MatchReplay.php
│   │   ├── Team.php
│   │   ├── Game.php
│   │   ├── GameSuggestion.php
│   │   ├── Product.php
│   │   ├── Coupon.php
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   ├── Wishlist.php
│   │   ├── BlogPost.php
│   │   ├── Comment.php
│   │   ├── PointTable.php
│   │   ├── PlayerStat.php
│   │   ├── Friend.php
│   │   ├── PvpChallenge.php
│   │   ├── Achievement.php
│   │   ├── UserAchievement.php
│   │   ├── Notification.php
│   │   ├── BracketPrediction.php
│   │   ├── Dispute.php
│   │   ├── Banner.php
│   │   ├── Sponsor.php
│   │   └── TournamentCheckin.php
│   ├── Observers/
│   │   ├── GameSuggestionObserver.php  (auto-create game on approval)
│   │   └── WishlistObserver.php        (email on price drop)
│   └── Services/
│       ├── BracketService.php
│       ├── PointTableService.php
│       ├── OrderService.php
│       ├── AchievementService.php      (auto-award badges)
│       ├── NotificationService.php     (create in-site notifications)
│       └── PredictionScoringService.php
├── database/
│   └── migrations/                     (31 migration files)
├── routes/
│   ├── api.php
│   └── web.php
├── .env
├── .gitignore
└── composer.json
```

---

## 15. Tech Stack Summary

| Component | Technology | Notes |
|---|---|---|
| Frontend markup | HTML5 | Custom Arena frontend |
| Frontend styling | CSS3 + Bootstrap 5 | Existing `style.css` |
| Frontend scripts | Vanilla JavaScript | jQuery already in project |
| Slider / carousel | Swiper.js | Already included |
| Animations | WOW.js + CSS | Already included |
| Backend framework | Laravel (PHP 8.2+) | REST API |
| Authentication | Laravel Sanctum | Token-based |
| Role management | Spatie Permission | Admin vs User |
| Database | MySQL 8.0+ | 31 tables |
| ORM | Eloquent (Laravel) | No raw SQL |
| Image handling | Intervention Image | Resize on upload |
| Email | Laravel Mailable + SMTP | Mailtrap for dev |
| Real-time | Pusher + Laravel Echo | Live scores + live match chat |
| Search | Laravel Scout | Basic full-text |
| Storage | Laravel Storage | Local → S3 production |
| Observers | Laravel Observers | Auto-create game on suggestion approval; wishlist price drop email |
| Services | Custom Service classes | Bracket, PointTable, Order, Achievement, Notification, Prediction |
| Version control | Git | `.env` never committed |

---

*This document describes the Arena platform architecture and feature set for development and maintenance.*
