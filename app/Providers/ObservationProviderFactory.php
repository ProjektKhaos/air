<?php
// Senast uppdaterad: 2026-09-03 12:50 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

namespace ChiangMaiAirWatch\Providers;

final class ObservationProviderFactory
{
    public function create(string $name): AirQualityProviderInterface
    {
        return match ($name) {
            'air4thai' => new Air4ThaiProvider(),
            'dustboy' => new DustBoyProvider(),
            'mock', 'mock_air' => new MockAirQualityProvider(),
            default => throw new ProviderException('Unknown observation provider', 'UNKNOWN_PROVIDER'),
        };
    }
}
