<?php
// Senast uppdaterad: 2026-09-05 14:00 Asia/Bangkok | Version 1.11 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

use ChiangMaiAirWatch\Config;
use ChiangMaiAirWatch\Database;
use ChiangMaiAirWatch\Providers\ObservationProviderFactory;
use ChiangMaiAirWatch\Providers\ProviderException;
use ChiangMaiAirWatch\Repositories\MeasurementRepository;
use ChiangMaiAirWatch\Repositories\ProviderHealthRepository;
use ChiangMaiAirWatch\Repositories\StationRepository;
use ChiangMaiAirWatch\Services\CollectorLock;
use ChiangMaiAirWatch\Services\CollectorRun;
use ChiangMaiAirWatch\Services\DustBoyRequestLedger;
use ChiangMaiAirWatch\Services\RiskCoordinator;

require dirname(__DIR__).'/app/bootstrap.php';

$lock=new CollectorLock();if(!$lock->acquire('collect-air')){fwrite(STDERR,"collector already running\n");exit(0);}
$db=Database::connection();$run=new CollectorRun($db,'collect_air');$health=new ProviderHealthRepository($db);$stationRepo=new StationRepository($db);$measurementRepo=new MeasurementRepository($db);$factory=new ObservationProviderFactory();
$names=array_values(array_unique(array_filter(array_map('strval',(array)Config::get('providers.observations',[Config::get('providers.observation','air4thai')])))));
$results=[];$insertedTotal=0;$updatedTotal=0;$successes=0;$failures=0;

foreach($names as $name){
    $requestId=null;$ledger=null;$stations=null;
    try{
        if($name==='dustboy'){
            $dustConfig=(array)Config::get('providers.dustboy',[]);
            if(!($dustConfig['enabled']??false)||!is_string($dustConfig['api_key']??null)||$dustConfig['api_key']===''){$results[$name]=['status'=>'skipped','reason'=>'disabled'];continue;}
            $stations=$stationRepo->forProvider('dustboy');if($stations===[]){$results[$name]=['status'=>'skipped','reason'=>'no_stations'];continue;}
            $ledger=new DustBoyRequestLedger($db);if(!$ledger->latestIsDue()){$results[$name]=['status'=>'skipped','reason'=>'not_due'];continue;}
            $requestId=$ledger->reserveLatestIfAvailable();if($requestId===null){$results[$name]=['status'=>'skipped','reason'=>'not_due'];continue;}
        }
        $provider=$factory->create($name);$stations=$stations??$stationRepo->forProvider($provider->getName());$records=$provider->fetchLatestMeasurements($stations);$map=[];
        foreach($stations as $station)$map[(string)$station['provider_station_code']]=(int)$station['id'];$inserted=0;$updated=0;$unchanged=0;
        foreach($records as $record){$code=(string)$record['provider_station_code'];if(!isset($map[$code]))continue;$stored=$measurementRepo->store($record,$map[$code]);$inserted+=(int)($stored==='inserted');$updated+=(int)($stored==='updated');$unchanged+=(int)($stored==='unchanged');}
        foreach($map as $id)$measurementRepo->refreshState($id);$health->success($provider->getName(),'observation');if($ledger&&$requestId)$ledger->finish($requestId,'success');
        $results[$name]=['status'=>'success','inserted'=>$inserted,'updated'=>$updated,'unchanged'=>$unchanged];$insertedTotal+=$inserted;$updatedTotal+=$updated;$successes++;unset($stations);
    }catch(Throwable $error){
        if($ledger&&$requestId)$ledger->finish($requestId,'failed');$code=$error instanceof ProviderException?$error->providerCode:'COLLECTOR_FAILED';
        if($code==='PROVIDER_DISABLED'){$results[$name]=['status'=>'skipped','reason'=>'disabled'];continue;}
        $health->failure($name,'observation',$code,$error->getMessage());$results[$name]=['status'=>'failed','error_code'=>$code];$failures++;unset($stations);
    }
}

try{(new RiskCoordinator($db))->calculate();}catch(Throwable $error){$run->fail('RISK_COORDINATOR_FAILED',$error->getMessage());fwrite(STDERR,"RISK_COORDINATOR_FAILED\n");exit(1);}
$message=json_encode($results,JSON_UNESCAPED_SLASHES);if($failures===0){$run->finish($insertedTotal,$updatedTotal,$message);}elseif($successes>0){$run->complete('partial',$insertedTotal,$updatedTotal,$message,'PROVIDER_PARTIAL_FAILURE');}else{$run->fail('ALL_PROVIDERS_FAILED',$message);}
echo json_encode(['ok'=>$failures===0,'providers'=>$results],JSON_UNESCAPED_SLASHES)."\n";exit($failures===0?0:($successes>0?2:1));
