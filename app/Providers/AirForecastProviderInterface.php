<?php
// Senast uppdaterad: 2026-09-02 19:05 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);
namespace ChiangMaiAirWatch\Providers;

interface AirForecastProviderInterface
{
    public function getName(): string;
    /** @param list<array<string,mixed>> $zones @return list<array<string,mixed>> */
    public function fetchForecast(array $zones): array;
}
