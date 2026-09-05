# Providers in V1.1

Air4Thai is the only official observation provider. The allowlist is station-code based (`35t`, `36t`), not text matching. Current observations run every ten minutes; the official history interface supplied the initial 30-day hourly backfill. Source AQI is accepted only from Air4Thai and labelled `TH_AQI_2023`. Sentinel values `-1` and `-999`, blank values and null become database null.

The Air4Thai endpoint currently serves an invalid public intermediate chain. The provider uses a private trust directory containing official Let's Encrypt YR1/YR2/YR3 issuer certificates. Hostname and peer verification remain enabled; no HTTP or `verify=false` fallback exists. Fingerprints are recorded in `PROVIDER_VALIDATION_AIR4THAI.md`.

CMU DustBoy is a supplementary observation provider. The official `/api/dustboy/station` contract returns the account-selected online sensors, and the API service is restricted to outdoor installations. The application then applies its own 15 km radius around `18.7883,98.9853` and caps the result at ten. The installation ID is canonical; `dustboy_id` is metadata only. Station sync is dry-run unless `--apply` is supplied. Missing stations are never automatically disabled. Latest collection is due no more often than every 55 minutes, and a database-backed named-lock ledger limits all DustBoy work to ten requests in a rolling hour. DustBoy rows have `affects_official_status=0`; they cannot influence TH AQI, area status, transitions, alerts, or push.

DustBoy historical imports are CLI-only and accept only `30d`, `1y`, or `5y` for already-synced `dustboy:*` stations. Values are normalized and deduplicated through the same measurement/revision storage as Air4Thai. Sentinel, timestamp, and range anomalies become null or a sanitized provider error. Temperature and humidity remain hidden until their live fields, units, and quality are verified. The API key belongs only in `/etc/chiang-mai-air-watch/config.php`.

Open-Meteo Air Quality is queried separately at `18.7883,98.9853` every 30 minutes for CAMS Global PM2.5, PM10, O₃, NO₂, SO₂, CO and model US AQI. It is always labelled model/forecast with attribution.

Open-Meteo Forecast is queried every 15 minutes for current temperature, wind speed/direction/gust, and precipitation. It is cached in `weather_state` and shown only as context; no causal claim is made about current air quality.

Production status on 2026-09-04: Air4Thai, both Open-Meteo providers, and DustBoy latest observations are `VERIFIED LIVE`. Six account-selected DustBoy stations passed the radius check and are active. Four stations completed 5y imports and summary rebuilds; CMU archive access for `5263` failed after a timeout/authorization response and `5264` is pending the rolling request window. Archive failures are reported by the CLI without replacing successful latest-observation health. Air health is HTTP 200/`ok`.
