<?php
// Senast uppdaterad: 2026-09-02 21:05 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

namespace ChiangMaiAirWatch\Services;

use GuzzleHttp\Client;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use ChiangMaiAirWatch\Config;

final class WebPushSender implements PushSenderInterface
{
    private WebPush $webPush;

    public function __construct(?WebPush $webPush = null)
    {
        $this->webPush = $webPush ?? new WebPush([
            'VAPID' => [
                'subject' => (string) Config::get('push.subject'),
                'publicKey' => (string) Config::get('push.public_key'),
                'privateKey' => (string) Config::get('push.private_key'),
            ],
        ], [], new Client(['timeout' => 15, 'connect_timeout' => 5]));
    }

    public function send(array $subscription, string $payload, array $options): PushDeliveryResult
    {
        try {
            $target = Subscription::create([
                'endpoint' => (string) $subscription['endpoint'],
                'keys' => ['p256dh' => (string) $subscription['p256dh'], 'auth' => (string) $subscription['auth']],
                'contentEncoding' => (string) ($subscription['content_encoding'] ?? 'aes128gcm'),
            ]);
            $report = $this->webPush->sendOneNotification($target, $payload, $options);
            $httpStatus = $report->getResponse()?->getStatusCode();
            if ($report->isSuccess()) {
                return new PushDeliveryResult(true, false, $httpStatus);
            }
            $permanent = $report->isSubscriptionExpired() || in_array($httpStatus, [404, 410], true);
            return new PushDeliveryResult(false, $permanent, $httpStatus, $permanent ? 'PUSH_SUBSCRIPTION_EXPIRED' : 'PUSH_PROVIDER_REJECTED');
        } catch (\Throwable) {
            return new PushDeliveryResult(false, false, null, 'PUSH_TRANSPORT_FAILED');
        }
    }
}
