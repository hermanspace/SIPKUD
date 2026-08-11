# SIPKUD Laravel - Production Dockerfile
# PHP 8.3 + Apache, pdo_pgsql, Redis. Database via external postgres-db (server infra).
# =============================================================================
# Stage 1: Composer - Install PHP dependencies (must run before node for Flux CSS)
# =============================================================================
FROM composer:2 AS composer

WORKDIR /app

# Install system deps needed for PHP extensions (for composer install)
RUN apk add --no-cache \
    libpq \
    libzip \
    libpng \
    libjpeg-turbo \
    libxml2 \
    icu-libs \
    freetype

# Copy composer files first for better layer caching
COPY composer.json composer.lock* ./

# Flux Pro: set COMPOSER_AUTH in .env (compose passes as build arg)
ARG COMPOSER_AUTH
RUN export COMPOSER_AUTH="$COMPOSER_AUTH" && composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .

# Generate optimized autoloader (--no-scripts: skip package:discover which requires dev deps like Pail)
RUN composer dump-autoload --optimize --no-dev --no-scripts

# =============================================================================
# Stage 2: Node - Build Vite / frontend assets (needs vendor for Flux CSS)
# =============================================================================
FROM node:20-alpine AS node

WORKDIR /app

COPY --from=composer /app/vendor ./vendor

COPY package.json package-lock.json* ./

RUN npm ci

COPY vite.config.js ./

COPY resources ./resources
COPY public ./public

RUN npm run build

# =============================================================================
# Stage 3: Final runtime - PHP 8.3 + Apache
# =============================================================================
FROM php:8.3-apache AS runtime

# Install runtime and build deps for PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libpq5 \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    libxml2-dev \
    libonig-dev \
    zip \
    unzip \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install and enable PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    gd \
    zip \
    intl \
    mbstring \
    bcmath \
    xml \
    dom \
    opcache

RUN pecl install redis && docker-php-ext-enable redis

# Client tools database untuk fitur backup/restore dari aplikasi:
# - postgresql-client-16 dari repo PGDG (client bawaan Debian terlalu tua
#   untuk men-dump server PostgreSQL 16)
# - default-mysql-client untuk deployment yang masih memakai MySQL
RUN apt-get update && apt-get install -y --no-install-recommends gnupg \
    && install -d /usr/share/postgresql-common/pgdg \
    && curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc \
        -o /usr/share/postgresql-common/pgdg/apt.postgresql.org.asc \
    && echo "deb [signed-by=/usr/share/postgresql-common/pgdg/apt.postgresql.org.asc] https://apt.postgresql.org/pub/repos/apt $(. /etc/os-release && echo $VERSION_CODENAME)-pgdg main" \
        > /etc/apt/sources.list.d/pgdg.list \
    && apt-get update && apt-get install -y --no-install-recommends \
        postgresql-client-16 \
        default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite headers

# OPcache settings for production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

# Apache: serve from Laravel's public directory
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && sed -i -e '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Install Composer for runtime (composer install, etc. in mounted volumes)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application from composer stage
COPY --from=composer /app /var/www/html

# Copy built assets from node stage
COPY --from=node /app/public/build /var/www/html/public/build

# Ensure storage and bootstrap/cache exist (incl. upload dirs for logo/favicon & Livewire temp)
RUN mkdir -p /var/www/html/storage/framework/{sessions,views,cache/data} \
    /var/www/html/storage/logs \
    /var/www/html/storage/app/public \
    /var/www/html/storage/app/private \
    /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# PHP upload limits (logo max 2MB, Livewire temp)
RUN echo "upload_max_filesize=10M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=12M" >> /usr/local/etc/php/conf.d/uploads.ini

# Entrypoint: fix permissions, storage:link, then start Apache
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Prefer production php.ini if present
RUN if [ -f "$PHP_INI_DIR/php.ini-production" ]; then \
    cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"; fi

EXPOSE 80

# Health check via endpoint /up (health route Laravel, ringan)
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost/up || exit 1

# Entrypoint: fix permissions + storage:link; CMD default = Apache (overridden by queue/scheduler)
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
