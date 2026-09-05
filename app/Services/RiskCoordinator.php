<?php
// Senast uppdaterad: 2026-09-02 19:35 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);
namespace ChiangMaiAirWatch\Services;

use ChiangMaiAirWatch\Repositories\AlertRepository;
use ChiangMaiAirWatch\Repositories\ForecastRepository;
use ChiangMaiAirWatch\Repositories\MeasurementRepository;
use ChiangMaiAirWatch\Repositories\RiskStateRepository;
use PDO;

final class RiskCoordinator
{
    public function __construct(private readonly PDO $db){}
    public function calculate():array
    {
        $stations=(new MeasurementRepository($this->db))->stationsWithState();$area=(new AirQualityEngine())->area($stations);$forecast=(new ForecastRiskEngine())->evaluate((new ForecastRepository($this->db))->latestPoints());$advisory=(new AdvisoryEngine())->evaluate($area,$forecast);$repo=new RiskStateRepository($this->db);
        $observedRisk=['severity'=>$area['status'],'reason_codes'=>$area['reason_codes'],'message_key'=>'air.status.'.$area['status'],'context'=>$area,'calculated_at'=>gmdate('Y-m-d H:i:s')];$forecastRisk=['severity'=>$forecast['severity'],'reason_codes'=>$forecast['reason_codes'],'message_key'=>'forecast.'.$forecast['severity'],'context'=>$forecast,'calculated_at'=>gmdate('Y-m-d H:i:s')];$repo->save('observed',$observedRisk);$repo->save('forecast',$forecastRisk);$repo->save('combined',$advisory);
        $this->transitions($stations);
        $alertSeverity=in_array($area['status'],['unhealthy','very_unhealthy'],true)?$area['status']:'normal';$alertRisk=['severity'=>$alertSeverity,'reason_codes'=>$area['reason_codes'],'message_key'=>'alert.message.'.$alertSeverity,'context'=>['station_ids'=>array_values(array_filter([$area['station_id']??null])),'source_aqi'=>$area['source_aqi']??null,'station_public_id'=>$area['station_public_id']??null]];(new AlertManager($this->db,new AlertRepository($this->db)))->reconcile($alertRisk);
        return['observed'=>$observedRisk,'forecast'=>$forecastRisk,'combined'=>$advisory];
    }
    /** @param list<array<string,mixed>> $stations */
    private function transitions(array $stations):void
    {
        $last=$this->db->prepare('SELECT to_status FROM air_quality_transitions WHERE station_id=:station ORDER BY id DESC LIMIT 1');
        $insert=$this->db->prepare('INSERT INTO air_quality_transitions (from_status,to_status,station_id,source_aqi,notify_eligible,occurred_at) VALUES (:from,:to,:station,:aqi,:notify,UTC_TIMESTAMP())');
        foreach($stations as $station){
            if(($station['source_status']??'')!=='verified')continue;
            $to=AirQualityEngine::category($station['source_aqi']??null);if($to==='unknown')continue;
            $last->execute(['station'=>$station['id']]);$from=$last->fetchColumn();if($from===$to)continue;
            $insert->execute(['from'=>$from?:null,'to'=>$to,'station'=>$station['id'],'aqi'=>$station['source_aqi'],'notify'=>in_array($to,['unhealthy','very_unhealthy'],true)?1:0]);
        }
    }
}
