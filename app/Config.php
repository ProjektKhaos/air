<?php
// Senast uppdaterad: 2026-09-04 15:45 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

namespace ChiangMaiAirWatch;

final class Config
{
    /** @var array<string,mixed> */
    private static array $values = [];

    public static function load(string $root): void
    {
        $defaults = [
            'app' => ['name' => 'Chiang Mai Air Watch', 'short_name' => 'Air Watch', 'environment' => 'development', 'base_url' => '/', 'public_origin' => 'https://air.aberg.online', 'asset_version' => '1.1.2', 'timezone' => 'Asia/Bangkok', 'debug' => false, 'default_language' => 'en', 'supported_languages' => ['en', 'th']],
            'db' => ['dsn' => '', 'username' => '', 'password' => ''],
            'providers' => [
                'observation' => 'air4thai', 'observations' => ['air4thai'], 'forecast' => 'openmeteo_air',
                'air4thai' => ['enabled' => true, 'current_url' => 'https://air4thai.pcd.go.th/services/getNewAQI_JSON.php', 'history_url' => 'https://air4thai.com/forweb/getHistoryData.php', 'ca_bundle' => $root . '/config/ca/air4thai', 'connect_timeout' => 5, 'timeout' => 20, 'max_bytes' => 2_000_000],
                'dustboy' => ['enabled' => false, 'url' => 'https://open-api.cmuccdc.org', 'api_key' => null, 'station_ids' => [], 'auto_discover' => false, 'center' => ['latitude' => 18.7883, 'longitude' => 98.9853], 'radius_km' => 15, 'maximum_stations' => 10, 'minimum_fetch_interval_minutes' => 55, 'maximum_requests_per_hour' => 10, 'connect_timeout' => 5, 'timeout' => 30, 'history_timeout' => 600, 'max_bytes' => 4_000_000, 'history_max_bytes' => 20_000_000],
                'openmeteo_air' => ['enabled' => true, 'url' => 'https://air-quality-api.open-meteo.com/v1/air-quality', 'connect_timeout' => 5, 'timeout' => 20, 'max_bytes' => 2_000_000],
                'openmeteo_weather' => ['enabled' => true, 'url' => 'https://api.open-meteo.com/v1/forecast', 'connect_timeout' => 5, 'timeout' => 20, 'max_bytes' => 1_000_000],
            ],
            'stations' => ['default' => 'air4thai:36t', 'allowlist' => ['35t', '36t'], 'minimum_live' => 1],
            'forecast_zone' => ['code' => 'mueang-chiang-mai', 'latitude' => 18.7883, 'longitude' => 98.9853],
            'maps' => ['tile_url' => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', 'attribution_url' => 'https://www.openstreetmap.org/copyright', 'max_zoom' => 18],
            'freshness' => ['live_minutes' => 90, 'delayed_minutes' => 180],
            'trends' => ['tolerance_minutes' => 20],
            'forecast_risk' => ['minimum_coverage' => 0.75, 'thresholds' => ['moderate' => 25.0, 'high' => 37.5, 'very_high' => 75.0], 'direction_absolute_delta' => 3.0, 'direction_relative_delta' => 0.10],
            'pm25_display' => ['thresholds' => ['moderate' => 25.0, 'high' => 37.5, 'very_high' => 75.0]],
            'alerts' => ['trigger_status' => 'unhealthy', 'clear_delay_minutes' => 90],
            'health' => ['air_collector_max_age_minutes' => 20, 'forecast_collector_max_age_minutes' => 60, 'weather_collector_max_age_minutes' => 45, 'air_collector_max_runtime_minutes' => 3, 'forecast_collector_max_runtime_minutes' => 5, 'weather_collector_max_runtime_minutes' => 3, 'observation_max_age_minutes' => 180, 'supplementary_observation_max_age_minutes' => 130, 'weather_max_age_minutes' => 45, 'forecast_valid_hours' => 24],
            'push' => ['enabled' => false, 'subject' => 'https://air.aberg.online/', 'public_key' => '', 'private_key' => '', 'claim_timeout_seconds' => 300, 'batch_size' => 20, 'max_attempts' => 5, 'retry_delays_seconds' => [60, 120, 300, 600], 'active_ttl_seconds' => 1800, 'cleared_ttl_seconds' => 7200],
            'security' => ['rate_limit_key' => '', 'push_subscribe_limit' => 10, 'push_unsubscribe_limit' => 20, 'push_rate_window_seconds' => 600],
            'retention' => ['measurement_months' => 24, 'forecast_months' => 12, 'operations_months' => 12, 'daily_summary_years' => 10, 'provider_request_days' => 2],
            'storage' => ['logs' => $root . '/storage/logs', 'locks' => $root . '/storage/locks', 'cache' => $root . '/storage/cache'],
        ];
        $file = getenv('CMAW_CONFIG_FILE') ?: '/etc/chiang-mai-air-watch/config.php';
        if (!is_file($file)) $file = $root . '/app/config.local.php';
        $local = is_file($file) ? require $file : [];
        if (!is_array($local)) throw new \RuntimeException('The local configuration file must return an array.');
        self::$values = self::merge($defaults, $local);
        // V1.0 configs selected one provider. Preserve that choice until the external config adopts the V1.1 list.
        if (isset($local['providers']['observation']) && !array_key_exists('observations', (array)($local['providers'] ?? []))) {
            self::$values['providers']['observations'] = [(string)$local['providers']['observation']];
        }
        $observations=(array)self::get('providers.observations',[self::get('providers.observation','air4thai')]);
        if (self::get('app.environment') === 'production' && array_intersect($observations, ['mock', 'mock_air'])) throw new \RuntimeException('Mock providers are forbidden in production.');
        date_default_timezone_set((string) self::get('app.timezone', 'Asia/Bangkok'));
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$values;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) return $default;
            $value = $value[$segment];
        }
        return $value;
    }

    /** @param array<string,mixed> $override */
    public static function overrideForTests(array $override): void { if (PHP_SAPI !== 'cli') throw new \LogicException('Runtime configuration overrides are CLI-only.'); self::$values = self::merge(self::$values, $override); }

    /** @param array<string,mixed> $base @param array<string,mixed> $override */
    private static function merge(array $base, array $override): array
    {
        foreach ($override as $key => $value) $base[$key] = is_array($value) && !array_is_list($value) && isset($base[$key]) && is_array($base[$key]) ? self::merge($base[$key], $value) : $value;
        return $base;
    }
}
