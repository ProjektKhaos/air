<?php
// Senast uppdaterad: 2026-09-05 14:00 Asia/Bangkok | Version 1.11 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);
namespace ChiangMaiAirWatch\Services;

use ChiangMaiAirWatch\Config;
use ChiangMaiAirWatch\Repositories\AlertRepository;
use ChiangMaiAirWatch\Repositories\ForecastRepository;
use ChiangMaiAirWatch\Repositories\MeasurementRepository;
use ChiangMaiAirWatch\Repositories\StationRepository;
use ChiangMaiAirWatch\Repositories\WeatherRepository;
use PDO;

final class DashboardService
{
    public function __construct(private readonly PDO $db){}
    public function home():array
    {
        $stations=$this->stations();$area=(new AirQualityEngine())->area($stations);$forecast=(new ForecastRiskEngine())->evaluate((new ForecastRepository($this->db))->latestPoints());$advisory=(new AdvisoryEngine())->evaluate($area,$forecast);$zone=(string)Config::get('forecast_zone.code','mueang-chiang-mai');
        return['stations'=>$stations,'primary'=>array_values(array_filter($stations,static fn(array$s):bool=>(bool)$s['is_primary']))[0]??null,'area'=>$area,'forecast'=>$forecast,'advisory'=>$advisory,'local_sensor_summary'=>$this->localSensorSummary(),'weather'=>$this->weather($zone),'generated_at'=>gmdate('Y-m-d H:i:s')];
    }
    /** @return list<array<string,mixed>> */
    public function stations():array{$rows=(new MeasurementRepository($this->db))->stationsWithState();foreach($rows as&$row){$row['air_status']=AirQualityEngine::category($row['source_aqi']??null);$row['pm25_band']=AirQualityEngine::pm25Band($row['pm25_ug_m3']??null);}return$rows;}
    public function station(string $publicId,string $period='72h'):?array{$station=(new StationRepository($this->db))->byPublicId($publicId);if(!$station)return null;$current=null;foreach($this->stations()as$row)if($row['public_id']===$publicId){$current=$row;break;}return['station'=>$current??$station,'history'=>(new MeasurementRepository($this->db))->history((int)$station['id'],$period),'period'=>$period,'aggregation'=>match($period){'7d'=>'3h mean','30d'=>'12h mean','90d','1y'=>'daily','5y'=>'weekly',default=>'raw hourly'}];}
    public function forecast():array{$points=(new ForecastRepository($this->db))->latestPoints();return['risk'=>(new ForecastRiskEngine())->evaluate($points),'points'=>$points];}
    public function alerts():array{$transitions=$this->db->query('SELECT t.*,CONCAT(s.provider,\':\',s.provider_station_code) station_public_id,s.display_name_en,s.display_name_th FROM air_quality_transitions t LEFT JOIN stations s ON s.id=t.station_id ORDER BY t.occurred_at DESC LIMIT 50')->fetchAll();return['active'=>(new AlertRepository($this->db))->active(),'recent'=>(new AlertRepository($this->db))->recent(),'transitions'=>$transitions];}

    /** @return array<string,mixed> */
    public function localSensorSummary():array
    {
        $rows=$this->db->query("SELECT m.pm25_ug_m3,ss.change_1h_pm25,CASE WHEN m.measured_at IS NULL THEN 'offline' WHEN m.measured_at>=UTC_TIMESTAMP()-INTERVAL ".(int)Config::get('freshness.live_minutes',90)." MINUTE THEN 'live' WHEN m.measured_at>=UTC_TIMESTAMP()-INTERVAL ".(int)Config::get('freshness.delayed_minutes',180)." MINUTE THEN 'delayed' ELSE 'stale' END freshness FROM stations s LEFT JOIN station_state ss ON ss.station_id=s.id LEFT JOIN measurements m ON m.id=ss.latest_measurement_id WHERE s.enabled=1 AND s.provider='dustboy'")->fetchAll();
        $values=[];$changes=[];$counts=['live'=>0,'delayed'=>0,'stale'=>0,'offline'=>0];foreach($rows as$row){$fresh=(string)$row['freshness'];$counts[$fresh]=($counts[$fresh]??0)+1;if($fresh==='live'&&is_numeric($row['pm25_ug_m3']))$values[]=(float)$row['pm25_ug_m3'];if($fresh==='live'&&is_numeric($row['change_1h_pm25']))$changes[]=(float)$row['change_1h_pm25'];}
        return['provider'=>'dustboy','live_count'=>$counts['live'],'valid_pm25_count'=>count($values),'delayed_count'=>$counts['delayed'],'stale_count'=>$counts['stale']+$counts['offline'],'median_pm25'=>$this->median($values),'min_pm25'=>$values===[]?null:min($values),'max_pm25'=>$values===[]?null:max($values),'median_change_1h'=>$this->median($changes),'generated_at'=>gmdate('Y-m-d H:i:s')];
    }
    /** @return array<string,mixed>|null */
    private function weather(string $zone):?array{$row=(new WeatherRepository($this->db))->current($zone);if(!$row||!(bool)Config::get('providers.openmeteo_weather.enabled',true)||strtotime($row['source_time'].' UTC')<time()-(int)Config::get('health.weather_max_age_minutes',45)*60)return null;return['source'=>'Open-Meteo','observed_at'=>$row['source_time'],'received_at'=>$row['received_at'],'temperature_c'=>$this->numeric($row['temperature_c']),'wind_speed_kmh'=>$this->numeric($row['wind_speed_kmh']),'wind_direction_deg'=>$this->numeric($row['wind_direction_deg']),'wind_direction_compass'=>Compass::fromDegrees($row['wind_direction_deg']),'wind_gust_kmh'=>$this->numeric($row['wind_gust_kmh']),'precipitation_mm'=>$this->numeric($row['precipitation_mm']),'source_status'=>$row['source_status']];}
    private function median(array $values):?float{if($values===[])return null;sort($values,SORT_NUMERIC);$count=count($values);$middle=intdiv($count,2);return$count%2?$values[$middle]:($values[$middle-1]+$values[$middle])/2;}
    private function numeric(mixed$value):?float{return is_numeric($value)?(float)$value:null;}
}
