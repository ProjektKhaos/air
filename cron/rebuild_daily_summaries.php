<?php
// Senast uppdaterad: 2026-09-03 13:40 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

use ChiangMaiAirWatch\Database;
use ChiangMaiAirWatch\Repositories\DailySummaryRepository;
use ChiangMaiAirWatch\Repositories\StationRepository;
use ChiangMaiAirWatch\Services\CollectorLock;

require dirname(__DIR__).'/app/bootstrap.php';if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
foreach(array_slice($argv,1)as$argument){if(!preg_match('/^--(?:days|station|from|to)=[^=]+$/',$argument)){fwrite(STDERR,"Usage: rebuild_daily_summaries.php --days=N [--station=provider:id] or --from=YYYY-MM-DD --to=YYYY-MM-DD\n");exit(64);}}$options=getopt('',['days:','station:','from:','to:']);$today=gmdate('Y-m-d');$from=$options['from']??null;$to=$options['to']??$today;
if(isset($options['days'])){$days=filter_var($options['days'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>3660]]);if($days===false){fwrite(STDERR,"Invalid --days\n");exit(64);}$from=gmdate('Y-m-d',time()-(((int)$days-1)*86400));}
if(!is_string($from)||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$from)||!is_string($to)||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$to)||$from>$to){fwrite(STDERR,"Use --days=N or --from=YYYY-MM-DD --to=YYYY-MM-DD\n");exit(64);}
$lock=new CollectorLock();if(!$lock->acquire('rebuild-daily-summaries'))exit(75);$db=Database::connection();$stationId=null;if(isset($options['station'])){$station=(new StationRepository($db))->byPublicId((string)$options['station']);if(!$station){fwrite(STDERR,"Station not found\n");exit(64);}$stationId=(int)$station['id'];}
$affected=(new DailySummaryRepository($db))->rebuild($from,$to,$stationId);echo json_encode(['ok'=>true,'from'=>$from,'to'=>$to,'station'=>$options['station']??null,'affected'=>$affected],JSON_UNESCAPED_SLASHES)."\n";
