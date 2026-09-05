# CURRENT STATE FINDINGS — air(1).zip
<!-- Senast uppdaterad: 2026-09-03 12:17 Asia/Bangkok | KlⒶssⓔ & Ⓐberg -->

Denna fil beskriver verifierade fynd från den befintliga koden.

# 1. Befintliga providers

Finns:

```text
app/Providers/Air4ThaiProvider.php
app/Providers/DustBoyProvider.php
app/Providers/OpenMeteoAirProvider.php
app/Providers/MockAirQualityProvider.php
```

DustBoy-klassen finns alltså redan men är inte fullt inkopplad.

---

# 2. `collect_air.php`

Nuvarande fil:

```text
cron/collect_air.php
```

läser:

```php
Config::get('providers.observation', 'air4thai')
```

och skapar endast:
- MockAirQualityProvider
- eller Air4ThaiProvider

`DustBoyProvider` instansieras inte här.

**Konsekvens:** DustBoy kan inte samlas in av produktionscron även om API key konfigureras.

---

# 3. Config

`app/Config.php` har:

```php
'providers' => [
    'observation' => 'air4thai',
    'forecast' => 'openmeteo_air',
    'dustboy' => [
        'enabled' => false,
        'url' => 'https://open-api.cmuccdc.org',
        'api_key' => null
    ],
]
```

Detta ska byggas ut till observationslista men med bakåtkompatibilitet.

Extern config laddas från:

```text
CMAW_CONFIG_FILE
```

eller:

```text
/etc/chiang-mai-air-watch/config.php
```

med fallback:

```text
app/config.local.php
```

Behåll detta.

---

# 4. DustBoy provider

Nuvarande:

```text
app/Providers/DustBoyProvider.php
```

använder latest:

```text
/api/dustboy/station
```

men historik:

```text
/api/dustboy/data?start=...&end=...
```

Den historikvägen ska inte vara kvar som ensam strategi.

Dokumentationen som Hasse har fått från CMU CCDC/DustBoy beskriver separata historikflöden för:
- 30 dagar
- 1 år
- 5 år

Se DustBoy-specfilen.

---

# 5. Seed

`sql/seed.sql` innehåller endast Air4Thai:

```text
air4thai:36t
air4thai:35t
```

Det finns inga DustBoy-stationer.

V1.1 ska inte hårdkoda ett stort godtyckligt stationsset i seed.sql.

Bygg i stället en explicit station-sync/import för DustBoy.

---

# 6. Datamodell

Tabellen `stations` är redan väl förberedd:

```text
provider
provider_station_code
is_primary
affects_official_status
source_metadata_json
```

Detta ska återanvändas.

DustBoy:
```text
provider = dustboy
is_primary = 0
affects_official_status = 0
```

Tabellen `measurements` har redan:
- PM2.5
- PM10
- temperature_c
- humidity_pct
- source_status
- raw_payload_json

Datamodellen behöver därför inte ersättas.

---

# 7. Official area-status

`AirQualityEngine::area()` filtrerar redan på:

```php
$s['affects_official_status']
```

Det är rätt arkitektur.

Säkerställ regressionstest så DustBoy aldrig påverkar official area.

---

# 8. Health-problem

`HealthService` använder global senaste measurement:

```sql
SELECT MAX(measured_at) FROM measurements
```

Efter DustBoy-integration kan detta ge falskt positiv health:
- DustBoy färsk
- Air4Thai trasig
- global latest ser ändå färsk ut

Detta måste ändras.

---

# 9. Historik

`MeasurementRepository::history()` stöder:

```text
24h
72h
7d
30d
```

med:
- raw
- 3h mean
- 12h mean

`api/history.php` allowlistar samma perioder.

`app/views/station.php` visar samma select.

Alla tre lager måste uppdateras samtidigt.

---

# 10. Retention

`cron/retention.php` raderar:

```sql
measurements äldre än 24 månader
```

Nuvarande config har även:

```php
'retention' => [
  'measurement_months' => 24,
  ...
]
```

men retention.php använder i dagsläget hårdkodade intervall.

V1.1 ska:
- använda config.
- behålla rådata rimlig tid.
- behålla daily summaries betydligt längre.

---

# 11. Karta

`assets/js/stations.js` använder:

```js
L.marker(...)
```

med standard Leaflet-marker.

CSS/kodbasen har utrymme för mer avancerad markering.

V1.1 ska:
- provider-medvetna ikoner.
- filter.
- popup-information.
- läsa tillbaka localStorage view.

---

# 12. Home station bug

`assets/js/home.js` byter:
- AQI
- kategori
- PM2.5
- PM10
- trend
- freshness
- tid

men huvudkortets `severity-*` uppdateras inte provider/status-medvetet.

Detta fixas.

---

# 13. Provider label hårdkodat

Exempel i:

```text
app/views/home.php
```

visar:

```text
Air4Thai / PCD
```

oavsett vilken station JS byter till.

Detta måste bli dynamiskt.

---

# 14. Stationslistan är AQI-first

`app/views/stations.php` visar stor:

```text
TH AQI
```

för alla stationer.

Efter DustBoy måste layouten vara provider-medveten.

---

# 15. Cron

Nuvarande crontab-fil:

```text
config/cron/chiang-mai-air-watch
```

har:
- collect_air var 10:e minut
- forecast var 30:e minut
- push varje minut
- retention dagligen
- backup dagligen

DustBoy-dokumentationen anger att DustBoy uppdateras ungefär en gång/timme och API-begränsning behöver respekteras.

**DustBoy ska därför inte anropas var 10:e minut bara för att Air4Thai gör det.**

Bygg provider-specifik due/cadence.

Exempel:
- Air4Thai: var 10 min
- DustBoy: högst cirka 1 gång/timme, lämpligen efter `XX:10`

Det kan lösas i samma collector genom provider due-check/cache timestamp.

---

# 16. Tester

Befintliga testområden:

```text
tests/Unit
tests/Integration
tests/Live
tests/E2E
```

Det finns redan DustBoy fixture:

```text
tests/fixtures/dustboy/latest-normal.json
```

Bygg vidare på befintlig teststrategi.

---

# 17. Bra delar som ska bevaras

- PDO/prepared statements.
- central Config.
- provider interfaces.
- repositories/services.
- source hash/revisions.
- provider health.
- PWA.
- offline.
- push infrastructure.
- bilingual EN/TH.
- Leaflet + MarkerCluster.
- Chart.js.
- Air4Thai CA-bundle-hantering.
- separering mellan observation och prognos.
