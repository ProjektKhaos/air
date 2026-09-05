# DustBoy validation

Status: `LATEST VERIFIED LIVE; FOUR OF SIX FIVE-YEAR IMPORTS VERIFIED`.

The CMU CCDC credential is installed only in the protected external configuration. On 2026-09-04 the account holder selected six outdoor stations. `/api/dustboy/station` returned all six; dry-run accepted every row at 0.07–4.09 km from the configured centre, apply inserted six stations, and latest collection inserted six normalized observations. DustBoy is enabled in production and health is HTTP 200/`ok`.

The 5y endpoint and ingestion path completed for installation IDs `3145`, `4445`, `12`, and `4`, producing roughly 97,000 unique historical rows plus deterministic daily summaries. CMU returned a transport timeout and then an authorization response for `5263`; `5264` remains pending behind the rolling request budget. The importer is resumable, and these archive-only outcomes do not overwrite successful live-provider health.

Fixtures cover normal latest data, missing PM2.5/PM10, `-1`, `-999`, invalid timestamp, unreasonable ranges, normal 30d/1y history, and invalid history shape. Integration tests cover dry-run station sync, idempotent apply, canonical installation IDs, official isolation, provider outcome combinations, request due state, rolling-hour request budget, summaries, and long-history reads. The local browser fixture verifies DustBoy selection, PM2.5-first display, source labels, summary statistics, filters and map markers.

Still required after the account holder selects stations: credentialed schema smoke for station/latest data and `data30day/{id}`, `data1year/{id}`, `data5year/{id}`; reviewed sync dry-run; `--apply`; latest collection; resumable backfills; daily summary rebuild; and production UI/health smoke. Only sanitized schema names, counts and normalized samples may be logged. The token must never enter the repository, command output, logs, raw payload, or a public API response.

Documentation source: <https://open-api.cmuccdc.org/?lang=english>.
