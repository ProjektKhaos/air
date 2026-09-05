<?php
// Senast uppdaterad: 2026-09-03 13:00 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);
namespace ChiangMaiAirWatch\Repositories;

use PDO;

final class WeatherRepository
{
    public function __construct(private readonly PDO $db){}
    public function store(array $weather):void
    {
        $statement=$this->db->prepare('INSERT INTO weather_state(zone_code,provider,source_time,received_at,temperature_c,wind_speed_kmh,wind_direction_deg,wind_gust_kmh,precipitation_mm,source_status,raw_payload_json) VALUES(:zone_code,:provider,:source_time,:received_at,:temperature_c,:wind_speed_kmh,:wind_direction_deg,:wind_gust_kmh,:precipitation_mm,:source_status,:raw_payload_json) ON DUPLICATE KEY UPDATE provider=VALUES(provider),source_time=VALUES(source_time),received_at=VALUES(received_at),temperature_c=VALUES(temperature_c),wind_speed_kmh=VALUES(wind_speed_kmh),wind_direction_deg=VALUES(wind_direction_deg),wind_gust_kmh=VALUES(wind_gust_kmh),precipitation_mm=VALUES(precipitation_mm),source_status=VALUES(source_status),raw_payload_json=VALUES(raw_payload_json)');
        $params=$weather;$params['raw_payload_json']=json_encode($weather['raw_payload']??null,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);unset($params['raw_payload']);$statement->execute($params);
    }
    public function current(string $zoneCode):?array{$statement=$this->db->prepare('SELECT * FROM weather_state WHERE zone_code=:code');$statement->execute(['code'=>$zoneCode]);$row=$statement->fetch();return is_array($row)?$row:null;}
}
