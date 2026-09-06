# ---------------------------------------------------------------------------
# Stage 1 - build front-end assets with Vite
# ---------------------------------------------------------------------------
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm install
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
RUN npm run build

# ---------------------------------------------------------------------------
# Stage 2 - PHP dependencies (Composer)
# ---------------------------------------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
# Platform extensions (pgsql, gd, intl, ...) live in the runtime image, not the
# composer image, so skip the platform check here.
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist \
        --no-interaction --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts --ignore-platform-reqs

# ---------------------------------------------------------------------------
# Stage 3 - runtime image (PHP-FPM)
# ---------------------------------------------------------------------------
FROM php:8.3-fpm-alpine AS runtime

# System deps + PHP extensions (pgsql, gd for DomPDF, zip for exports)
RUN apk add --no-cache \
        postgresql-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev icu-dev oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql gd zip intl bcmath mbstring opcache pcntl

# Sensible production PHP config
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html

# Application code + built assets + vendor
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

RUN chmod +x docker/php/entrypoint.sh \
    && mkdir -p storage/framework/{cache/data,sessions,views} storage/logs storage/app/attachments bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

ENTRYPOINT ["/var/www/html/docker/php/entrypoint.sh"]
CMD ["php-fpm"]

# ---------------------------------------------------------------------------
# Stage 4 - Nginx image (serves static files, proxies PHP to the app container)
# ---------------------------------------------------------------------------
# public/ is copied FROM the runtime stage (not rebuilt) so the static files
# nginx serves are byte-for-byte the ones the app container generates manifest
# entries for - otherwise a hash drift between two Vite builds 404s the CSS/JS.
FROM nginx:1.27-alpine AS web

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=runtime /var/www/html/public /var/www/html/public
