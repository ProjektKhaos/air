# MASTER MISSION — Chiang Mai Air Watch V1.1
<!-- Senast uppdaterad: 2026-09-03 12:17 Asia/Bangkok | KlⒶssⓔ & Ⓐberg -->

## Mission
Vidareutveckla befintliga `air.aberg.online` till **Chiang Mai Air Watch V1.1** med fokus på lokal sensorbredd, tydlig källseparation och bättre historik.

Sajten ska fortfarande visuellt och funktionellt höra ihop med nuvarande Ping/Air-designfamilj.

---

# 1. Arkitekturmål

## 1.1 Datakällor
Observationer:

### Officiell källa
**Air4Thai / Pollution Control Department**
- befintlig integration behålls.
- officiell TH AQI.
- påverkar områdesstatus.
- får fortsätta utlösa befintlig officiell alert-/risklogik.

### Lokal kompletterande källa
**CMU DustBoy / CMU CCDC**
- ny verklig aktiv provider.
- lokala stationer i/kring Mueang Chiang Mai.
- PM2.5/PM10 och andra fält som API-responsen faktiskt stöder.
- `affects_official_status = 0`.
- får inte skapa eller ändra officiell TH AQI-status.
- AQI ska visas som `—` om källan inte levererar officiell AQI.
- källan ska tydligt märkas `CMU DustBoy`.

Prognos:
- befintlig Open-Meteo/CAMS air-quality-prognos behålls.
- prognos ska fortsatt vara tydligt märkt som modell/prognos.

Väder:
- lägg till en liten separat Open-Meteo weather-context-provider.
- vind, vindriktning och nederbörd är kontext, inte luftkvalitetsmätning.

---

# 2. Multi-provider collector

Nuvarande `cron/collect_air.php` väljer exakt en provider.

Bygg om till stöd för flera observationsproviders.

Föreslagen konfiguration:

```php
'providers' => [
    'observations' => ['air4thai', 'dustboy'],
    'forecast' => 'openmeteo_air',
    // ...
],
```

Behåll bakåtkompatibilitet med gamla:

```php
'providers.observation'
```

under minst V1.1.

## Krav
- Providerlista ska byggas från config.
- Skapa gärna `ObservationProviderFactory`.
- Varje provider körs isolerat.
- Ett DustBoy-fel får inte hindra Air4Thai från att lagras.
- Ett Air4Thai-fel får inte radera/ersätta tidigare Air4Thai-data.
- Provider health uppdateras separat per provider.
- `PROVIDER_DISABLED` för en avsiktligt avstängd provider ska behandlas som skip, inte tekniskt haveri.
- `RiskCoordinator` körs efter att providerförsöken är avslutade.
- collector output ska ange resultat per provider utan att logga hemligheter.

Exempel:

```json
{
  "ok": true,
  "providers": {
    "air4thai": {"status":"success","inserted":2,"updated":0},
    "dustboy": {"status":"success","inserted":14,"updated":0}
  }
}
```

---

# 3. DustBoy

Implementera riktig DustBoy-integration enligt `03_DUSTBOY_MULTI_PROVIDER_SPEC.md`.

Viktig princip:

**Frontend/API får aldrig anropa DustBoy direkt.**

Flöde:

```text
CMU DustBoy
    ↓
server-side collector
    ↓
normalisering
    ↓
MariaDB
    ↓
Chiang Mai Air Watch API
    ↓
frontend
```

---

# 4. Officiell vs lokal data

Behåll befintlig `AirQualityEngine::area()`-princip där endast stationer med:

```text
affects_official_status = 1
```

kan påverka officiell områdesstatus.

Kontrollera hela kodbasen så att DustBoy inte indirekt kan påverka:
- area status
- official severity
- official alerts
- alert transitions
- pushnotiser för officiell AQI

## Viktig health-fix
Nuvarande health check använder global:

```sql
SELECT MAX(measured_at) FROM measurements
```

Det kan bli fel när DustBoy är färskt men Air4Thai är gammalt.

Ändra health till minst:

- `latest_official_observation_at`
- `latest_supplementary_observation_at`

Officiell observations-health ska beräknas från stationer där:

```sql
affects_official_status = 1
```

DustBoy ska inte kunna maskera att Air4Thai har slutat fungera.

---

# 5. Dashboard

Startsidan ska fortsatt prioritera officiell Air4Thai-status högst.

Lägg därefter till en kompakt lokal sammanfattning:

```text
LOCAL SENSOR NETWORK
CMU DustBoy

18 sensors online
Median PM2.5      21.4 µg/m³
Lowest             14.1 µg/m³
Highest            34.8 µg/m³
```

Beräkna endast på:
- enabled DustBoy-station
- live/fresh station
- numeriskt PM2.5

Använd median, inte enbart medelvärde.

