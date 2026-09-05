<?php
// Senast uppdaterad: 2026-09-03 13:15 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);
namespace ChiangMaiAirWatch\Services;

final class Geo
{
    public static function distanceKm(float $lat1,float $lon1,float $lat2,float $lon2):float
    {
        $earth=6371.0088;$latDelta=deg2rad($lat2-$lat1);$lonDelta=deg2rad($lon2-$lon1);$a=sin($latDelta/2)**2+cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($lonDelta/2)**2;return $earth*2*atan2(sqrt($a),sqrt(1-$a));
    }
}
