#!/bin/sh
set -e

mkdir -p /app/storage/framework/sessions \
         /app/storage/framework/views \
         /app/storage/framework/cache \
         /app/storage/logs \
         /app/bootstrap/cache \
         /app/database

chmod -R 777 /app/storage /app/bootstrap/cache /app/database

if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    if [ ! -f /app/database/database.sqlite ] || [ ! -s /app/database/database.sqlite ]; then
        touch /app/database/database.sqlite
        chmod 777 /app/database/database.sqlite
        php artisan migrate --force --seed
    else
        php artisan migrate --force
    fi
else
    php artisan migrate --force
fi

php artisan storage:link || true

echo "BeyondPlay Backend starting on port ${PORT:-8000}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
