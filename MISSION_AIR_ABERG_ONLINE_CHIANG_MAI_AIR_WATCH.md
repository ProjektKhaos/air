# CODEX MASTER MISSION — Chiang Mai Air Watch

**Mission ID:** CMAW-001  
**Version:** 1.00  
**Created:** 2026-09-02  
**Timezone:** Asia/Bangkok  
**Target:** Codex  
**Production URL:** `https://air.aberg.online`  
**Scope:** Air quality monitoring for **Mueang Chiang Mai District, Chiang Mai, Thailand**  
**Reference application:** the supplied `ping/` project from `air.zip` — current Ping Flood Watch codebase

---

# 1. Mission objective

Create a new production-ready web application/PWA for monitoring air quality in **Mueang Chiang Mai**.

The new application must deliberately reuse the **same visual language, layout, UX principles, architecture quality and operational standard** as the existing Ping Flood Watch application supplied in this mission package.

The new application must be deployed under:

```text
https://air.aberg.online
```

Working product name:

```text
Chiang Mai Air Watch
```

The name must be centralized in translations/configuration so it can be renamed later without searching through the codebase.

The application must let the user understand the current air situation within a few seconds:

- current Thai AQI / air-quality category,
- current PM2.5,
- PM10 and other pollutants when supplied by the source,
- whether air quality is improving or deteriorating,
- latest measurement time and freshness,
- history for the last 24 hours and longer periods,
- overview of monitoring stations in Mueang Chiang Mai,
- station map,
- air-quality forecast kept clearly separate from measured observations,
- a simple combined advisory based on observations + forecast,
- alerts and optional Web Push notifications,
- provider/system health,
- offline/PWA support.

This must be a **new independent application**, not a destructive transformation of Ping Flood Watch.

---

# 2. Non-negotiable safety rule for the existing Ping application

The existing Ping Flood Watch application is production software and is the reference implementation.

Codex MUST:

- inspect it,
- reuse/copy appropriate code,
- reuse design patterns,
- reuse infrastructure patterns,
- reuse test patterns,
- reuse hardening patterns,

but MUST NOT:

- modify the live Ping Flood Watch application,
- alter its production database,
- rename its tables,
- change its Apache vhost,
- change its cron jobs,
- reuse its production VAPID secrets,
- reuse its database credentials,
- reuse the same service-worker cache names,
- create cross-dependencies that can break `air.aberg.online`.

The two applications must be independently deployable and independently removable.

---

# 3. First task — inspect before coding

Before changing or creating production files, inspect the supplied Ping Flood Watch project and document the reusable architecture.

At minimum inspect:

```text
README.md
index.php
stations.php
station.php
alerts.php
app/
app/views/
app/lang/
app/Providers/
app/Repositories/
app/Services/
api/
assets/css/app.css
assets/js/
cron/
config/
scripts/
sql/
docs/
manifest.webmanifest
sw.js
composer.json
package.json
phpunit.xml
playwright.config.js
```

Create:

```text
docs/REFERENCE_PING_ARCHITECTURE.md
```

Document:

1. which files/patterns will be reused,
2. which flood-specific concepts must be replaced,
3. which components can be copied nearly unchanged,
4. which production-hardening mechanisms must remain,
5. which secrets/config paths must be unique.

Do not blindly search/replace `Ping` → `Air`. Understand responsibilities first.

---

# 4. Technical baseline

Match the reference application's production stack unless the installed environment requires a compatible adjustment.

Target stack:

- PHP 8.2+; prefer the same PHP version used by the server/reference deployment,
- Apache 2.4,
- MariaDB/MySQL via PDO,
- Composer,
- vanilla JavaScript,
- Chart.js,
- Leaflet + Leaflet.markercluster,
- OpenStreetMap tiles,
- PWA manifest + service worker,
- Web Push via `minishlink/web-push`,
- cron for scheduled collection,
- PHPUnit,
- Playwright + axe accessibility testing.

Reuse the same font strategy as Ping Flood Watch, including Thai font support.

No framework migration is part of this mission.

---

# 5. Portability requirements

Hasse's standard portability rules apply.

The project directory is the DocumentRoot from the application's point of view.

The source code must NOT hardcode:

- project folder names,
- `/air/` URL prefixes,
- server DocumentRoot paths,
- `air.aberg.online` in ordinary application links,
- absolute asset paths.

Use the same `BASE_URL` / `url()` pattern as Ping Flood Watch.

Production may use:

```php
'app' => [
    'base_url' => '/',
    'public_origin' => 'https://air.aberg.online',
]
```

but all internal links/assets/API endpoints must continue to work if the project is later moved into a subdirectory.

---

# 6. File version standard

All NEW PHP and HTML files must follow Hasse's project convention:

- line 1: language opening as required, e.g. `<?php`,
- line 2: a clear version/update comment.

Recommended PHP example:

```php
// Senast uppdaterad: 2026-09-02 18:00 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg
```

Update that line when the file is materially changed.

Add useful pedagogical comments where logic may later need to be understood or changed. Do not fill trivial code with noise.

---

# 7. Naming and namespace

Use a unique namespace, for example:

```text
ChiangMaiAirWatch\
```

Suggested Composer package name:

```text
abergonline/chiang-mai-air-watch
```

