<?php
// Senast uppdaterad: 2026-09-02 21:05 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

use ChiangMaiAirWatch\Api;
use ChiangMaiAirWatch\ApiPresenter;
use ChiangMaiAirWatch\Config;
use ChiangMaiAirWatch\Database;
use ChiangMaiAirWatch\Services\DashboardService;

require __DIR__ . '/_bootstrap.php';

api_run(function (): void {
    $language = Api::language(isset($_GET['lang']) ? (string) $_GET['lang'] : null);
    $alerts = (new DashboardService(Database::connection()))->alerts();
    Api::success(['active'=>$alerts['active']?ApiPresenter::alert($alerts['active'],$language):null,'recent'=>array_map(static fn(array$a):array=>ApiPresenter::alert($a,$language),$alerts['recent']),'transitions'=>$alerts['transitions']],['generated_at'=>gmdate('Y-m-d H:i:s'),'timezone'=>Config::get('app.timezone'),'language'=>$language]);
});
