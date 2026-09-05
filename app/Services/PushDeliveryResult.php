<?php
// Senast uppdaterad: 2026-09-02 21:05 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

namespace ChiangMaiAirWatch\Services;

final class PushDeliveryResult
{
    public function __construct(
        public readonly bool $success,
        public readonly bool $permanentFailure,
        public readonly ?int $httpStatus = null,
        public readonly string $errorCode = 'OK'
    ) {
    }
}
