# UI / MAP / LOCAL SUMMARY SPEC
<!-- Senast uppdaterad: 2026-09-03 12:17 Asia/Bangkok | KlⒶssⓔ & Ⓐberg -->

# 1. Designprincip

Behåll nuvarande Air Watch-design.

Ingen ny dashboardstil som känns som ett annat projekt.

Återanvänd:
- cards.
- typografi.
- spacing.
- light/dark theme.
- material symbols.
- befintliga severity/freshness-principer.
- PWA/mobile-first.

---

# 2. Home page

Prioriteringsordning:

1. officiell Air4Thai-status.
2. officiell 24h chart.
3. local DustBoy summary.
4. stationsöversikt.
5. forecast.
6. weather context.
7. advisory.

Ordningen kan justeras något för mobil, men official status ska vara tydligt främst.

---

# 3. Official source card

Rubrikförslag:

EN:
```text
OFFICIAL AREA STATUS
```

TH:
översätt naturligt i språkfil.

Källrad:
```text
Air4Thai / PCD
```

Visa TH AQI.

---

# 4. Local sensor summary

Ny servicefunktion, exempel:

```php
DashboardService::localSensorSummary()
```

Returnera:

```php
[
  'provider' => 'dustboy',
  'live_count' => 18,
  'delayed_count' => 2,
  'median_pm25' => 21.4,
  'min_pm25' => 14.1,
  'max_pm25' => 34.8,
  'median_change_1h' => 2.7,
  'generated_at' => ...
]
```

Median ska räknas korrekt även vid jämnt antal.

Exkludera:
- null.
- stale/offline om sammanfattningen säger live.
- disabled station.

Visa inga falska 0-värden.

---

# 5. Stationslista

Lägg filter högst upp:

```text
[ All ] [ Official ] [ Local ]
```

Optional:
```text
[ Live only ]
```

## Air4Thai card
Stor:
```text
TH AQI 47
```

Sekundärt:
```text
PM2.5 18.0 µg/m³
```

## DustBoy card
Stor:
```text
PM2.5
21.4 µg/m³
```

Sekundärt:
```text
PM10
Temp
Humidity
```

om tillgängligt.

Källbadge:
```text
CMU DustBoy
```

---

# 6. Home station

Nuvarande home station kan sparas lokalt.

V1.1 ska stödja DustBoy som home station men:
- header-kortet byter presentation.
- DustBoy får PM2.5-first.
- Air4Thai får AQI-first.
- källa ändras dynamiskt.
- severity-class uppdateras korrekt.

Om tidigare vald station tas bort/disabled:
- fallback till configured default.
- inga JS-fel.

---

# 7. Karta

## Filter
```text
All
Official
Local
```

Filtrering ska ske klient-side på redan laddade stationsdata.

## Marker
Exempel:

Air4Thai:
- stjärna / diamant / annan tydlig official form.

DustBoy:
- rund marker.

Freshness kan påverka opacity/border.

## Färg
Air4Thai:
- färg efter officiell AQI category.

DustBoy:
- färg efter PM2.5 concentration display band.
- legend ska explicit säga `PM2.5`, inte `AQI`.

Unknown:
- neutral grå.

## Popup Air4Thai
```text
Yupparaj Wittayalai School
Official · Air4Thai / PCD
TH AQI 47
PM2.5 18 µg/m³
Updated 12:10
[View details]
```

## Popup DustBoy
```text
DustBoy / station name
Local · CMU DustBoy
PM2.5 21.4 µg/m³
PM10 34.0 µg/m³
Updated 12:10
[View details]
```

---

# 8. Map state persistence

Nuvarande JS skriver:

```text
cmaw-map-view
```

V1.1 ska vid load:
- läsa nyckeln.
- verifiera `list|map`.
- aktivera rätt panel.
- initiera Leaflet endast när kartpanelen används.

---

# 9. Leaflet scaling

Behåll MarkerCluster.

Målet är att utan märkbar seghet visa minst:
```text
50–100 stationer
```

Ingen external fetch per marker.

---

# 10. Station detail

Header ska visa providerbadge.

Air4Thai:
```text
OFFICIAL
Air4Thai / PCD
```

DustBoy:
```text
LOCAL SENSOR
CMU DustBoy / CMU CCDC
```

DustBoy ska inte ha tom stor AQI-komponent.

---

# 11. Chart

Chart.js ska fortsatt användas.

PM2.5:
primär serie.

PM10:
sekundär.

För 1y/5y:
- färre points.
- tooltip ska visa daily/weekly aggregation.
- aggregation label ska matcha API-respons.

---

# 12. Accessibility

- filterknappar med aria-pressed.
- marker popup ska fungera med keyboard där Leaflet stöder.
- inga statusar som enbart kommuniceras med färg.
- textlabel ska alltid finnas.
- dark mode kontrast ska kontrolleras.

---

# 13. Mobile

Testa särskilt:
- 360 px.
- 390 px.
- 430 px.

Ingen horisontell scroll på:
- home.
- stations.
- station detail.
- map filters.
- history period select.
