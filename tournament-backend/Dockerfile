# BeyondPlay Arena Laravel Backend Dockerfile for Render / Cloud
FROM php:8.4-cli-alpine

RUN apk add --no-cache \
    curl \
    git \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    sqlite-dev \
    oniguruma-dev \
    linux-headers \
    && docker-php-ext-install \
    bcmath \
    mbstring \
    pdo \
    pdo_mysql \
    pdo_sqlite \
    zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . /app/

# Automatically detect if built from repo root or tournament-backend folder
RUN if [ -d "/app/tournament-backend" ] && [ -f "/app/tournament-backend/composer.json" ]; then \
        cp -rf /app/tournament-backend/. /app/ && rm -rf /app/tournament-backend; \
    fi

RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs \
    && chmod +x /app/entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/app/entrypoint.sh"]
