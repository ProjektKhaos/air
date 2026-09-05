# CODEX MISSION PACK — Chiang Mai Air Watch V1.1
<!-- Senast uppdaterad: 2026-09-03 12:17 Asia/Bangkok | KlⒶssⓔ & Ⓐberg -->

## Projekt
**Chiang Mai Air Watch**  
Publik URL: `https://air.aberg.online`

Detta missionspaket gäller en **vidareutveckling av den befintliga sajten**, inte en ny implementation från noll.

Utgångsläget är källpaketet `air(1).zip`, granskat 2026-09-03.

## Huvudmål
Gör V1.1 till en riktig multi-provider-lösning där:

- **Air4Thai / PCD** fortsätter vara officiell källa för TH AQI och officiell områdesstatus.
- **CMU DustBoy / CMU CCDC** blir en parallell lokal, kompletterande mätkälla för Mueang Chiang Mai.
- DustBoy-data får aldrig av misstag presenteras som officiell TH AQI.
- kartan blir provider-medveten och betydligt mer användbar.
- lång historik kan visas utan att frontenden laddar orimliga mängder rådata.
- väder/vind kan visas som kontext utan att påstå kausalitet.
- befintligt PWA-upplägg, språk, design, push, offline och säkerhetsmodell bevaras.

## Körordning för Codex
Läs filerna i denna ordning:

1. `01_MASTER_MISSION_V1_1.md`
2. `02_CURRENT_STATE_FINDINGS.md`
3. `03_DUSTBOY_MULTI_PROVIDER_SPEC.md`
4. `04_UI_MAP_LOCAL_SUMMARY_SPEC.md`
5. `05_HISTORY_WEATHER_SPEC.md`
6. `06_DATABASE_MIGRATION_SPEC.md`
7. `07_TEST_ACCEPTANCE_RELEASE.md`
8. `08_FUTURE_BACKLOG_V1_2.md`

## Viktiga arbetsregler
- Arbeta vidare i befintlig arkitektur och stil.
- Gör inte en onödig framework-rewrite.
- Rör inte `ping.aberg.online`.
- Rör inte DNS, TLS, Apache-vhost eller server-global konfiguration om inte Hasse uttryckligen ber om det.
- Lägg aldrig DustBoy API-nyckeln i Git/repo, HTML, JavaScript, loggar eller API-respons.
- Befintlig extern configmodell ska behållas:
  - `CMAW_CONFIG_FILE`
  - standard: `/etc/chiang-mai-air-watch/config.php`
- Alla ändringar ska vara bakåtkompatibla där det är rimligt.
- Alla nya PHP/HTML-filer ska ha tydlig versions-/uppdateringsrad på rad 2.
- Uppdatera motsvarande versionsrad i befintliga filer som ändras.
- Lägg pedagogiska kodkommentarer där logik är viktig att förstå eller ändra senare.
- Databasmigreringar måste vara idempotenta och får inte kräva att hela databasen återskapas.
- API-endpoints får inte kontakta DustBoy/Open-Meteo direkt på besökarens request. Publika requests ska läsa lokal cache/databas.

## Definition av V1.1
V1.1 är klar när Air4Thai och DustBoy kan leva sida vid sida, frontend tydligt skiljer officiell och lokal data, lång historik fungerar, kartan har provider-filter och provider-medvetna markörer, samt regressions-/acceptanstester är genomförda.

NASA FIRMS, avancerad smoke-season heatmap och helt användarstyrda pushtrösklar är **V1.2**, se backlogfilen.
