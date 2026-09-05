<?php
// Senast uppdaterad: 2026-09-03 13:25 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);
namespace ChiangMaiAirWatch\Services;

use ChiangMaiAirWatch\Config;

final class AirQualityEngine
{
    public static function category(mixed $aqi): string { if(!is_numeric($aqi)||(float)$aqi<0)return'unknown';$v=(float)$aqi;return $v<=25?'very_good':($v<=50?'good':($v<=100?'moderate':($v<=200?'unhealthy':'very_unhealthy'))); }
    public static function rank(string $status):int{return ['unknown'=>-1,'very_good'=>0,'good'=>1,'moderate'=>2,'unhealthy'=>3,'very_unhealthy'=>4][$status]??-1;}
    public static function pm25Band(mixed $value):string
    {
        if(!is_numeric($value)||(float)$value<0)return'unknown';$thresholds=(array)Config::get('pm25_display.thresholds',[]);$v=(float)$value;return$v<=(float)($thresholds['moderate']??25)?'low':($v<=(float)($thresholds['high']??37.5)?'moderate':($v<=(float)($thresholds['very_high']??75)?'high':'very_high'));
    }
    /** @param list<array<string,mixed>> $stations @return array<string,mixed> */
    public function area(array $stations):array
    {
        $live=array_values(array_filter($stations,static fn(array $s):bool=>($s['enabled']??1)&&($s['affects_official_status']??0)&&(string)($s['freshness_status']??'')==='live'&&self::category($s['source_aqi']??null)!=='unknown'));
        if(count($live)<(int)Config::get('stations.minimum_live',1))return['status'=>'unknown','source_aqi'=>null,'station_id'=>null,'station_public_id'=>null,'reason_codes'=>['NO_LIVE_OFFICIAL_STATION']];
        usort($live,static fn(array $a,array $b):int=>self::rank(self::category($b['source_aqi']))<=>self::rank(self::category($a['source_aqi'])) ?: ((int)$b['source_aqi']<=> (int)$a['source_aqi']));$worst=$live[0];
        return['status'=>self::category($worst['source_aqi']),'source_aqi'=>(int)$worst['source_aqi'],'station_id'=>(int)$worst['id'],'station_public_id'=>(string)$worst['public_id'],'station_name_en'=>$worst['display_name_en'],'station_name_th'=>$worst['display_name_th'],'reason_codes'=>['WORST_LIVE_OFFICIAL_STATION'],'live_station_count'=>count($live)];
    }
}
