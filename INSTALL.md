# Foodness — Full Install Guide

PHP **8.3 বা তার উপরে** লাগে। Composer platform লক করা আছে `8.3.0` — লোকালে ৮.৫ থাকলেও সার্ভার ৮.৩-এ চলবে।

---

## 1) Local PC (Windows / Laragon / XAMPP)

### 1.1 Requirements
- PHP 8.3+ (`php -v`)
- Composer
- MySQL/MariaDB
- Node.js 18+

### 1.2 Steps

```bash
git clone https://github.com/hayatullabd/foodness.git
cd foodness

composer install
copy .env.example .env
php artisan key:generate
```

`.env` এডিট করুন:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_DATABASE=foodness
DB_USERNAME=root
DB_PASSWORD=
```

```bash
php artisan migrate --seed
npm install
npm run build
php artisan storage:link
php artisan serve
```

Browser: http://127.0.0.1:8000

Admin path ডিফল্ট: `/SecretAdminPanel` (`.env` এ `ADMIN_PREFIX` দিয়ে বদলানো যায়)

---

## 2) cPanel / Shared Hosting

### 2.1 PHP version
**MultiPHP Manager** → domain → **PHP 8.3** (বা 8.4)

Extensions ON: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `curl`, `gd`, `zip`

### 2.2 Database
1. cPanel → **MySQL Databases**
2. Create database + user + password
3. User → DB → **ALL PRIVILEGES**
4. phpMyAdmin → Import SQL dump (যদি থাকে) অথবা পরে `php artisan migrate --seed`

### 2.3 Upload code

**Option A — Git (যদি SSH/Terminal থাকে)**

```bash
cd ~
git clone https://github.com/hayatullabd/foodness.git web.foodness.com.bd
cd web.foodness.com.bd
composer install --no-dev --optimize-autoloader
```

**Option B — ZIP upload**
1. লোকালে `composer install --no-dev` চালিয়ে ZIP বানান (`vendor` সহ)
2. File Manager → subdomain ফোল্ডারে Extract

### 2.4 Document Root (গুরুত্বপূর্ণ)
Domains → আপনার ডোমেইন → Document Root:

```text
/home/USER/your-domain/public
```

উদাহরণ: `/home/foodness/web.foodness.com.bd/public`

### 2.5 `.env`

File Manager → **Show Hidden Files** → `.env.example` কপি → নাম `.env`

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_KEY=   # খালি রাখবেন না

DB_HOST=localhost
DB_DATABASE=cpanel_db_name
DB_USERNAME=cpanel_db_user
DB_PASSWORD=cpanel_db_password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_SECURE_COOKIE=true
```

Terminal:

```bash
cd ~/your-domain
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2.6 Permission

```bash
chmod -R 775 storage bootstrap/cache
chmod 640 .env
```

---

## 3) যদি সাইট 500 দেয় — CageFS / vendor অদৃশ্য

কিছু cPanel হোস্টে Document Root = `public` হলে ওয়েব PHP `public/`-এর **বাইরে** `vendor` দেখে না।

চেক (Terminal):

```bash
cd ~/your-domain/public
echo '<?php header("Content-Type:text/plain"); echo file_exists(__DIR__."/../vendor/autoload.php")?"YES":"NO";' > t.php
curl -s https://your-domain.com/t.php
rm -f t.php
```

`NO` হলে workaround:

```bash
cd ~/your-domain
mkdir -p public/laravel
cp -a app bootstrap config database resources routes vendor storage public/laravel/
cp -a .env artisan composer.json composer.lock public/laravel/

cat > public/index.php << 'EOF'
<?php
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
define('LARAVEL_START', microtime(true));
$base = __DIR__.'/laravel';
if (file_exists($maintenance = $base.'/storage/framework/maintenance.php')) {
    require $maintenance;
}
require $base.'/vendor/autoload.php';
/** @var Application $app */
$app = require_once $base.'/bootstrap/app.php';
$app->handleRequest(Request::capture());
EOF

printf '%s\n' 'Require all denied' > public/laravel/.htaccess
cd public/laravel && rm -rf public && ln -s .. public
rm -f ../storage && ln -s laravel/storage/app/public ../storage

php artisan optimize:clear
php artisan config:cache
```

এর পর artisan:

```bash
cd ~/your-domain/public/laravel
php artisan ...
```

Security check:

```bash
curl -s -o /dev/null -w '%{http_code}\n' https://your-domain.com/
curl -s -o /dev/null -w '%{http_code}\n' https://your-domain.com/laravel/.env
# expect: 200 and 403
```

---

## 4) Composer PHP platform (লোকাল ডেভ)

এই প্রজেক্টে `composer.json` → `config.platform.php = 8.3.0`

মানে লোকাল PHP ৮.৫-এ `composer install` চালালেও `vendor/composer/platform_check.php` **৮.৩+** চাইবে — cPanel ৮.৩-এ আগের মতো `>= 8.5.12` এরর হবে না।

নতুন মেশিনে:

```bash
composer install
# বা platform রিফ্রেশ:
composer dump-autoload
```

---

## 5) Production security checklist

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] Strong `APP_KEY` (`php artisan key:generate`)
- [ ] `.env` permission `640`
- [ ] Document Root = `public` only
- [ ] If using `public/laravel/` → `.htaccess` = `Require all denied`
- [ ] Delete `t.php`, `boot.php`, `cpanel-check.php`
- [ ] HTTPS on + `SESSION_SECURE_COOKIE=true`

---

## 6) Common errors

| Error | Fix |
|-------|-----|
| `MissingAppKeyException` | `.env` এ `APP_KEY` সেট / `php artisan key:generate` |
| `Access denied for user` | cPanel DB user/password/privileges |
| `PHP >= 8.5.12` platform check | PHP 8.3+ + এই রেপোর `composer.json` platform lock; `composer dump-autoload` |
| `autoload: NO` / vendor missing | Section 3 CageFS workaround |
| `Vite manifest not found` | `npm run build` অথবা `public/laravel/public` → symlink to web `public` |

---

## Support paths

- Repo: https://github.com/hayatullabd/foodness
- Live example structure: Document Root → `.../public`
