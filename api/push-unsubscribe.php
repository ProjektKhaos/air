<?php
// Senast uppdaterad: 2026-09-02 21:05 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

use ChiangMaiAirWatch\Api;
use ChiangMaiAirWatch\Config;
use ChiangMaiAirWatch\Database;
use ChiangMaiAirWatch\Repositories\PushSubscriptionRepository;
use ChiangMaiAirWatch\Services\PushSubscriptionValidator;
use ChiangMaiAirWatch\Services\RateLimiter;

require __DIR__ . '/_bootstrap.php';

api_run(function (): void {
    Api::requirePostJsonSameOrigin();
    if (!(bool) Config::get('push.enabled', false)) {
        Api::error('PUSH_DISABLED', t('error.api.push_disabled'), 503);
    }
    $db = Database::connection();
    $limit = (new RateLimiter($db))->consume(
        'push-unsubscribe', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
        (int) Config::get('security.push_unsubscribe_limit', 20),
        (int) Config::get('security.push_rate_window_seconds', 600)
    );
    if (!$limit['allowed']) {
        header('Retry-After: ' . $limit['retry_after']);
        Api::error('RATE_LIMITED', t('error.api.rate_limited'), 429);
    }
    try {
        $endpoint = (new PushSubscriptionValidator())->unsubscribe(Api::jsonBody());
    } catch (InvalidArgumentException) {
        Api::error('INVALID_SUBSCRIPTION', t('error.api.invalid_subscription'));
    }
    (new PushSubscriptionRepository($db))->disableByEndpoint($endpoint);
    Api::success(['subscribed' => false]);
});
