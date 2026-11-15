# Image de production de DevKora pour le service web Systalink (région Dakar).
# Nginx et PHP-FPM dans un seul conteneur, base PostgreSQL managée à part (variables DB_*).

# 1. Assets (Vite)
FROM node:22-bookworm-slim AS assets
WORKDIR /app
ENV PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD=1
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
RUN npm run build

# 2. Dépendances PHP, sans les outils de développement
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --no-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative --no-scripts

# 3. Exécution
FROM php:8.2-fpm-bookworm
RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx supervisor gettext-base postgresql postgresql-contrib libpq-dev libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev libicu-dev libzip-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql gd intl bcmath zip opcache \
    && apt-get purge -y --auto-remove libpq-dev libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev libicu-dev libzip-dev \
    && apt-get install -y --no-install-recommends libpq5 libpng16-16 libjpeg62-turbo libwebp7 libfreetype6 libicu72 libzip4 \
    && rm -rf /var/lib/apt/lists/* /etc/nginx/sites-enabled/default

WORKDIR /var/www/devkora
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

COPY deploy/nginx/devkora-security-headers.conf /etc/nginx/snippets/devkora-security-headers.conf
COPY docker/nginx.conf /etc/nginx/conf.d/devkora.conf.template
COPY docker/php.ini /usr/local/etc/php/conf.d/devkora.ini
COPY docker/fpm.conf /usr/local/etc/php-fpm.d/zz-devkora.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/devkora.conf
COPY docker/entrypoint.sh /usr/local/bin/devkora-entrypoint

RUN chmod +x /usr/local/bin/devkora-entrypoint \
    && rm -f .env \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Base locale de secours : grappe PostgreSQL créée et démo semée dès la construction de l'image,
# pour que le conteneur démarre en quelques secondes même si la base managée ne répond pas.
RUN PGBIN=$(ls -d /usr/lib/postgresql/*/bin | head -n1) \
    && install -d -o postgres -g postgres /var/lib/postgresql/devkora \
    && su postgres -c "$PGBIN/initdb -D /var/lib/postgresql/devkora -E UTF8 --locale=C.UTF-8 -A trust" \
    && su postgres -c "$PGBIN/pg_ctl -D /var/lib/postgresql/devkora -o '-c listen_addresses=127.0.0.1' -w start" \
    && su postgres -c "psql -c \"create role devkora login password 'devkora' superuser\"" \
    && su postgres -c "createdb -O devkora devkora" \
    && su -s /bin/bash www-data -c "APP_ENV=production APP_DEMO=true APP_KEY=base64:$(head -c 32 /dev/urandom | base64) DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5432 DB_DATABASE=devkora DB_USERNAME=devkora DB_PASSWORD=devkora CACHE_STORE=database SESSION_DRIVER=database LOG_CHANNEL=stderr php artisan demo:reset" \
    && su postgres -c "$PGBIN/pg_ctl -D /var/lib/postgresql/devkora -m fast -w stop" \
    && rm -f bootstrap/cache/*.php

ENV PORT=8080
EXPOSE 8080
ENTRYPOINT ["devkora-entrypoint"]
