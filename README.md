# BeyondPlay (Arena)

Full-stack esports tournament platform: custom HTML/CSS/JS frontend and a Laravel 12 API.

**View on GitHub only. Proprietary — all rights reserved.**  
Copyright © 2026 [MD RAIYAN IBNE KAMAL](https://github.com/raiyanibnekamal). You may look at this project; you may not copy, reuse, or run it as your own. See [LICENSE](LICENSE).

**Docs:** [DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md) · [ARENA_SPEC.md](ARENA_SPEC.md) · [DEPLOY.md](DEPLOY.md) · [CHANGELOG.md](CHANGELOG.md)

---

## Features

- Player auth (register, login, password reset) and admin panel
- Tournaments, brackets, matches, standings, check-in, predictions
- Teams, friends, PvP challenges, notifications, activity feed
- Shop, cart, checkout, coupons, wishlist, orders
- Blog, banners, sponsors, contact, newsletter
- Rate-limited REST API (`/api/v1`) with Sanctum

### Usage examples

```bash
# API (from tournament-backend)
php artisan serve
# → http://127.0.0.1:8000/api/v1

# Frontend (from repo root)
.\serve-frontend.ps1
# → http://127.0.0.1:5500/index.html

# Tests
cd tournament-backend
php artisan test
```

Open **http://127.0.0.1:5500/index.html** in a browser (not `file://`).

---

## Installation & Usage

### 1. Backend

```bash
cd tournament-backend
composer install
cp .env.example .env
php artisan key:generate
```

**MySQL (XAMPP):** start Apache/MySQL, create database `tournament_db`, set `DB_CONNECTION=mysql` in `.env`, then:

```bash
php artisan migrate:fresh --seed
php artisan serve
```

**SQLite:** set `DB_CONNECTION=sqlite` in `.env`, then `php artisan migrate --seed` and `php artisan serve`.

After seeding, the console prints one-time passwords for:

- Admin: `admin@arena.gg`
- Player: `player@arena.gg`

Optional: set `ADMIN_SEED_PASSWORD` and `PLAYER_SEED_PASSWORD` in `.env` before seeding.

### 2. Frontend

Do **not** double-click HTML files. Serve the project root over HTTP:

```powershell
# Terminal 1 — API
cd tournament-backend
php artisan serve

# Terminal 2 — frontend (repo root)
.\serve-frontend.ps1
```

Then open **http://127.0.0.1:5500/index.html**.

Local API is `http://127.0.0.1:8000/api/v1` (auto from `api.js` on port 5500). Production: set `ARENA_API_BASE` in `assets/js/config.js`.

### 3. Queue & digest (optional)

```bash
php artisan queue:work
php artisan arena:weekly-digest
```

### Environment (backend)

| Variable | Purpose |
|----------|---------|
| `APP_DEBUG` | `false` in production |
| `APP_URL` | Laravel app URL |
| `DB_*` | MySQL `tournament_db` or SQLite |
| `MAIL_*` | Order confirmations, digests, bulk email |
| `QUEUE_CONNECTION` | `database` or `sync` for dev |
| `FRONTEND_URL` | Password-reset links; site URL |
| `CORS_ALLOWED_ORIGINS` | Dev: `http://127.0.0.1:5500,http://localhost:5500` — prod: your domain only |

`.env` is gitignored — never commit secrets.

Avatar uploads: run `php artisan storage:link` once.

Production deploy: [DEPLOY.md](DEPLOY.md). QA: [QA_CHECKLIST.md](QA_CHECKLIST.md).

---

## License

**Proprietary — all rights reserved** (`LicenseRef-Proprietary`). This is **not** MIT, Apache-2.0, or GPL.

| Allowed | Not allowed |
|---------|-------------|
| View this repo on GitHub | Copy, reuse, sell, or run as your own site |
| | Fork-for-reuse, redistribute, or remove copyright |

Only the owner can change this repository. See [LICENSE](LICENSE) and [LICENSE.md](LICENSE.md).

---

## Author

**Author: MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal**

- GitHub: [@raiyanibnekamal](https://github.com/raiyanibnekamal)
- Email: [raiyanibnekamal@gmail.com](mailto:raiyanibnekamal@gmail.com)
- Repository: [github.com/raiyanibnekamal/BeyondPlay](https://github.com/raiyanibnekamal/BeyondPlay)

Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal  
`SPDX-License-Identifier: LicenseRef-Proprietary`

---

## Contribution guideline

This project is **proprietary**. Unsolicited pull requests are not accepted.

- Do not fork this repo to republish or reuse the code.
- Bug reports or license questions: contact the author via GitHub.
- Collaborators (if any) must be invited by the owner; `CODEOWNERS` requires review from [@raiyanibnekamal](https://github.com/raiyanibnekamal).

See [CONTRIBUTING.md](CONTRIBUTING.md).

---

## Project structure

```
BeyondPlay/
├── assets/                 # CSS, JS, images
├── admin/                  # Admin HTML
├── *.html                  # Public & player pages
├── tournament-backend/     # Laravel 12 API
├── LICENSE
├── LICENSE.md
└── CHANGELOG.md
```

## Security

- Sanctum + bcrypt + admin middleware + API rate limits
- Details: [DEVELOPER_GUIDE.md — Section 8](DEVELOPER_GUIDE.md#8-security) and [PRODUCTION_SECURITY.md](PRODUCTION_SECURITY.md)
- Production: HTTPS, CORS lock, `APP_DEBUG=false`, rotate seed passwords
