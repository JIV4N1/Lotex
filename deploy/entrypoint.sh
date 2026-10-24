#!/bin/sh
set -eu
cd /var/www/html
mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R u=rwX,g=rX,o= storage bootstrap/cache
# Never migrate automatically or regenerate APP_KEY on container restart.
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data bootstrap/cache storage/framework/views
chmod -R u=rwX,g=rX,o= bootstrap/cache storage/framework/views
exec docker-php-entrypoint "$@"
