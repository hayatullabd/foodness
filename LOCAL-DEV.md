# Local development (Laravel standard)

Shared-hosting files stay at the **repo root** (`index.php`, `build/`, …).  
For local work, use the standard Laravel app in **`laravel/`**.

```bash
cd laravel
php artisan serve
```

Open: http://127.0.0.1:8000

- Document root: `laravel/public`
- Env: `laravel/.env` (local). Production copy: `laravel/.env.production.bak`
- Admin: `/SecretAdminPanel` (or `ADMIN_PREFIX` in `.env`)

If the DB is empty:

```bash
cd laravel
php artisan migrate --seed
```
