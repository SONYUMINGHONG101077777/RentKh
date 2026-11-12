
FROM php:8.4-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    ca-certificates \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    libicu-dev \
    && update-ca-certificates \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        bcmath \
        intl \
        zip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install Laravel dependencies
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

# Copy Laravel application
COPY . .

# Create Laravel storage directories
RUN mkdir -p \
    storage/app/public \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Optimize Composer autoload
RUN composer dump-autoload --optimize --no-dev

# Set storage permissions
RUN chmod -R 775 storage bootstrap/cache

# Create public storage link
RUN ln -sfn /var/www/html/storage/app/public /var/www/html/public/storage

# Expose Render port
EXPOSE 10000

# Start Laravel API
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
