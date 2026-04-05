#!/usr/bin/env sh
set -eu

cd /var/www/html

if [ ! -f ".env" ]; then
  cp .env.example .env
fi

if [ ! -f "vendor/autoload.php" ]; then
  composer install --no-interaction --prefer-dist
fi

php artisan key:generate --force >/dev/null 2>&1 || true
php artisan storage:link >/dev/null 2>&1 || true

exec php artisan serve --host=0.0.0.0 --port=8000
