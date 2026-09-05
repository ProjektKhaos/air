<?php
// Senast uppdaterad: 2026-09-04 15:40 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

use ChiangMaiAirWatch\Database;
use ChiangMaiAirWatch\Providers\DustBoyProvider;
use ChiangMaiAirWatch\Providers\ProviderException;
use ChiangMaiAirWatch\Repositories\DailySummaryRepository;
use ChiangMaiAirWatch\Repositories\MeasurementRepository;
use ChiangMaiAirWatch\Repositories\StationRepository;
use ChiangMaiAirWatch\Services\CollectorLock;
use ChiangMaiAirWatch\Services\DustBoyRequestLedger;

require dirname(__DIR__).'/app/bootstrap.php';if(PHP_SAPI!=='cli'){http_response_code(404);exit;}foreach(array_slice($argv,1)as$argument){if($argument!=='--all'&&!preg_match('/^--(?:station|period)=[^=]+$/',$argument)){fwrite(STDERR,"Usage: backfill_dustboy.php (--station=dustboy:ID|--all) --period=30d|1y|5y\n");exit(64);}}$options=getopt('',['station:','period:','all']);$period=(string)($options['period']??'');if(!in_array($period,['30d','1y','5y'],true)){fwrite(STDERR,"Use --period=30d|1y|5y\n");exit(64);}$all=array_key_exists('all',$options);$publicId=(string)($options['station']??'');if($all===($publicId!=='')){fwrite(STDERR,"Use exactly one of --station=dustboy:ID or --all\n");exit(64);}
$lock=new CollectorLock();if(!$lock->acquire('backfill-dustboy'))exit(75);$db=Database::connection();$stationRepo=new StationRepository($db);$stations=$all?$stationRepo->forProvider('dustboy'):array_filter([$stationRepo->byPublicId($publicId)]);if($stations===[]){fwrite(STDERR,"No enabled DustBoy station found\n");exit(64);}foreach($stations as $station)if($station['provider']!=='dustboy'){fwrite(STDERR,"Only dustboy:* stations are accepted\n");exit(64);}
$provider=new DustBoyProvider();$ledger=new DustBoyRequestLedger($db);$measurements=new MeasurementRepository($db);$summaries=new DailySummaryRepository($db);$results=[];
foreach($stations as $station){$code=(string)$station['provider_station_code'];try{$requestId=$ledger->reserve('history_'.$period);try{$rows=$provider->fetchHistoryPeriod($code,$period);$ledger->finish($requestId,'success');}catch(Throwable $error){$ledger->finish($requestId,'failed');throw $error;}$counts=['inserted'=>0,'updated'=>0,'unchanged'=>0];$from=null;$to=null;foreach($rows as $row){$stored=$measurements->store($row,(int)$station['id']);$counts[$stored]++;$date=substr((string)$row['measured_at'],0,10);$from=$from===null||$date<$from?$date:$from;$to=$to===null||$date>$to?$date:$to;}if($from&&$to)$summaries->rebuild($from,$to,(int)$station['id']);$measurements->refreshState((int)$station['id']);$results[$code]=['status'=>'success','records'=>count($rows)]+$counts;}catch(Throwable $error){$codeName=$error instanceof ProviderException?$error->providerCode:'BACKFILL_FAILED';$results[$code]=['status'=>'failed','error_code'=>$codeName];echo json_encode(['ok'=>false,'period'=>$period,'stations'=>$results],JSON_UNESCAPED_SLASHES)."\n";exit($codeName==='RATE_LIMITED'?75:1);}}
echo json_encode(['ok'=>true,'period'=>$period,'stations'=>$results],JSON_UNESCAPED_SLASHES)."\n";
