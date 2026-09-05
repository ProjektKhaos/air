<?php
// Senast uppdaterad: 2026-09-04 15:40 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

namespace ChiangMaiAirWatch\Providers;

use ChiangMaiAirWatch\Config;
use ChiangMaiAirWatch\HttpClient;
use DateTimeImmutable;
use DateTimeZone;

final class DustBoyProvider implements AirQualityProviderInterface
{
    public function __construct(private readonly HttpClient $http=new HttpClient()) {}
    public function getName():string{return 'dustboy';}

    /** @return list<array<string,mixed>> */
    public function fetchSelectedStations():array
    {
        $rows=$this->rows($this->request('/api/dustboy/station'));
        $result=[];
        foreach($rows as $row){
            $id=$this->stationCode($row);if($id==='')continue;
            $result[]=['id'=>$id,'dustboy_id'=>$this->text($row['dustboy_id']??null),'name'=>$this->text($row['dustboy_name']??$row['station_name']??$row['name']??null) ?: 'DustBoy '.$id,'latitude'=>$this->number($row['dustboy_lat']??$row['latitude']??$row['lat']??null,-90,90),'longitude'=>$this->number($row['dustboy_lon']??$row['longitude']??$row['lon']??$row['lng']??null,-180,180),'model'=>$this->text($row['model']??null),'version'=>$this->text($row['version']??null),'raw'=>$row];
        }
        return $result;
    }

    public function fetchLatestMeasurements(array $stations):array{return $this->normalizeLatest($this->rows($this->request('/api/dustboy/station')),$stations);}

    public function fetchHistory(array $stations,DateTimeImmutable $from,DateTimeImmutable $to):array
    {
        $days=max(1,(int)ceil(($to->getTimestamp()-$from->getTimestamp())/86400));$period=$days<=31?'30d':($days<=366?'1y':'5y');$result=[];
        foreach($stations as $station)$result=array_merge($result,$this->fetchHistoryPeriod((string)$station['provider_station_code'],$period));
        return $result;
    }

    /** @return list<array<string,mixed>> */
    public function fetchHistoryPeriod(string $stationId,string $period):array
    {
        $endpoint=match($period){'30d'=>'data30day','1y'=>'data1year','5y'=>'data5year',default=>throw new \InvalidArgumentException('Invalid DustBoy history period.')};
        if(!preg_match('/^[A-Za-z0-9_-]+$/',$stationId))throw new \InvalidArgumentException('Invalid DustBoy station ID.');
        $json=$this->request('/api/dustboy/'.$endpoint.'/'.rawurlencode($stationId),true);$container=is_array($json['data']??null)?$json['data']:$json;
        if(isset($container[0])&&is_array($container[0])){$meta=['id'=>$stationId];$readings=$container;}else{if(!array_key_exists('value',$container)&&!array_key_exists('readings',$container))throw new ProviderException('DustBoy history response schema is invalid','INVALID_SCHEMA');$meta=$container;$readings=$container['value']??$container['readings'];}
        if(!is_array($readings))throw new ProviderException('DustBoy history response schema is invalid','INVALID_SCHEMA');
        $code=$this->stationCode(is_array($meta)?$meta:[]) ?: $stationId;$result=[];
        foreach($readings as $reading){if(is_array($reading))$result[]=$this->measurement($code,$reading);}
        return $result;
    }

    /** @param list<array<string,mixed>> $rows @param list<array<string,mixed>> $stations @return list<array<string,mixed>> */
    public function normalizeLatest(array $rows,array $stations):array
    {
        $allow=array_fill_keys(array_map(static fn(array $s):string=>(string)$s['provider_station_code'],$stations),true);$result=[];
        foreach($rows as $row){$code=$this->stationCode($row);if(!isset($allow[$code]))continue;$reading=$row['value']??$row;if(is_array($reading)&&array_is_list($reading))$reading=$reading[0]??[];if(is_array($reading))$result[]=$this->measurement($code,$reading);}
        return $result;
    }

