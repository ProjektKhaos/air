<?php
// Senast uppdaterad: 2026-09-03 14:00 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

use ChiangMaiAirWatch\Database;
use ChiangMaiAirWatch\Services\DashboardService;
use ChiangMaiAirWatch\View;

require __DIR__ . '/app/bootstrap.php';

try {
    $dashboard = (new DashboardService(Database::connection()))->home();
    $error = null;
} catch (Throwable $exception) {
    $dashboard=['stations'=>[],'primary'=>null,'area'=>['status'=>'unknown','source_aqi'=>null],'forecast'=>['severity'=>'unknown','direction'=>'unknown','windows'=>[24=>['mean'=>null]]],'advisory'=>['severity'=>'unknown','message_key'=>'advisory.unknown'],'local_sensor_summary'=>['live_count'=>0,'delayed_count'=>0,'stale_count'=>0,'median_pm25'=>null,'min_pm25'=>null,'max_pm25'=>null,'median_change_1h'=>null],'weather'=>null,'generated_at'=>gmdate('Y-m-d H:i:s')];
    $error = $exception;
}

View::render('home', [
    'pageTitle' => t('app.name'), 'activeNav' => 'home', 'dashboard' => $dashboard, 'loadError' => $error,
    'pageScripts' => ['assets/vendor/chart.umd.min.js', 'assets/js/home.js'],
]);