Do not retain `ChiangMaiAirWatch\` in new application classes.

Recommended unique identifiers:

```text
Application: Chiang Mai Air Watch
Short name: Air Watch
DB: chiang_mai_air_watch
Runtime DB user: cmaw_runtime@localhost
External config directory: /etc/chiang-mai-air-watch/
Backup directory: /var/backups/chiang-mai-air-watch/
Service-worker cache prefix: chiang-mai-air-watch-
```

If the actual server has an established better naming convention, follow it consistently and document it.

---

# 8. Geographic scope

The application's normal scope is:

```text
Mueang Chiang Mai District
Chiang Mai Province
Thailand
```

Do NOT accidentally include every station in Chiang Mai Province.

Station discovery must distinguish:

- province = Chiang Mai,
- district/amphoe = Mueang Chiang Mai.

Because provider metadata may be inconsistent, do not rely on one fragile English string match.

During provider research:

1. inspect station metadata,
2. identify candidate Mueang Chiang Mai stations,
3. verify names and coordinates,
4. create an explicit configured/seeded allowlist for production,
5. document why each station is included,
6. store provider station IDs rather than relying only on names.

The data model must still support future expansion to adjacent districts or all Chiang Mai Province.

---

# 9. Observation providers — strict separation from forecasts

## 9.1 Primary target: Air4Thai / Pollution Control Department

The preferred official observation source is **Air4Thai**, operated by Thailand's Pollution Control Department (PCD).

Known historical/current public endpoint family to validate:

```text
https://air4thai.pcd.go.th/services/getNewAQI_JSON.php
```

Important: this endpoint has changed/broken at times and must NOT be assumed to be permanently stable.

Before production implementation, Codex must perform live provider validation and document:

- working canonical endpoint,
- HTTPS behavior,
- status code,
- content type,
- response size,
- response structure,
- station IDs,
- station coordinates,
- timestamp format/timezone,
- pollutant units,
- AQI field meaning,
- update interval,
- missing/null conventions,
- rate limits/terms if published,
- source attribution requirements.

Create:

```text
docs/PROVIDER_VALIDATION_AIR4THAI.md
```

If the legacy endpoint is unavailable, investigate the currently operating official Air4Thai service/API rather than scraping a random third-party AQI website.

Implement Air4Thai behind an adapter, for example:

```text
app/Providers/AirQualityProviderInterface.php
app/Providers/Air4ThaiProvider.php
```

The UI must NEVER access Air4Thai directly.

## 9.2 Optional/strongly recommended local provider: CMU CCDC DustBoy

Chiang Mai University's Climate Change Data Center exposes the DustBoy API with local PM2.5 monitoring stations.

Current documented model:

```text
Authorization: Bearer {api_key}
GET /api/dustboy/station
GET /api/dustboy/province
GET /api/dustboy/stations
GET /api/dustboy/nearme/{latitude}/{longitude}/{distance}
GET /api/dustboy/data30day/{id}
GET /api/dustboy/data1year/{id}
GET /api/dustboy/data5year/{id}
```

The public account documentation currently states that general users can retrieve up to a limited number of installation points and requires source attribution.

Implement support as an OPTIONAL second adapter:

```text
app/Providers/DustBoyProvider.php
```

Configuration:

```php
'dustboy' => [
    'enabled' => false,
    'api_key' => null,
]
```

Never place the DustBoy API key in the webroot, Git repository, JS or public API output.

If no API key is available during development:

- implement the adapter contract,
- provide sanitized fixture data,
- keep it disabled in production config,
- clearly mark live acceptance as pending.

Do not block V1 deployment solely because DustBoy credentials are unavailable if Air4Thai works.

## 9.3 Development mock provider

Implement a deterministic mock provider for tests and local development.

Mock data must be unmistakably marked as demo/test data and must never be selected by production config.

---

# 10. Forecast provider

Use a forecast/model source through a SEPARATE provider interface.

Recommended initial provider:

```text
Open-Meteo Air Quality API
https://air-quality-api.open-meteo.com/v1/air-quality
```

Validate its current documentation and terms at implementation time.

For Chiang Mai/Thailand, understand that Open-Meteo air-quality forecast data is based on atmospheric models, not a physical sensor at the requested coordinate.

The UI MUST label model data as forecast/model data.

Do not display it with a `LIVE` sensor badge.

Recommended requested variables, subject to live API validation:

```text
pm2_5
pm10
ozone
nitrogen_dioxide
sulphur_dioxide
carbon_monoxide
us_aqi
us_aqi_pm2_5
us_aqi_pm10
```

Use:

```text
timezone=Asia/Bangkok
```

Store model values independently from observations.

Create:

```text
app/Providers/AirForecastProviderInterface.php
app/Providers/OpenMeteoAirProvider.php
```

Never merge forecast records into the observation table.

---

# 11. AQI scale — Thailand standard is primary

The application's primary public AQI scale must be **Thailand AQI (TH AQI)**.

Use current official Pollution Control Department criteria and document the source/version.

Current 2023-era PM2.5 24-hour breakpoint guidance to verify before production:

| TH AQI | PM2.5 24h µg/m³ | Meaning |
|---:|---:|---|
| 0–25 | 0–15.0 | Very good |
| 26–50 | 15.1–25.0 | Good |
| 51–100 | 25.1–37.5 | Moderate |
| 101–200 | 37.6–75.0 | Affects health / unhealthy |
| >200 | >75.0 | Strong health impact / very unhealthy |

The official Thai AQI uses several pollutants and different averaging windows.

Rules:

1. If Air4Thai supplies an official AQI value, preserve and prefer that value.
2. Store the source AQI unchanged with scale metadata.
3. Do not silently replace a source AQI with a locally calculated US AQI.
4. If locally deriving TH AQI, only do so from data/averaging windows sufficient for the official calculation.
5. Document the interpolation formula and breakpoint version.
6. Unit-test every breakpoint and boundary.
7. Never compare a TH AQI number directly with a US AQI number as though they were the same scale.

Open-Meteo's native US AQI may be stored and shown in a clearly labelled forecast/detail context, but it must not silently become the application's main Thai AQI.

---

# 12. Proposed application structure

Keep the structure intentionally close to Ping Flood Watch so maintenance remains familiar.

Suggested structure:

```text
/
├── index.php
├── stations.php
├── station.php
├── alerts.php
├── offline.php
├── manifest.webmanifest
├── sw.js
├── composer.json
├── package.json
│
├── app/
│   ├── bootstrap.php
│   ├── Config.php
│   ├── Database.php
│   ├── HttpClient.php
│   ├── Logger.php
│   ├── Api.php
│   ├── ApiPresenter.php
│   ├── View.php
│   ├── Translator.php
│   ├── helpers.php
│   ├── config.example.php
│   │
│   ├── Providers/
│   │   ├── ProviderException.php
│   │   ├── AirQualityProviderInterface.php
│   │   ├── AirForecastProviderInterface.php
│   │   ├── Air4ThaiProvider.php
│   │   ├── DustBoyProvider.php
│   │   ├── OpenMeteoAirProvider.php
│   │   ├── MockAirQualityProvider.php
│   │   └── MockAirForecastProvider.php
│   │
│   ├── Repositories/
│   │   ├── StationRepository.php
│   │   ├── MeasurementRepository.php
│   │   ├── ForecastRepository.php
│   │   ├── RiskStateRepository.php
│   │   ├── AlertRepository.php
│   │   ├── ProviderHealthRepository.php
│   │   ├── PushSubscriptionRepository.php
│   │   └── NotificationOutboxRepository.php
│   │
│   ├── Services/
│   │   ├── DashboardService.php
│   │   ├── AirQualityEngine.php
│   │   ├── ForecastRiskEngine.php
│   │   ├── AdvisoryEngine.php
│   │   ├── TrendCalculator.php
│   │   ├── AlertManager.php
│   │   ├── HealthService.php
│   │   ├── CollectorRun.php
│   │   ├── CollectorLock.php
│   │   ├── NotificationDispatcher.php
│   │   ├── NotificationPayloadFactory.php
│   │   ├── RateLimiter.php
│   │   └── VisitorCounter.php
│   │
│   ├── lang/
│   │   ├── en.php
│   │   └── th.php
│   │
│   └── views/
│       ├── home.php
│       ├── stations.php
│       ├── station.php
│       ├── alerts.php
│       └── partials/
│           ├── header.php
│           └── footer.php
│
├── api/
│   ├── _bootstrap.php
│   ├── current.php
│   ├── history.php
│   ├── stations.php
│   ├── forecast.php
│   ├── alerts.php
│   ├── status.php
│   ├── health.php
│   ├── push-subscribe.php
│   └── push-unsubscribe.php
│
├── cron/
│   ├── collect-air.php
│   ├── collect-forecast.php
│   ├── dispatch_push.php
│   └── retention.php
│
├── assets/
│   ├── css/app.css
│   ├── js/app.js
│   ├── js/home.js
│   ├── js/stations.js
│   ├── js/station.js
│   ├── js/alerts.js
│   ├── js/push.js
│   ├── js/offline.js
│   ├── js/theme-init.js
│   ├── icons/
│   └── vendor/
│
├── sql/
│   ├── schema.sql
│   └── seed.sql
│
├── scripts/
│   ├── migrate.sh
│   ├── backup.sh
│   ├── build-assets.sh
│   ├── build-icons.php
│   ├── configure-vapid.php
│   ├── provision-databases.sh
│   └── install-ops.sh
│
├── config/
│   ├── apache/
│   ├── cron/
│   └── logrotate/
│
├── storage/
│   ├── cache/
│   ├── locks/
│   └── logs/
│
├── tests/
│
└── docs/
    ├── INSTALL.md
    ├── API.md
    ├── PROVIDERS.md
    ├── AQI.md
    ├── ALERTS.md
    ├── TEST_REPORT.md
    ├── REFERENCE_PING_ARCHITECTURE.md
    ├── PROVIDER_VALIDATION_AIR4THAI.md
    └── PROVIDER_VALIDATION_OPEN_METEO.md