    /** @return array<string,mixed> */
    private function measurement(string $code,array $row):array
    {
        $source=$this->text($row['log_datetime']??$row['timestamp']??$row['datetime']??null);$time=$source!==null?$this->sourceTime($source):false;
        if(!$time)throw new ProviderException('DustBoy timestamp is invalid','INVALID_TIMESTAMP');$environment=(bool)Config::get('providers.dustboy.environment_fields_enabled',false);
        return ['provider'=>'dustboy','provider_station_code'=>$code,'measured_at'=>$time->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),'source_measured_at'=>$source,'source_aqi'=>null,'source_aqi_scale'=>null,'source_aqi_pollutant'=>null,'pm25_ug_m3'=>$this->number($row['pm25']??$row['pm2_5']??null,0,2000),'pm10_ug_m3'=>$this->number($row['pm10']??null,0,3000),'pm25_unit'=>'µg/m³','pm10_unit'=>'µg/m³','ozone_value'=>null,'ozone_unit'=>null,'carbon_monoxide_value'=>null,'carbon_monoxide_unit'=>null,'nitrogen_dioxide_value'=>null,'nitrogen_dioxide_unit'=>null,'sulphur_dioxide_value'=>null,'sulphur_dioxide_unit'=>null,'temperature_c'=>$environment?$this->number($row['temp']??$row['temperature']??null,-20,60):null,'humidity_pct'=>$environment?$this->number($row['humid']??$row['humidity']??null,0,100):null,'source_status'=>'supplementary','raw_payload'=>$row];
    }

    /** @return array<string,mixed> */
    private function request(string $path,bool $history=false):array
    {
        $config=(array)Config::get('providers.dustboy',[]);$key=$config['api_key']??null;if(!($config['enabled']??false)||!is_string($key)||$key==='')throw new ProviderException('DustBoy is disabled or has no API key','PROVIDER_DISABLED');
        $timeout=(int)($config[$history?'history_timeout':'timeout']??($history?600:30));
        return $this->http->getJson(rtrim((string)$config['url'],'/').$path,(int)($config['connect_timeout']??5),$timeout,(int)($config[$history?'history_max_bytes':'max_bytes']??($history?20_000_000:4_000_000)),['Authorization: Bearer '.$key]);
    }

    /** @return list<array<string,mixed>> */
    private function rows(array $json):array
    {
        if(!array_key_exists('data',$json)&&isset($json['message'])&&is_string($json['message'])&&str_contains(strtolower($json['message']),'data is empty'))throw new ProviderException('The DustBoy account has no selected stations','NO_STATIONS_SELECTED');
        $rows=$json['data']??$json;if(!is_array($rows)||!array_is_list($rows))throw new ProviderException('DustBoy response schema is invalid','INVALID_SCHEMA');return array_values(array_filter($rows,'is_array'));
    }
    private function stationCode(array $row):string{return $this->text($row['id']??$row['station_id']??$row['dustboy_id']??null)??'';}
    private function text(mixed $value):?string{$value=is_scalar($value)?trim((string)$value):'';return $value===''?null:$value;}
    private function number(mixed $value,float $min,float $max):?float{if($value===null||$value===''||!is_numeric($value)||in_array((float)$value,[-1.0,-999.0],true))return null;$number=(float)$value;if($number<$min||$number>$max)throw new ProviderException('DustBoy value is outside the accepted range','VALUE_RANGE');return $number;}
    private function sourceTime(string $source):DateTimeImmutable|false
    {
        $zone=new DateTimeZone('Asia/Bangkok');
        foreach(['Y-m-d H:i:s','Y-m-d H:i']as$format){$time=DateTimeImmutable::createFromFormat('!'.$format,$source,$zone);$errors=DateTimeImmutable::getLastErrors();if($time&&($errors===false||($errors['warning_count']===0&&$errors['error_count']===0))&&$time->format($format)===$source)return $time;}
        return false;
    }
}
