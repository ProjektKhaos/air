<?php
// Senast uppdaterad: 2026-09-03 14:45 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg
declare(strict_types=1);
namespace ChiangMaiAirWatch\Tests\Live;

use ChiangMaiAirWatch\Providers\Air4ThaiProvider;
use ChiangMaiAirWatch\Providers\OpenMeteoAirProvider;
use ChiangMaiAirWatch\Providers\OpenMeteoWeatherProvider;
use ChiangMaiAirWatch\Providers\DustBoyProvider;
use ChiangMaiAirWatch\Config;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class ProviderSmokeTest extends TestCase
{
    private array$stations=[['provider_station_code'=>'36t'],['provider_station_code'=>'35t']];
    public function testSecureAir4ThaiLatestAndAllowlist():void{$rows=(new Air4ThaiProvider())->fetchLatestMeasurements($this->stations);self::assertCount(2,$rows);self::assertEqualsCanonicalizing(['36t','35t'],array_column($rows,'provider_station_code'));foreach($rows as$r){self::assertSame('TH_AQI_2023',$r['source_aqi_scale']);self::assertSame('verified',$r['source_status']);}}
    public function testOfficialThirtyDayHistory():void{$now=new DateTimeImmutable('now',new DateTimeZone('UTC'));$rows=(new Air4ThaiProvider())->fetchHistory($this->stations,$now->modify('-30 days'),$now);self::assertGreaterThanOrEqual(1400,count($rows));self::assertEqualsCanonicalizing(['35t','36t'],array_values(array_unique(array_column($rows,'provider_station_code'))));}
    public function testOpenMeteoSchemaUnitsAndTimezone():void{$runs=(new OpenMeteoAirProvider())->fetchForecast([['code'=>'mueang-chiang-mai','latitude'=>18.7883,'longitude'=>98.9853]]);self::assertCount(1,$runs);self::assertGreaterThanOrEqual(48,count($runs[0]['points']));self::assertSame('model',$runs[0]['points'][0]['source_status']);}
    public function testOpenMeteoWeatherContext():void{$weather=(new OpenMeteoWeatherProvider())->fetchCurrent(['code'=>'mueang-chiang-mai','latitude'=>18.7883,'longitude'=>98.9853]);self::assertSame('openmeteo_weather',$weather['provider']);self::assertIsFloat($weather['wind_speed_kmh']);self::assertNotEmpty($weather['source_time']);}
    public function testDustBoyLiveSchemasWhenCredentialIsInstalled():void{$config=(array)Config::get('providers.dustboy',[]);if(!($config['enabled']??false)||!is_string($config['api_key']??null)||$config['api_key']==='')self::markTestSkipped('DustBoy external credential is not installed.');$provider=new DustBoyProvider();$stations=$provider->fetchSelectedStations();self::assertNotEmpty($stations);$id=(string)$stations[0]['id'];$latest=$provider->fetchLatestMeasurements([['provider_station_code'=>$id]]);self::assertNotEmpty($latest);self::assertNull($latest[0]['source_aqi']);foreach(['30d','1y','5y']as$period)self::assertNotEmpty($provider->fetchHistoryPeriod($id,$period));}
}
