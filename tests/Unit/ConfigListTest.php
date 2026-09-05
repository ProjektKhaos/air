<?php
// Senast uppdaterad: 2026-09-05 Asia/Bangkok | Version 1.12 | KlⒶssⓔ & Ⓐberg
declare(strict_types=1);
namespace ChiangMaiAirWatch\Tests\Unit;
use ChiangMaiAirWatch\Config;
use PHPUnit\Framework\TestCase;
final class ConfigListTest extends TestCase
{
    public function testProviderListsCanBeReplacedAndCleared():void
    {
        Config::overrideForTests(['providers'=>['observations'=>['air4thai','dustboy']]]);
        Config::overrideForTests(['providers'=>['observations'=>['air4thai']]]);
        self::assertSame(['air4thai'],Config::get('providers.observations'));
        Config::overrideForTests(['providers'=>['observations'=>[]]]);
        self::assertSame([],Config::get('providers.observations'));
    }
}
