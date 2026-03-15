# =============================================================================
# Stage 1: Node - Build Vite / frontend assets
# =============================================================================
FROM node:20-alpine AS node

WORKDIR /app

# Copy package files
COPY package.json package-lock.json* ./

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install dependencies (including optional Linux binaries for Vite/Tailwind)
RUN npm ci

# Copy frontend source (Tailwind 4 config is in CSS / Vite plugin)
COPY vite.config.js ./
COPY resources ./resources

# Copy Laravel files needed for Vite (public index, manifest placeholder)
COPY public ./public

# Build production assets
RUN npm run build

# =============================================================================
# Stage 2: Composer - Install PHP dependencies
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

# For private Flux Pro repo: set COMPOSER_AUTH in build or at runtime
# ARG COMPOSER_AUTH
# ENV COMPOSER_AUTH=${COMPOSER_AUTH}

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .

# Run composer scripts and generate optimized autoloader
RUN composer dump-autoload --optimize --no-dev

# =============================================================================
# Stage 3: Final runtime - PHP 8.3 + Apache
# =============================================================================
FROM php:8.3-apache AS runtime

# Install runtime and build deps for PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq5 \
    libzip4 \
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

# Copy application from composer stage
COPY --from=composer /app /var/www/html

# Copy built assets from node stage
COPY --from=node /app/public/build /var/www/html/public/build

# Ensure storage and bootstrap/cache exist and are writable
RUN mkdir -p /var/www/html/storage/framework/{sessions,views,cache/data} \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Prefer production php.ini if present
RUN if [ -f "$PHP_INI_DIR/php.ini-production" ]; then \
    cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"; fi

EXPOSE 80

# Health check via HTTP
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Apache runs as PID 1
CMD ["apache2-foreground"]
