#!/bin/sh
set -e

cd /var/www/html

mkdir -p \
  storage/app/public \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

if [ ! -L public/storage ]; then
  rm -rf public/storage
  ln -s /var/www/html/storage/app/public public/storage
fi

php artisan optimize:clear --no-interaction >/dev/null 2>&1 || true

exec "$@"
