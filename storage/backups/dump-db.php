<?php

declare(strict_types=1);

/**
 * One-off production DB dump helper.
 * Usage: php storage/backups/dump-db.php
 */

$root = dirname(__DIR__, 2);
$envFile = $root.DIRECTORY_SEPARATOR.'.env';
if (! is_file($envFile)) {
    fwrite(STDERR, ".env not found\n");
    exit(1);
}

$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
        continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $value = trim($value);
    if (
        (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
        (str_starts_with($value, "'") && str_ends_with($value, "'"))
    ) {
        $value = substr($value, 1, -1);
    }
    $env[trim($key)] = $value;
}

$host = $env['DB_HOST'] ?? '127.0.0.1';
$port = $env['DB_PORT'] ?? '3306';
$db = $env['DB_DATABASE'] ?? '';
$user = $env['DB_USERNAME'] ?? '';
$pass = $env['DB_PASSWORD'] ?? '';

$outDir = 'D:'.DIRECTORY_SEPARATOR.'Coding'.DIRECTORY_SEPARATOR.'Laravel WEB'.DIRECTORY_SEPARATOR.'Foodness-delivery-2026-11-07';
if (! is_dir($outDir) && ! mkdir($outDir, 0775, true) && ! is_dir($outDir)) {
    fwrite(STDERR, "Cannot create output dir: {$outDir}\n");
    exit(1);
}

$out = $outDir.DIRECTORY_SEPARATOR.'foodness.sql';

$pdo = new PDO(
    "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
    $user,
    $pass,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    ]
);
$pdo->exec('SET NAMES utf8mb4');

$fh = fopen($out, 'wb');
if ($fh === false) {
    fwrite(STDERR, "Cannot write {$out}\n");
    exit(1);
}

$write = static function (string $s) use ($fh): void {
    fwrite($fh, $s);
};

$qt = static function (string $name): string {
    return '`'.str_replace('`', '``', $name).'`';
};

$write("-- Foodness MySQL dump\n");
$write('-- Generated: '.date('c')."\n");
$write("-- Database: {$qt($db)}\n\n");
$write("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\nSET time_zone = '+00:00';\n\n");

$tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
$views = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'")->fetchAll(PDO::FETCH_NUM);

foreach ($tables as $row) {
    $table = (string) $row[0];
    $write("\n-- ----------------------------\n-- Table structure for {$qt($table)}\n-- ----------------------------\n");
    $write("DROP TABLE IF EXISTS {$qt($table)};\n");
    $create = $pdo->query('SHOW CREATE TABLE '.$qt($table))->fetch(PDO::FETCH_ASSOC);
    $write($create['Create Table'].";\n\n");
    $write("-- Dumping data for {$qt($table)}\n");

    $stmt = $pdo->query('SELECT * FROM '.$qt($table));
    $count = 0;
    while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cols = array_map(static fn ($c) => $qt((string) $c), array_keys($data));
        $vals = array_map(static function ($v) use ($pdo) {
            if ($v === null) {
                return 'NULL';
            }

            return $pdo->quote((string) $v);
        }, array_values($data));
        $write('INSERT INTO '.$qt($table).' ('.implode(',', $cols).') VALUES ('.implode(',', $vals).");\n");
        $count++;
    }
    $write("-- {$count} rows\n");
}

foreach ($views as $row) {
    $view = (string) $row[0];
    $write("\n-- ----------------------------\n-- View structure for {$qt($view)}\n-- ----------------------------\n");
    $write("DROP VIEW IF EXISTS {$qt($view)};\n");
    $create = $pdo->query('SHOW CREATE VIEW '.$qt($view))->fetch(PDO::FETCH_ASSOC);
    $write(($create['Create View'] ?? '').";\n\n");
}

$write("\nSET FOREIGN_KEY_CHECKS=1;\n");
fclose($fh);

echo 'OK tables='.count($tables).' views='.count($views).' file='.$out.' size='.filesize($out).PHP_EOL;