```

Adapt where the existing Ping architecture has a demonstrably better current pattern.

---

# 13. Database design

Build a clean MariaDB schema with migration tracking.

Minimum concepts:

## `schema_migrations`

Same pattern as Ping Flood Watch.

## `stations`

Recommended fields:

```text
id
provider
provider_station_code
display_name_en
display_name_th
area_en
area_th
province_en
province_th
district_en
district_th
latitude
longitude
station_type
is_primary
enabled
sort_order
source_metadata_json
created_at
updated_at
```

A station from Air4Thai and a DustBoy sensor must remain distinguishable by provider.

## `measurements`

Recommended normalized fields:

```text
id
station_id
provider
measured_at
received_at
source_aqi
source_aqi_scale
th_aqi
pm25_ug_m3
pm10_ug_m3
ozone_value
ozone_unit
carbon_monoxide_value
carbon_monoxide_unit
nitrogen_dioxide_value
nitrogen_dioxide_unit
sulphur_dioxide_value
sulphur_dioxide_unit
temperature_c
humidity_pct
source_status
source_hash
raw_payload_json
created_at
updated_at
```

Not every provider supplies every pollutant. Null is correct. Do not manufacture values.

Preserve raw payload only server-side for traceability. Never expose raw provider payloads through public APIs.

## `measurement_revisions`

Reuse Ping's principle: if the same station+measurement timestamp later arrives with a materially changed value, preserve a revision before replacement.

## `forecast_zones`

Initially define one primary Mueang Chiang Mai forecast point and allow more later.

## `forecast_runs`

Store provider, received time, optional model/run time, payload hash and sanitized/raw server-side payload.

## `air_forecast_points`

Fields should support:

```text
forecast_run_id
forecast_zone_id
valid_at
pm25_ug_m3
pm10_ug_m3
ozone_ug_m3
no2_ug_m3
so2_ug_m3
co_ug_m3
us_aqi
us_aqi_pm25
us_aqi_pm10
source_status
```

## Reuse hardening tables/patterns from Ping

Reuse/adapt:

```text
provider_health
risk_state
alerts
alert_events
collector_runs
push_subscriptions
notification_outbox
push_deliveries
api_rate_limits
```

Keep table definitions specific to this database, not shared with Ping.

---

# 14. Time handling

All provider timestamps must be traceable.

Rules:

- interpret source timestamps using the source's documented timezone,
- normalize stored timestamps consistently, preferably UTC,
- display in `Asia/Bangkok`,
- retain source timestamp text or enough metadata to audit parsing,
- never assume UTC merely because a source string is ambiguous,
- add unit tests around timezone conversion.

The UI should show local Chiang Mai time.

---

# 15. Data freshness

Observation freshness must be based on measurement time, not merely the time our cron fetched it.

Initial configurable defaults for hourly observation sources:

```text
LIVE: <= 90 minutes old
DELAYED: > 90 and <= 180 minutes
STALE: > 180 minutes
OFFLINE: no usable stored observation / provider unavailable with no retained value
```

Do not hardcode these values throughout the code. Put them in central configuration.

If provider update cadence proves different during live validation, adjust and document it.

Provider failure must NEVER turn an old reading into a fake fresh/normal state.

---

# 16. Main dashboard — same design as Ping Flood Watch

The visual target is not a redesign.

Start from the current Ping Flood Watch mobile dashboard design and translate its information architecture to air quality.

Preserve:

- overall mobile shell,
- header proportions,
- typography,
- spacing,
- card radius,
- card shadows/borders,
- dark/light/system theme behavior,
- language switch,
- status badges,
- responsive behavior,
- desktop centered-shell behavior,
- bottom/primary navigation style,
- Chart.js visual style,
- risk/advisory card visual treatment,
- updated timestamp placement,
- accessibility focus styles,
- PWA feel.

Do not make a generic new Bootstrap admin dashboard.

## Suggested home hierarchy

```text
AIR QUALITY · MUEANG CHIANG MAI
Air conditions                       ●

[station picker]

CURRENT
<Primary station name>               LIVE

42
TH AQI
Moderate

