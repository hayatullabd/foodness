#!/bin/bash
set -e
ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"
echo "Foodness shared-host setup v1.0.20..."
rm -f storage
ln -sfn laravel/storage/app/public storage
mkdir -p laravel/public
rm -f laravel/public/storage
ln -sfn ../storage/app/public laravel/public/storage
mkdir -p css
cp -f laravel/public/css/foodness-theme.css css/foodness-theme.css 2>/dev/null || true
chmod -R 775 laravel/storage laravel/bootstrap/cache 2>/dev/null || true
printf "Require all denied\n" > laravel/.htaccess
if [ ! -f laravel/.env ]; then
  if [ -f laravel/.env.production.example ]; then
    cp laravel/.env.production.example laravel/.env
  else
    cp laravel/.env.example laravel/.env
  fi
  echo "Created laravel/.env ? edit APP_URL and DB_* now."
fi
chmod 640 laravel/.env 2>/dev/null || true
cd laravel
php artisan key:generate --force
php artisan app:scrub-invalid-secrets --no-interaction || true
php artisan migrate --force || true
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
cd ..
echo "DONE ? re-enter API keys in Admin (AI / Courier / Payment / Mail)."
