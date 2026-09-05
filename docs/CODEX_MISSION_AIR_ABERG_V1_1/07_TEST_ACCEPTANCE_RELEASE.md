# TEST / ACCEPTANCE / RELEASE CHECKLIST
<!-- Senast uppdaterad: 2026-09-03 12:17 Asia/Bangkok | KlⒶssⓔ & Ⓐberg -->

Codex ska bocka av det som faktiskt körts.

Skriv inte PASS om testet inte körts.

# A. Static / PHP

- [ ] `php -l` på alla ändrade PHP-filer.
- [ ] Composer autoload fungerar.
- [ ] inga API keys i repo:
  - [ ] grep efter Bearer token.
  - [ ] grep efter verklig DustBoy key.
- [ ] inga debug dumps kvar.
- [ ] inga `var_dump`, `print_r` i publika paths.

---

# B. Unit tests

Minst:

## DustBoy parser
- [ ] normal latest.
- [ ] PM10 saknas.
- [ ] PM2.5 saknas.
- [ ] bad timestamp.
- [ ] sentinel -1.
- [ ] sentinel -999.
- [ ] value-range.
- [ ] history sample.

## Multi-provider
- [ ] Air4Thai success + DustBoy success.
- [ ] Air4Thai success + DustBoy failure.
- [ ] Air4Thai failure + DustBoy success.
- [ ] DustBoy disabled.
- [ ] DustBoy not due.

## Air quality rules
- [ ] DustBoy `affects_official_status=0` kan inte påverka official area.
- [ ] official area fortsätter välja värsta live official station.
- [ ] DustBoy kan inte trigga official transition.

## Local summary
- [ ] korrekt median udda antal.
- [ ] korrekt median jämnt antal.
- [ ] null ignoreras.
- [ ] stale exkluderas från live summary.

## Compass
- [ ] N.
- [ ] NE.
- [ ] E.
- [ ] null.

---

# C. Integration tests

- [ ] migration på testdatabas.
- [ ] existing V1.0 rows finns kvar.
- [ ] station sync dry-run ändrar inget.
- [ ] station sync --apply skapar DustBoy-station.
- [ ] duplicate sync ger inte dubletter.
- [ ] collect_air lagrar båda providers.
- [ ] provider_health får separata rader.
- [ ] official health kan bli stale även om DustBoy är färsk.
- [ ] history 90d.
- [ ] history 1y.
- [ ] history 5y.
- [ ] daily summary rebuild är idempotent.
- [ ] retention använder config.

---

# D. API tests

## current
- [ ] source metadata finns.
- [ ] Air4Thai official true.
- [ ] DustBoy official false.
- [ ] DustBoy AQI är null om upstream saknar AQI.
- [ ] local summary finns.
- [ ] weather context fungerar eller är graceful null.

## stations
- [ ] providerfält korrekt.
- [ ] bilingual station name.
- [ ] source classification.

## history
- [ ] invalid period → 4xx.
- [ ] invalid station → 4xx/404.
- [ ] 24h.
- [ ] 72h.
- [ ] 7d.
- [ ] 30d.
- [ ] 90d.
- [ ] 1y.
- [ ] 5y.

---

# E. Frontend

Desktop + mobil.

- [ ] Home official card.
- [ ] DustBoy local summary.
- [ ] home station Air4Thai.
- [ ] home station DustBoy.
- [ ] severity class uppdateras efter station switch.
- [ ] source label uppdateras.
- [ ] map/list localStorage återställs.
- [ ] map filter All.
- [ ] map filter Official.
- [ ] map filter Local.
- [ ] marker popup Air4Thai.
- [ ] marker popup DustBoy.
- [ ] no horizontal overflow 360 px.
- [ ] dark mode.
- [ ] light mode.
- [ ] EN.
- [ ] TH.

---

# F. PWA/offline

- [ ] service worker fortfarande registreras.
- [ ] offline shell fungerar.
- [ ] snapshot har `snapshot_saved_at`.
- [ ] offline visar korrekt sparad tid.
- [ ] nya JS/CSS assets finns i cache/versionering där relevant.
- [ ] ingen stale service-worker gör gamla UI:n permanent.

---

# G. Live provider smoke tests

Kör endast om credentials/config finns.

- [ ] Air4Thai live.
- [ ] DustBoy live.
- [ ] token syns inte i output/log.
- [ ] DustBoy response parser matchar verkligt schema.
- [ ] DustBoy timvis due-check fungerar.
- [ ] weather provider live.

Om live provider-test inte kan köras:
markera det tydligt som NOT RUN.

---

# H. Cron

- [ ] `collect_air` manuellt.
- [ ] DustBoy körs när due.
- [ ] DustBoy skip när not due.
- [ ] `collect_forecast`.
- [ ] `collect_weather`.
- [ ] summary rebuild.
- [ ] retention.
- [ ] push fortfarande fungerar/inte regressad.

---

# I. Security sanity

- [ ] API key endast server-side.
- [ ] external config utanför webroot.
- [ ] no token in logs.
- [ ] raw payload innehåller inga request headers.
- [ ] public APIs gör inga upstream calls.
- [ ] rate limit / push protections kvar.
- [ ] SQL prepared statements.

---

# J. Release order

Föreslagen ordning:

1. backup DB.
2. backup current code.
3. deploy code.
4. kör migrations.
5. lägg external config för DustBoy.
6. station sync dry-run.
7. granska vilka stationer som hittas.
8. station sync --apply.
9. kör `collect_air` manuellt.
10. verifiera Air4Thai + DustBoy DB rows.
11. kör 30d backfill på 1 teststation.
12. rebuild summary.
13. frontend smoke test.
14. aktivera/uppdatera cron.
15. kontrollera health endpoint.
16. kontrollera loggar.

---

# K. Rollback acceptance

Det ska gå att snabbt köra:

```text
DustBoy enabled=false
observations=[air4thai]
```

och få en fullt fungerande Air4Thai-only site.

Nya DB-tabeller behöver inte tas bort för snabb rollback.