PM2.5      PM10       1-hour trend
31.8       48         +3.2 µg/m³
µg/m³      µg/m³

AIR QUALITY
MODERATE
Sensitive groups should monitor symptoms and reduce prolonged outdoor activity if affected.

24 hour trend
[Chart]

Monitoring stations
[station rows]

FORECAST RISK
MODERATE
Model forecast may worsen during the next 24 hours.

Next 24 hours
PM2.5 forecast
Peak PM2.5   Model AQI   Peak time
...

6 h   12 h   24 h   48 h
...

COMBINED ADVISORY
...

Updated HH:MM
```

Use real source labels/wording once provider fields are verified.

---

# 17. Primary measurement card

The main card must prioritize human-readable status.

Display in this order:

1. station picker,
2. station name,
3. freshness badge,
4. large TH AQI when available,
5. official category label,
6. PM2.5,
7. PM10,
8. short trend metric,
9. measurement timestamp.

If TH AQI is unavailable but PM2.5 is available:

- show PM2.5 prominently,
- do not invent an AQI unless calculation prerequisites are met,
- explain `AQI unavailable` rather than showing zero.

Persist the user's selected home station locally on that device, using the same concept as Ping Flood Watch.

The home station changes presentation only. The overall Mueang advisory may still use multiple stations.

---

# 18. Trend calculation

Implement reusable trend calculations.

At minimum:

- 1 hour,
- 3 hours,
- 24 hours.

For PM2.5 show:

```text
+4.2 µg/m³ / 1h
-8.1 µg/m³ / 3h
```

Interpretation:

- positive = pollution concentration increasing / worsening for PM metrics,
- negative = improving,
- null when there is insufficient history.

Do not infer a trend from one reading.

Avoid dramatic words such as `rapid deterioration` unless a configurable rule has actually been defined and tested.

---

# 19. 24-hour chart

Reuse the Ping Chart.js card and interaction model.

Default chart:

```text
PM2.5 history — last 24 hours
```

Requirements:

- line chart,
- no chart animation during automatic refresh,
- same responsive/mobile behavior as Ping,
- clear `µg/m³` y-axis,
- local time x-axis,
- preserve last valid chart if refresh fails,
- offline snapshot support,
- no false zero for missing values.

On station detail pages support periods such as:

```text
24h
72h
7d
30d
```

Use aggregation/downsampling for longer periods where needed.

---

# 20. Station overview

Home page should show Mueang Chiang Mai monitoring points below the main chart.

Each row should include:

- station name/code,
- provider indicator if useful,
- TH AQI/category if supplied,
- PM2.5,
- trend,
- measurement time,
- freshness badge.

Do not overload the home list with every pollutant.

Detailed values belong on `station.php`.

---

# 21. Stations page and map

Reuse the Ping station page concept:

```text
List | Map
```

Use self-hosted Leaflet and marker clustering as in Ping Flood Watch.

OpenStreetMap tiles remain online-only and must not be bulk-cached by the service worker.

Each marker should visually reflect the current TH AQI category where practical while remaining accessible without color alone.

Popup/detail should include:

- station name,
- source/provider,
- TH AQI,
- PM2.5,
- PM10 when available,
- last measurement time,
- freshness,
- link to station details.

If map tiles fail, the station list must remain fully usable.

---

# 22. Station detail page

Required content:

- station name,
- provider and provider station code,
- coordinates,
- current freshness,
- current TH AQI/source AQI,
- PM2.5,
- PM10,
- O3,
- CO,
- NO2,
- SO2,
- temperature/humidity when the provider supplies them,
- measurement time,
- trend cards,
- historical chart,
- source attribution,
- clear null display (`—`) for unavailable pollutants.

Never normalize a missing pollutant to `0`.

---

# 23. Air-quality status engine

Create:

```text
app/Services/AirQualityEngine.php
```

Primary observed status values:

```text
very_good
good
moderate
unhealthy
very_unhealthy
unknown
```

Base the public status on official TH AQI categories.

Recommended semantic labels:

English:

```text
Very good
Good
Moderate
Affects health
Strong health impact
Update pending
```

Thai translations should follow current official Air4Thai/PCD terminology where possible.

Do not reinterpret official health guidance into stronger medical advice.

---

# 24. Area-wide Mueang status

The application should produce one overall **Mueang Chiang Mai Air Quality** status while preserving station detail.

Initial rule:

- consider only fresh/enabled observation stations inside the configured Mueang allowlist,
- choose the worst verified current TH AQI category among those stations,
- expose the station responsible for the area status,
- if too few/none are fresh, return `unknown` rather than `good`.

Make the minimum fresh-station requirement configurable.

Do not average AQI values across stations by default. An average can hide a local bad area.

If an optional median/mean PM2.5 statistic is shown, label it as an area statistic and keep it separate from the official area status.

---

# 25. Forecast risk

Create:

```text
app/Services/ForecastRiskEngine.php
```

The forecast is a model signal, not a measured current AQI.

Forecast card should answer:

```text
Is air quality expected to improve, remain similar or worsen in the next 24–48 hours?
```

Possible forecast severity values:

```text
low
moderate
high
very_high
unknown
```

However, do NOT invent arbitrary forecast thresholds.

Before production:

- define thresholds in configuration,
- document which pollutant/index drives each threshold,
- use official TH PM2.5 breakpoints when a valid comparable 24-hour PM2.5 forecast aggregation is calculated,
- otherwise describe forecast PM2.5 values without pretending they are official TH AQI.

Recommended forecast metrics:

- PM2.5 next 6h,
- PM2.5 next 12h,
- PM2.5 next 24h,
- PM2.5 next 48h,
- peak predicted PM2.5,
- peak model AQI if supplied,
- time of predicted peak,
- forecast received/model timestamp.

---

# 26. Combined advisory

Create:

```text
app/Services/AdvisoryEngine.php
```

Combine:

1. verified measured air quality,
2. measured trend,
3. forecast signal.

Examples of safe language:

```text
Air quality is currently moderate and the model forecast is broadly stable.
```

```text
Current readings are acceptable, but modelled PM2.5 is expected to increase later today.
```

```text
Measured air quality is poor and the forecast does not show a clear near-term improvement.
```

Do not claim certainty.

Avoid wording such as:

```text
The air will become dangerous at 18:00.
```

Use:

```text
The model indicates a higher pollution risk around 18:00.
```

---

# 27. Health guidance text

Health guidance must be conservative and source-based.

Rules:

- use official Thai AQI/PCD guidance where available,
- keep advice short on the home screen,
- offer fuller wording on details/help,
- clearly distinguish general public from sensitive groups when official wording does,
- do not diagnose medical conditions,
- do not make personalized medical decisions,
- add source attribution/documentation.

The app is an information/decision-support tool, not a medical service.

---

# 28. Alerts

Reuse the mature Ping alert lifecycle architecture rather than creating simplistic one-shot alerts.

Alert events should support category transitions such as:

```text
good -> moderate
moderate -> unhealthy
unhealthy -> very_unhealthy
very_unhealthy -> unhealthy
unhealthy -> moderate
clear/recovery
```

Push notifications should default to meaningful events, not every hourly value change.

Recommended production defaults:

- do not push for `very_good` → `good`,
- optional/no push for ordinary `moderate`,
- push when current area status reaches `unhealthy`,
- urgent/high-priority push when it reaches `very_unhealthy`,
- push one recovery message when conditions fall below the configured alert threshold and remain there for a debounce period,
- avoid flapping if values cross a boundary repeatedly.

Make thresholds/debounce configurable.

Reuse Ping's outbox, delivery tracking, retry, expiry, superseding-event and subscription hardening patterns.

Do not send medical emergency claims.

---

# 29. Alerts page

Reuse the Ping Alerts page design.

Show:

- current notification setting,
- Enable/Disable control,
- current active air-quality alert if any,
- recent alert history,
- severity/category,
- trigger time,
- station/area reason,
- PM2.5/AQI context,
- cleared/recovered state.

English and Thai required.

---

# 30. Push/PWA behavior

Clone and rename the proven Ping PWA/Web Push architecture.

Requirements:

- unique VAPID key pair,
- unique push subject `https://air.aberg.online/`,
- permission requested only after explicit user action,
- iOS Home Screen PWA guidance retained/adapted,
- Android support retained,
- same-origin notification click behavior,
- no Ping URLs or labels in payloads,
- separate push subscription table in the Air DB,
- separate VAPID config.

