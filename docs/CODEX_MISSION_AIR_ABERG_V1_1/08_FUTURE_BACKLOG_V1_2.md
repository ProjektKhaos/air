# FUTURE BACKLOG — V1.2+
<!-- Senast uppdaterad: 2026-09-03 12:17 Asia/Bangkok | KlⒶssⓔ & Ⓐberg -->

Dessa funktioner är medvetet inte blockerande för V1.1.

# P1 efter stabil V1.1

## 1. Smoke season heatmap
Årsjämförelse:
- Jan.
- Feb.
- Mar.
- Apr.
- May.

Visualisera daily PM2.5 från `daily_air_summary`.

## 2. År mot år
Exempel:
```text
2024
2025
2026
```

- monthly mean.
- max.
- antal dagar över vald koncentrationsnivå.

## 3. CSV export
Station + period:
```text
30d
90d
1y
5y
```

Export från lokal DB.

---

# P2

## 4. NASA FIRMS fire hotspots
Separat datakälla.

Mål:
- hotspots inom t.ex. 50/100/150 km.
- kartlager.
- antal senaste 24/48 h.
- separat attribution.
- ingen slutsats att en viss hotspot orsakar en viss PM2.5-spik.

## 5. Smoke context card
Exempel:
```text
Active hotspots within 100 km: 23
Closest detected hotspot: 38 km
```

## 6. Wind arrows/map overlay
Visa väderriktning tillsammans med brandlager.

---

# P3

## 7. Smart push
Användaren väljer:
- official TH AQI.
- PM2.5 threshold.
- favorite station.
- rising trend.

Behöver egen subscription preference-modell.

## 8. Station favorites
Flera favoriter i localStorage eller server-side anonymt.

## 9. System status page
Visa:
- Air4Thai.
- DustBoy.
- Open-Meteo air.
- Open-Meteo weather.
- collectors.
- last success.

Ingen intern stacktrace eller hemlig information.

---

# P4

## 10. "Best air nearby"
Visa lokala live-sensorer sorterade efter lägst PM2.5.

Viktigt:
- presentation som sensorvärden.
- inte hälsolöfte.

## 11. Spatial interpolation
Eventuell heatmap mellan sensorer.

Endast om:
- sensortäthet tillräcklig.
- metod tydligt märks som interpolerad.
- råa mätstationer fortsatt synliga.

---

# Ej önskat
Undvik:
- att konvertera DustBoy till "officiell AQI" utan källstöd.
- att blanda forecast och observation utan märkning.
- att skapa dramatisk "smoke emergency"-text från en enda sensor.
- att låta publika browser requests bränna externa API quotas.
