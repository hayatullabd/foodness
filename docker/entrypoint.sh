#!/bin/sh
set -e

# Create storage subdirectories if missing
mkdir -p /var/www/storage/framework/cache/data \
         /var/www/storage/framework/sessions \
         /var/www/storage/framework/views \
         /var/www/storage/logs \
         /var/www/bootstrap/cache

# Ensure proper write permissions for storage and bootstrap/cache
chmod -R 777 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

# Check database connection
if [ -n "$DB_HOST" ] && [ "$DB_CONNECTION" = "mysql" ]; then
    echo "==> Waiting for MySQL ($DB_HOST)..."
    max_retries=30
    count=0
    until php -r "try { new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: '3306') . ';dbname=' . getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); exit(0); } catch (Exception \$e) { exit(1); }" 2>/dev/null; do
        count=$((count+1))
        if [ $count -gt $max_retries ]; then
            echo "==> Database connection timeout, continuing..."
            break
        fi
        sleep 2
    done
    echo "==> Database connection verified!"
fi

# Ensure storage symlink exists
if [ ! -L /var/www/public/storage ]; then
    echo "==> Creating storage symlink..."
    php /var/www/artisan storage:link 2>/dev/null || true
fi

exec "$@"
