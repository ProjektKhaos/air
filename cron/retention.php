<?php
// Senast uppdaterad: 2026-09-05 14:05 Asia/Bangkok | Version 1.11 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

use ChiangMaiAirWatch\Database;
use ChiangMaiAirWatch\Logger;
use ChiangMaiAirWatch\Services\CollectorLock;
use ChiangMaiAirWatch\Services\RetentionService;

require dirname(__DIR__) . '/app/bootstrap.php';
$lock = new CollectorLock();
if (!$lock->acquire('retention')) {
    exit(75);
}
try {
    $db = Database::connection();
    $deleted=(new RetentionService($db))->purge();
    Logger::info('retention_complete',$deleted);
    fwrite(STDOUT, "Retention complete.\n");
} catch (Throwable $error) {
    Logger::error('retention_failed', ['message' => $error->getMessage()]);
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
