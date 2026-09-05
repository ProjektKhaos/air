<?php
// Senast uppdaterad: 2026-09-03 14:40 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);
namespace ChiangMaiAirWatch\Services;

use ChiangMaiAirWatch\Providers\ProviderException;

final class ObservationProviderCoordinator
{
    /** @param list<string> $providers @return array<string,array<string,mixed>> */
    public function run(array $providers,callable $runner):array
    {
        $results=[];foreach(array_values(array_unique($providers))as$name){try{$results[$name]=$runner($name)+['status'=>'success'];}catch(ProviderException$error){$results[$name]=$error->providerCode==='PROVIDER_DISABLED'?['status'=>'skipped','reason'=>'disabled']:['status'=>'failed','error_code'=>$error->providerCode];}catch(\Throwable){$results[$name]=['status'=>'failed','error_code'=>'COLLECTOR_FAILED'];}}return$results;
    }
}
