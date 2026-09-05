# Chiang Mai Air Watch V1.1 acceptance report

Executed 2026-09-03–05 on PHP 8.3.6, MariaDB 10.11.14, Apache 2.4.58, Composer 2.7.1, and isolated Node 22.23.2. System Node 18 was not changed. Results below distinguish automated, live, fixture/integration, and user-verified physical-device work.

## Automated results

| Suite | Result | Evidence |
|---|---:|---|
| PHP unit | PASS | 37 tests, 77 assertions |
| MariaDB integration | PASS | 14 tests, 61 assertions, isolated `chiang_mai_air_watch_test`; includes retention, quota skip, provider degradation and true no-op station sync |
| Opt-in provider live smoke | PARTIAL PASS | Air4Thai current/history/CA and both Open-Meteo providers pass; DustBoy selected-station/latest schema is verified through production sync/collection; separate 30d/1y smoke awaits the rolling request window |
| Playwright + axe, local fixture | PASS | 44 tests at 360/390/430/1280 px, including a DustBoy station |
| Playwright + axe, production HTTPS | PASS | 48/48 tests at 360/390/430/1280 px; includes 100 synthetic map stations and axe on the visible map without exclusions |
| PHP/JS/JSON syntax | PASS | 98 PHP, 13 JavaScript, 17 JSON/webmanifest files; no syntax failures |
| Dependency audit | PASS | Composer: no advisories; npm: 0 vulnerabilities |
| V1.0 migration replay | PASS | real pre-V1.1 dump; 1,513 measurements before/after first run/after second run; three new tables; two recorded V1.1 migrations |
| V1.1 backup restore | PASS | restored into temporary DB: 1,515 measurements, 66 daily summaries, one weather row, three migration records |
| Production release archive | PASS | V1.1.1: 1,189 entries; no runtime/test-cache/Node directories; Composer production dependencies only |
| Active production tree | PASS | Node modules, PHPUnit caches, Playwright reports/results and Composer development dependencies removed after testing; recoverable test artifacts stored outside webroot |

## Acceptance matrix

| Area | Status | Notes |
|---|---|---|
| V1.1 mission unpacked | VERIFIED | ten files under `docs/CODEX_MISSION_AIR_ABERG_V1_1/` |
| V1.0 code/DB backup before work | VERIFIED | timestamped archives and SHA-256 under `/var/backups/chiang-mai-air-watch/pre-v1.1/` |
| Multi-provider config and V1.0 fallback | VERIFIED | `observations` list active; legacy `observation` remains supported |
| Provider-isolated collector outcomes | VERIFIED LIVE/AUTOMATED | production Air4Thai and DustBoy both complete successfully; fixture combinations cover failure/disabled isolation |
| Air4Thai official isolation | VERIFIED LIVE/AUTOMATED | only `35t`/`36t`; `affects_official_status=1`; DustBoy cannot change area/transitions/alerts/push |
| DustBoy parser and schema guards | VERIFIED FIXTURE | missing fields, sentinels, invalid time/range/history and null AQI covered |
| DustBoy selected-station sync | VERIFIED INTEGRATION | dry-run changes no station rows; identical apply reports `unchanged`, changed metadata reports `updated`; fallback name and canonical ID covered |
| DustBoy radius/max/account selection | VERIFIED LIVE | six selected outdoor stations accepted at 0.07–4.09 km; 15 km/max ten enforced; auto-discover off |
| DustBoy 55-minute due and rolling request ledger | VERIFIED INTEGRATION | persistent DB ledger, named lock, request class allowlist and quota exhaustion covered; local exhaustion returns collector-compatible `skipped/not_due` without another request |
| DustBoy authentication | VERIFIED LIVE | credential accepted without exposing it in repository, API output, logs, or release files |
| DustBoy credentialed latest | VERIFIED LIVE | six selected stations and six normalized latest observations stored; all six are live |
| DustBoy credentialed history | PARTIAL LIVE | 5y import completed for `3145`, `4445`, `12`, `4`; `5263` archive access failed after timeout/authorization response and `5264` awaits quota; 30d/1y schema smoke pending |
| Measurement dedupe/revisions/nulls | VERIFIED INTEGRATION | V1.0 unique key retained and corrections audited |
| Daily summaries and 90d/1y/5y | VERIFIED INTEGRATION/PRODUCTION API | deterministic rebuild and sample-weighted weekly reads covered |
| Weather context | VERIFIED LIVE | Open-Meteo schema/units/UTC validated; cached server-side; production collector healthy |
| Provider-separated health | VERIFIED | official and supplementary timestamps separate; fresh DustBoy cannot mask stale Air4Thai; enabled DustBoy/weather failure paths covered directly |
| Retention from config | VERIFIED INTEGRATION/PRODUCTION | expiration and preservation boundaries tested for measurements, summaries and request ledger; production manual run passed |
| API source metadata and errors | VERIFIED LOCAL/PRODUCTION | source object, null AQI contract, local summary/weather, seven periods, 400/404 errors, no raw payload |
| Provider-aware home/detail/list/map | VERIFIED LOCAL/PRODUCTION | AQI-first official, PM2.5-first local, filters, shapes, PM bands, source/freshness labels |
| EN/TH, themes, persistence and layout | VERIFIED LOCAL/PRODUCTION | 360/390/430/1280 px; no horizontal overflow; axe clean, including the visible Leaflet map without exclusions |
| Offline/PWA V1.1 | VERIFIED AUTOMATED | shell `v1.1.0`, snapshot save time, API/OSM exclusion and server-rendered fallback |
| Push/outbox regression | VERIFIED AUTOMATED/PRODUCTION NO-OP | dispatcher completed with zero queued jobs; same-origin/rate-limit protections retained |
| Security and private paths | VERIFIED PRODUCTION | no repository/log token marker, no debug calls, API `no-store`, security headers, private paths blocked/not found |
| Runtime database grants | VERIFIED PRODUCTION | `SELECT`, `INSERT`, `UPDATE`, `DELETE` only on the Air database |
| Air cron/logrotate | VERIFIED PRODUCTION | Air 10m, forecast 30m, weather 15m, push 1m, summary/retention/backup nightly |
| TLS/vhost | VERIFIED READ-ONLY | HTTPS SAN is correct, HTTP redirects, Apache configtest is `Syntax OK`; V1.1 made no Apache/TLS change |
| Ping regression | REACHABILITY PASS / EXTERNAL DEGRADED | homepage and status HTTP 200; health was 200/`ok` after deployment on 2026-09-03, but final 2026-09-04 check is 503 because Pings own Thaiwater provider failed twice; Ping files/config/cron were untouched |
| Android push | USER VERIFIED PHYSICAL DEVICE | completed and confirmed by the operator on 2026-09-05 |
| iPhone installed-PWA push | USER VERIFIED PHYSICAL DEVICE | completed and confirmed by the operator on 2026-09-05 |
| Home Screen install UX | USER VERIFIED PHYSICAL DEVICE | physical installation completed and confirmed by the operator on 2026-09-05 |

