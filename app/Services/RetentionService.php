<?php
// Senast uppdaterad: 2026-09-05 14:05 Asia/Bangkok | Version 1.11 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

namespace ChiangMaiAirWatch\Services;

use ChiangMaiAirWatch\Config;
use PDO;

final class RetentionService
{
    public function __construct(private readonly PDO $db) {}

    /** @return array<string,int> */
    public function purge(): array
    {
        $forecastMonths=max(1,(int)Config::get('retention.forecast_months',12));
        $measurementMonths=max(1,(int)Config::get('retention.measurement_months',24));
        $operationMonths=max(1,(int)Config::get('retention.operations_months',12));
        $summaryYears=max(1,(int)Config::get('retention.daily_summary_years',10));
        $requestDays=max(1,(int)Config::get('retention.provider_request_days',2));

        return [
            'forecast_runs_deleted'=>$this->db->exec("DELETE FROM forecast_runs WHERE received_at < UTC_TIMESTAMP() - INTERVAL $forecastMonths MONTH"),
            'measurements_deleted'=>$this->db->exec("DELETE FROM measurements WHERE measured_at < UTC_TIMESTAMP() - INTERVAL $measurementMonths MONTH"),
            'collector_runs_deleted'=>$this->db->exec("DELETE FROM collector_runs WHERE started_at < UTC_TIMESTAMP() - INTERVAL $operationMonths MONTH"),
            'push_deliveries_deleted'=>$this->db->exec("DELETE FROM push_deliveries WHERE created_at < UTC_TIMESTAMP() - INTERVAL $operationMonths MONTH"),
            'daily_summaries_deleted'=>$this->db->exec("DELETE FROM daily_air_summary WHERE summary_date < UTC_DATE() - INTERVAL $summaryYears YEAR"),
            'provider_requests_deleted'=>$this->db->exec("DELETE FROM provider_api_requests WHERE requested_at < UTC_TIMESTAMP() - INTERVAL $requestDays DAY"),
            'rate_limits_deleted'=>(new RateLimiter($this->db))->purgeExpired(),
        ];
    }
}
