<?php
// Senast uppdaterad: 2026-09-04 14:25 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg
declare(strict_types=1);
namespace ChiangMaiAirWatch\Tests\Unit;

use ChiangMaiAirWatch\Config;
use ChiangMaiAirWatch\HttpClient;
use ChiangMaiAirWatch\Providers\Air4ThaiProvider;
use ChiangMaiAirWatch\Providers\DustBoyProvider;
use ChiangMaiAirWatch\Providers\OpenMeteoWeatherProvider;
use ChiangMaiAirWatch\Providers\ProviderException;
use PHPUnit\Framework\TestCase;

final class ProviderFixtureTest extends TestCase
{
    public function testAllowlistSentinelsUnitsAndBangkokTime():void{$payload=$this->airFixture('latest-normal.json');$rows=(new Air4ThaiProvider($this->client($payload)))->fetchLatestMeasurements($this->airStations());self::assertCount(2,$rows);self::assertSame(['36t','35t'],array_column($rows,'provider_station_code'));self::assertSame('2026-09-02 12:00:00',$rows[0]['measured_at']);self::assertSame('TH_AQI_2023',$rows[0]['source_aqi_scale']);self::assertSame('µg/m³',$rows[0]['pm25_unit']);self::assertNull($rows[1]['pm25_ug_m3']);self::assertNull($rows[1]['pm10_ug_m3']);self::assertNull($rows[1]['source_aqi']);}
    public function testMissingAllowlistedStationFailsClosed():void{$payload=$this->airFixture('latest-normal.json');$payload['stations']=[$payload['stations'][0]];$this->expectException(ProviderException::class);(new Air4ThaiProvider($this->client($payload)))->fetchLatestMeasurements($this->airStations());}
    public function testAir4ThaiBadTimestampIsRejected():void{$this->expectException(ProviderException::class);(new Air4ThaiProvider($this->client($this->airFixture('latest-bad-time.json'))))->fetchLatestMeasurements([$this->airStations()[0]]);}
    public function testDustBoyIsDisabledWithoutKey():void{$this->expectException(ProviderException::class);(new DustBoyProvider())->fetchLatestMeasurements([]);}
    public function testDustBoyLatestUsesInstallationIdAndNeverCreatesAqi():void{$rows=$this->dustProvider('latest-normal.json')->fetchLatestMeasurements([['provider_station_code'=>'5050']]);self::assertCount(1,$rows);self::assertSame('5050',$rows[0]['provider_station_code']);self::assertSame('supplementary',$rows[0]['source_status']);self::assertNull($rows[0]['source_aqi']);self::assertSame(18.2,$rows[0]['pm25_ug_m3']);self::assertSame('2026-09-02 12:00:00',$rows[0]['measured_at']);}
    public function testDustBoySelectedStationMetadata():void{$stations=$this->dustProvider('latest-normal.json')->fetchSelectedStations();self::assertSame('5050',$stations[0]['id']);self::assertSame('D0A15TEST001',$stations[0]['dustboy_id']);self::assertEqualsWithDelta(18.7901,$stations[0]['latitude'],.00001);}
    public function testDustBoyEmptyAccountHasExplicitError():void{try{$this->dustProvider('stations-empty.json')->fetchSelectedStations();self::fail('Expected provider exception');}catch(ProviderException$error){self::assertSame('NO_STATIONS_SELECTED',$error->providerCode);}}
    public function testDustBoyMissingPollutantsRemainNull():void{$pm10=$this->dustProvider('latest-missing-pm10.json')->fetchLatestMeasurements([['provider_station_code'=>'5050']]);$pm25=$this->dustProvider('latest-missing-pm25.json')->fetchLatestMeasurements([['provider_station_code'=>'5050']]);self::assertNull($pm10[0]['pm10_ug_m3']);self::assertNull($pm25[0]['pm25_ug_m3']);}
    public function testDustBoySentinelsRemainNull():void{$rows=$this->dustProvider('latest-sentinels.json')->fetchLatestMeasurements([['provider_station_code'=>'5050']]);self::assertNull($rows[0]['pm25_ug_m3']);self::assertNull($rows[0]['pm10_ug_m3']);}
    public function testDustBoyBadTimestampAndRangeAreRejected():void{foreach([['latest-bad-time.json','INVALID_TIMESTAMP'],['latest-range.json','VALUE_RANGE']]as[$fixture,$code]){try{$this->dustProvider($fixture)->fetchLatestMeasurements([['provider_station_code'=>'5050']]);self::fail('Expected provider exception');}catch(ProviderException$error){self::assertSame($code,$error->providerCode);}}}
    public function testDustBoyHistorySchemas():void{$month=$this->dustProvider('history-30d.json')->fetchHistoryPeriod('5050','30d');$year=$this->dustProvider('history-1y-sample.json')->fetchHistoryPeriod('5050','1y');self::assertCount(2,$month);self::assertCount(1,$year);self::assertSame(42.0,$year[0]['pm25_ug_m3']);self::assertSame('2026-03-15 03:00:00',$year[0]['measured_at']);self::assertNull($month[0]['source_aqi']);}
    public function testDustBoyInvalidHistorySchemaIsRejected():void{$this->expectException(ProviderException::class);$this->dustProvider('history-invalid.json')->fetchHistoryPeriod('5050','30d');}
    public function testWeatherSchemaUnitsAndUtcTime():void{$payload=$this->json(__DIR__.'/../fixtures/openmeteo/weather-current.json');Config::overrideForTests(['providers'=>['openmeteo_weather'=>['enabled'=>true,'url'=>'https://fixture.invalid']]]);$weather=(new OpenMeteoWeatherProvider($this->client($payload)))->fetchCurrent(['code'=>'mueang-chiang-mai','latitude'=>18.7883,'longitude'=>98.9853]);self::assertSame('2026-09-03 05:45:00',$weather['source_time']);self::assertSame(6.6,$weather['wind_speed_kmh']);self::assertSame(154.0,$weather['wind_direction_deg']);}
    private function dustProvider(string$file):DustBoyProvider{Config::overrideForTests(['providers'=>['dustboy'=>['enabled'=>true,'url'=>'https://fixture.invalid','api_key'=>'fixture-key','environment_fields_enabled'=>false]]]);return new DustBoyProvider($this->client($this->json(__DIR__.'/../fixtures/dustboy/'.$file)));}
    private function airStations():array{return[['provider_station_code'=>'36t'],['provider_station_code'=>'35t']];}
    private function airFixture(string$name):array{return$this->json(__DIR__.'/../fixtures/air4thai/'.$name);}
    private function json(string$file):array{return json_decode((string)file_get_contents($file),true,512,JSON_THROW_ON_ERROR);}
    private function client(array$payload):HttpClient{return new class($payload)extends HttpClient{public function __construct(private array$p){}public function getJson(string$url,int$connectTimeout,int$timeout,int$maxBytes,array$headers=[],?string$caBundle=null):array{return$this->p;}};}
}
