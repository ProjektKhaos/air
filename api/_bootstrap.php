<?php
// Senast uppdaterad: 2026-09-02 21:05 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

use ChiangMaiAirWatch\Api;
use ChiangMaiAirWatch\Logger;

require dirname(__DIR__) . '/app/bootstrap.php';

/** @param callable():void $callback */
function api_run(callable $callback): void
{
    try {
        $callback();
    } catch (Throwable $error) {
        Logger::error('api_request_failed', ['path' => $_SERVER['REQUEST_URI'] ?? '', 'message' => $error->getMessage()]);
        Api::error('INTERNAL_ERROR', t('error.api.internal'), 500);
    }
}
