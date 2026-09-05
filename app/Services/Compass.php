<?php
// Senast uppdaterad: 2026-09-03 13:00 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);
namespace ChiangMaiAirWatch\Services;

final class Compass
{
    public static function fromDegrees(mixed $degrees):?string
    {
        if(!is_numeric($degrees))return null;$value=fmod((float)$degrees+360.0,360.0);$directions=['N','NE','E','SE','S','SW','W','NW'];return $directions[(int)floor(($value+22.5)/45)%8];
    }
}
