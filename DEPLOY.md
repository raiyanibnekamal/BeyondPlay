# Arena — Production deployment



See also `PRODUCTION_SECURITY.md`, `DEVELOPER_GUIDE.md`, and `NEXT_STEPS.md` Phase 2.



## Before every deployment (required)



Verify these in the server `.env` and frontend config:



| Setting | Production value |

|---------|------------------|

| `APP_ENV` | `production` |

| `APP_DEBUG` | `false` |

| `LOG_LEVEL` | `error` |

| `SESSION_ENCRYPT` | `true` |

| `FRONTEND_URL` | `https://yourdomain.com` |

| `CORS_ALLOWED_ORIGINS` | `https://yourdomain.com` |

| `assets/js/config.js` | Real `ARENA_API_BASE` (not localhost / placeholder) |



**Session encryption:** Enabling `SESSION_ENCRYPT=true` invalidates existing sessions. Users must log in again after deploy.



**Seed accounts:** After `db:seed`, the console prints one-time passwords. Rotate or delete `admin@arena.gg` / `player@arena.gg` before go-live.



**Git:** Never commit `.env`. If it was committed, follow `SECURITY_GIT.md` and rotate all secrets.



## Server requirements



- PHP 8.2+, Composer, MySQL 8+, Nginx or Apache

- Supervisor (queue), Certbot (HTTPS)



## Backend



```bash

cd tournament-backend

composer install --no-dev --optimize-autoloader

cp .env.production.example .env   # edit all [CHANGE ME] values

php artisan key:generate

php artisan migrate --force

# First deploy only — save passwords printed to console:

# ADMIN_SEED_PASSWORD=... PLAYER_SEED_PASSWORD=... php artisan db:seed

php artisan config:cache

php artisan route:cache

php artisan view:cache

```



### Production `.env` essentials



```env

APP_ENV=production

APP_DEBUG=false

LOG_LEVEL=error

APP_URL=https://api.yourdomain.com



DB_CONNECTION=mysql

DB_DATABASE=tournament_db

DB_USERNAME=arena_app

DB_PASSWORD=[strong password]



SESSION_ENCRYPT=true

SESSION_SECURE_COOKIE=true

SESSION_DOMAIN=.yourdomain.com



FRONTEND_URL=https://yourdomain.com

CORS_ALLOWED_ORIGINS=https://yourdomain.com



SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com

ARENA_HTTPONLY_AUTH_COOKIE=true



QUEUE_CONNECTION=database

MAIL_MAILER=smtp

```



## Queue worker (Supervisor)



Copy `deployment/supervisor/arena-worker.conf` to `/etc/supervisor/conf.d/`, edit paths and user, then:



```bash

sudo supervisorctl reread

sudo supervisorctl update

sudo supervisorctl start arena-queue:*

```



Logs: `tournament-backend/storage/logs/worker.log`



## Scheduler



Cron (every minute):



```

* * * * * cd /path/to/tournament-backend && php artisan schedule:run >> /dev/null 2>&1

```



## Database backups



```bash

export DB_HOST=127.0.0.1 DB_USER=arena_app DB_PASSWORD=... DB_NAME=tournament_db BACKUP_DIR=/var/backups/arena

bash deployment/scripts/backup-db.sh

```



Example cron (daily 3 AM):



```

0 3 * * * cd /path/to/Tournament && DB_HOST=127.0.0.1 DB_USER=arena_app DB_PASSWORD=secret DB_NAME=tournament_db BACKUP_DIR=/var/backups/arena bash deployment/scripts/backup-db.sh

```



## Frontend



1. Edit `assets/js/config.js` — set `ARENA_API_BASE` to `https://api.yourdomain.com/api/v1`

2. Serve `Tournament/` as static site root (Nginx `root`)

3. Proxy API to Laravel `public/` or use API subdomain



```

location /api {

    proxy_pass http://127.0.0.1:8000;

}

```



## Storage (avatars)



```bash

php artisan storage:link

```



## Post-deploy checks



- [ ] HTTPS on frontend and API

- [ ] `php artisan test` on release build

- [ ] Seed passwords rotated or seed users removed

- [ ] CORS only allows production domain

- [ ] Register, login, checkout, admin in browser

- [ ] Queue worker running (`supervisorctl status`)


