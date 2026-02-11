# ============================================================
# Stage 1: Composer — install PHP dependencies
# ============================================================
FROM composer:2 AS composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

COPY . .
RUN composer dump-autoload --optimize --no-dev

# ============================================================
# Stage 2: Frontend — build Vite assets
# ============================================================
FROM node:20-alpine AS frontend

WORKDIR /app
COPY package.json package-lock.json ./

# Ziggy needs to be available for the Vite build
COPY --from=composer /app/vendor/tightenco/ziggy /app/vendor/tightenco/ziggy

COPY resources/ resources/
COPY vite.config.js tsconfig.json tailwind.config.js postcss.config.js ./

RUN npm ci && npm run build

# ============================================================
# Stage 3: App — PHP-FPM runtime
# ============================================================
FROM php:8.2-fpm-bookworm AS app

# Install system dependencies + PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libwebp-dev \
        libzip-dev \
        libicu-dev \
        libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        gd \
        intl \
        zip \
        bcmath \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

# OPcache production settings
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.enable_cli=1'; \
    } > /usr/local/etc/php/conf.d/opcache-prod.ini

# PHP-FPM tuning
RUN { \
    echo '[www]'; \
    echo 'pm = dynamic'; \
    echo 'pm.max_children = 20'; \
    echo 'pm.start_servers = 4'; \
    echo 'pm.min_spare_servers = 2'; \
    echo 'pm.max_spare_servers = 6'; \
    } > /usr/local/etc/php-fpm.d/zz-tuning.conf

# PHP production settings
RUN { \
    echo 'upload_max_filesize = 20M'; \
    echo 'post_max_size = 25M'; \
    echo 'memory_limit = 256M'; \
    echo 'expose_php = Off'; \
    } > /usr/local/etc/php/conf.d/production.ini

WORKDIR /var/www/html

# Copy application code
COPY --from=composer /app /var/www/html

# Copy built frontend assets
COPY --from=frontend /app/public/build /var/www/html/public/build

# Move public to a staging directory — entrypoint copies it to the shared volume
RUN cp -r /var/www/html/public /var/www/html/public-staging

# Create storage directories
RUN mkdir -p \
    storage/app/attachments \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache /var/www/html/public-staging

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