Add a CLI test push command equivalent to Ping's test command, clearly marked `TEST`.

---

# 31. API design

Use the same JSON envelope conventions as Ping Flood Watch.

Minimum endpoints:

## `GET /api/current.php`

Return:

- area status,
- primary/home station candidates,
- latest station observations,
- trends,
- freshness,
- forecast summary,
- combined advisory.

## `GET /api/history.php`

Parameters:

```text
station=<provider-aware station code/id>
period=24h|72h|7d|30d
```

## `GET /api/stations.php`

Return normalized station metadata/latest summary.

## `GET /api/forecast.php`

Return model forecast only.

## `GET /api/alerts.php`

Return current/recent alerts.

## `GET /api/status.php`

Human/system status summary.

## `GET /api/health.php`

Operational machine-readable health endpoint.

## Push write endpoints

Reuse the Ping security model for method/content-type/origin validation and rate limiting.

All public read APIs:

- JSON only,
- explicit error envelope,
- `Cache-Control: no-store` for live/current endpoints,
- no provider secrets,
- no raw source payload,
- no stack traces in production.

---

# 32. Frontend refresh behavior

Reuse Ping's resilient browser refresh model.

Suggested intervals:

```text
current observations: browser refresh every 5 minutes
history chart: every 10 minutes
forecast: via current payload or 15–30 minute browser refresh
```

Server-side provider collection interval is independent.

On failed refresh:

- keep the last verified display,
- visibly mark delayed/stale/offline,
- do not blank the screen,
- do not show old data as LIVE,
- retain last valid chart,
- retry automatically.

---

# 33. Collection cron

Initial server schedule, subject to live provider limits:

```text
Air observations: every 10 minutes
Air forecast: every 30 minutes
Push dispatch: every minute
Retention: nightly
Backup: nightly
```

Use `flock` and the existing Ping collector-run/hung-collector patterns.

If Air4Thai only publishes hourly, frequent polling should still be lightweight and deduplicated by source timestamp/hash.

Respect provider terms/rate limits.

---

# 34. Provider resilience

A provider outage must not crash the app.

For every provider implement:

- timeout,
- maximum response size,
- HTTP status validation,
- content-type validation where practical,
- JSON/schema validation,
- numeric range sanity checks,
- timestamp validation,
- unit validation,
- sanitized errors,
- provider health state,
- consecutive failure count,
- last success,
- last failure,
- last error code,
- collector runtime.

If a provider fails:

- retain last known verified values,
- mark freshness accurately,
- return `unknown` when current status can no longer be verified,
- never silently switch to a third-party number without source labeling.

---

# 35. Observation provider merging

If both Air4Thai and DustBoy are enabled:

- keep station/source identity,
- do not merge two nearby sensors into one synthetic station,
- do not average them into a fake measurement,
- allow both on the map/list,
- expose provider label in details,
- use source priority only for selecting the app's configured primary station or area policy.

Possible area policy:

```text
Official Air4Thai stations define official area status.
DustBoy adds local situational detail.
```

If adopted, document it clearly.

Alternatively, if both sources contribute to area status, define and test the rule explicitly.

---

# 36. Design mapping from Ping Flood Watch

Use these conceptual replacements:

```text
Ping Flood Watch              -> Chiang Mai Air Watch
PING RIVER · CHIANG MAI       -> AIR QUALITY · MUEANG CHIANG MAI
River conditions              -> Air conditions
River level                   -> TH AQI / PM2.5
Water level history           -> PM2.5 history
River Risk                    -> Air Quality
Weather Risk                  -> Forecast Risk
Upstream stations             -> Monitoring stations
Combined Advisory             -> Combined Advisory
Flood alerts                  -> Air-quality alerts
Water stations                -> Air monitoring stations
```

The design should feel like the same product family.

Do not preserve flood/wave icons where they no longer make semantic sense.

Use appropriate Material Symbols such as air/haze/mist/health-related neutral environmental icons while retaining card dimensions and style.

---

# 37. Colors and accessibility

Thai AQI categories traditionally use color coding, but color must not be the only communication method.

Each category must have:

- text label,
- accessible contrast,
- optional icon/shape treatment,
- ARIA-readable content.

Do not destroy the existing light/dark palette merely to mimic bright AQI colors.

Integrate category colors into the existing Ping design system in a controlled way.

Run axe tests in both light and dark themes where practical.

---

# 38. Languages

Keep the same bilingual architecture:

```text
English
Thai
```

All visible production strings must live in translation files unless there is a compelling technical reason otherwise.

