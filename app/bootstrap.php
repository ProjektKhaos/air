<?php
// Senast uppdaterad: 2026-09-02 21:05 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$autoload = APP_ROOT . '/vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'ChiangMaiAirWatch\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file = APP_ROOT . '/app/' . $relative . '.php';
        if (is_file($file)) {
            require $file;
        }
    });
}

require_once APP_ROOT . '/app/helpers.php';

ChiangMaiAirWatch\Config::load(APP_ROOT);

