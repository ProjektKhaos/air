<?php
// Senast uppdaterad: 2026-09-02 21:05 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

namespace ChiangMaiAirWatch\Providers;

final class ProviderException extends \RuntimeException
{
    public function __construct(string $message, public readonly string $providerCode = 'PROVIDER_UNAVAILABLE')
    {
        parent::__construct($message);
    }
}
