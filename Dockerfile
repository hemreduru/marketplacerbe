# syntax=docker/dockerfile:1
#
# Cirotik production image (immutable). 3 aşama:
#   assets → Vite front-end build
#   app    → PHP-FPM runtime (bcmath/redis/pdo_mysql...) + composer + built assets
#   web    → nginx (public/ + fastcgi_pass app:9000)
#
# NOT (kod-dışı, flag): gerçek build + deploy insan kararıdır (Dokploy Compose).
# Görüntü yayına almadan önce build edilip staging'de doğrulanmalı.

# ── Stage 1: front-end assets ──
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
RUN npm ci
COPY resources ./resources
COPY public ./public
RUN npm run build

# ── Stage 2: PHP-FPM runtime ──
FROM php:8.3-fpm-alpine AS app
WORKDIR /var/www/html

# Money precision (bcmath), MySQL, Redis (rate-limit token bucket / queue / cache),
# intl (localization), zip/gd (composer + görseller), pcntl (queue signal), opcache.
RUN apk add --no-cache git curl libzip-dev icu-dev freetype-dev libjpeg-turbo-dev libpng-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath pdo_mysql intl zip gd opcache pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-cirotik.ini

# Bağımlılıkları önce kopyala (katman cache) → sonra kaynak
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY . .
COPY --from=assets /app/public/build ./public/build
RUN composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]

# ── Stage 3: nginx web ──
FROM nginx:1.27-alpine AS web
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
EXPOSE 80
