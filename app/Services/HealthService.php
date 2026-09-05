<?php
// Senast uppdaterad: 2026-09-03 13:35 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);
namespace ChiangMaiAirWatch\Services;

use ChiangMaiAirWatch\Config;
use ChiangMaiAirWatch\Repositories\ProviderHealthRepository;
use DateTimeImmutable;
use DateTimeZone;
use PDO;

final class HealthService
{
    public function __construct(private readonly PDO $db){}
    public function evaluate(?DateTimeImmutable $now=null):array
    {
        $now??=new DateTimeImmutable('now',new DateTimeZone('UTC'));$reasons=[];$collectors=[];
        $definitions=['collect_air'=>[(int)Config::get('health.air_collector_max_age_minutes',20),(int)Config::get('health.air_collector_max_runtime_minutes',3)],'collect_forecast'=>[(int)Config::get('health.forecast_collector_max_age_minutes',60),(int)Config::get('health.forecast_collector_max_runtime_minutes',5)]];
        if((bool)Config::get('providers.openmeteo_weather.enabled',true))$definitions['collect_weather']=[(int)Config::get('health.weather_collector_max_age_minutes',45),(int)Config::get('health.weather_collector_max_runtime_minutes',3)];
        foreach($definitions as $name=>$limits){$statement=$this->db->prepare('SELECT * FROM collector_runs WHERE collector=:name ORDER BY started_at DESC LIMIT 1');$statement->execute(['name'=>$name]);$row=$statement->fetch();$state='missing';if(is_array($row)){$started=new DateTimeImmutable($row['started_at'].' UTC');$age=($now->getTimestamp()-$started->getTimestamp())/60;$state=$row['status']==='running'&&$age>$limits[1]?'hung':(in_array($row['status'],['success','partial'],true)&&$age<=$limits[0]?'ok':($row['status']==='running'?'running':'stale'));}$collectors[$name]=['status'=>$state,'last_run'=>is_array($row)?$row['started_at']:null];if($state!=='ok')$reasons[]='COLLECTOR_'.strtoupper($name).'_'.strtoupper($state);}
        $latestOfficial=$this->latestObservation(true);if($this->tooOld($latestOfficial,(int)Config::get('health.observation_max_age_minutes',180),$now))$reasons[]='OFFICIAL_OBSERVATION_TOO_OLD';
        $dustEnabled=in_array('dustboy',(array)Config::get('providers.observations',[]),true)&&(bool)Config::get('providers.dustboy.enabled',false);$latestSupplementary=$this->latestObservation(false);
        if($dustEnabled&&$this->tooOld($latestSupplementary,(int)Config::get('health.supplementary_observation_max_age_minutes',130),$now))$reasons[]='SUPPLEMENTARY_OBSERVATION_TOO_OLD';
        $weatherStatement=$this->db->prepare('SELECT source_time FROM weather_state WHERE zone_code=:code');$weatherStatement->execute(['code'=>(string)Config::get('forecast_zone.code','mueang-chiang-mai')]);$weather=$weatherStatement->fetchColumn();
        if((bool)Config::get('providers.openmeteo_weather.enabled',true)&&$this->tooOld($weather?:null,(int)Config::get('health.weather_max_age_minutes',45),$now))$reasons[]='WEATHER_TOO_OLD';
        $forecast=(int)$this->db->query('SELECT COUNT(*) FROM forecast_state fs JOIN air_forecast_points p ON p.forecast_run_id=fs.latest_forecast_run_id AND p.forecast_zone_id=fs.forecast_zone_id WHERE p.valid_at>=UTC_TIMESTAMP() AND p.valid_at<UTC_TIMESTAMP()+INTERVAL 24 HOUR AND p.pm25_ug_m3 IS NOT NULL')->fetchColumn();if($forecast<(int)ceil(24*(float)Config::get('forecast_risk.minimum_coverage',.75)))$reasons[]='FORECAST_INSUFFICIENT';
        $providers=(new ProviderHealthRepository($this->db))->all();foreach($providers as $provider){$name=(string)$provider['provider'];if((int)$provider['consecutive_failures']>0&&($name!=='dustboy'||$dustEnabled)&&($name!=='openmeteo_weather'||(bool)Config::get('providers.openmeteo_weather.enabled',true)))$reasons[]='PROVIDER_'.strtoupper($name).'_FAILED';}
        $reasons=array_values(array_unique($reasons));return['status'=>$reasons===[]?'ok':'degraded','database'=>'ok','providers'=>$providers,'collectors'=>$collectors,'latest_observation_at'=>$latestOfficial,'latest_official_observation_at'=>$latestOfficial,'latest_supplementary_observation_at'=>$latestSupplementary?:null,'weather_observed_at'=>$weather?:null,'valid_forecast_points_24h'=>$forecast,'reasons'=>$reasons,'generated_at'=>$now->format('Y-m-d H:i:s')];
    }
    private function latestObservation(bool $official):?string{$statement=$this->db->prepare('SELECT MAX(m.measured_at) FROM measurements m JOIN stations s ON s.id=m.station_id WHERE s.enabled=1 AND s.affects_official_status=:official');$statement->execute(['official'=>$official?1:0]);$value=$statement->fetchColumn();return$value?(string)$value:null;}
    private function tooOld(mixed$value,int$minutes,DateTimeImmutable$now):bool{return!is_string($value)||$value===''||$now->getTimestamp()-(new DateTimeImmutable($value.' UTC'))->getTimestamp()>$minutes*60;}
}
