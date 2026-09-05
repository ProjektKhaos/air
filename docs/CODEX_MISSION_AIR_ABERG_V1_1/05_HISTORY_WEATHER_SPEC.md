# HISTORY + WEATHER CONTEXT SPEC
<!-- Senast uppdaterad: 2026-09-03 12:17 Asia/Bangkok | KlⒶssⓔ & Ⓐberg -->

# DEL A — HISTORIK

## 1. Perioder

Utöka API + service + UI samtidigt:

```text
24h
72h
7d
30d
90d
1y
5y
```

`Api::period()` ska fortsatt allowlista.

---

## 2. Aggregering

Rekommenderat:

| Period | Källa | Aggregering |
|---|---|---|
| 24h | measurements | raw/hourly |
| 72h | measurements | raw/hourly |
| 7d | measurements | 3h mean |
| 30d | measurements | 12h mean |
| 90d | daily_air_summary | daily |
| 1y | daily_air_summary | daily |
| 5y | daily_air_summary | daily eller weekly |

Håll API-respons under rimlig storlek.

5 år daily ≈ 1825 punkter per serie, vilket är acceptabelt men kan downsamplas till weekly på små skärmar/API om det behövs.

---

## 3. Daily summary

Skapa daily summary enligt DB-spec.

Minst:
- date.
- station_id.
- samples.
- pm25_avg.
- pm25_min.
- pm25_max.
- pm10_avg.
- pm10_min.
- pm10_max.
- temp_avg.
- humidity_avg.
- source_aqi_max för official stationer om användbart.
- created/updated.

---

## 4. Rebuild

Skapa CLI:

```bash
php cron/rebuild_daily_summaries.php --days=7
php cron/rebuild_daily_summaries.php --station=dustboy:123 --from=2025-01-01 --to=2026-09-03
```

Backfill-kommandot ska trigga relevant summary rebuild.

---

## 5. Retention

Rå measurements:
- behåll default 24 månader eller configstyrt.

Daily summaries:
- behåll minst 10 år eller tills vidare.

På så sätt:
- detaljerad raw senaste år.
- kompakt långtidsdata över smoke seasons.

---

## 6. Future-friendly smoke season

V1.1 behöver inte bygga heatmap, men summary-tabellen ska göra V1.2 möjligt:

```text
Jan–May per year
daily PM2.5
days over threshold
monthly mean/max
```

---

# DEL B — VÄDERKONTEXT

## 7. Syfte

Visa väder som kontext:
- vindhastighet.
- vindriktning.
- nederbörd.
- optional temperatur.

Inte påstå orsakssamband.

---

## 8. Provider

Skapa separat provider, t.ex.:

```text
app/Providers/OpenMeteoWeatherProvider.php
```

och gärna interface:

```text
WeatherProviderInterface
```

Verifiera aktuell Open-Meteo Forecast API-dokumentation innan kodning.

Använd server-side fetch.

---

## 9. Lagring

Skapa enkel:

```text
weather_state
```

eller motsvarande cachetabell.

Behovet är främst senaste väderkontext, inte en full meteorologisk databas.

Fält:
- zone_code.
- observed_at/source_time.
- received_at.
- temperature_c nullable.
- wind_speed_kmh nullable.
- wind_direction_deg nullable.
- wind_gust_kmh nullable.
- precipitation_mm nullable.
- source_status.
- raw_payload_json.

---

## 10. Cron

Exempel:

```text
collect_weather.php
```

var 15 eller 30 minut.

Detta är separat från DustBoy limit.

---

## 11. API

Antingen:
- inkludera weather context i `api/current.php`
- eller ny `api/weather.php`

Föredra `api/current.php` om payloaden är liten och home ändå behöver den.

Exempel:

```json
"weather": {
  "source": "Open-Meteo",
  "observed_at": "...",
  "wind_speed_kmh": 12.0,
  "wind_direction_deg": 35,
  "wind_direction_compass": "NE",
  "precipitation_mm": 0
}
```

---

## 12. Compass helper

Skapa testad helper för:
```text
N NE E SE S SW W NW
```

Hantera null.

---

## 13. UI

Litet card.

Exempel:

```text
AIR CONTEXT
Wind      NE 12 km/h
Rain      0.0 mm
Updated   12:15
```

Attribution:
```text
Weather data: Open-Meteo
```

---

## 14. Failure

Om weather provider ligger nere:
- air quality ska fortsätta fungera.
- weather card visar unavailable/hidden.
- health kan rapportera provider separat.
