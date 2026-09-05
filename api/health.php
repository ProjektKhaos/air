<?php
// Senast uppdaterad: 2026-09-02 21:05 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

use ChiangMaiAirWatch\Api;
use ChiangMaiAirWatch\Database;
use ChiangMaiAirWatch\Logger;
use ChiangMaiAirWatch\Services\HealthService;

require __DIR__ . '/_bootstrap.php';

api_run(function (): void {
    try {
        $db = Database::connection();
        $db->query('SELECT 1')->fetchColumn();
        $health = (new HealthService($db))->evaluate();
        $generatedAt = $health['generated_at'];
        unset($health['generated_at']);
        Api::success($health, ['generated_at' => $generatedAt], $health['status'] === 'ok' ? 200 : 503);
    } catch (Throwable) {
        Logger::error('health_database_failed', ['error_code' => 'DATABASE_UNAVAILABLE']);
        Api::success(
            ['status' => 'degraded', 'database' => 'failed', 'providers' => [], 'collectors' => []],
            ['generated_at' => gmdate('Y-m-d H:i:s')],
            503
        );
    }
});
