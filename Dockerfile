# syntax=docker/dockerfile:1
# Official OCI-compatible Dockerfile for MapaCultural
# Multi-stage build with separated Node.js and PHP builds

ARG NODE_VERSION=20
ARG PHP_VERSION=8.3

# =============================================================================
# Stage 1: Node.js builder - Compiles frontend assets
# =============================================================================
FROM node:${NODE_VERSION}-alpine AS builder-node

RUN corepack enable && corepack prepare pnpm@latest --activate

# Install sass globally for BaseV1 compilation
RUN npm install -g sass

WORKDIR /build

# Copy entire src directory for pnpm workspace
# This ensures proper workspace resolution
COPY src/ ./

# Install dependencies
RUN pnpm install --frozen-lockfile 2>/dev/null || pnpm install

# Build all workspaces (modules, plugins, themes)
RUN pnpm run build

# Compile SASS for BaseV1 theme (legacy)
RUN if [ -f themes/BaseV1/assets/css/sass/main.scss ]; then \
      sass themes/BaseV1/assets/css/sass/main.scss:themes/BaseV1/assets/css/main.css --quiet; \
    fi

# Remove node_modules to reduce copy size (not needed in final image)
RUN find . -name "node_modules" -type d -prune -exec rm -rf {} + 2>/dev/null || true

# =============================================================================
# Stage 2: Composer builder - Installs PHP dependencies
# =============================================================================
FROM php:${PHP_VERSION}-cli-alpine AS builder-composer

RUN apk add --no-cache git unzip

# Install PHP extensions needed for Composer dependencies
RUN apk add --no-cache \
    libpng \
    libjpeg-turbo \
    freetype \
    libzip && \
    apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) gd zip && \
    docker-php-ext-enable gd zip && \
    apk del .build-deps && \
    rm -rf /tmp/pear /var/cache/apk/*

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /build

COPY composer.json composer.lock ./

ARG COMPOSER_ARGS="--no-dev --optimize-autoloader --no-interaction"
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN composer install ${COMPOSER_ARGS} --no-scripts

# =============================================================================
# Stage 3: Production image - PHP-FPM runtime
# =============================================================================
FROM php:${PHP_VERSION}-fpm-alpine AS production

LABEL org.opencontainers.image.title="MapaCultural"
LABEL org.opencontainers.image.description="Platform for cultural mapping"
LABEL org.opencontainers.image.vendor="RedeMapas"
LABEL org.opencontainers.image.source="https://github.com/redemapas/mapas"
LABEL org.opencontainers.image.licenses="AGPL-3.0"

# Install runtime dependencies
RUN apk add --no-cache \
    sudo \
    bash \
    git \
    imagemagick \
    libpq \
    libzip \
    libpng \
    libjpeg-turbo \
    freetype \
    icu-libs \
    zstd-libs \
    curl

# Install build dependencies for PHP extensions
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    imagemagick-dev \
    postgresql-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    linux-headers

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) \
    opcache \
    pdo_pgsql \
    zip \
    gd \
    intl

# Install APCu
RUN pecl install apcu && \
    docker-php-ext-enable apcu

# Install Imagick
RUN pecl install imagick && \
    docker-php-ext-enable imagick

# Install Redis
RUN pecl install redis && \
    docker-php-ext-enable redis

# Cleanup build dependencies
RUN apk del .build-deps && \
    rm -rf /tmp/pear /var/cache/apk/*

# Create www-data user if not exists and setup directories
RUN mkdir -p /var/www/var/DoctrineProxies \
             /var/www/var/logs \
             /var/www/var/private-files \
             /var/www/html/assets \
             /var/www/html/files && \
    chown -R www-data:www-data /var/www

WORKDIR /var/www

# Copy composer dependencies
COPY --from=builder-composer /build/vendor ./vendor/

# Copy application source
COPY --chown=www-data:www-data config ./config/
COPY --chown=www-data:www-data public ./html/
COPY --chown=www-data:www-data scripts ./scripts/
COPY --chown=www-data:www-data src ./src/

# Copy built frontend assets from Node.js stage
COPY --from=builder-node --chown=www-data:www-data /build/modules/ ./src/modules/
COPY --from=builder-node --chown=www-data:www-data /build/themes/ ./src/themes/

# Copy version file
COPY version.txt ./version.txt

# Copy PHP configuration
COPY docker/production/php.ini /usr/local/etc/php/php.ini
COPY docker/timezone.ini /usr/local/etc/php/conf.d/timezone.ini

# Copy entrypoint and scripts
COPY docker/entrypoint.sh /entrypoint.sh
COPY docker/jobs-cron.sh /jobs-cron.sh
COPY docker/recreate-pending-pcache-cron.sh /recreate-pending-pcache-cron.sh

RUN chmod +x /entrypoint.sh /jobs-cron.sh /recreate-pending-pcache-cron.sh

# Create symlink for public
RUN ln -sf /var/www/html /var/www/public

# Ensure proper permissions
RUN chown -R www-data:www-data /var/www/html/ /var/www/var/

EXPOSE 9000

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]

# =============================================================================
# Stage 4: Development image - Includes build tools
# =============================================================================
FROM production AS development

# Install Node.js and pnpm for hot-reload development
RUN apk add --no-cache nodejs npm && \
    npm install -g pnpm sass terser uglifycss autoprefixer postcss

# Install xdebug (but don't enable by default)
RUN apk add --no-cache --virtual .xdebug-deps $PHPIZE_DEPS linux-headers && \
    pecl install xdebug && \
    apk del .xdebug-deps && \
    ln -s $(find /usr/local/lib/php/extensions/ -name xdebug.so) /usr/local/lib/php/extensions/xdebug.so

# Copy development files
COPY docker/development/router.php /var/www/dev/router.php
COPY docker/development/start.sh /var/www/dev/start.sh

RUN chmod +x /var/www/dev/start.sh

# Development uses built-in PHP server
EXPOSE 80

CMD ["/var/www/dev/start.sh"]
