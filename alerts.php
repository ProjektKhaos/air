<?php
// Senast uppdaterad: 2026-09-02 21:05 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

use ChiangMaiAirWatch\Database;
use ChiangMaiAirWatch\Services\DashboardService;
use ChiangMaiAirWatch\View;

require __DIR__ . '/app/bootstrap.php';

try {
    $alerts = (new DashboardService(Database::connection()))->alerts();
    $loadError = null;
} catch (Throwable $error) {
    $alerts = [];
    $loadError = $error;
}
View::render('alerts', [
    'pageTitle' => t('alerts.title') . ' · ' . t('app.name'), 'activeNav' => 'alerts',
    'alerts' => $alerts, 'loadError' => $loadError, 'pageScripts' => ['assets/js/push.js'],
]);
