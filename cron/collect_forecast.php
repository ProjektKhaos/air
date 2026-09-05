<?php
// Senast uppdaterad: 2026-09-02 20:15 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

use ChiangMaiAirWatch\Config;
use ChiangMaiAirWatch\Database;
use ChiangMaiAirWatch\Providers\MockAirForecastProvider;
use ChiangMaiAirWatch\Providers\OpenMeteoAirProvider;
use ChiangMaiAirWatch\Providers\ProviderException;
use ChiangMaiAirWatch\Repositories\ForecastRepository;
use ChiangMaiAirWatch\Repositories\ProviderHealthRepository;
use ChiangMaiAirWatch\Services\CollectorLock;
use ChiangMaiAirWatch\Services\CollectorRun;
use ChiangMaiAirWatch\Services\RiskCoordinator;

require dirname(__DIR__).'/app/bootstrap.php';
$lock=new CollectorLock();if(!$lock->acquire('collect-forecast')){fwrite(STDERR,"collector already running\n");exit(0);}
$db=Database::connection();$run=new CollectorRun($db,'collect_forecast');$health=new ProviderHealthRepository($db);$name=(string)Config::get('providers.forecast','openmeteo_air');
try{$provider=$name==='mock_air'?new MockAirForecastProvider():new OpenMeteoAirProvider();$repo=new ForecastRepository($db);$inserted=0;foreach($provider->fetchForecast($repo->zones())as$forecast)$inserted+=(int)($repo->storeRun($forecast)==='inserted');(new RiskCoordinator($db))->calculate();$health->success($provider->getName(),'forecast');$run->finish($inserted,0);echo json_encode(['ok'=>true,'inserted'=>$inserted])."\n";}
catch(Throwable $e){$code=$e instanceof ProviderException?$e->providerCode:'COLLECTOR_FAILED';$health->failure($name,'forecast',$code,$e->getMessage());$run->fail($code,$e->getMessage());fwrite(STDERR,$code.': '.$e->getMessage()."\n");exit(1);}
