<?php

/**
 * Temporary cPanel diagnostic. Delete this file after fixing the 500 error.
 * Open: https://web.foodness.com.bd/cpanel-check.php
 */

header('Content-Type: text/plain; charset=utf-8');

echo "Foodness cPanel check\n";
echo "=====================\n";
echo 'PHP: '.PHP_VERSION."\n";
echo 'SAPI: '.PHP_SAPI."\n";
echo 'Document root: '.($_SERVER['DOCUMENT_ROOT'] ?? '-')."\n";
echo 'Script: '.__FILE__."\n\n";

$root = dirname(__DIR__);
echo 'App root: '.$root."\n";

$checks = [
    'vendor/autoload.php' => $root.'/vendor/autoload.php',
    'bootstrap/app.php' => $root.'/bootstrap/app.php',
    '.env' => $root.'/.env',
    'storage writable' => $root.'/storage',
    'bootstrap/cache writable' => $root.'/bootstrap/cache',
    'public/index.php' => $root.'/public/index.php',
];

foreach ($checks as $label => $path) {
    if (str_contains($label, 'writable')) {
        echo $label.': '.(is_dir($path) && is_writable($path) ? 'OK' : 'FAIL')." ({$path})\n";
    } else {
        echo $label.': '.(is_file($path) ? 'OK' : 'MISSING')."\n";
    }
}

echo "\n--- .env keys ---\n";
$envPath = $root.'/.env';
if (! is_file($envPath)) {
    echo "FAIL: .env missing. Copy .env.production.example to .env\n";
    exit;
}

$env = file_get_contents($envPath);
foreach (['APP_KEY', 'APP_URL', 'APP_DEBUG', 'APP_ENV', 'DB_DATABASE', 'DB_USERNAME', 'DB_HOST'] as $key) {
    if (preg_match('/^'.$key.'=(.*)$/m', $env, $m)) {
        $val = trim($m[1], " \t\"'");
        if ($key === 'APP_KEY') {
            echo $key.': '.(strlen($val) > 10 ? 'SET' : 'EMPTY')."\n";
        } elseif (str_starts_with($key, 'DB_') && $key !== 'DB_HOST') {
            echo $key.': '.(strlen($val) ? 'SET' : 'EMPTY')."\n";
        } else {
            echo $key.': '.$val."\n";
        }
    } else {
        echo $key.": MISSING\n";
    }
}

echo "\n--- Laravel boot ---\n";
try {
    require $root.'/vendor/autoload.php';
    echo "autoload: OK\n";
    $app = require $root.'/bootstrap/app.php';
    echo "bootstrap: OK\n";
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    echo "kernel: OK\n";
    echo 'app.name: '.config('app.name')."\n";
    echo 'app.url: '.config('app.url')."\n";

    try {
        Illuminate\Support\Facades\DB::connection()->getPdo();
        echo "database: OK\n";
    } catch (Throwable $e) {
        echo 'database: FAIL — '.$e->getMessage()."\n";
    }
} catch (Throwable $e) {
    echo 'BOOT FAIL: '.$e->getMessage()."\n";
    echo $e->getFile().':'.$e->getLine()."\n";
}

echo "\nDelete this file after debugging.\n";
