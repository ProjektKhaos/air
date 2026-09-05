'use strict';
(()=>{const A=window.CMAW;const select=document.getElementById('home-station-select');const cacheKey='cmaw-offline-snapshot-'+A.language;const card=document.getElementById('selected-station-card');const snapshotTime=document.getElementById('offline-snapshot-time');let chart;let payload;let chartStation;let offline=false;let historyRequest=0;
const severity=s=>{if(!card)return;[...card.classList].filter(c=>c.startsWith('severity-')).forEach(c=>card.classList.remove(c));card.classList.add('severity-'+(s.source.official?s.observation.category:s.observation.pm25_band));};
const showStation=s=>{if(!s)return;const official=s.source.official;card.dataset.classification=official?'official':'supplementary';severity(s);document.getElementById('selected-kicker').textContent=card.dataset.labelSelected+' · '+(official?card.dataset.labelOfficial:card.dataset.labelLocal);document.getElementById('primary-title').textContent=s.name;document.getElementById('primary-reading').textContent=official?(s.observation.aqi??'—'):(s.observation.pm25_ug_m3??'—');document.getElementById('primary-reading-label').textContent=official?'TH AQI':'PM2.5 µg/m³';document.getElementById('primary-category').textContent=official?s.observation.category_label:s.observation.pm25_band_label;document.getElementById('aqi-unavailable').hidden=!official||s.observation.aqi!==null;document.getElementById('primary-pm25').textContent=(s.observation.pm25_ug_m3??'—')+' µg/m³';document.getElementById('primary-pm10').textContent=(s.observation.pm10_ug_m3??'—')+' µg/m³';document.getElementById('primary-trend').textContent=A.trend(s.trends.pm25_1h);document.getElementById('primary-time').textContent=A.time(s.observation.measured_at);document.getElementById('primary-source').textContent=s.source.label;const badge=document.getElementById('primary-freshness');badge.className='badge badge-'+(offline?'offline':s.observation.freshness);badge.textContent=offline?'OFFLINE':s.observation.freshness_label;};
const render=(p,savedAt=null)=>{payload=p;updateDashboard(p.data);const stations=p.data.stations||[];const codes=stations.map(s=>s.id);const id=A.home(codes);if(select)select.value=id;showStation(stations.find(s=>s.id===id)||stations[0]);const official=p.data.area?.station_public_id||stations.find(s=>s.source?.official)?.id;if(official&&!offline){chartStation=official;document.getElementById('air-chart').dataset.station=official;loadHistory(official);}if(snapshotTime){snapshotTime.hidden=!savedAt;if(savedAt)snapshotTime.textContent=snapshotTime.dataset.label.replace('{time}',A.time(savedAt));}};
const load=async()=>{try{const r=await fetch(A.base+'api/current.php?lang='+A.language,{cache:'no-store'});const p=await r.json();if(!r.ok||!p.ok)throw 0;offline=false;render(p);try{localStorage.setItem(cacheKey,JSON.stringify({snapshot_saved_at:new Date().toISOString(),payload:p}));}catch{}A.failure('current',false);}catch{offline=true;historyRequest++;A.failure('current',true);if(payload)render(payload);try{const stored=JSON.parse(localStorage.getItem(cacheKey));const p=stored?.payload||stored;if(p){render(p,stored?.snapshot_saved_at||null);const b=document.getElementById('primary-freshness');b.className='badge badge-offline';b.textContent='OFFLINE';}}catch{}}};
const loadHistory=async id=>{const request=++historyRequest;try{const r=await fetch(A.base+'api/history.php?station='+encodeURIComponent(id)+'&period=24h',{cache:'no-store'});const p=await r.json();if(!r.ok||!p.ok)throw 0;if(request!==historyRequest)return;draw(p.data.points);A.failure('history',false);}catch{if(request===historyRequest)A.failure('history',true);}};
const draw=points=>{const c=document.getElementById('air-chart');if(!c||!window.Chart)return;const valid=points||[];document.getElementById('chart-empty').hidden=valid.length>0;if(chart)chart.destroy();const q=A.palette();chart=new Chart(c,{type:'line',data:{labels:valid.map(p=>A.time(p.measured_at)),datasets:[{label:'PM2.5',data:valid.map(p=>p.pm25_ug_m3),borderColor:q.line,pointRadius:0,spanGaps:false,tension:.25},{label:'PM10',data:valid.map(p=>p.pm10_ug_m3),borderColor:q.second,pointRadius:0,spanGaps:false,tension:.25}]},options:{responsive:true,maintainAspectRatio:false,animation:false,interaction:{mode:'index',intersect:false},scales:{x:{ticks:{maxTicksLimit:5,color:q.tick},grid:{display:false}},y:{ticks:{color:q.tick,callback:v=>v+' µg/m³'},grid:{color:q.grid}}}}});};

const updateDashboard=d=>{
 if(!d.labels)return;
 const text=(selector,value)=>{const node=document.querySelector(selector);if(node)node.textContent=value;};
 const tone=(selector,value)=>{const node=document.querySelector(selector);if(node){[...node.classList].filter(c=>c.startsWith('severity-')).forEach(c=>node.classList.remove(c));node.classList.add('severity-'+value);}};
 text('.official-area-card h2',d.labels.area+(d.area.source_aqi===null?'':' · TH AQI '+d.area.source_aqi));
 const responsible=d.stations.find(s=>s.id===d.area.station_public_id);
 text('.official-area-card > div:last-child > p:last-child',(responsible?.name||d.labels.area)+' · '+(responsible?.source.label||'Air4Thai / PCD'));
 tone('.official-area-card',d.area.status);
 if(responsible)text('.chart-card h2',responsible.name);
 const local=d.local_sensor_summary;
 const summaryGrid=document.querySelector('.local-summary-grid');if(summaryGrid)summaryGrid.hidden=!local.valid_pm25_count;const empty=document.querySelector('.local-empty');if(empty)empty.hidden=local.valid_pm25_count>0;
 text('.local-summary-card .badge',local.live_count+' '+d.labels.online);
 const localBadge=document.querySelector('.local-summary-card .badge');if(localBadge)localBadge.className='badge badge-'+(offline?'offline':local.live_count?'live':'offline');
 const values=[local.median_pm25,local.min_pm25,local.max_pm25];
 document.querySelectorAll('.local-summary-grid strong').forEach((node,i)=>node.textContent=i===3?A.trend(local.median_change_1h):(values[i]??'—')+' µg/m³');
 text('.local-summary-card > p:last-child',d.labels.valid+': '+local.valid_pm25_count+' / '+local.live_count+' · '+d.labels.delayed+': '+(local.delayed_count+local.stale_count));
 text('.forecast-risk strong',d.labels.forecast);
 text('.forecast-risk span',(d.forecast.windows?.[24]?.mean??'—')+' µg/m³');
 text('.forecast-risk + p',d.labels.direction);tone('.forecast-risk',d.forecast.severity);
 text('.advisory h2',d.labels.advisory_severity);text('.advisory h2 + p',d.labels.advisory);tone('.advisory',d.advisory.severity);
 document.querySelectorAll('.station-row').forEach(row=>{
  const id=new URL(row.href).searchParams.get('code');const s=d.stations.find(s=>s.id===id);if(!s)return;
  row.querySelector('.station-value strong').textContent=s.source.official?'TH AQI '+(s.observation.aqi??'—'):'PM2.5 '+(s.observation.pm25_ug_m3??'—');
  const badge=row.querySelector('.badge');badge.textContent=offline?'OFFLINE':s.observation.freshness_label;badge.className='badge badge-inline badge-'+(offline?'offline':s.observation.freshness);
 });
 const weather=d.weather;const grid=document.querySelector('.weather-context-grid');
 if(grid){grid.hidden=!weather;if(weather){const v=[(weather.wind_direction_compass||'—')+' '+(weather.wind_speed_kmh??'—')+' km/h',(weather.wind_gust_kmh??'—')+' km/h',(weather.precipitation_mm??'—')+' mm',(weather.temperature_c??'—')+' °C'];grid.querySelectorAll('strong').forEach((node,i)=>node.textContent=v[i]);}}
 text('.context-card .muted',weather?A.time(weather.observed_at):d.labels.weather_unavailable);
};

addEventListener('offline',()=>{offline=true;historyRequest++;if(payload)render(payload);});
addEventListener('online',load);
select?.addEventListener('change',()=>{A.setHome(select.value);if(payload)showStation(payload.data.stations.find(s=>s.id===select.value));});load();setInterval(load,300000);})();
