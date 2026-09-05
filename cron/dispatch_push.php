<?php
// Senast uppdaterad: 2026-09-02 21:05 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

use ChiangMaiAirWatch\Config;
use ChiangMaiAirWatch\Database;
use ChiangMaiAirWatch\Logger;
use ChiangMaiAirWatch\Services\CollectorLock;
use ChiangMaiAirWatch\Services\NotificationDispatcher;
use ChiangMaiAirWatch\Services\WebPushSender;

require dirname(__DIR__) . '/app/bootstrap.php';

if (!(bool) Config::get('push.enabled', false)) {
    fwrite(STDOUT, "Push delivery is disabled.\n");
    exit(0);
}

$lock = new CollectorLock();
if (!$lock->acquire('push-dispatcher')) {
    fwrite(STDERR, "Push dispatcher is already running.\n");
    exit(75);
}

try {
    $result = (new NotificationDispatcher(Database::connection(), new WebPushSender()))->dispatch();
    Logger::info('push_dispatch_complete', $result);
    fwrite(STDOUT, sprintf(
        "Push dispatch complete: %d jobs, %d delivered, %d failed, %d superseded, %d expired.\n",
        $result['processed'], $result['delivered'], $result['failed'], $result['superseded'], $result['expired']
    ));
} catch (Throwable $error) {
    Logger::error('push_dispatch_failed', ['error_code' => 'PUSH_DISPATCH_FAILED', 'message' => $error->getMessage()]);
    fwrite(STDERR, "PUSH_DISPATCH_FAILED\n");
    exit(1);
}
