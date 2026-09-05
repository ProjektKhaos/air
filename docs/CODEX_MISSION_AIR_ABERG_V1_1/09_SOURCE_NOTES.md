# SOURCE NOTES
<!-- Senast uppdaterad: 2026-09-03 12:17 Asia/Bangkok | KlⒶssⓔ & Ⓐberg -->

## Kodbas som missionen bygger på
`air(1).zip`

Verifierade centrala paths:
```text
app/Config.php
app/ApiPresenter.php
app/Providers/Air4ThaiProvider.php
app/Providers/DustBoyProvider.php
app/Providers/OpenMeteoAirProvider.php
app/Repositories/MeasurementRepository.php
app/Repositories/ProviderHealthRepository.php
app/Repositories/StationRepository.php
app/Services/AirQualityEngine.php
app/Services/DashboardService.php
app/Services/HealthService.php
cron/collect_air.php
cron/collect_forecast.php
cron/retention.php
api/current.php
api/stations.php
api/history.php
app/views/home.php
app/views/stations.php
app/views/station.php
assets/js/home.js
assets/js/stations.js
assets/js/station.js
sql/schema.sql
sql/seed.sql
config/cron/chiang-mai-air-watch
```

## DustBoy-dokument från Hasse
Missionen bygger även på tre uppladdade CMU CCDC/DustBoy-guider:

1. `ขั้นตอนการลงทะเบียนขอรับ API.pdf`
   - konto/API-registrering.
   - verifiering.
   - val av station/device.
   - Bearer token/API key.
   - ungefär timvis uppdatering.
   - rate-limit enligt guiden.

2. `ขั้นตอนการลงทะเบียนดาวโหลดย้อนหลัง 30 วัน.pdf`
   - 30 dagars historik/downloaddata.

3. `ขั้นตอนการดึงข้อมูลย้อนหลังผ่าน API รายปี.pdf`
   - 1 år.
   - 5 år.
   - station/device-ID.
   - Bearer token.
   - historisk JSON-data.

## Viktig implementationregel
När dokumentbild och live API skiljer sig ska den verkliga nuvarande API-responsen/verifierade endpointen vinna, men Codex ska dokumentera avvikelsen.

Codex får inte "fixa" skillnaden genom att gissa osynliga fält.
