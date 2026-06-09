# TreeVisits — single-container PHP image running Laravel's built-in server.
FROM php:8.3-cli

# SQLite + common Laravel extensions.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libsqlite3-dev libzip-dev unzip \
    && docker-php-ext-install pdo_sqlite zip \
    && rm -rf /var/lib/apt/lists/*

# Composer.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install dependencies first to leverage Docker layer caching.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist

COPY . .
RUN composer dump-autoload --optimize

EXPOSE 8000

# Prepare env/key/db on first run, then serve.
CMD ["sh", "-c", "\
    [ -f .env ] || cp .env.example .env; \
    php artisan key:generate --force; \
    touch database/database.sqlite; \
    php artisan migrate --force; \
    php artisan serve --host=0.0.0.0 --port=8000"]
