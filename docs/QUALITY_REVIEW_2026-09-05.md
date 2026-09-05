# Quality review — 2026-09-05, V1.1.2

Reviewed production pages, EN/TH, light/dark views, all main APIs, station history, provider collection, configuration, PWA/offline behavior and access controls.

## Corrections

- Offline page accepts the saved snapshot wrapper and displays its original save time; supplementary stations display PM2.5 first.
- Offline selection and network loss retain OFFLINE labels instead of restoring LIVE from a saved observation.
- Successful localStorage writes are optional; blocked storage no longer turns a valid API response into a failed update.
- Home charts refresh even when the responsible station is unchanged; recovered history requests clear the error banner.
- History requests ignore late responses from previously selected periods/stations.
- Official status, local summary, forecast, station rows, weather and advisory refresh with the current response.
- PWA shell matches versioned static requests and uses cache/asset version 1.1.2.
- Provider list overrides replace lists, allowing DustBoy to be removed or the list to be cleared.
- Each observation-provider iteration initializes its station selection independently.
- Old or disabled weather is withheld from current presentation. Temporary weather server/transport failures retry at most twice; authorization/schema errors do not retry.
- Invalid weather calendar dates are rejected; missing model US AQI remains null; oversized responses get the correct error code.
- Unknown station detail URLs return HTTP 404.

## Verification

- PHPUnit unit: 40 tests / 83 assertions, passed.
- MariaDB integration: 14 tests / 61 assertions, passed against the isolated test database.
- Browser suite: 68 cases across 360/390/430/1280 px. One Chromium process crash required a targeted rerun; distinguish this test-runner failure from application assertions.
- Browser tests include 100 map markers, axe, page errors/overflow, EN/TH, themes, saved snapshot timestamps, station selection during failure, and a fully offline navigation with the installed service worker.
- Public pages and APIs return expected 200 responses; unknown station returns 404; private source/docs paths return 403.
- PHP/JS/JSON syntax checks and Composer audit passed. Weather collection passed; production health is checked at handoff.
- Test dependencies and results remain in `/var/tmp/cmaw-review-20260905`, outside production.

## Scope and remaining external work

Physical Android/iPhone push and Home Screen installation remain operator-confirmed as reported on 2026-09-05. No notifications were sent during review.

Previously incomplete DustBoy archive imports for 5263/5264 and independent 30d/1y upstream schema checks are not claimed complete by this review. The retry change reduces transient weather failures but cannot guarantee upstream availability.

Pre-review code backup: `/var/backups/chiang-mai-air-watch/pre-review-20260905.tar.gz`.
Current release: `/var/backups/chiang-mai-air-watch/chiang-mai-air-watch-v1.1.2-20260905.tar.gz`, with adjacent SHA-256 file.
