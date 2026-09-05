<?php
// Senast uppdaterad: 2026-09-02 19:05 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);
namespace ChiangMaiAirWatch\Providers;

final class MockAirForecastProvider implements AirForecastProviderInterface
{
    public function getName(): string { return 'mock_air_forecast'; }
    public function fetchForecast(array $zones): array { $runs = []; foreach ($zones as $zone) { $points = []; for ($hour = 0; $hour < 72; $hour++) $points[] = ['valid_at' => gmdate('Y-m-d H:00:00', time() + $hour * 3600), 'pm25_ug_m3' => 20 + $hour / 4, 'pm10_ug_m3' => 30 + $hour / 4, 'ozone_ug_m3' => null, 'no2_ug_m3' => null, 'so2_ug_m3' => null, 'co_ug_m3' => null, 'us_aqi' => null, 'us_aqi_pm25' => null, 'us_aqi_pm10' => null, 'source_status' => 'demo']; $runs[] = ['provider' => $this->getName(), 'zone_code' => $zone['code'], 'received_at' => gmdate('Y-m-d H:i:s'), 'model_time' => null, 'points' => $points, 'raw_payload' => ['demo' => true]]; } return $runs; }
}
