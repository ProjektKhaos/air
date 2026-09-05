<?php
// Senast uppdaterad: 2026-09-03 13:00 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);
namespace ChiangMaiAirWatch\Providers;

interface WeatherProviderInterface
{
    public function getName():string;
    /** @param array<string,mixed> $zone @return array<string,mixed> */
    public function fetchCurrent(array $zone):array;
}
