<?php
// Senast uppdaterad: 2026-09-03 13:40 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

use ChiangMaiAirWatch\Api;
use ChiangMaiAirWatch\ApiPresenter;
use ChiangMaiAirWatch\Config;
use ChiangMaiAirWatch\Database;
use ChiangMaiAirWatch\Services\DashboardService;

require __DIR__ . '/_bootstrap.php';

api_run(function (): void {
    $language = Api::language(isset($_GET['lang']) ? (string) $_GET['lang'] : null);
    $dashboard = (new DashboardService(Database::connection()))->home();
    $data = ['area'=>$dashboard['area'],'stations'=>array_map(static fn(array $station):array=>ApiPresenter::station($station,$language),$dashboard['stations']),'local_sensor_summary'=>$dashboard['local_sensor_summary'],'forecast'=>$dashboard['forecast'],'weather'=>$dashboard['weather'],'advisory'=>$dashboard['advisory']];
    $data['labels'] = [
        'area'=>t('air.status.'.$dashboard['area']['status'],[],$language),
        'forecast'=>t('severity.'.($dashboard['forecast']['severity']??'unknown'),[],$language),
        'direction'=>t('forecast.direction.'.($dashboard['forecast']['direction']??'unknown'),[],$language),
        'advisory'=>t($dashboard['advisory']['message_key']??'advisory.unknown',[],$language),
        'advisory_severity'=>t('severity.'.($dashboard['advisory']['severity']??'unknown'),[],$language),
        'online'=>t('home.sensors_online',[],$language),
        'valid'=>t('home.valid_pm25_sensors',[],$language),
        'delayed'=>t('home.delayed_sensors',[],$language),
        'weather_unavailable'=>t('home.weather_unavailable',[],$language),
    ];
    Api::success($data, ['generated_at' => $dashboard['generated_at'], 'timezone' => Config::get('app.timezone'), 'language' => $language]);
});