Lägg gärna även:
- 1h medianförändring om datatäckning finns.
- antal delayed/stale som sekundär information.

Om inga DustBoy-data finns ska kortet visa ett snyggt neutral state, inte PHP-fel eller tom layout.

---

# 6. Stationer

Stationdetalj ska vara provider-medveten.

## Air4Thai
Visa:
- TH AQI
- kategori
- PM2.5
- PM10
- övriga tillgängliga pollutantfält
- källa: Air4Thai / PCD

## DustBoy
Visa primärt:
- PM2.5
- PM10
- temperatur om API:t faktiskt ger detta
- luftfuktighet om API:t faktiskt ger detta
- lokal trend
- källa: CMU DustBoy / CMU CCDC

Visa inte en stor `TH AQI`-ruta med `—` som huvudinformation för DustBoy.

---

# 7. Karta

Bygg om stationskartan till provider-medveten karta.

Krav:
- filter:
  - Alla
  - Officiella
  - Lokala
- tydligt olika marker-utseende mellan Air4Thai och DustBoy.
- markerfärg ska spegla relevant mätvärde.
- DustBoy-färg får inte kallas AQI om den baseras på PM2.5-koncentration.
- marker popup ska visa provider, station, uppdateringstid och relevanta mätvärden.
- markercluster behålls.
- fitBounds ska fortsatt fungera.
- `cmaw-map-view` ska läsas tillbaka från localStorage vid sidladdning.

---

# 8. Historik

Utöka perioder:

```text
24h
72h
7d
30d
90d
1y
5y
```

API:n får inte skicka tiotusentals datapunkter.

Princip:
- 24h: rå/hourly
- 72h: rå/hourly
- 7d: 3h-aggregation
- 30d: 12h-aggregation
- 90d: daily
- 1y: daily
- 5y: daily eller veckovis beroende på antal punkter

Lägg `daily_air_summary` enligt DB-spec.

Historik på publik request ska alltid läsas ur lokal DB.

Extern DustBoy historical fetch ska endast ske via CLI/backfill.

---

# 9. Väderkontext

Lägg ett diskret väderkort:

```text
AIR CONTEXT

Wind          12 km/h
Direction     NE
Rain          0.0 mm
Updated       12:15
```

Inga påståenden såsom:
> "luften är dålig på grund av vinden"

utan stöd.

Formuleringar får vara:
- "Wind context"
- "Current wind"
- "Rain"
- "Weather context"

---

# 10. Provider/source metadata i API

Utöka `ApiPresenter::station()` med explicit source metadata, exempel:

```json
"source": {
  "provider": "dustboy",
  "label": "CMU DustBoy",
  "classification": "supplementary",
  "official": false
}
```

Observation kan fortsatt ha:

```json
"aqi": null
```

för DustBoy.

Lägg `source_status` i API-respons om det behövs för frontend.

---

# 11. Frontendbuggar som ska fixas i samma mission

## Home severity
När användaren byter home station i `home.js` ska:
- rätt `severity-*`-klass uppdateras på huvudkortet.
- provider/source-label uppdateras.
- layout växla mellan AQI-first och PM2.5-first vid behov.

## Map/list persistence
`stations.js` sparar `cmaw-map-view`, men V1.1 ska också läsa och använda värdet vid init.

## Offline snapshot
Spara explicit:

```json
snapshot_saved_at
```

när snapshot skrivs.

Offline UI ska visa när data faktiskt sparades, inte bara när offline-sidan öppnades.

---

# 12. Språk

All ny publik UI-text ska finnas i:
- `app/lang/en.php`
- `app/lang/th.php`

Ingen ny hårdkodad engelsk UI-text i PHP/JS om den är synlig för användaren.

---

# 13. Säkerhet

- API-token server-side endast.
- Header:
  `Authorization: Bearer <token>`
- token får aldrig loggas.
- token får aldrig hamna i raw payload.
- extern config ska fortsatt användas.
- nya CLI-kommandon ska validera input.
- station IDs ska valideras.
- API-perioder ska allowlistas.
- använd prepared statements.

---

# 14. Prestanda

- ingen extern provider-fetch per webrequest.
- station summary ska undvika N+1 queries.
- karta ska fortfarande fungera med 50+ stationer.
- 5y-historik ska vara aggregerad.
- indexera nya summary-tabeller korrekt.

---

# 15. Leverabler från Codex

Codex ska efter implementation lämna:

1. sammanfattning av ändringar.
2. lista över ändrade/nya filer.
3. migrationsfiler.
4. exakt config-exempel för DustBoy utan hemlig token.
5. exakta kommandon för migration.
6. exakta kommandon för DustBoy station sync.
7. exakta kommandon för 30d/1y/5y backfill.
8. testresultat.
9. eventuella blockerare.
10. kort rollback-plan.

Codex ska inte skriva att något är testat om det inte faktiskt körts.
