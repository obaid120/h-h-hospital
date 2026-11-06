# Build from the REPOSITORY root: docker build -f h-h-hospital/Dockerfile .
FROM node:24-bookworm-slim AS assets
WORKDIR /build
COPY h-h-hospital/package.json h-h-hospital/package-lock.json h-h-hospital/.npmrc ./
RUN npm ci --ignore-scripts
COPY h-h-hospital/resources ./resources
COPY h-h-hospital/vite.config.js ./vite.config.js
RUN npm run build

FROM php:8.4-fpm-bookworm AS runtime
RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx supervisor libonig-dev libzip-dev unzip ca-certificates \
    && docker-php-ext-install pdo_mysql mbstring zip opcache \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
# Explicit allowlist: runtime secrets/files are never copied into image layers.
COPY h-h-hospital/composer.json h-h-hospital/composer.lock ./
RUN composer install --no-dev --prefer-dist --no-scripts --no-autoloader --no-interaction
COPY h-h-hospital/app ./app
COPY h-h-hospital/bootstrap ./bootstrap
COPY h-h-hospital/config ./config
COPY h-h-hospital/database/migrations ./database/migrations
COPY h-h-hospital/database/seeders ./database/seeders
COPY h-h-hospital/lang ./lang
COPY h-h-hospital/routes ./routes
COPY h-h-hospital/resources/views ./resources/views
COPY h-h-hospital/public ./public
COPY h-h-hospital/artisan ./artisan
COPY --from=assets /build/public/build ./public/build
RUN mkdir -p storage/app/private storage/app/public storage/framework/cache/data \
    storage/framework/cache/locks storage/framework/sessions storage/framework/views storage/logs database/factories \
    && composer dump-autoload --no-dev --optimize --no-interaction \
    && composer check-platform-reqs --no-dev \
    && chown -R www-data:www-data storage bootstrap/cache
COPY docs/hosting/render-nginx.conf /etc/nginx/nginx.conf
COPY docs/hosting/render-supervisord.conf /etc/supervisor/conf.d/handh.conf
COPY docs/hosting/render-php.ini /usr/local/etc/php/conf.d/handh.ini
COPY docs/hosting/render-fpm.conf /usr/local/etc/php-fpm.d/zz-handh.conf
COPY docs/hosting/render-entrypoint.sh /usr/local/bin/handh-start
RUN chmod 755 /usr/local/bin/handh-start
ENV PORT=10000
EXPOSE 10000
ENTRYPOINT ["/usr/local/bin/handh-start"]
