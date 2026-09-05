<?php
// Senast uppdaterad: 2026-09-02 19:05 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);
namespace ChiangMaiAirWatch\Providers;

final class MockAirQualityProvider implements AirQualityProviderInterface
{
    public function getName(): string { return 'mock_air'; }
    public function fetchLatestMeasurements(array $stations): array { return $this->fetchHistory($stations, new \DateTimeImmutable('-1 hour'), new \DateTimeImmutable()); }
    public function fetchHistory(array $stations, \DateTimeImmutable $from, \DateTimeImmutable $to): array { $rows = []; foreach ($stations as $index => $station) $rows[] = ['provider' => 'mock_air', 'provider_station_code' => $station['provider_station_code'], 'measured_at' => gmdate('Y-m-d H:00:00'), 'source_measured_at' => 'DEMO', 'source_aqi' => 51 + $index, 'source_aqi_scale' => 'TH_AQI_2023', 'source_aqi_pollutant' => 'PM25', 'pm25_ug_m3' => 26.0 + $index, 'pm10_ug_m3' => null, 'pm25_unit' => 'µg/m³', 'pm10_unit' => 'µg/m³', 'ozone_value' => null, 'ozone_unit' => 'ppb', 'carbon_monoxide_value' => null, 'carbon_monoxide_unit' => 'ppm', 'nitrogen_dioxide_value' => null, 'nitrogen_dioxide_unit' => 'ppb', 'sulphur_dioxide_value' => null, 'sulphur_dioxide_unit' => 'ppb', 'temperature_c' => null, 'humidity_pct' => null, 'source_status' => 'demo', 'raw_payload' => ['demo' => true]]; return $rows; }
}
