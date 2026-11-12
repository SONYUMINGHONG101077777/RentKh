FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm install && npm run build

FROM php:8.3-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libonig-dev libsqlite3-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite mbstring zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-scripts \
    --optimize-autoloader --no-interaction

COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache

RUN composer dump-autoload --optimize

RUN chmod -R 775 storage bootstrap/cache

CMD php artisan serve --host=0.0.0.0 --port=${PORT:-10000}