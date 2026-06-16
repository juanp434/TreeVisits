# TreeVisits — single-container PHP image running Laravel's built-in server.
FROM php:8.3-cli

# MySQL + common Laravel extensions.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev unzip \
    && docker-php-ext-install pdo_mysql zip \
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

# Prepare env/key on first run, wait for MySQL, migrate, then serve.
CMD ["sh", "-c", "\
    [ -f .env ] || cp .env.example .env; \
    php artisan key:generate --force; \
    until php artisan migrate --force; do echo 'waiting for db...'; sleep 2; done; \
    php artisan serve --host=0.0.0.0 --port=8000"]
