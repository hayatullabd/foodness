# Foodness / Foodness Docker Development Guide

এই প্রোজেক্টটি Docker এর মাধ্যমে লোকাল ডেভেলপমেন্টের জন্য সম্পূর্ণ প্রস্তুত করা হয়েছে।

---

## 🚀 সার্ভিসসমূহ ও পোর্ট (Services & Ports)

| Service | Container Name | Host URL / Port | বিবরণ |
|---|---|---|---|
| **Web Server (Nginx)** | `foodness_webserver` | `http://localhost:8000` | মূল ওয়েব অ্যাপ্লিকেশন |
| **PHP App (PHP 8.5-FPM)** | `foodness_app` | Port `9000` (internal), `5173` (Vite) | Laravel ব্যাকএন্ড ও ফাইল প্রসেসিং |
| **Database (MySQL 8.4)** | `foodness_db` | `localhost:3306` | ডেটাবেজ (`foodness.sql` স্বয়ংক্রিয়ভাবে ইম্পোর্ট হবে) |

---

## 🔑 ডেটাবেজ ক্রেডেনশিয়ালস (Database Credentials)

- **Database Name:** `foodness`
- **Username:** `hayatulla`
- **Password:** `@Nipa2005`
- **Root Password:** `root`
- **Host (Inside Docker container):** `db`
- **Host (From your PC / DBeaver / HeidiSQL / Navicat):** `127.0.0.1` বা `localhost`
- **Port:** `3306`

> **নোট:** কন্টেইনার প্রথমবার চালুর সময় `./foodness.sql` ফাইলটি স্বয়ংক্রিয়ভাবে `foodness` ডেটাবেজে ইম্পোর্ট হয়ে যাবে।

---

## 🛠️ প্রয়োজনীয় কমান্ডসমূহ (Commands)

### ১. ডকার চালু ও বন্ধ করা (Start / Stop)
```bash
# ব্যাকগ্রাউন্ডে কন্টেইনার চালু করা
docker compose up -d

# স্ট্যাটাস চেক করা
docker compose ps

# কন্টেইনার বন্ধ করা
docker compose down

# লগ দেখা
docker compose logs -f
```

---

### ২. ব্যাকএন্ড (Laravel / Artisan / Composer)
কন্টেইনারের ভেতর যেকোনো Laravel কমান্ড চালাতে `docker compose exec app` ব্যবহার করুন:

```bash
# Artisan কমান্ড চালানো
docker compose exec app php artisan migrate
docker compose exec app php artisan route:list
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan tinker

# Composer কমান্ড চালানো
docker compose exec app composer install
docker compose exec app composer update
```

---

### ৩. ফ্রন্টএন্ড (Frontend / Vite / Tailwind CSS)
ফ্রন্টএন্ডের কাজ দুইভাবে করতে পারেন:

#### অপশন ক: ডকারের ভেতর Vite চালানো
```bash
# প্যাকেজ ইন্সটল করা
docker compose exec app npm install

# হট-রিলোড ডেভেলপমেন্ট সার্ভার চালু রাখা
docker compose exec app npm run dev

# প্রোডাকশন বিল্ড তৈরি করা
docker compose exec app npm run build
```

#### অপশন খ: সরাসরি আপনার কম্পিউটারে (Windows Host) চালানো
যেহেতু আপনার পিসিতে Node.js এবং npm অলরেডি ইন্সটল করা আছে:
```bash
npm install
npm run dev
```

---

## 🔄 ডেটাবেজ রিসেট করতে চাইলে (Fresh DB Reset)
যদি কখনো ডেটাবেজ সম্পূর্ণ ডিলিট করে আবার `foodness.sql` থেকে ফ্রেশ ইম্পোর্ট করতে চান:
```bash
docker compose down -v
docker compose up -d
```
