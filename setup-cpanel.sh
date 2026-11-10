#!/bin/bash
# Foodness shared-host bootstrap (document root = public_html)
set -euo pipefail

ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"

echo "Foodness shared-host setup..."

if [ ! -d "$ROOT/laravel" ]; then
  echo "ERROR: laravel/ folder not found in $ROOT"
  exit 1
fi

# Replace a path with a symlink. If a real directory exists, move it aside first.
link_or_replace() {
  local target="$1"   # symlink path to create
  local source="$2"   # relative/absolute target of symlink

  if [ -L "$target" ]; then
    rm -f "$target"
  elif [ -d "$target" ]; then
    local bak="${target}.dir.bak.$(date +%Y%m%d%H%M%S)"
    echo "NOTE: '$target' is a directory — moving to '$bak' then creating symlink."
    mv "$target" "$bak"
  elif [ -e "$target" ]; then
    rm -f "$target"
  fi

  ln -sfn "$source" "$target"
  echo "OK: $target -> $source"
}

# Web-accessible uploads: /storage/...  =>  laravel/storage/app/public
mkdir -p laravel/storage/app/public
link_or_replace "storage" "laravel/storage/app/public"

# Also keep laravel/public/storage for artisan/tools that assume /public
mkdir -p laravel/public
link_or_replace "laravel/public/storage" "../storage/app/public"

# Theme CSS for document root
mkdir -p css
if [ -f laravel/public/css/foodness-theme.css ]; then
  cp -f laravel/public/css/foodness-theme.css css/foodness-theme.css
fi

# Protect app source from direct web access
printf '%s\n' 'Require all denied' > laravel/.htaccess

# Permissions
chmod -R 775 laravel/storage laravel/bootstrap/cache 2>/dev/null || true

# .env bootstrap
if [ ! -f laravel/.env ]; then
  if [ -f laravel/.env.production.example ]; then
    cp laravel/.env.production.example laravel/.env
  elif [ -f laravel/.env.example ]; then
    cp laravel/.env.example laravel/.env
  else
    echo "WARNING: no .env example found — create laravel/.env manually."
  fi
  if [ -f laravel/.env ]; then
    echo "Created laravel/.env — edit APP_URL and DB_* now."
  fi
fi
chmod 640 laravel/.env 2>/dev/null || true

cd laravel

php artisan key:generate --force 2>/dev/null || true
php artisan app:scrub-invalid-secrets --no-interaction 2>/dev/null || true
php artisan storage:link --force 2>/dev/null || true
php artisan migrate --force || true
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

cd "$ROOT"

# Re-assert docroot storage link after artisan storage:link (paths can differ)
link_or_replace "storage" "laravel/storage/app/public"

echo ""
echo "DONE."
echo "Check: ls -la storage  (should show symlink -> laravel/storage/app/public)"
echo "Then open: https://YOUR-DOMAIN/storage/  (or a known uploaded image URL)"
echo "Re-enter API keys in Admin if needed (AI / Courier / Payment / Mail)."
