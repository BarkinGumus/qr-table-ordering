#!/bin/sh
set -e

# The key comes from the host's .env (through env_file); the image itself
# contains no .env. Stop here with a clear message instead of serving 500s.
if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is empty. Generate one and put it in .env on the host:" >&2
    echo "  docker compose run --rm --no-deps --entrypoint php app artisan key:generate --show" >&2
    exit 1
fi

# Share the built public/ folder (CSS, admin assets) with the nginx container.
if [ -d /shared-public ]; then
    cp -a /var/www/html/public/. /shared-public/
fi

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
