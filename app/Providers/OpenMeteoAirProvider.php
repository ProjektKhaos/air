<?php
// Senast uppdaterad: 2026-09-02 19:05 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);
namespace ChiangMaiAirWatch\Providers;

use ChiangMaiAirWatch\Config;
use ChiangMaiAirWatch\HttpClient;
use DateTimeImmutable;
use DateTimeZone;

final class OpenMeteoAirProvider implements AirForecastProviderInterface
{
    public function __construct(private readonly HttpClient $http = new HttpClient()) {}
    public function getName(): string { return 'openmeteo_air'; }
    public function fetchForecast(array $zones): array
    {
        $config = Config::get('providers.openmeteo_air', []); $runs = [];
        foreach ($zones as $zone) {
            $query = http_build_query(['latitude' => $zone['latitude'], 'longitude' => $zone['longitude'], 'hourly' => 'pm2_5,pm10,ozone,nitrogen_dioxide,sulphur_dioxide,carbon_monoxide,us_aqi,us_aqi_pm2_5,us_aqi_pm10', 'timezone' => 'Asia/Bangkok', 'forecast_days' => 3], '', '&', PHP_QUERY_RFC3986);
            $json = $this->http->getJson((string) $config['url'] . '?' . $query, (int) $config['connect_timeout'], (int) $config['timeout'], (int) $config['max_bytes']);
            if (($json['timezone'] ?? null) !== 'Asia/Bangkok' || !is_array($json['hourly'] ?? null) || !is_array($json['hourly_units'] ?? null)) throw new ProviderException('Open-Meteo forecast schema is invalid', 'INVALID_SCHEMA');
            foreach (['pm2_5','pm10','ozone','nitrogen_dioxide','sulphur_dioxide','carbon_monoxide'] as $field) if (($json['hourly_units'][$field] ?? null) !== 'μg/m³') throw new ProviderException('Open-Meteo pollutant unit is invalid', 'INVALID_UNIT');
            $times = $json['hourly']['time'] ?? null;
            if (!is_array($times)) throw new ProviderException('Open-Meteo forecast times are missing', 'INVALID_SCHEMA');
            $points = [];
            foreach ($times as $index => $localTime) {
                if (!is_string($localTime)) throw new ProviderException('Open-Meteo forecast time is invalid', 'INVALID_TIMESTAMP');
                $time = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $localTime, new DateTimeZone('Asia/Bangkok'));
                if (!$time) throw new ProviderException('Open-Meteo forecast time is invalid', 'INVALID_TIMESTAMP');
                $points[] = ['valid_at' => $time->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'), 'pm25_ug_m3' => $this->number($json['hourly']['pm2_5'][$index] ?? null, 2000), 'pm10_ug_m3' => $this->number($json['hourly']['pm10'][$index] ?? null, 3000), 'ozone_ug_m3' => $this->number($json['hourly']['ozone'][$index] ?? null, 5000), 'no2_ug_m3' => $this->number($json['hourly']['nitrogen_dioxide'][$index] ?? null, 5000), 'so2_ug_m3' => $this->number($json['hourly']['sulphur_dioxide'][$index] ?? null, 5000), 'co_ug_m3' => $this->number($json['hourly']['carbon_monoxide'][$index] ?? null, 100000), 'us_aqi' => $this->number($json['hourly']['us_aqi'][$index] ?? null, 1000), 'us_aqi_pm25' => $this->number($json['hourly']['us_aqi_pm2_5'][$index] ?? null, 1000), 'us_aqi_pm10' => $this->number($json['hourly']['us_aqi_pm10'][$index] ?? null, 1000), 'source_status' => 'model'];
            }
            $runs[] = ['provider' => 'openmeteo_air', 'zone_code' => $zone['code'], 'received_at' => gmdate('Y-m-d H:i:s'), 'model_time' => null, 'points' => $points, 'raw_payload' => $json];
        }
        return $runs;
    }
    private function number(mixed $value, float $max): ?float { if ($value === null) return null; if (!is_numeric($value) || (float) $value < 0 || (float) $value > $max) throw new ProviderException('Open-Meteo value is outside the accepted range', 'VALUE_RANGE'); return (float) $value; }
}
