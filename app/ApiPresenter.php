<?php
// Senast uppdaterad: 2026-09-03 13:25 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

namespace ChiangMaiAirWatch;

use ChiangMaiAirWatch\Services\AirQualityEngine;

final class ApiPresenter
{
    /** @param array<string,mixed> $row @return array<string,mixed> */
    public static function station(array $row,string $language):array
    {
        $category=AirQualityEngine::category($row['source_aqi']??null);
        $official=(bool)($row['affects_official_status']??false);$provider=(string)$row['provider'];
        return [
            'id'=>(string)($row['public_id']??($row['provider'].':'.$row['provider_station_code'])),
            'provider'=>(string)$row['provider'],'code'=>(string)$row['provider_station_code'],
            'name'=>(string)$row['display_name_'.$language],'area'=>(string)$row['area_'.$language],
            'latitude'=>self::number($row['latitude']),'longitude'=>self::number($row['longitude']),
            'is_primary'=>(bool)$row['is_primary'],'affects_official_status'=>(bool)$row['affects_official_status'],
            'source'=>['provider'=>$provider,'label'=>t('source.'.$provider,[],$language),'classification'=>$official?'official':'supplementary','official'=>$official],
            'observation'=>[
                'measured_at'=>$row['measured_at']??null,'source_measured_at'=>$row['source_measured_at']??null,
                'received_at'=>$row['received_at']??null,'freshness'=>(string)($row['freshness_status']??'offline'),
                'freshness_label'=>t('freshness.'.($row['freshness_status']??'offline'),[],$language),
                'aqi'=>is_numeric($row['source_aqi']??null)?(int)$row['source_aqi']:null,
                'aqi_scale'=>$row['source_aqi_scale']??null,'dominant_pollutant'=>$row['source_aqi_pollutant']??null,
                'category'=>$category,'category_label'=>t('air.status.'.$category,[],$language),
                'pm25_ug_m3'=>self::number($row['pm25_ug_m3']??null),'pm10_ug_m3'=>self::number($row['pm10_ug_m3']??null),
                'ozone'=>self::pollutant($row,'ozone'),'carbon_monoxide'=>self::pollutant($row,'carbon_monoxide'),
                'nitrogen_dioxide'=>self::pollutant($row,'nitrogen_dioxide'),'sulphur_dioxide'=>self::pollutant($row,'sulphur_dioxide'),
                'temperature_c'=>self::number($row['temperature_c']??null),'humidity_pct'=>self::number($row['humidity_pct']??null),
                'source_status'=>(string)($row['source_status']??'unknown'),'pm25_band'=>AirQualityEngine::pm25Band($row['pm25_ug_m3']??null),'pm25_band_label'=>t('pm25.band.'.AirQualityEngine::pm25Band($row['pm25_ug_m3']??null),[],$language),
            ],
            'trends'=>['pm25_1h'=>self::number($row['change_1h_pm25']??null),'pm25_3h'=>self::number($row['change_3h_pm25']??null),'pm25_24h'=>self::number($row['change_24h_pm25']??null)],
        ];
    }

    /** @param array<string,mixed> $risk @return array<string,mixed> */
    public static function risk(array $risk,string $language):array
    {
        $key=(string)($risk['message_key']??'air.status.unknown');
        return ['severity'=>$risk['severity']??'unknown','severity_label'=>t('severity.'.($risk['severity']??'unknown'),[],$language),'reason_codes'=>$risk['reason_codes']??[],'message_key'=>$key,'message'=>t($key,[],$language),'values'=>$risk['context']??[],'calculated_at'=>$risk['calculated_at']??null];
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    public static function alert(array $row,string $language):array
    {
        return ['id'=>(int)$row['id'],'severity'=>(string)$row['severity'],'status'=>(string)$row['status'],'title'=>t((string)$row['title_key'],[],$language),'message'=>t((string)$row['message_key'],[],$language),'reason_codes'=>$row['reason_codes']??[],'triggered_at'=>$row['triggered_at'],'last_seen_at'=>$row['last_seen_at'],'pending_since'=>$row['pending_since'],'cleared_at'=>$row['cleared_at']];
    }

    /** @param array<string,mixed> $row @return array{value:?float,unit:?string} */
    private static function pollutant(array $row,string $name):array{return ['value'=>self::number($row[$name.'_value']??null),'unit'=>isset($row[$name.'_unit'])?(string)$row[$name.'_unit']:null];}
    private static function number(mixed $value):?float{return is_numeric($value)?(float)$value:null;}
}
