<?php
// Senast uppdaterad: 2026-09-03 14:25 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg
declare(strict_types=1);
namespace ChiangMaiAirWatch\Tests\Unit;

use ChiangMaiAirWatch\Config;
use ChiangMaiAirWatch\Services\AdvisoryEngine;
use ChiangMaiAirWatch\Services\AirQualityEngine;
use ChiangMaiAirWatch\Services\ForecastRiskEngine;
use ChiangMaiAirWatch\Services\TrendCalculator;
use ChiangMaiAirWatch\Services\Compass;
use ChiangMaiAirWatch\Services\Geo;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AirRulesTest extends TestCase
{
    public static function categories():array{return[[null,'unknown'],[-1,'unknown'],[0,'very_good'],[25,'very_good'],[26,'good'],[50,'good'],[51,'moderate'],[100,'moderate'],[101,'unhealthy'],[200,'unhealthy'],[201,'very_unhealthy']];}
    #[DataProvider('categories')] public function testOfficialCategoryBoundaries(mixed$value,string$expected):void{self::assertSame($expected,AirQualityEngine::category($value));}
    public function testAreaUsesOnlyWorstLiveOfficialStation():void{$rows=[['id'=>1,'public_id'=>'air4thai:36t','enabled'=>1,'affects_official_status'=>1,'freshness_status'=>'live','source_aqi'=>45,'display_name_en'=>'A','display_name_th'=>'ก'],['id'=>2,'public_id'=>'air4thai:35t','enabled'=>1,'affects_official_status'=>1,'freshness_status'=>'live','source_aqi'=>110,'display_name_en'=>'B','display_name_th'=>'ข'],['id'=>3,'public_id'=>'dustboy:x','enabled'=>1,'affects_official_status'=>0,'freshness_status'=>'live','source_aqi'=>300,'display_name_en'=>'C','display_name_th'=>'ค']];$area=(new AirQualityEngine())->area($rows);self::assertSame('unhealthy',$area['status']);self::assertSame('air4thai:35t',$area['station_public_id']);}
    public function testAreaUnknownWithoutLiveOfficialStation():void{self::assertSame('unknown',(new AirQualityEngine())->area([['enabled'=>1,'affects_official_status'=>1,'freshness_status'=>'stale','source_aqi'=>200]])['status']);}
    public function testTrendToleranceAndSign():void{$points=[['measured_at'=>'2026-09-02 12:00:00','value'=>20],['measured_at'=>'2026-09-02 11:05:00','value'=>15]];self::assertSame(5.0,TrendCalculator::change($points,1,20));self::assertNull(TrendCalculator::change($points,3,20));}
    public function testForecastCoverageThresholdsAndDirection():void{$now=new DateTimeImmutable('2026-09-02 12:00:00',new DateTimeZone('UTC'));$points=[];for($h=0;$h<48;$h++)$points[]=['valid_at'=>$now->modify("+$h hours")->format('Y-m-d H:i:s'),'pm25_ug_m3'=>$h<6?50.0:($h>=18&&$h<24?20.0:30.0),'us_aqi'=>70,'received_at'=>'2026-09-02 11:30:00','provider'=>'openmeteo_air'];$risk=(new ForecastRiskEngine())->evaluate($points,$now);self::assertSame('moderate',$risk['severity']);self::assertSame('improving',$risk['direction']);self::assertSame(24,$risk['windows'][24]['count']);}
    public function testForecastRequiresSeventyFivePercent():void{$now=new DateTimeImmutable('2026-09-02 12:00:00',new DateTimeZone('UTC'));$points=[];for($h=0;$h<17;$h++)$points[]=['valid_at'=>$now->modify("+$h hours")->format('Y-m-d H:i:s'),'pm25_ug_m3'=>20];self::assertSame('unknown',(new ForecastRiskEngine())->evaluate($points,$now)['severity']);}
    public function testAdvisoryIsCautiousAndSeparate():void{$advice=(new AdvisoryEngine())->evaluate(['status'=>'good','reason_codes'=>[]],['severity'=>'high','direction'=>'worsening','reason_codes'=>[]]);self::assertSame('warning',$advice['severity']);self::assertSame('advisory.warning',$advice['message_key']);}
    public function testPm25DisplayBandsAreNotAqi():void{self::assertSame('unknown',AirQualityEngine::pm25Band(null));self::assertSame('low',AirQualityEngine::pm25Band(25));self::assertSame('moderate',AirQualityEngine::pm25Band(25.1));self::assertSame('high',AirQualityEngine::pm25Band(37.6));self::assertSame('very_high',AirQualityEngine::pm25Band(75.1));}
    public function testCompassBoundariesAndNull():void{self::assertSame('N',Compass::fromDegrees(0));self::assertSame('NE',Compass::fromDegrees(45));self::assertSame('E',Compass::fromDegrees(90));self::assertNull(Compass::fromDegrees(null));}
    public function testDustBoyRadiusDistance():void{self::assertLessThan(1,Geo::distanceKm(18.7883,98.9853,18.7901,98.9867));self::assertGreaterThan(15,Geo::distanceKm(18.7883,98.9853,19.0,99.2));}
}
