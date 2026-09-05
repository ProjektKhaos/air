<?php
// Senast uppdaterad: 2026-09-05 Asia/Bangkok | Version 1.12 | KlⒶssⓔ & Ⓐberg
declare(strict_types=1);
namespace ChiangMaiAirWatch\Tests\Unit;
use ChiangMaiAirWatch\Config;
use ChiangMaiAirWatch\HttpClient;
use ChiangMaiAirWatch\Providers\OpenMeteoWeatherProvider;
use ChiangMaiAirWatch\Providers\ProviderException;
use PHPUnit\Framework\TestCase;
final class WeatherRetryTest extends TestCase
{
    public function testTemporaryFailureRetriesAndRecovers():void
    {
        Config::overrideForTests(['providers'=>['openmeteo_weather'=>['enabled'=>true,'url'=>'https://fixture.invalid']]]);
        $payload=json_decode(file_get_contents(__DIR__.'/../fixtures/openmeteo/weather-current.json'),true);
        $client=new class($payload) extends HttpClient {
            public int $calls=0;
            public function __construct(private array $payload){}
            public function getJson(string $url,int $connectTimeout,int $timeout,int $maxBytes,array $headers=[],?string $caBundle=null):array{
                if(++$this->calls<3)throw new ProviderException('temporary','UPSTREAM_HTTP_ERROR');
                return $this->payload;
            }
        };
        $weather=(new OpenMeteoWeatherProvider($client))->fetchCurrent(['code'=>'test','latitude'=>18.78,'longitude'=>98.98]);
        self::assertSame(3,$client->calls);
        self::assertSame('2026-09-03 05:45:00',$weather['source_time']);
    }
    public function testAuthorizationFailureDoesNotRetry():void
    {
        Config::overrideForTests(['providers'=>['openmeteo_weather'=>['enabled'=>true,'url'=>'https://fixture.invalid']]]);
        $client=new class extends HttpClient {
            public int $calls=0;
            public function getJson(string $url,int $connectTimeout,int $timeout,int $maxBytes,array $headers=[],?string $caBundle=null):array{
                $this->calls++;throw new ProviderException('denied','AUTH_FAILED');
            }
        };
        try{(new OpenMeteoWeatherProvider($client))->fetchCurrent(['code'=>'test','latitude'=>18.78,'longitude'=>98.98]);self::fail('Expected failure');}
        catch(ProviderException $error){self::assertSame('AUTH_FAILED',$error->providerCode);self::assertSame(1,$client->calls);}
    }
}
