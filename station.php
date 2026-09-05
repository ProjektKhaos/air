<?php
// Senast uppdaterad: 2026-09-03 14:15 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

use ChiangMaiAirWatch\Database;
use ChiangMaiAirWatch\Services\DashboardService;
use ChiangMaiAirWatch\View;

require __DIR__ . '/app/bootstrap.php';

$code=(string)($_GET['code']??'air4thai:36t');
if(!preg_match('/^[a-z0-9_-]+:[a-zA-Z0-9_-]+$/',$code)){
    http_response_code(400);
    $code='air4thai:36t';
    $invalidStation = true;
}
try {
    $station=(new DashboardService(Database::connection()))->station($code,'72h');
    $loadError = $station === null ? new RuntimeException('Station unavailable') : null;
    if($station===null)http_response_code(404);
} catch (Throwable $error) {
    $station = null;
    $loadError = $error;
}

View::render('station', [
    'pageTitle' => $code . ' · ' . t('app.name'), 'activeNav' => 'stations', 'station' => $station,
    'stationCode' => $code, 'loadError' => $loadError, 'invalidStation' => $invalidStation ?? false,
    'pageScripts' => ['assets/vendor/chart.umd.min.js', 'assets/js/station.js'],
]);
