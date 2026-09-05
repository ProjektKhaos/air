<?php
// Senast uppdaterad: 2026-09-02 19:20 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);
namespace ChiangMaiAirWatch\Repositories;

use PDO;

final class ForecastRepository
{
    public function __construct(private readonly PDO $db) {}
    /** @return list<array<string,mixed>> */ public function zones():array{return $this->db->query('SELECT * FROM forecast_zones WHERE enabled=1 ORDER BY sort_order')->fetchAll();}
    public function storeRun(array $run):string
    {
        $zone=$this->db->prepare('SELECT id FROM forecast_zones WHERE code=:code AND enabled=1');$zone->execute(['code'=>$run['zone_code']]);$zoneId=$zone->fetchColumn();if(!$zoneId)throw new \RuntimeException('Forecast zone is not configured.');
        $raw=json_encode($run['raw_payload'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);$hash=hash('sha256',$raw);$existing=$this->db->prepare('SELECT id FROM forecast_runs WHERE provider=:provider AND payload_hash=:hash');$existing->execute(['provider'=>$run['provider'],'hash'=>$hash]);$runId=$existing->fetchColumn();
        if($runId){$this->refreshState((int)$zoneId,(int)$runId,(string)$run['received_at']);return 'unchanged';}
        $this->db->beginTransaction();try{$insert=$this->db->prepare('INSERT INTO forecast_runs (provider,received_at,model_time,payload_hash,raw_payload_json) VALUES (:provider,:received,:model,:hash,:raw)');$insert->execute(['provider'=>$run['provider'],'received'=>$run['received_at'],'model'=>$run['model_time'],'hash'=>$hash,'raw'=>$raw]);$runId=(int)$this->db->lastInsertId();$point=$this->db->prepare('INSERT INTO air_forecast_points (forecast_run_id,forecast_zone_id,valid_at,pm25_ug_m3,pm10_ug_m3,ozone_ug_m3,no2_ug_m3,so2_ug_m3,co_ug_m3,us_aqi,us_aqi_pm25,us_aqi_pm10,source_status) VALUES (:run,:zone,:valid,:pm25,:pm10,:o3,:no2,:so2,:co,:aqi,:aqi25,:aqi10,:status)');foreach($run['points'] as $p)$point->execute(['run'=>$runId,'zone'=>$zoneId,'valid'=>$p['valid_at'],'pm25'=>$p['pm25_ug_m3'],'pm10'=>$p['pm10_ug_m3'],'o3'=>$p['ozone_ug_m3'],'no2'=>$p['no2_ug_m3'],'so2'=>$p['so2_ug_m3'],'co'=>$p['co_ug_m3'],'aqi'=>$p['us_aqi'],'aqi25'=>$p['us_aqi_pm25'],'aqi10'=>$p['us_aqi_pm10'],'status'=>$p['source_status']]);$this->db->commit();$this->refreshState((int)$zoneId,$runId,(string)$run['received_at']);return 'inserted';}catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw$e;}
    }
    public function refreshState(int $zoneId,int $runId,string $received):void{$s=$this->db->prepare("INSERT INTO forecast_state (forecast_zone_id,latest_forecast_run_id,latest_forecast_received_at,freshness_status,updated_at) VALUES (:zone,:run,:received,'current',UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE latest_forecast_run_id=VALUES(latest_forecast_run_id),latest_forecast_received_at=VALUES(latest_forecast_received_at),freshness_status='current',updated_at=UTC_TIMESTAMP()");$s->execute(['zone'=>$zoneId,'run'=>$runId,'received'=>$received]);}
    /** @return list<array<string,mixed>> */ public function latestPoints(int $hours=72):array{$s=$this->db->prepare('SELECT p.*,z.code zone_code,r.provider,r.received_at,r.model_time FROM forecast_state fs JOIN forecast_zones z ON z.id=fs.forecast_zone_id JOIN forecast_runs r ON r.id=fs.latest_forecast_run_id JOIN air_forecast_points p ON p.forecast_run_id=r.id AND p.forecast_zone_id=z.id WHERE p.valid_at>=UTC_TIMESTAMP()-INTERVAL 1 HOUR AND p.valid_at<=UTC_TIMESTAMP()+INTERVAL :hours HOUR ORDER BY p.valid_at');$s->bindValue(':hours',$hours,PDO::PARAM_INT);$s->execute();return $s->fetchAll();}
}
