# Git security — remove committed `.env`

`.env` is listed in `tournament-backend/.gitignore`. If it was ever committed, remove it from history before pushing.

## 1. Stop tracking (keeps local file)

```bash
cd tournament-backend
git rm --cached .env
git commit -m "Stop tracking .env"
```

## 2. Purge from history (BFG — recommended)

```bash
# Install BFG, then from repo root:
java -jar bfg.jar --delete-files .env
git reflog expire --expire=now --all && git gc --prune=now --aggressive
```

Or with `git filter-repo`:

```bash
git filter-repo --path tournament-backend/.env --invert-paths
```

## 3. Rotate secrets

After removal, on the server:

```bash
cd tournament-backend
php artisan key:generate
```

Update database passwords, `MAIL_*`, and any API keys that were in the old `.env`.

## 4. Verify

```bash
git log --all --full-history -- tournament-backend/.env
# should return nothing
```

Never commit `tournament-backend/.env` again. Use `.env.production.example` as the template.
