# Ping Flood Watch V1.2 reference architecture

Updated: 2026-09-02 18:40 Asia/Bangkok · Chiang Mai Air Watch V1.0

## Reference inspected

The production reference at `/var/www/abergonline/ping` was inspected before Air Watch application files were created. The inspection covered the public entry points, `app/` bootstrap/config/database/view/translation layers, providers, repositories, services, all API and cron entry points, frontend assets, SQL/migrations/seeds, operational scripts, Apache/cron/logrotate configuration, PWA files, PHPUnit/Playwright configuration, tests and operational documentation.

Ping was read only. Air Watch never imports Ping classes at runtime and never connects to a Ping database or configuration file.

## Patterns reused

- Small framework-free PHP entry points bootstrapping PSR-4 classes and rendering escaped PHP views.
- Central nested configuration with an external production file and a portable `base_url`/`url()` helper.
- PDO repositories with native prepared statements, transactions, UTC storage and explicit public presenters.
- Provider adapters behind interfaces, bounded cURL requests, normalized payloads and sanitized `ProviderException` codes.
- Source-hash deduplication and an immutable revision record before replacing corrected measurements.
- Materialized station/forecast state for fast dashboards, with freshness calculated from source measurement time.
- Collector locks, collector run records, provider health and a machine-readable health endpoint that can return 503.
- Risk state, alert reconciliation, neutral notification outbox, row claiming, retries, expiry, superseding and delivery history.
- Strict JSON success/error envelopes, same-origin JSON POST checks, body limits and database-backed rate limiting.
- Mobile-first centered shell, local fonts, Chart.js, Leaflet/markercluster, EN/TH translations, theme persistence and bottom navigation.
- Service-worker application-shell caching, network-only live APIs, an offline fallback and an explicitly labelled local last-known snapshot.
- Least-privilege database provisioning, external secrets, separate test database, backups, cron, logrotate, Apache path denial, CSP and release checksums.

## Flood concepts replaced

- Water stations, gauge/MSL levels, discharge and capacity become provider-aware air stations and pollutant concentrations.
- River warning thresholds and upstream/downstream roles become official Thailand AQI categories and a worst-fresh-official-station area policy.
- Rain forecasts become separately stored CAMS/Open-Meteo air-quality model forecasts.
- River/weather/combined risks become observed air quality, forecast risk and a cautious combined advisory.
- Water-level trends become PM2.5 changes in µg/m³; positive values mean worsening.
- Flood incident text and icons become source-based air-quality guidance and neutral air/haze iconography.

## Components copied nearly unchanged in responsibility

Configuration merging, database connection setup, logging, translation lookup, view rendering, API envelopes, rate limiting, collector locks/runs, provider-health persistence, push-subscription validation, outbox claiming/retry/expiry, Web Push transport, theme initialization, refresh scheduling, offline handling and the production operations scripts retain their proven responsibilities. Namespaces, identifiers, schemas, copy, configuration keys and test expectations are Air-specific.

## Hardening retained

- TLS verification, connect/total timeouts, response-size limits, HTTP/content-type/JSON validation and schema/range checks.
- No provider keys or raw payloads in browser responses; logs redact endpoints, keys and user-agent details.
- Runtime PDO accounts cannot create or alter schema.
- Private directories, dependencies, SQL, Markdown, logs, backups, dotfiles and source configuration are denied by Apache.
- API and dynamic-page responses are `no-store`; versioned static assets are immutable.
- Alert state changes and outbox insertion are transactional; dispatch occurs only after commit.
- Failed refreshes preserve the last valid UI while freshness/provider state remains honest.
- A mock provider is rejected by production configuration.
- Release/rollback artifacts exclude secrets, mutable storage, database dumps, `node_modules` and browser artifacts.

## Unique Air Watch paths and identifiers

| Purpose | Air Watch value |
|---|---|
| PHP namespace | `ChiangMaiAirWatch\\` |
| Composer package | `abergonline/chiang-mai-air-watch` |
| Production database | `chiang_mai_air_watch` |
| Test database | `chiang_mai_air_watch_test` |
| Runtime account | `cmaw_runtime@localhost` |
| External configuration | `/etc/chiang-mai-air-watch/config.php` |
| Backup/release directory | `/var/backups/chiang-mai-air-watch/` |
| VAPID subject | `https://air.aberg.online/` |
| Service-worker cache | `chiang-mai-air-watch-shell-v1.0.0` |
| Language cookie | `cmaw_lang` |
| Browser storage prefix | `cmaw-` |
| Cron/lock/log names | `chiang-mai-air-watch` / `cmaw-*` |

Ping's production database credentials, VAPID pair, external configuration, tables, service-worker cache, vhost, certificate, cron files, lock files, backups and logs are explicitly excluded from reuse.
