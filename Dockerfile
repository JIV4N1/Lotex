FROM node:24-bookworm-slim AS frontend
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js ./
RUN npm run build

FROM php:8.4-apache-bookworm AS runtime
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev libicu-dev libzip-dev libonig-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_pgsql intl zip bcmath mbstring gd opcache \
    && a2enmod rewrite headers expires \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY . .
COPY --from=frontend /build/public/build ./public/build
RUN mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader \
    && composer check-platform-reqs --no-dev \
    && chown -R www-data:www-data storage bootstrap/cache
COPY deploy/apache.conf /etc/apache2/sites-available/000-default.conf
COPY deploy/php.ini /usr/local/etc/php/conf.d/production.ini
COPY deploy/entrypoint.sh /usr/local/bin/horizonte-entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/horizonte-entrypoint \
    && chmod 755 /usr/local/bin/horizonte-entrypoint
EXPOSE 80
ENTRYPOINT ["horizonte-entrypoint"]
CMD ["apache2-foreground"]
