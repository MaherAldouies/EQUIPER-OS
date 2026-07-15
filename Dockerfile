# EQUIPER OS — Render.com free-tier deployment image.
# Single container running `php artisan serve` (Laravel's built-in
# server, bound to Render's $PORT) — not a production-grade setup for
# real traffic, but matches the free-tier scope already agreed on:
# background workers (queue, event relay, scheduled Salla/X polling)
# do not run here regardless, since Render's free plan has no worker
# service.
FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev libonig-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && npm install \
    && npm run build \
    && npm cache clean --force

EXPOSE 10000

# Render sets $PORT at runtime; migrations run on boot (safe to run on
# every deploy — Laravel's migrator only applies pending ones).
CMD php artisan migrate --force && php artisan config:cache && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
