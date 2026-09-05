<?php
// Senast uppdaterad: 2026-09-02 19:35 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);
namespace ChiangMaiAirWatch\Services;

final class AdvisoryEngine
{
    public function evaluate(array $observed,array $forecast):array
    {
        $status=(string)($observed['status']??'unknown');$model=(string)($forecast['severity']??'unknown');
        if($status==='unknown'||$model==='unknown'){$severity='unknown';}
        elseif($status==='very_unhealthy'||$model==='very_high'){$severity='critical';}
        elseif($status==='unhealthy'||$model==='high'){$severity='warning';}
        elseif($status==='moderate'||$model==='moderate'){$severity='watch';}
        else{$severity='normal';}
        return['severity'=>$severity,'message_key'=>'advisory.'.$severity,'reason_codes'=>array_merge($observed['reason_codes']??[],$forecast['reason_codes']??[]),'context'=>['observed_status'=>$status,'forecast_severity'=>$model,'forecast_direction'=>$forecast['direction']??'unknown'],'calculated_at'=>gmdate('Y-m-d H:i:s')];
    }
}
