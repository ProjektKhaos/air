<?php
// Senast uppdaterad: 2026-09-02 19:35 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);
namespace ChiangMaiAirWatch\Services;

use ChiangMaiAirWatch\Config;
use DateTimeImmutable;
use DateTimeZone;

final class ForecastRiskEngine
{
    /** @param list<array<string,mixed>> $points @return array<string,mixed> */
    public function evaluate(array $points,?DateTimeImmutable $now=null):array
    {
        $now??=new DateTimeImmutable('now',new DateTimeZone('UTC'));$windows=[];foreach([6,12,24,48]as$hours)$windows[$hours]=$this->window($points,$now,$hours);
        $mean=$windows[24]['mean'];$severity='unknown';$thresholds=Config::get('forecast_risk.thresholds');if(is_numeric($mean)){$severity=$mean<=(float)$thresholds['moderate']?'low':($mean<=(float)$thresholds['high']?'moderate':($mean<=(float)$thresholds['very_high']?'high':'very_high'));}
        $near=$this->rangeMean($points,$now,0,6);$later=$this->rangeMean($points,$now,18,24);$direction='unknown';if(is_numeric($near)&&is_numeric($later)){$delta=$later-$near;$required=max((float)Config::get('forecast_risk.direction_absolute_delta',3),abs($near)*(float)Config::get('forecast_risk.direction_relative_delta',.1));$direction=abs($delta)<$required?'stable':($delta>0?'worsening':'improving');}
        $aqiValues=array_values(array_filter(array_column($points,'us_aqi'),'is_numeric'));$valid=array_values(array_filter($points,static fn(array$p):bool=>is_numeric($p['pm25_ug_m3']??null)));$peak=null;foreach($valid as$p)if($peak===null||(float)$p['pm25_ug_m3']>(float)$peak['pm25_ug_m3'])$peak=$p;
        return['severity'=>$severity,'direction'=>$direction,'windows'=>$windows,'peak_pm25'=>$peak? (float)$peak['pm25_ug_m3']:null,'peak_at'=>$peak['valid_at']??null,'model_us_aqi_peak'=>$aqiValues===[]?null:max(array_map('floatval',$aqiValues)),'received_at'=>$points[0]['received_at']??null,'provider'=>$points[0]['provider']??null,'reason_codes'=>$severity==='unknown'?['INSUFFICIENT_FORECAST']:['MODELLED_PM25_24H_MEAN']];
    }
    private function window(array $points,DateTimeImmutable $now,int $hours):array{$values=$this->rangeValues($points,$now,0,$hours);$required=(int)ceil($hours*(float)Config::get('forecast_risk.minimum_coverage',.75));return['hours'=>$hours,'count'=>count($values),'required'=>$required,'mean'=>count($values)>=$required?round(array_sum($values)/count($values),1):null];}
    private function rangeMean(array $points,DateTimeImmutable $now,int $from,int $to):?float{$v=$this->rangeValues($points,$now,$from,$to);$needed=(int)ceil(($to-$from)*(float)Config::get('forecast_risk.minimum_coverage',.75));return count($v)>=$needed?array_sum($v)/count($v):null;}
    /** @return list<float> */private function rangeValues(array $points,DateTimeImmutable $now,int $from,int $to):array{$start=$now->modify("+$from hours")->getTimestamp();$end=$now->modify("+$to hours")->getTimestamp();$v=[];foreach($points as$p){if(!is_numeric($p['pm25_ug_m3']??null))continue;$t=(new DateTimeImmutable($p['valid_at'].' UTC'))->getTimestamp();if($t>=$start&&$t<$end)$v[]=(float)$p['pm25_ug_m3'];}return$v;}
}
