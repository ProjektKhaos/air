<?php
// Senast uppdaterad: 2026-09-03 13:17 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(2);
}

$path = $argv[1] ?? '/etc/chiang-mai-air-watch/config.php';
if (!is_file($path) || !is_readable($path)) {
    fwrite(STDERR, "Configuration file is not readable.\n");
    exit(2);
}

$config = require $path;
if (!is_array($config)) {
    fwrite(STDERR, "Configuration must return an array.\n");
    exit(2);
}

$existingDustBoy = (array) ($config['providers']['dustboy'] ?? []);
$dustBoyKey = $existingDustBoy['api_key'] ?? null;
$dustBoyEnabled = ($existingDustBoy['enabled'] ?? false) === true;
if ($dustBoyEnabled && (!is_string($dustBoyKey) || trim($dustBoyKey) === '')) {
    fwrite(STDERR, "Refusing to enable DustBoy without an API key.\n");
    exit(2);
}

$config['app'] = array_replace((array) ($config['app'] ?? []), [
    'asset_version' => '1.1.1',
]);
$config['providers'] = array_replace_recursive((array) ($config['providers'] ?? []), [
    'observations' => ['air4thai', 'dustboy'],
    'dustboy' => [
        'enabled' => $dustBoyEnabled,
        'api_key' => $dustBoyKey,
        'station_ids' => (array) ($existingDustBoy['station_ids'] ?? []),
        'auto_discover' => false,
        'center' => ['latitude' => 18.7883, 'longitude' => 98.9853],
        'radius_km' => 15,
        'maximum_stations' => 10,
        'minimum_fetch_interval_minutes' => 55,
        'maximum_requests_per_hour' => 10,
        'connect_timeout' => 5,
        'timeout' => 30,
        'max_bytes' => 4_000_000,
        'history_max_bytes' => 20_000_000,
        'environment_fields_enabled' => false,
    ],
    'openmeteo_weather' => [
        'enabled' => true,
        'url' => 'https://api.open-meteo.com/v1/forecast',
        'connect_timeout' => 5,
        'timeout' => 20,
        'max_bytes' => 1_000_000,
    ],
]);
$config['health'] = array_replace((array) ($config['health'] ?? []), [
    'weather_collector_max_age_minutes' => 45,
    'weather_collector_max_runtime_minutes' => 3,
    'supplementary_observation_max_age_minutes' => 130,
    'weather_max_age_minutes' => 45,
]);
$config['retention'] = array_replace((array) ($config['retention'] ?? []), [
    'measurement_months' => 24,
    'forecast_months' => 12,
    'operations_months' => 12,
    'daily_summary_years' => 10,
    'provider_request_days' => 2,
]);

$backup = $path . '.pre-v1.1-' . gmdate('Ymd\THis\Z');
if (!copy($path, $backup)) {
    fwrite(STDERR, "Could not create configuration backup.\n");
    exit(1);
}

$temporary = $path . '.v1.1.tmp';
$contents = "<?php\n// Chiang Mai Air Watch production configuration. Secrets must remain outside the repository.\nreturn "
    . var_export($config, true) . ";\n";
if (file_put_contents($temporary, $contents, LOCK_EX) === false || !chmod($temporary, 0640)) {
    @unlink($temporary);
    fwrite(STDERR, "Could not write upgraded configuration.\n");
    exit(1);
}
if (!rename($temporary, $path)) {
    @unlink($temporary);
    fwrite(STDERR, "Could not install upgraded configuration.\n");
    exit(1);
}

fwrite(STDOUT, "V1.1 configuration installed; secrets were not displayed.\n");
fwrite(STDOUT, "DustBoy is " . ($dustBoyEnabled ? "enabled.\n" : "disabled until a key is installed and enabled.\n"));
