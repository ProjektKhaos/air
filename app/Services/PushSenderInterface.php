<?php
// Senast uppdaterad: 2026-09-02 21:05 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

namespace ChiangMaiAirWatch\Services;

interface PushSenderInterface
{
    /** @param array<string,mixed> $subscription @param array<string,mixed> $options */
    public function send(array $subscription, string $payload, array $options): PushDeliveryResult;
}
