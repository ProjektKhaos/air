<?php
// Senast uppdaterad: 2026-09-03 14:20 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg
$current=$station['station']??null;
$official=(bool)($current['affects_official_status']??false);
?>
<section class="page-heading">
 <a class="back-link" href="<?=e(url('stations.php?lang='.locale()))?>">← <?=e(t('nav.stations'))?></a>
 <h1><?=e($current['display_name_'.locale()]??$stationCode)?></h1><p><?=e($stationCode)?></p>
</section>
<?php if($loadError):?><div class="notice notice-unknown"><strong><?=e(t('error.title'))?></strong></div><?php endif;?>
<?php if($current):?>
<section class="card detail-summary <?=$official?'official-detail':'local-detail'?>">
 <div class="card-heading"><div><p class="kicker"><?=e(t($official?'station.official':'station.local'))?></p><strong><?=e(t('source.'.$current['provider']))?></strong></div><span class="badge badge-<?=e($current['freshness_status']??'offline')?>"><?=e(t('freshness.'.($current['freshness_status']??'offline')))?></span></div>
 <div class="aqi-reading"><span><?=e($official?(is_numeric($current['source_aqi']??null)?(string)$current['source_aqi']:'—'):format_pollutant($current['pm25_ug_m3']??null))?></span><small><?=$official?'TH AQI':'PM2.5 µg/m³'?></small></div>
 <?php if(!$official):?><h2><?=e(t('pm25.band.'.\ChiangMaiAirWatch\Services\AirQualityEngine::pm25Band($current['pm25_ug_m3']??null)))?></h2><?php endif;?>
 <div class="pollutant-grid">
 <?php
 $pollutants=$official
   ? [['PM2.5','pm25_ug_m3','µg/m³'],['PM10','pm10_ug_m3','µg/m³'],['O₃','ozone_value',$current['ozone_unit']??'ppb'],['CO','carbon_monoxide_value',$current['carbon_monoxide_unit']??'ppm'],['NO₂','nitrogen_dioxide_value',$current['nitrogen_dioxide_unit']??'ppb'],['SO₂','sulphur_dioxide_value',$current['sulphur_dioxide_unit']??'ppb']]
   : [['PM2.5','pm25_ug_m3','µg/m³'],['PM10','pm10_ug_m3','µg/m³'],[t('home.temperature'),'temperature_c','°C'],[t('home.humidity'),'humidity_pct','%']];
 foreach($pollutants as[$label,$field,$unit]):?><div><span><?=e($label)?></span><strong><?=e(format_pollutant($current[$field]??null))?> <?=e($unit)?></strong></div><?php endforeach;?>
 </div>
 <div class="trend-grid"><?php foreach([1,3,24]as$h):?><div><span><?=$h?> h</span><strong><?=e(trend_display($current['change_'.$h.'h_pm25']??null))?></strong></div><?php endforeach;?></div>
</section>
<section class="card chart-card">
 <div class="card-heading"><h2><?=e(t('home.chart_title'))?></h2><select id="history-period"><?php foreach(['24h','72h','7d','30d','90d','1y','5y']as$period):?><option value="<?=e($period)?>"<?=$period==='72h'?' selected':''?>><?=e($period)?></option><?php endforeach;?></select></div>
 <p id="aggregation-label" class="muted"><?=e($station['aggregation'])?></p><div class="chart-wrap tall"><canvas id="station-chart" data-station="<?=e($stationCode)?>"></canvas><p id="chart-empty" hidden><?=e(t('home.chart_empty'))?></p></div>
</section>
<?php endif;?>
