<?php
// Senast uppdaterad: 2026-09-02 21:05 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

use ChiangMaiAirWatch\Api;
use ChiangMaiAirWatch\Database;
use ChiangMaiAirWatch\Services\DashboardService;

require __DIR__ . '/_bootstrap.php';
api_run(function (): void {
    $language = Api::language(isset($_GET['lang']) ? (string) $_GET['lang'] : null);
    $result=(new DashboardService(Database::connection()))->forecast();
    $points=array_map(static fn(array$p):array=>['valid_at'=>$p['valid_at'],'pm25_ug_m3'=>is_numeric($p['pm25_ug_m3'])?(float)$p['pm25_ug_m3']:null,'pm10_ug_m3'=>is_numeric($p['pm10_ug_m3'])?(float)$p['pm10_ug_m3']:null,'ozone_ug_m3'=>is_numeric($p['ozone_ug_m3'])?(float)$p['ozone_ug_m3']:null,'no2_ug_m3'=>is_numeric($p['no2_ug_m3'])?(float)$p['no2_ug_m3']:null,'so2_ug_m3'=>is_numeric($p['so2_ug_m3'])?(float)$p['so2_ug_m3']:null,'co_ug_m3'=>is_numeric($p['co_ug_m3'])?(float)$p['co_ug_m3']:null,'model_us_aqi'=>is_numeric($p['us_aqi'])?(float)$p['us_aqi']:null],$result['points']);
    Api::success(['zone'=>'mueang-chiang-mai','risk'=>$result['risk'],'points'=>$points],['generated_at'=>gmdate('Y-m-d H:i:s'),'timezone'=>'UTC','attribution'=>'Air quality model data by Open-Meteo.com (CAMS Global)']);
});
