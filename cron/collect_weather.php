<?php
// Senast uppdaterad: 2026-09-03 13:05 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

use ChiangMaiAirWatch\Config;
use ChiangMaiAirWatch\Database;
use ChiangMaiAirWatch\Providers\OpenMeteoWeatherProvider;
use ChiangMaiAirWatch\Providers\ProviderException;
use ChiangMaiAirWatch\Repositories\ProviderHealthRepository;
use ChiangMaiAirWatch\Repositories\WeatherRepository;
use ChiangMaiAirWatch\Services\CollectorLock;
use ChiangMaiAirWatch\Services\CollectorRun;

require dirname(__DIR__).'/app/bootstrap.php';
$lock=new CollectorLock();if(!$lock->acquire('collect-weather')){fwrite(STDERR,"collector already running\n");exit(0);}
$db=Database::connection();$run=new CollectorRun($db,'collect_weather');$health=new ProviderHealthRepository($db);$provider=new OpenMeteoWeatherProvider();
try{$zone=(array)Config::get('forecast_zone');$weather=$provider->fetchCurrent($zone);(new WeatherRepository($db))->store($weather);$health->success($provider->getName(),'weather');$run->finish(1,0,'weather context collected');echo json_encode(['ok'=>true,'source_time'=>$weather['source_time']],JSON_UNESCAPED_SLASHES)."\n";}
catch(Throwable $error){$code=$error instanceof ProviderException?$error->providerCode:'COLLECTOR_FAILED';if($code==='PROVIDER_DISABLED'){$run->finish(0,0,'weather provider disabled');echo json_encode(['ok'=>true,'status'=>'skipped','reason'=>'disabled'])."\n";exit(0);}$health->failure($provider->getName(),'weather',$code,$error->getMessage());$run->fail($code,$error->getMessage());fwrite(STDERR,$code."\n");exit(1);}
