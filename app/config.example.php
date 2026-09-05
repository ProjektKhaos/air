<?php
// Senast uppdaterad: 2026-09-02 21:05 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

return [
    'app' => [
        'base_url' => '/',
        'public_origin' => 'https://air.aberg.online',
        'environment' => 'production',
        'debug' => false,
    ],
    'db' => [
        'dsn' => 'mysql:host=127.0.0.1;dbname=chiang_mai_air_watch;charset=utf8mb4',
        'username' => 'cmaw_runtime',
        'password' => 'replace-with-a-local-secret',
    ],
    'push' => [
        'enabled' => false,
        'subject' => 'https://air.aberg.online/',
        'public_key' => 'replace-with-vapid-public-key',
        'private_key' => 'replace-with-vapid-private-key',
    ],
    'security' => [
        'rate_limit_key' => 'replace-with-a-random-local-secret',
    ],
];
