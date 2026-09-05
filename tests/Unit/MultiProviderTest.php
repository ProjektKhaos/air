<?php
// Senast uppdaterad: 2026-09-03 14:40 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);
namespace ChiangMaiAirWatch\Tests\Unit;

use ChiangMaiAirWatch\Providers\ProviderException;
use ChiangMaiAirWatch\Services\ObservationProviderCoordinator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MultiProviderTest extends TestCase
{
    public static function outcomes():array{return[['ok','ok','success','success'],['ok','fail','success','failed'],['fail','ok','failed','success'],['ok','disabled','success','skipped']];}
    #[DataProvider('outcomes')]
    public function testProvidersAreAlwaysIsolated(string$air,string$dust,string$airStatus,string$dustStatus):void
    {
        $states=['air4thai'=>$air,'dustboy'=>$dust];$results=(new ObservationProviderCoordinator())->run(['air4thai','dustboy'],static function(string$name)use($states):array{$state=$states[$name];if($state==='fail')throw new ProviderException('sanitized','UPSTREAM_HTTP_ERROR');if($state==='disabled')throw new ProviderException('disabled','PROVIDER_DISABLED');return['inserted'=>1];});self::assertSame($airStatus,$results['air4thai']['status']);self::assertSame($dustStatus,$results['dustboy']['status']);
    }
}
