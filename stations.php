<?php
// Senast uppdaterad: 2026-09-03 14:15 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

use ChiangMaiAirWatch\Database;
use ChiangMaiAirWatch\Services\DashboardService;
use ChiangMaiAirWatch\View;

require __DIR__ . '/app/bootstrap.php';

try {
    $stations = (new DashboardService(Database::connection()))->stations();
    $loadError = null;
} catch (Throwable $error) {
    $stations = [];
    $loadError = $error;
}

View::render('stations', [
    'pageTitle' => t('station.title') . ' · ' . t('app.name'), 'activeNav' => 'stations',
    'stations' => $stations, 'loadError' => $loadError,
    'pageStyles' => ['assets/vendor/leaflet.css', 'assets/vendor/MarkerCluster.css', 'assets/vendor/MarkerCluster.Default.css'],
    'pageScripts' => ['assets/vendor/leaflet.js', 'assets/vendor/leaflet.markercluster.js', 'assets/js/stations.js'],
]);