## Production smoke

Air4Thai collection completed for both allowlisted stations and DustBoy collection completed for all six selected stations. Forecast and weather collectors completed, daily summaries rebuilt, retention completed, and push dispatch completed without a queued job. Health returned HTTP 200/`ok` with separate live official and supplementary timestamps, 24 valid next-day forecast points, and current weather context.

All prescribed public APIs returned valid success envelopes with `Cache-Control: no-store` in English/Thai where applicable. Every history period returned successfully. Invalid period returned 400 and unknown station returned 404. Public JSON contained no `raw_payload` key. Air private application, documentation, SQL and log paths returned 403; equivalent parent-vhost paths returned 404.

## Backups and rollback

The post-migration DB backup is `/var/backups/chiang-mai-air-watch/chiang-mai-air-watch-20260903T061650Z.sql.gz`, SHA-256 `9b69f92f5b725e623c20b26b091ba88e96514ccbc7e9e2f78a0698d451f6b404`, and its restore was verified before release. Fast rollback is `observations=['air4thai']`, `dustboy.enabled=false`, and optionally `openmeteo_weather.enabled=false`; the new tables may remain.

The superseded V1.1.0 artifact is `/var/backups/chiang-mai-air-watch/chiang-mai-air-watch-v1.1.0-20260904T0847Z.tar.gz`. The current production artifact is `/var/backups/chiang-mai-air-watch/chiang-mai-air-watch-v1.1.2-20260905.tar.gz`; its adjacent `.sha256` file is authoritative and was verified after the final archive build.

## Truthful DustBoy handoff

Real DustBoy data is live side by side with Air4Thai. Selection, reviewed dry-run/apply, latest parsing, storage, API/UI rendering and four resumable 5y imports are verified. Two station archives and the separate 30d/1y schema smoke remain explicitly pending; they do not affect live collection or official Air4Thai decisions. No credential is present in repository or public output.

## Final Ping observation

The read-only final regression after DustBoy activation on 2026-09-04 returned HTTP 200 for Ping homepage, status and health. No Ping collector was started manually and no Ping file, configuration, cron entry, database, vhost, or certificate was changed during V1.1 work.

## V1.1.2 quality review

See [QUALITY_REVIEW_2026-09-05.md](QUALITY_REVIEW_2026-09-05.md) for current fixes and verification: 40 unit tests, 14 integration tests, and 68 browser cases passed (one browser process crash passed on targeted rerun). Asset and PWA shell version is 1.1.2. Earlier counts above describe the preceding release.
