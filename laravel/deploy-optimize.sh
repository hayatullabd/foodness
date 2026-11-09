#!/usr/bin/env bash
set -e

echo "Starting Foodness Production Deployment Optimization..."

# Ensure storage and bootstrap/cache permissions are healthy
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Run database migrations safely
echo "==> Running database migrations..."
php artisan migrate --force --no-interaction

# Clear old compiled caches
echo "==> Clearing old application caches..."
php artisan optimize:clear --no-interaction

# Drop secrets encrypted under a previous APP_KEY (safe no-op when clean)
echo "==> Scrubbing undecryptable secrets..."
php artisan app:scrub-invalid-secrets --no-interaction || true

# Compile and cache framework artifacts
echo "==> Caching configurations..."
php artisan config:cache --no-interaction

echo "==> Caching routes..."
php artisan route:cache --no-interaction

echo "==> Caching views..."
php artisan view:cache --no-interaction

echo "==> Caching events..."
php artisan event:cache --no-interaction

echo "==> Running framework optimizer..."
php artisan optimize --no-interaction

# Gracefully restart queue workers to load updated codebase
echo "==> Restarting background queue workers..."
php artisan queue:restart --no-interaction || true

echo "Foodness deployment optimization completed successfully!"
