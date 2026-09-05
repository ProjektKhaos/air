<?php
// Senast uppdaterad: 2026-09-03 13:25 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);
namespace ChiangMaiAirWatch\Repositories;

use ChiangMaiAirWatch\Config;
use ChiangMaiAirWatch\Services\TrendCalculator;
use DateTimeImmutable;
use DateTimeZone;
use PDO;

final class MeasurementRepository
{
    private const COLUMNS = ['provider','measured_at','source_measured_at','source_aqi','source_aqi_scale','source_aqi_pollutant','pm25_ug_m3','pm10_ug_m3','pm25_unit','pm10_unit','ozone_value','ozone_unit','carbon_monoxide_value','carbon_monoxide_unit','nitrogen_dioxide_value','nitrogen_dioxide_unit','sulphur_dioxide_value','sulphur_dioxide_unit','temperature_c','humidity_pct','source_status'];
    public function __construct(private readonly PDO $db) {}

    public function store(array $measurement, int $stationId): string
    {
        $canonical=[]; foreach (self::COLUMNS as $column) $canonical[$column]=$measurement[$column]??null;
        $payload=json_encode($canonical,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); $hash=hash('sha256',$payload); $received=gmdate('Y-m-d H:i:s');
        $this->db->beginTransaction();
        try {
            $select=$this->db->prepare('SELECT id,source_hash,raw_payload_json FROM measurements WHERE station_id=:station_id AND measured_at=:measured_at FOR UPDATE'); $select->execute(['station_id'=>$stationId,'measured_at'=>$measurement['measured_at']]); $existing=$select->fetch();
            $params=$this->params($measurement,$stationId,$hash,$received);
            if (!is_array($existing)) {
                $columns=array_merge(['station_id'],self::COLUMNS,['received_at','source_hash','raw_payload_json']); $names=implode(',', $columns); $marks=implode(',',array_map(static fn(string $c):string=>':'.$c,$columns));
                $insert=$this->db->prepare("INSERT INTO measurements ($names) VALUES ($marks)"); $insert->execute($params); $this->db->commit(); return 'inserted';
            }
            if (hash_equals((string)$existing['source_hash'],$hash)) { $this->db->commit(); return 'unchanged'; }
            $revision=$this->db->prepare('INSERT INTO measurement_revisions (measurement_id,previous_payload_json,replacement_payload_json,previous_hash,replacement_hash,revised_at) VALUES (:id,:previous,:replacement,:old_hash,:new_hash,:at)');
            $revision->execute(['id'=>$existing['id'],'previous'=>$existing['raw_payload_json'],'replacement'=>json_encode($measurement['raw_payload']??null,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),'old_hash'=>$existing['source_hash'],'new_hash'=>$hash,'at'=>$received]);
            $updateColumns=array_values(array_filter(self::COLUMNS,static fn(string$c):bool=>$c!=='measured_at'));$sets=implode(',',array_map(static fn(string $c):string=>"$c=:$c",$updateColumns));unset($params['measured_at']);$params['lookup_measured_at']=$measurement['measured_at'];$update=$this->db->prepare("UPDATE measurements SET $sets,received_at=:received_at,source_hash=:source_hash,raw_payload_json=:raw_payload_json,revised_at=:revised_at,revision_count=revision_count+1 WHERE station_id=:station_id AND measured_at=:lookup_measured_at");$params['revised_at']=$received;$update->execute($params);
            $this->db->commit(); return 'updated';
        } catch (\Throwable $error) { if($this->db->inTransaction())$this->db->rollBack(); throw $error; }
    }

    public function refreshState(int $stationId, ?DateTimeImmutable $now=null): void
    {
        $now??=new DateTimeImmutable('now',new DateTimeZone('UTC')); $s=$this->db->prepare('SELECT id,measured_at,pm25_ug_m3 AS value FROM measurements WHERE station_id=:id ORDER BY measured_at DESC LIMIT 800'); $s->execute(['id'=>$stationId]); $rows=$s->fetchAll(); $latest=$rows[0]??null; $fresh='offline';
        if(is_array($latest)){ $age=max(0,($now->getTimestamp()-(new DateTimeImmutable($latest['measured_at'].' UTC'))->getTimestamp())/60); $fresh=$age<=(int)Config::get('freshness.live_minutes')?'live':($age<=(int)Config::get('freshness.delayed_minutes')?'delayed':'stale'); }
        $points=array_map(static fn(array $r):array=>['measured_at'=>(string)$r['measured_at'],'value'=>is_numeric($r['value'])?(float)$r['value']:null],$rows); $tol=(int)Config::get('trends.tolerance_minutes',20);
        $up=$this->db->prepare('INSERT INTO station_state (station_id,latest_measurement_id,freshness_status,change_1h_pm25,change_3h_pm25,change_24h_pm25,updated_at) VALUES (:id,:measurement,:fresh,:c1,:c3,:c24,:at) ON DUPLICATE KEY UPDATE latest_measurement_id=VALUES(latest_measurement_id),freshness_status=VALUES(freshness_status),change_1h_pm25=VALUES(change_1h_pm25),change_3h_pm25=VALUES(change_3h_pm25),change_24h_pm25=VALUES(change_24h_pm25),updated_at=VALUES(updated_at)');
        $up->execute(['id'=>$stationId,'measurement'=>$latest['id']??null,'fresh'=>$fresh,'c1'=>TrendCalculator::change($points,1,$tol),'c3'=>TrendCalculator::change($points,3,$tol),'c24'=>TrendCalculator::change($points,24,$tol),'at'=>$now->format('Y-m-d H:i:s')]);
    }

