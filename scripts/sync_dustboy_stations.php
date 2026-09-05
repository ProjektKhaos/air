<?php
// Senast uppdaterad: 2026-09-03 13:40 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

use ChiangMaiAirWatch\Config;
use ChiangMaiAirWatch\Database;
use ChiangMaiAirWatch\Providers\DustBoyProvider;
use ChiangMaiAirWatch\Repositories\StationRepository;
use ChiangMaiAirWatch\Services\CollectorLock;
use ChiangMaiAirWatch\Services\DustBoyRequestLedger;
use ChiangMaiAirWatch\Services\DustBoyStationSync;

require dirname(__DIR__).'/app/bootstrap.php';
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}foreach(array_slice($argv,1)as$argument){if($argument!=='--apply'){fwrite(STDERR,"Usage: sync_dustboy_stations.php [--apply]\n");exit(64);}}$apply=in_array('--apply',$argv,true);$lock=new CollectorLock();if(!$lock->acquire('sync-dustboy-stations')){fwrite(STDERR,"sync already running\n");exit(75);}
$db=Database::connection();$ledger=new DustBoyRequestLedger($db);$requestId=$ledger->reserve('stations');
try{$rows=(new DustBoyProvider())->fetchSelectedStations();$ledger->finish($requestId,'success');}catch(Throwable $error){$ledger->finish($requestId,'failed');fwrite(STDERR,"DustBoy station sync failed: ".($error instanceof \ChiangMaiAirWatch\Providers\ProviderException?$error->providerCode:'SYNC_FAILED')."\n");exit(1);}
$result=(new DustBoyStationSync((array)Config::get('providers.dustboy',[])))->synchronize($rows,new StationRepository($db),$apply);
echo json_encode(['ok'=>true]+$result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)."\n";
