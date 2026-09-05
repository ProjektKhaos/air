<?php
// Senast uppdaterad: 2026-09-02 19:05 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);
namespace ChiangMaiAirWatch\Providers;

use ChiangMaiAirWatch\Config;
use ChiangMaiAirWatch\HttpClient;
use DateTimeImmutable;
use DateTimeZone;

final class Air4ThaiProvider implements AirQualityProviderInterface
{
    public function __construct(private readonly HttpClient $http = new HttpClient()) {}
    public function getName(): string { return 'air4thai'; }

    public function fetchLatestMeasurements(array $stations): array
    {
        $config = Config::get('providers.air4thai', []);
        $json = $this->http->getJson((string) $config['current_url'], (int) $config['connect_timeout'], (int) $config['timeout'], (int) $config['max_bytes'], [], (string) ($config['ca_bundle'] ?? ''));
        if (!isset($json['stations']) || !is_array($json['stations'])) throw new ProviderException('Air4Thai stations are missing', 'INVALID_SCHEMA');
        $expected = array_fill_keys(array_map(static fn(array $station): string => (string) $station['provider_station_code'], $stations), true);
        $result = [];
        foreach ($json['stations'] as $row) {
            if (!is_array($row) || !isset($expected[(string) ($row['stationID'] ?? '')])) continue;
            $result[] = $this->normalizeLatest($row);
        }
        if (count($result) !== count($expected)) throw new ProviderException('One or more configured Air4Thai stations are missing', 'STATION_MISSING');
        return $result;
    }

    public function fetchHistory(array $stations, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $config = Config::get('providers.air4thai', []);
        $codes = array_map(static fn(array $station): string => (string) $station['provider_station_code'], $stations);
        $query = http_build_query(['stationID' => implode(',', $codes), 'param' => 'PM25,PM10,O3,CO,NO2,SO2', 'type' => 'hr', 'sdate' => $from->setTimezone(new DateTimeZone('Asia/Bangkok'))->format('Y-m-d'), 'edate' => $to->setTimezone(new DateTimeZone('Asia/Bangkok'))->format('Y-m-d'), 'stime' => '00', 'etime' => '23'], '', '&', PHP_QUERY_RFC3986);
        $json = $this->http->getJson((string) $config['history_url'] . '?' . $query, (int) $config['connect_timeout'], max(30, (int) $config['timeout']), 4_000_000, [], (string) ($config['ca_bundle'] ?? ''));
        if (($json['result'] ?? null) !== 'OK' || !isset($json['stations']) || !is_array($json['stations'])) throw new ProviderException('Air4Thai history schema is invalid', 'INVALID_SCHEMA');
        $result = [];
        foreach ($json['stations'] as $station) {
            if (!is_array($station) || !in_array((string) ($station['stationID'] ?? ''), $codes, true) || !is_array($station['data'] ?? null)) continue;
            foreach ($station['data'] as $row) if (is_array($row)) $result[] = $this->normalizeHistory((string) $station['stationID'], $row);
        }
        return $result;
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private function normalizeLatest(array $row): array
    {
        $last = $row['AQILast'] ?? null;
        if (!is_array($last) || !is_string($last['date'] ?? null) || !is_string($last['time'] ?? null)) throw new ProviderException('Air4Thai latest record is invalid', 'INVALID_SCHEMA');
        $sourceTime = trim($last['date'] . ' ' . $last['time']);
        $time = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $sourceTime, new DateTimeZone('Asia/Bangkok'));
        if (!$time) throw new ProviderException('Air4Thai timestamp is invalid', 'INVALID_TIMESTAMP');
        $aqiNode = is_array($last['AQI'] ?? null) ? $last['AQI'] : [];
        return $this->record((string) $row['stationID'], $time, $sourceTime, [
            'source_aqi' => $this->number($aqiNode['aqi'] ?? null, 0, 1000, true),
            'source_aqi_pollutant' => is_string($aqiNode['param'] ?? null) && $aqiNode['param'] !== '' ? (string) $aqiNode['param'] : null,
            'pm25_ug_m3' => $this->pollutant($last, 'PM25', 2000), 'pm10_ug_m3' => $this->pollutant($last, 'PM10', 3000),
            'ozone_value' => $this->pollutant($last, 'O3', 5000), 'carbon_monoxide_value' => $this->pollutant($last, 'CO', 100),
            'nitrogen_dioxide_value' => $this->pollutant($last, 'NO2', 5000), 'sulphur_dioxide_value' => $this->pollutant($last, 'SO2', 5000),
        ], $row);
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private function normalizeHistory(string $code, array $row): array
    {
        $sourceTime = (string) ($row['DATETIMEDATA'] ?? '');
        $time = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $sourceTime, new DateTimeZone('Asia/Bangkok'));
        if (!$time) throw new ProviderException('Air4Thai history timestamp is invalid', 'INVALID_TIMESTAMP');
        return $this->record($code, $time, $sourceTime, [
            'source_aqi' => null, 'source_aqi_pollutant' => null,
            'pm25_ug_m3' => $this->number($row['PM25'] ?? null, 0, 2000), 'pm10_ug_m3' => $this->number($row['PM10'] ?? null, 0, 3000),
            'ozone_value' => $this->number($row['O3'] ?? null, 0, 5000), 'carbon_monoxide_value' => $this->number($row['CO'] ?? null, 0, 100),
            'nitrogen_dioxide_value' => $this->number($row['NO2'] ?? null, 0, 5000), 'sulphur_dioxide_value' => $this->number($row['SO2'] ?? null, 0, 5000),
        ], $row);
    }

    /** @param array<string,mixed> $values @param array<string,mixed> $raw @return array<string,mixed> */
    private function record(string $code, DateTimeImmutable $time, string $sourceTime, array $values, array $raw): array
    {
        return array_merge(['provider' => 'air4thai', 'provider_station_code' => $code, 'measured_at' => $time->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'), 'source_measured_at' => $sourceTime, 'source_aqi_scale' => $values['source_aqi'] === null ? null : 'TH_AQI_2023', 'pm25_unit' => 'µg/m³', 'pm10_unit' => 'µg/m³', 'ozone_unit' => 'ppb', 'carbon_monoxide_unit' => 'ppm', 'nitrogen_dioxide_unit' => 'ppb', 'sulphur_dioxide_unit' => 'ppb', 'temperature_c' => null, 'humidity_pct' => null, 'source_status' => 'verified', 'raw_payload' => $raw], $values);
    }

    /** @param array<string,mixed> $last */
    private function pollutant(array $last, string $key, float $max): ?float { $node = $last[$key] ?? null; return is_array($node) ? $this->number($node['value'] ?? null, 0, $max) : null; }
    private function number(mixed $value, float $min, float $max, bool $integer = false): int|float|null
    {
        if (!is_numeric($value) || (float) $value === -1.0 || (float) $value === -999.0) return null;
        $number = (float) $value;
        if ($number < $min || $number > $max) throw new ProviderException('Air4Thai value is outside the accepted range', 'VALUE_RANGE');
        return $integer ? (int) round($number) : $number;
    }
}
