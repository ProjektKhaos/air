# DUSTBOY + MULTI-PROVIDER SPEC
<!-- Senast uppdaterad: 2026-09-03 12:17 Asia/Bangkok | KlⒶssⓔ & Ⓐberg -->

# 1. Källa

Hasse har nu ett konto hos CMU CCDC / DustBoy Open API.

Tillhandahållna guider visar:
- registrering för API.
- Bearer token / API key.
- val av stations/device-ID.
- 30 dagars historik.
- 1 års historik.
- 5 års historik.
- ungefär timvis datauppdatering.
- API-limit på ungefär 10 anrop/timme per API key.
- rekommendation att cachelagra data och hämta efter att timmens data hunnit publiceras.

Token ska inte läggas i denna mission eller repo.

---

# 2. Config

Utöka exempel:

```php
'providers' => [
    'observations' => ['air4thai', 'dustboy'],

    'dustboy' => [
        'enabled' => true,
        'url' => 'https://open-api.cmuccdc.org',
        'api_key' => 'SET-IN-EXTERNAL-CONFIG',
        'station_ids' => [],
        'auto_discover' => false,
        'center' => [
            'latitude' => 18.7883,
            'longitude' => 98.9853,
        ],
        'radius_km' => 15,
        'minimum_fetch_interval_minutes' => 55,
    ],
]
```

API key ska endast anges i extern config.

---

# 3. Latest endpoint

Befintlig implementation använder:

```text
GET /api/dustboy/station
Authorization: Bearer <token>
```

Behåll endpointen om live smoke test bekräftar den.

**Viktigt:** Codex får inte gissa slutligt response-schema.

Gör:
1. använd befintlig fixture för unit-test.
2. om live-API finns tillgängligt, kör ett manuellt smoke test.
3. logga endast schema/antal, aldrig Authorization-header eller token.
4. anpassa parsern till verklig respons.
5. stöd några defensiva namnvarianter, men inte obegränsad "guessing".

---

# 4. Historikendpoints

Guiderna från CMU visar separata historikfunktioner.

Implementera providerlogik kring station/device-ID och period:

```text
30d  → /api/dustboy/data30day/{id}
1y   → /api/dustboy/data1year/{id}
5y   → /api/dustboy/data5year/{id}
```

Om det verkliga API:t skiljer sig ska Codex:
- verifiera mot live/aktuell portal.
- dokumentera exakt vad som användes.
- inte behålla en endpoint som testats till 404/invalid.

Dessutom kan CMU-portalen ha database/månadsendpoint. Den är inte krav för V1.1 om 30d/1y/5y fungerar.

---

# 5. Station sync

Skapa exempelvis:

```text
cron/sync_dustboy_stations.php
```

eller:

```text
scripts/sync_dustboy_stations.php
```

## Standard
Dry-run utan `--apply`.

Exempel:

```bash
php scripts/sync_dustboy_stations.php
php scripts/sync_dustboy_stations.php --apply
```

## Urval
Prioritet:

1. `station_ids` i extern config om listan är satt.
2. annars optional auto-discover i geografiskt område.
3. auto-discover måste vara explicit enabled.

## Geografi
Målområde är Mueang Chiang Mai.

Använd:
- stationens lat/lon.
- center/radius config.
- eller DustBoy nearme-endpoint om den verifieras.

Importera inte hela Thailand av misstag.

## Stationfält
Sätt:

```text
provider = dustboy
provider_station_code = verkligt DustBoy-ID
is_primary = 0
affects_official_status = 0
enabled = 1
station_type = GROUND
source_metadata_json = relevant source metadata
```

För namn/adress:
- använd API-data om tillgänglig.
- translitterera inte maskinellt om korrekt namn saknas.
- fallback ska vara neutral, t.ex. `DustBoy 123`.

---

# 6. Timvis fetch trots 10-minuters collector

Nuvarande `collect_air` kör var 10:e minut.

Implementera provider due-check.

DustBoy ska inte anropas om senaste lyckade DustBoy-fetch ligger inom:

```text
minimum_fetch_interval_minutes
```

standard exempel:
```text
55
```

Syfte:
- max cirka ett live-anrop/timme.
- Air4Thai kan fortsätta var 10:e minut.
- DustBoy rate limit respekteras.

Provider health eller cache ska kunna avgöra senaste success.

Om DustBoy inte är due:
```json
{"status":"skipped","reason":"not_due"}
```

Inte failure.

---

# 7. Normalisering

DustBoy-mätning ska normaliseras till befintlig measurementmodell:

```php
[
  'provider' => 'dustboy',
  'provider_station_code' => '...',
  'measured_at' => 'UTC datetime',
  'source_measured_at' => 'source time',
  'source_aqi' => null,
  'source_aqi_scale' => null,
  'source_aqi_pollutant' => null,
  'pm25_ug_m3' => ...,
  'pm10_ug_m3' => ...,
  'temperature_c' => ...,
  'humidity_pct' => ...,
  'source_status' => 'supplementary',
  'raw_payload' => ...
]
```

Temperatur och humidity ska mappas **endast om verklig response har tillförlitliga fält**.

Saknat värde:
```text
null
```

Inte 0.

Sentinelvärden som `-1`, `-999` ska fortsatt hanteras som invalid/null enligt providerlogiken.

---

# 8. AQI-princip

DustBoy ska inte tilldelas ett påhittat officiellt TH AQI.

Om källan bara ger PM2.5:

```text
source_aqi = null
source_aqi_scale = null
```

Frontend får använda en separat PM2.5 display severity för färg/visualisering, men:
- den ska vara konfigurerbar.
- etiketten ska vara PM2.5.
- den ska inte heta TH AQI.

---

# 9. Backfill CLI

Skapa ett dedikerat kommando.

Exempel:

```bash
php cron/backfill_dustboy.php --station=dustboy:123 --period=30d
php cron/backfill_dustboy.php --station=dustboy:123 --period=1y
php cron/backfill_dustboy.php --station=dustboy:123 --period=5y
```

Optional flera stationer:

```bash
php cron/backfill_dustboy.php --period=30d --all
```

men rate-limit ska respekteras.

## Krav
- inget on-demand backfill från publik webrequest.
- station måste finnas/enabled.
- endast `dustboy:*`.
- period allowlist.
- CLI-resultat anger inserted/updated/unchanged.
- bygger/rebuildar daily summary efter lyckad backfill.
- stoppar innan osäker API-rate-limit överskrids.
- ingen token i output.

---

# 10. Raw payload

Det är bra att spara raw payload per measurement för spårbarhet.

Men kontrollera att:
- responsen inte råkar innehålla account/API credential.
- endast mätdata sparas.
- request headers aldrig serialiseras i payload.

---

# 11. Attribution

Publik UI ska visa länk/attribution:

```text
CMU DustBoy / CMU CCDC
```

Behåll Air4Thai attribution separat.

---

# 12. Felhantering

Exempel på provider codes:

```text
PROVIDER_DISABLED
AUTH_FAILED
RATE_LIMITED
INVALID_SCHEMA
INVALID_TIMESTAMP
VALUE_RANGE
UPSTREAM_TIMEOUT
UPSTREAM_HTTP_ERROR
```

DustBoy-failure får inte stoppa Air4Thai.

---

# 13. Testfixture

Utöka `tests/fixtures/dustboy/`.

Minst:
- latest-normal.json
- latest-missing-pm10.json
- latest-bad-time.json
- history-30d.json
- history-1y-sample.json
- history-invalid.json

Fixtures ska vara anonymiserade om live payload används.