Do not ship placeholder machine-translated Thai without review.

At minimum provide correct Thai translations for:

- app name/tagline,
- navigation,
- pollutant names,
- freshness states,
- AQI categories,
- health guidance,
- forecast language,
- alerts,
- errors,
- PWA/push instructions,
- station/map UI.

Use official PCD Thai category terminology where available.

---

# 39. Source attribution

Display appropriate source attribution.

At minimum document/display as required:

```text
Air4Thai / Pollution Control Department (Thailand)
```

If DustBoy is enabled, include required wording such as:

```text
Data supported by the Climate Change Data Center, Chiang Mai University (CCDC CMU)
```

or equivalent wording required by their current terms.

Open-Meteo/CAMS attribution must follow the current Open-Meteo Air Quality API terms/documentation.

Do not hide source attribution in source code only.

---

# 40. PWA offline strategy

Reuse Ping Flood Watch's offline philosophy:

Cache:

- application shell,
- CSS/JS,
- local fonts,
- icons,
- offline page,
- other immutable local assets.

Do NOT cache as authoritative live data:

- `/api/current.php`,
- observation APIs,
- forecast APIs,
- map tiles.

A local last-known dashboard snapshot may be displayed offline only if it is clearly marked:

```text
OFFLINE / showing last stored data
```

Never display cached AQI as LIVE while offline.

---

# 41. Security

Match or improve Ping Flood Watch hardening.

Requirements:

- PDO prepared statements,
- output escaping,
- external production config outside DocumentRoot,
- least-privilege runtime DB user,
- source/config/vendor/docs/tests/sql/storage access restrictions in Apache,
- CSP,
- HSTS after TLS is verified,
- `X-Content-Type-Options: nosniff`,
- suitable frame/referrer/permissions policy,
- no secrets in repository,
- no API keys in JS,
- no raw provider payload in browser API,
- sanitized logs,
- API body-size limits,
- origin/method/content-type checks for push writes,
- rate limiting,
- deny dotfiles,
- deny backups/logs/SQL/Markdown from public web access.

Run `composer audit` and `npm audit` and document results.

---

# 42. External configuration

Production config must be outside DocumentRoot.

Suggested:

```text
/etc/chiang-mai-air-watch/config.php
```

Example structure:

```php
<?php

return [
    'app' => [
        'base_url' => '/',
        'public_origin' => 'https://air.aberg.online',
        'debug' => false,
        'timezone' => 'Asia/Bangkok',
    ],
    'db' => [
        'dsn' => 'mysql:host=127.0.0.1;dbname=chiang_mai_air_watch;charset=utf8mb4',
        'username' => 'cmaw_runtime',
        'password' => 'EXTERNAL_SECRET',
    ],
    'providers' => [
        'air4thai' => [
            'enabled' => true,
        ],
        'dustboy' => [
            'enabled' => false,
            'api_key' => null,
        ],
        'open_meteo' => [
            'enabled' => true,
        ],
    ],
    'push' => [
        'enabled' => false,
        'subject' => 'https://air.aberg.online/',
        'public_key' => null,
        'private_key' => null,
    ],
    'security' => [
        'rate_limit_key' => 'EXTERNAL_RANDOM_SECRET',
    ],
];
```

No real secret values belong in `config.example.php`.

---

# 43. Production database permissions

Follow Ping's least-privilege pattern.

Runtime account should normally have only:

```text
SELECT
INSERT
UPDATE
DELETE
```

Schema migration must use an administrative connection/process, not grant ALTER/CREATE to the web runtime account.

---

# 44. Retention

Initial recommendation:

- raw/high-resolution observations: 24 months,
- forecast points: 12 months,
- alert events: retain longer unless size becomes material,
- provider health/collector logs: reasonable operational retention,
- application logs: rotate daily, retain approximately 30 rotations,
- DB backups: retain approximately 30 daily copies unless existing server policy differs.

Make retention configurable and document it.

Do not delete Ping backups.

---

# 45. Deployment target

The final production origin is:

```text
https://air.aberg.online
```

Before deployment inspect the current server's Ping vhost and directory layout.

If Ping currently uses a pattern such as:

```text
/var/www/abergonline/ping
```

then a sibling location such as:

```text
/var/www/abergonline/air
```

is acceptable for the deployment, but the application source itself must remain portable.

Do not assume this path without checking the actual server.

---

# 46. Apache vhost

Create a dedicated vhost for:

```text
air.aberg.online
```

Mirror the proven security/header/TLS pattern from the Ping vhost while changing all site-specific values.

Requirements:

- dedicated DocumentRoot,
- dedicated access/error logs if the existing convention uses them,
- deny internal/private paths,
- correct PHP handling,
- HTTP → HTTPS redirect,
- no fallback to another aberg.online vhost,
- no accidental alias into Ping directories.

Validate with:

```bash
apache2ctl configtest
```

before reload.

---

# 47. DNS and TLS

Check whether DNS already exists for:

```text
air.aberg.online
```

If Codex has no authorized DNS-management capability:

- do not invent a provider change,
- report the exact A/AAAA record that Hasse must create,
- continue all local/server preparation that does not depend on public DNS.

Once DNS resolves to the intended server:

- provision a dedicated Let's Encrypt certificate,
- verify SAN/subject,
- verify renewal timer,
- run `certbot renew --dry-run` where appropriate.

Do not modify/remove the Ping certificate.

---

# 48. Scheduled operations

Create application-specific cron entries.

Example:

```text
*/10 * * * * collect-air.php
*/30 * * * * collect-forecast.php
* * * * * dispatch_push.php
nightly retention
nightly backup
```

Use application-specific lock files.

Do not reuse Ping lock names.

---

# 49. Health endpoint

`/api/health.php` must cover:

- database connectivity,
- most recent air collector run,
- most recent forecast collector run,
- hung collectors,
- Air4Thai health,
- DustBoy health when enabled,
- Open-Meteo health,
- last successful observation age,
- last successful forecast age.

Return HTTP 503 when monitoring cannot currently be considered operational according to documented rules.

A stale external provider must not be hidden behind HTTP 200 `everything fine`.

---

# 50. Test fixtures

For every real provider create small sanitized fixtures representing:

- normal response,
- missing pollutant,
- null AQI,
- malformed JSON,
- wrong content type,
- HTTP error,
- stale measurement,
- unknown station,
- duplicate measurement,
- corrected/revised measurement,
- unit anomaly,
- timestamp anomaly.

Fixtures must contain public/environmental data only and no secrets.

