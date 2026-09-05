<?php
// Senast uppdaterad: 2026-09-03 13:00 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);
namespace ChiangMaiAirWatch\Repositories;

use PDO;

final class DailySummaryRepository
{
    public function __construct(private readonly PDO $db){}
    public function rebuild(string $from,string $to,?int $stationId=null):int
    {
        $sql='INSERT INTO daily_air_summary(station_id,summary_date,samples,pm25_avg,pm25_min,pm25_max,pm10_avg,pm10_min,pm10_max,source_aqi_max,temperature_avg,humidity_avg,first_measured_at,last_measured_at) SELECT station_id,DATE(measured_at),COUNT(*),AVG(pm25_ug_m3),MIN(pm25_ug_m3),MAX(pm25_ug_m3),AVG(pm10_ug_m3),MIN(pm10_ug_m3),MAX(pm10_ug_m3),MAX(source_aqi),AVG(temperature_c),AVG(humidity_pct),MIN(measured_at),MAX(measured_at) FROM measurements WHERE measured_at>=:from AND measured_at<DATE_ADD(:to,INTERVAL 1 DAY)'.($stationId!==null?' AND station_id=:station_id':'').' GROUP BY station_id,DATE(measured_at) ON DUPLICATE KEY UPDATE samples=VALUES(samples),pm25_avg=VALUES(pm25_avg),pm25_min=VALUES(pm25_min),pm25_max=VALUES(pm25_max),pm10_avg=VALUES(pm10_avg),pm10_min=VALUES(pm10_min),pm10_max=VALUES(pm10_max),source_aqi_max=VALUES(source_aqi_max),temperature_avg=VALUES(temperature_avg),humidity_avg=VALUES(humidity_avg),first_measured_at=VALUES(first_measured_at),last_measured_at=VALUES(last_measured_at)';
        $statement=$this->db->prepare($sql);$params=['from'=>$from,'to'=>$to];if($stationId!==null)$params['station_id']=$stationId;$statement->execute($params);return $statement->rowCount();
    }
}
