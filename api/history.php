<?php
// Senast uppdaterad: 2026-09-03 13:40 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

use ChiangMaiAirWatch\Api;
use ChiangMaiAirWatch\Database;
use ChiangMaiAirWatch\Services\DashboardService;

require __DIR__ . '/_bootstrap.php';
api_run(function (): void {
    $language = Api::language(isset($_GET['lang']) ? (string) $_GET['lang'] : null);
    $code=isset($_GET['station'])?(string)$_GET['station']:'air4thai:36t';
    if(!preg_match('/^[a-z0-9_-]+:[a-zA-Z0-9_-]+$/',$code))Api::error('INVALID_STATION',t('error.api.invalid_station',[],$language));
    $period=Api::period(isset($_GET['period'])?(string)$_GET['period']:'24h',['24h','72h','7d','30d','90d','1y','5y'],$language);
    $station=(new DashboardService(Database::connection()))->station($code,$period);
    if ($station === null) {
        Api::error('STATION_NOT_FOUND', t('error.api.station_not_found', [], $language), 404);
    }
    $points = array_map(static fn (array $point): array => [
        'measured_at'=>$point['measured_at'],'pm25_ug_m3'=>is_numeric($point['pm25_ug_m3'])?(float)$point['pm25_ug_m3']:null,
        'pm10_ug_m3'=>is_numeric($point['pm10_ug_m3'])?(float)$point['pm10_ug_m3']:null,'aqi'=>is_numeric($point['source_aqi'])?(int)$point['source_aqi']:null,'revision_count'=>(int)$point['revision_count'],
    ], $station['history']);
    Api::success(['station'=>$code,'period'=>$period,'aggregation'=>$station['aggregation'],'points'=>$points],['generated_at'=>gmdate('Y-m-d H:i:s'),'timezone'=>'UTC']);
});