---

# 51. Unit tests

Minimum PHPUnit coverage:

## Provider parsing

- Air4Thai valid payload,
- Air4Thai station filtering,
- Mueang allowlist,
- DustBoy valid payload when adapter exists,
- Open-Meteo forecast parsing,
- errors and invalid schema.

## AQI logic

- every TH AQI breakpoint,
- exact boundary values,
- null handling,
- scale metadata,
- no TH/US AQI mixing.

## Trend logic

- rising PM2.5,
- falling PM2.5,
- insufficient history,
- gaps.

## Freshness

- LIVE,
- DELAYED,
- STALE,
- OFFLINE.

## Area status

- worst fresh station wins,
- stale bad station does not masquerade as current,
- no fresh stations -> unknown,
- configured primary station behavior.

## Forecast/advisory

- observed good + forecast low,
- observed good + forecast worsening,
- observed unhealthy + forecast stable,
- observed unhealthy + forecast improving,
- unknown observation,
- unknown forecast,
- no deterministic claims.

## Alerts

- escalation,
- debounce,
- recovery,
- anti-flap,
- outbox transaction,
- dispatcher concurrency/superseding behavior where inherited.

---

# 52. Browser tests

Create/port Playwright coverage for at least:

- home dashboard,
- station picker persistence,
- stations list,
- map opening,
- station detail,
- history chart,
- alerts page,
- EN/TH switching,
- light/dark/system theme,
- stale provider state,
- offline snapshot state,
- PWA registration,
- push capability states,
- API failure banner,
- no-JS graceful baseline where applicable,
- accessibility.

Test representative mobile widths such as:

```text
360 px
390 px
430 px
```

and at least one wider viewport to confirm the mobile shell remains intentional.

---

# 53. Manual QA

Before Definition of Done, manually inspect:

- Android Chrome,
- iPhone Safari/Home Screen PWA where available,
- Thai font rendering,
- dark mode,
- long Thai station names,
- map popups,
- category colors,
- chart tooltips,
- offline state,
- push notification copy,
- install icon/name.

If real-device testing is unavailable, mark it `MANUAL PENDING`; do not falsely report it passed.

---

# 54. PWA metadata and branding

Create app-specific assets and metadata.

Minimum:

```text
name: Chiang Mai Air Watch
short_name: Air Watch
start_url: generated/portable equivalent
scope: generated/portable equivalent
theme/background colors: aligned with Ping product family
```

Create:

- 192×192 icon,
- 512×512 icon,
- maskable icon if the existing build supports it,
- OG/Facebook image,
- favicon equivalents.

Visual direction:

- same family as Ping Flood Watch,
- Chiang Mai air/haze/clean-air concept,
- not a copy of the Ping river graphic,
- simple enough for app icon readability.

---

# 55. Visitor counter/analytics

If Ping's privacy-friendly visitor counter is reused:

- use the Air application's own storage/table,
- do not combine counts with Ping,
- do not add third-party tracking by default,
- document exactly what is counted.

---

# 56. Documentation deliverables

At minimum produce/update:

```text
README.md
docs/INSTALL.md
docs/API.md
docs/PROVIDERS.md
docs/AQI.md
docs/ALERTS.md
docs/TEST_REPORT.md
docs/REFERENCE_PING_ARCHITECTURE.md
docs/PROVIDER_VALIDATION_AIR4THAI.md
docs/PROVIDER_VALIDATION_OPEN_METEO.md
```

If DustBoy is implemented/tested:

```text
docs/PROVIDER_VALIDATION_DUSTBOY.md
```

Documentation must state what is:

- verified live,
- implemented but disabled,
- fixture-only,
- manually pending.

---

# 57. README minimum content

README should quickly explain:

```text
Chiang Mai Air Watch

A mobile PWA for monitoring measured air quality in Mueang Chiang Mai,
with station history, forecast context and optional push alerts.

Production: https://air.aberg.online
```

Then list:

- observation sources,
- forecast source,
- TH AQI scale,
- main features,
- architecture docs,
- installation docs,
- test status.

---

# 58. Installation workflow

Create a fresh-install process equivalent in quality to Ping's.

Expected style:

```bash
composer install --no-dev --classmap-authoritative
npm ci
./scripts/build-assets.sh
sudo ./scripts/provision-databases.sh
sudo ./scripts/install-ops.sh
sudo php scripts/configure-vapid.php
sudo -u www-data php cron/collect-air.php
sudo -u www-data php cron/collect-forecast.php
```

Actual commands may differ after inspection, but document exact tested commands.

No command should overwrite Ping's external config or database.

---

# 59. Backup and rollback

Before production deployment:

- back up the new Air DB if it already exists,
- back up any previous `air.aberg.online` webroot/vhost if present,
- create release artifact/checksum,
- have a rollback plan.

Do not take destructive shortcuts because this is V1.

A rollback of Air Watch must not roll back Ping Flood Watch.

---

# 60. Release artifact

Produce a clean production artifact outside the webroot, following the Ping release process.

Include:

- application source,
- production vendor dependencies,
- docs,
- migrations/schema,
- required built assets,
- lock files.

Exclude:

- `node_modules`,
- real config secrets,
- logs,
- cache,
- test results,
- local browser artifacts,
- `.env` secrets,
- DB dumps,
- VAPID private keys.

Create SHA-256 checksum.

---

# 61. Explicit non-goals for V1

Do NOT turn this mission into a giant environmental platform.

Out of scope unless nearly free after the core is complete:

- wildfire satellite map,
- fire/hotspot detection,
- pollen outside supported reliable sources,
- personal medical recommendations,
- indoor air sensors,
- user accounts,
- crowdsourced sensors,
- native Android/iOS app,
- machine-learning pollution prediction,
- province-wide Thailand map,
- social networking,
- advertising.

Build the Mueang Chiang Mai air-quality product first.

---

# 62. Important source semantics

Codex must preserve these distinctions everywhere:

```text
OBSERVED = measured by a monitoring station/sensor
FORECAST = modelled future atmospheric conditions
TH AQI = Thailand AQI scale
US AQI = United States AQI scale
PM2.5 = pollutant concentration, not itself AQI
```

Never label:

- forecast as live,
- PM2.5 concentration as AQI,
- US AQI as TH AQI,
- stale data as current,
- a local DustBoy sensor as an official PCD station.

---

# 63. Acceptance criteria — functional

Mission is not complete until all applicable items are addressed.

## A. Application

