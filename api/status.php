<?php
// Senast uppdaterad: 2026-09-02 21:05 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

use ChiangMaiAirWatch\Api;
use ChiangMaiAirWatch\Database;
use ChiangMaiAirWatch\Services\HealthService;

require __DIR__ . '/_bootstrap.php';

api_run(function (): void {
    $health = (new HealthService(Database::connection()))->evaluate();
    $generatedAt = $health['generated_at'];
    unset($health['generated_at']);
    Api::success($health, ['generated_at' => $generatedAt]);
});
