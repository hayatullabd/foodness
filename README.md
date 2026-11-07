# Foodness

Laravel 13 eCommerce (storefront + admin) for Foodness.

**Requirements:** PHP **8.3+**, MySQL/MariaDB, Composer, Node.js 18+ (for asset build)

## Quick start (local)

```bash
git clone https://github.com/hayatullabd/foodness.git
cd foodness
composer install
copy .env.example .env   # Windows
php artisan key:generate
# Edit .env → DB_* credentials
php artisan migrate --seed
npm install
npm run build
php artisan storage:link
php artisan serve
```

Open: http://127.0.0.1:8000

## Full install guide

See **[INSTALL.md](INSTALL.md)** for:

- Local PC setup (PHP 8.3+)
- cPanel / shared hosting deploy
- Database, `.env`, Document Root
- CageFS / `vendor not found` workaround
- Security checklist

## Stack

- Laravel 13 / PHP 8.3+
- MySQL (session, cache, queue — no Redis required)
- Vite + Tailwind
- Spatie Permission, Socialite

## License

MIT