- [ ] `air.aberg.online` application exists independently of Ping.
- [ ] Home page uses Ping Flood Watch design language.
- [ ] English and Thai work.
- [ ] light/dark/system theme works.
- [ ] mobile layout works at target widths.

## B. Observations

- [ ] at least one real Mueang Chiang Mai observation provider is live-validated,
- [ ] station scope is verified,
- [ ] current observations collect automatically,
- [ ] history is stored,
- [ ] source timestamps and units are preserved/normalized correctly,
- [ ] stale/offline behavior is correct.

## C. AQI

- [ ] TH AQI semantics documented,
- [ ] source AQI preserved,
- [ ] breakpoint logic tested if local computation exists,
- [ ] no TH/US scale confusion.

## D. UI

- [ ] main AQI/PM2.5 card,
- [ ] trend,
- [ ] 24h chart,
- [ ] station rows,
- [ ] stations page,
- [ ] map,
- [ ] station detail.

## E. Forecast

- [ ] Open-Meteo or another verified forecast adapter works,
- [ ] forecast data stored separately,
- [ ] clearly labelled as model/forecast,
- [ ] 24–48h forecast UI works,
- [ ] forecast failure does not break observations.

## F. Advisory

- [ ] observed status engine works,
- [ ] forecast-risk logic works,
- [ ] combined advisory works,
- [ ] unknown states are handled honestly.

## G. Alerts/PWA

- [ ] alert lifecycle works,
- [ ] push subscribe/unsubscribe endpoints hardened,
- [ ] PWA installs,
- [ ] offline shell works,
- [ ] cached data cannot masquerade as LIVE,
- [ ] real-device push marked pass or manual pending truthfully.

## H. Operations

- [ ] migrations work,
- [ ] collector locks work,
- [ ] cron works,
- [ ] health endpoint works,
- [ ] backups work,
- [ ] log rotation works,
- [ ] production config externalized,
- [ ] least-privilege DB runtime user works.

## I. Security

- [ ] secrets absent from repository/artifact,
- [ ] private directories blocked,
- [ ] CSP/security headers verified,
- [ ] composer/npm audit documented,
- [ ] public APIs expose no raw provider secrets/payloads.

## J. Deployment

- [ ] Apache config test passes,
- [ ] DNS status documented,
- [ ] HTTPS works,
- [ ] HTTP redirects to HTTPS,
- [ ] certificate verified,
- [ ] `curl` smoke tests pass,
- [ ] Ping Flood Watch remains operational after deployment.

---

# 64. Mandatory regression check for Ping Flood Watch

After deploying Air Watch, verify that the reference site still works.

At minimum:

```text
https://air.aberg.online/
https://air.aberg.online/api/health.php
https://air.aberg.online/api/status.php
```

Do not change Ping merely to make this regression check pass.

Record results in the Air Watch test report.

---

# 65. Required production smoke tests

Once deployed and DNS/TLS are ready:

```bash
curl -fsSI http://air.aberg.online/
curl -fsSI https://air.aberg.online/
curl -fsS https://air.aberg.online/api/health.php
curl -fsS https://air.aberg.online/api/status.php
curl -fsS 'https://air.aberg.online/api/current.php?lang=en'
curl -fsS 'https://air.aberg.online/api/current.php?lang=th'
```

Verify:

- HTTP redirect,
- correct hostname,
- correct TLS certificate,
- JSON envelopes,
- no PHP warnings,
- no Ping branding/data leakage,
- correct local station data,
- freshness timestamps make sense.

---

# 66. Definition of Done

Codex may report the mission complete only when:

1. the new application exists independently,
2. Ping Flood Watch remains unchanged/operational,
3. at least one verified real observation source is working,
4. current Mueang Chiang Mai air data is stored and displayed,
5. TH AQI semantics are correct and documented,
6. PM2.5 history/chart works,
7. stations/list/map/detail work,
8. forecast is implemented and clearly separated from observations,
9. combined advisory is implemented without false certainty,
10. stale/offline/provider failure behavior is robust,
11. bilingual UI works,
12. theme/PWA work,
13. alert architecture works,
14. production config/secrets are external,
15. DB/runtime security is in place,
16. tests are run and results documented,
17. Apache/DNS/TLS status is documented,
18. backup/rollback instructions exist,
19. all known limitations/manual-pending checks are explicitly listed,
20. `docs/TEST_REPORT.md` contains a truthful final acceptance matrix.

---

# 67. Final Codex report format

When work is finished, return a concise but complete report with exactly these sections:

```text
1. Summary
2. Files created/changed
3. Architecture reused from Ping Flood Watch
4. Observation providers and live validation
5. Mueang Chiang Mai stations selected
6. AQI implementation and scale
7. Forecast implementation
8. Alerts/PWA
9. Database/migrations
10. Apache/DNS/TLS deployment status
11. Cron/backup/health status
12. Tests executed and results
13. Ping regression check
14. Known limitations / manual pending
15. Exact next action for Hasse, if any
```

Do not say `everything works` without evidence.

If something is blocked by an external dependency such as DNS, API-key approval or provider outage, complete everything else and report the blocker precisely.

---

# 68. Reference sources to verify during implementation

These are starting points, not permission to skip live validation.

## Air4Thai / Pollution Control Department

```text
https://air4thai.pcd.go.th/
https://air4thai.net/
https://air4thai.pcd.go.th/services/getNewAQI_JSON.php
```

## Thailand AQI information

Use current official PCD/MNRE/Thai AQI documentation. Verify the 2023 PM2.5 standard before locking production breakpoints.

A current public reference describing the Thailand AQI criteria is also available from the Thai Air Quality Information Center.

## CMU CCDC DustBoy Open API

```text
https://open-api.cmuccdc.org/
```

## Open-Meteo Air Quality API

```text
https://open-meteo.com/en/docs/air-quality-api
https://air-quality-api.open-meteo.com/v1/air-quality
```

## OpenStreetMap tile policy

Use the same policy/attribution approach already documented in Ping Flood Watch.

---

# 69. Final implementation philosophy

This application should feel immediately familiar to anyone who has used Ping Flood Watch.

The goal is not to build a visually different air-quality portal.

The goal is to create a second member of the same product family:

```text
air.aberg.online  -> water / flood situational awareness
air.aberg.online   -> air-quality situational awareness
```

Same simplicity.
Same operational discipline.
Same mobile-first clarity.
Different environmental signal.

Build it so Hasse can open `air.aberg.online` on a phone in Chiang Mai and understand the current air situation in a few seconds.

