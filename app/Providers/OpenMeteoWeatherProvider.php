<?php
// Senast uppdaterad: 2026-09-03 13:00 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);
namespace ChiangMaiAirWatch\Providers;

use ChiangMaiAirWatch\Config;
use ChiangMaiAirWatch\HttpClient;
use DateTimeImmutable;
use DateTimeZone;

final class OpenMeteoWeatherProvider implements WeatherProviderInterface
{
    public function __construct(private readonly HttpClient $http=new HttpClient()){}
    public function getName():string{return 'openmeteo_weather';}
    public function fetchCurrent(array $zone):array
    {
        $config=(array)Config::get('providers.openmeteo_weather',[]);if(!($config['enabled']??false))throw new ProviderException('Weather provider is disabled','PROVIDER_DISABLED');
        $query=http_build_query(['latitude'=>$zone['latitude'],'longitude'=>$zone['longitude'],'current'=>'temperature_2m,precipitation,wind_speed_10m,wind_direction_10m,wind_gusts_10m','timezone'=>'UTC','wind_speed_unit'=>'kmh','precipitation_unit'=>'mm']);
        for($attempt=0;;$attempt++){
            try{
                $json=$this->http->getJson(rtrim((string)$config['url'],'?').'?'.$query,(int)($config['connect_timeout']??5),(int)($config['timeout']??20),(int)($config['max_bytes']??1_000_000));
                break;
            }catch(ProviderException $error){
                if($attempt>=2||!in_array($error->providerCode,['UPSTREAM_HTTP_ERROR','HTTP_TRANSPORT'],true))throw $error;
                usleep(250000*(2**$attempt));
            }
        }
        $current=$json['current']??null;$units=$json['current_units']??null;if(!is_array($current)||!is_array($units))throw new ProviderException('Open-Meteo weather schema is invalid','INVALID_SCHEMA');
        if(($units['wind_speed_10m']??'')!=='km/h'||($units['precipitation']??'')!=='mm')throw new ProviderException('Open-Meteo weather units are invalid','INVALID_UNITS');
        $time=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',(string)($current['time']??''),new DateTimeZone('UTC'));if(!$time||$time->format('Y-m-d\TH:i')!==(string)($current['time']??''))throw new ProviderException('Open-Meteo weather time is invalid','INVALID_TIMESTAMP');
        return ['zone_code'=>(string)$zone['code'],'provider'=>$this->getName(),'source_time'=>$time->format('Y-m-d H:i:s'),'received_at'=>gmdate('Y-m-d H:i:s'),'temperature_c'=>$this->number($current['temperature_2m']??null,-80,70),'wind_speed_kmh'=>$this->number($current['wind_speed_10m']??null,0,500),'wind_direction_deg'=>$this->number($current['wind_direction_10m']??null,0,360),'wind_gust_kmh'=>$this->number($current['wind_gusts_10m']??null,0,600),'precipitation_mm'=>$this->number($current['precipitation']??null,0,1000),'source_status'=>'model_context','raw_payload'=>$json];
    }
    private function number(mixed $value,float $min,float $max):?float{if(!is_numeric($value))return null;$value=(float)$value;if($value<$min||$value>$max)throw new ProviderException('Open-Meteo weather value is outside range','VALUE_RANGE');return $value;}
}
