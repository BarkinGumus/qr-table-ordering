#!/bin/sh
set -e

# Share the built public/ folder (CSS, admin assets) with the nginx container.
if [ -d /shared-public ]; then
    cp -a /var/www/html/public/. /shared-public/
fi

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