    /** @return list<array<string,mixed>> */
    public function stationsWithState(): array
    {
        $now=new DateTimeImmutable('now',new DateTimeZone('UTC')); $live=$now->modify('-'.(int)Config::get('freshness.live_minutes').' minutes')->format('Y-m-d H:i:s'); $delayed=$now->modify('-'.(int)Config::get('freshness.delayed_minutes').' minutes')->format('Y-m-d H:i:s');
        $sql="SELECT s.*,CONCAT(s.provider,':',s.provider_station_code) public_id,CASE WHEN m.measured_at IS NULL THEN 'offline' WHEN m.measured_at>=:live THEN 'live' WHEN m.measured_at>=:delayed THEN 'delayed' ELSE 'stale' END freshness_status,ss.change_1h_pm25,ss.change_3h_pm25,ss.change_24h_pm25,m.id measurement_id,m.measured_at,m.source_measured_at,m.received_at,m.source_aqi,m.source_aqi_scale,m.source_aqi_pollutant,m.pm25_ug_m3,m.pm10_ug_m3,m.ozone_value,m.ozone_unit,m.carbon_monoxide_value,m.carbon_monoxide_unit,m.nitrogen_dioxide_value,m.nitrogen_dioxide_unit,m.sulphur_dioxide_value,m.sulphur_dioxide_unit,m.temperature_c,m.humidity_pct,m.source_status FROM stations s LEFT JOIN station_state ss ON ss.station_id=s.id LEFT JOIN measurements m ON m.id=ss.latest_measurement_id WHERE s.enabled=1 ORDER BY s.sort_order";
        $s=$this->db->prepare($sql);$s->execute(['live'=>$live,'delayed'=>$delayed]);return $s->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function history(int $stationId,string $period): array
    {
        if(in_array($period,['90d','1y'],true)){
            $days=$period==='90d'?90:365;$s=$this->db->prepare('SELECT CONCAT(summary_date," 00:00:00") measured_at,pm25_avg pm25_ug_m3,pm10_avg pm10_ug_m3,source_aqi_max source_aqi,0 revision_count,samples FROM daily_air_summary WHERE station_id=:id AND summary_date>=UTC_DATE()-INTERVAL :days DAY ORDER BY summary_date');$s->bindValue(':id',$stationId,PDO::PARAM_INT);$s->bindValue(':days',$days,PDO::PARAM_INT);$s->execute();return $s->fetchAll();
        }
        if($period==='5y'){
            $s=$this->db->prepare('SELECT CONCAT(MIN(summary_date)," 00:00:00") measured_at,ROUND(SUM(pm25_avg*samples)/NULLIF(SUM(CASE WHEN pm25_avg IS NOT NULL THEN samples ELSE 0 END),0),3) pm25_ug_m3,ROUND(SUM(pm10_avg*samples)/NULLIF(SUM(CASE WHEN pm10_avg IS NOT NULL THEN samples ELSE 0 END),0),3) pm10_ug_m3,MAX(source_aqi_max) source_aqi,0 revision_count,SUM(samples) samples FROM daily_air_summary WHERE station_id=:id AND summary_date>=UTC_DATE()-INTERVAL 5 YEAR GROUP BY YEARWEEK(summary_date,3) ORDER BY MIN(summary_date)');$s->execute(['id'=>$stationId]);return $s->fetchAll();
        }
        [$hours,$bucket]=match($period){'24h'=>[24,0],'72h'=>[72,0],'7d'=>[168,3],'30d'=>[720,12],default=>[24,0]};
        if($bucket===0){$s=$this->db->prepare('SELECT measured_at,pm25_ug_m3,pm10_ug_m3,source_aqi,revision_count FROM measurements WHERE station_id=:id AND measured_at>=UTC_TIMESTAMP()-INTERVAL :hours HOUR ORDER BY measured_at');$s->bindValue(':id',$stationId,PDO::PARAM_INT);$s->bindValue(':hours',$hours,PDO::PARAM_INT);$s->execute();return $s->fetchAll();}
        $seconds=$bucket*3600; $sql="SELECT FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(measured_at)/$seconds)*$seconds) measured_at,ROUND(AVG(pm25_ug_m3),2) pm25_ug_m3,ROUND(AVG(pm10_ug_m3),2) pm10_ug_m3,NULL source_aqi,0 revision_count FROM measurements WHERE station_id=:id AND measured_at>=UTC_TIMESTAMP()-INTERVAL :hours HOUR GROUP BY FLOOR(UNIX_TIMESTAMP(measured_at)/$seconds) ORDER BY measured_at"; $s=$this->db->prepare($sql);$s->bindValue(':id',$stationId,PDO::PARAM_INT);$s->bindValue(':hours',$hours,PDO::PARAM_INT);$s->execute();return $s->fetchAll();
    }

    private function params(array $m,int $stationId,string $hash,string $received):array { $p=['station_id'=>$stationId]; foreach(self::COLUMNS as $c)$p[$c]=$m[$c]??null; $p['received_at']=$received;$p['source_hash']=$hash;$p['raw_payload_json']=json_encode($m['raw_payload']??null,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);return $p; }
}
