<?php
// Senast uppdaterad: 2026-09-02 19:05 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);
namespace ChiangMaiAirWatch\Providers;

interface AirQualityProviderInterface
{
    public function getName(): string;
    /** @param list<array<string,mixed>> $stations @return list<array<string,mixed>> */
    public function fetchLatestMeasurements(array $stations): array;
    /** @param list<array<string,mixed>> $stations @return list<array<string,mixed>> */
    public function fetchHistory(array $stations, \DateTimeImmutable $from, \DateTimeImmutable $to): array;
}
