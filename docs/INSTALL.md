# V1.1 installation, activation and rollback

Requirements: PHP 8.3 with PDO MySQL/cURL/GD, MariaDB 10.11, Apache 2.4, Composer 2, and the isolated Node 22 toolchain at `/opt/node-v22.23.2-linux-x64`. V1.1 does not require or authorize changes to Ping, DNS, TLS, Apache, server-global configuration, or system Node 18.

## Upgrade order

1. Back up the Air code, `/etc/chiang-mai-air-watch/config.php`, Air database, and `/etc/cron.d/chiang-mai-air-watch`.
2. Deploy the V1.1 code and run `composer install --no-dev --classmap-authoritative` for the production artifact.
3. Apply `002_multi_provider_history.sql` and `003_weather_state.sql` with `sudo scripts/migrate.sh`. Migration credentials remain administrative; the runtime user keeps only `SELECT`, `INSERT`, `UPDATE`, and `DELETE`.
4. Upgrade the existing external config without displaying secrets: `sudo php scripts/upgrade-config-v1.1.php`. The script creates a timestamped configuration backup, preserves DB/VAPID/rate-limit secrets, installs V1.1 defaults, and refuses an enabled DustBoy provider without a key.
5. Install the Air-specific schedules with `sudo scripts/install-ops.sh`. Run `apache2ctl configtest` as a read-only sanity check; V1.1 does not replace or reload the vhost.
6. Run Air4Thai, forecast, weather, daily-summary and health smoke before DustBoy activation.

```bash
sudo -u www-data php cron/collect_air.php
sudo -u www-data php cron/collect_forecast.php
sudo -u www-data php cron/collect_weather.php
sudo -u www-data php cron/rebuild_daily_summaries.php --days=730
curl -fsS https://air.aberg.online/api/health.php
```

`collect_air.php` remains scheduled every ten minutes and performs an internal 55-minute DustBoy due check. `collect_weather.php` runs every 15 minutes. Recent daily summaries are rebuilt nightly before retention and backup.

## DustBoy secret and activation

Edit only `/etc/chiang-mai-air-watch/config.php` as root. Keep ownership `root:www-data` and mode `0640`. Do not store the token in a shell history, environment file under the web root, documentation, logs, or chat.

The relevant structure is:

```php
'providers' => [
    'observations' => ['air4thai', 'dustboy'],
    'dustboy' => [
        'enabled' => true,
        'api_key' => '<installed directly by the operator>',
        'station_ids' => [],
        'auto_discover' => false,
        'center' => ['latitude' => 18.7883, 'longitude' => 98.9853],
        'radius_km' => 15,
        'maximum_stations' => 10,
        'minimum_fetch_interval_minutes' => 55,
        'maximum_requests_per_hour' => 10,
    ],
],
```

After the key is installed, run the credentialed smoke first. Then review the dry-run output before applying any station rows:

```bash
sudo -u www-data php scripts/sync_dustboy_stations.php
sudo -u www-data php scripts/sync_dustboy_stations.php --apply
sudo -u www-data php cron/collect_air.php
```

The selected account stations remain authoritative, then the 15 km radius and maximum of ten are applied. The command does not automatically disable stations that disappear or go offline.

## DustBoy history and summaries

Backfill accepts an enabled canonical `dustboy:*` station or `--all`, and one allowlisted period. It is resumable through measurement deduplication/revisions. Because all provider requests share the rolling-hour ledger, a rate-budget exit must be retried later.

```bash
sudo -u www-data php cron/backfill_dustboy.php --station=dustboy:INSTALLATION_ID --period=30d
sudo -u www-data php cron/backfill_dustboy.php --station=dustboy:INSTALLATION_ID --period=1y
sudo -u www-data php cron/backfill_dustboy.php --station=dustboy:INSTALLATION_ID --period=5y
sudo -u www-data php cron/backfill_dustboy.php --all --period=5y
sudo -u www-data php cron/rebuild_daily_summaries.php --days=3650
```

Never use a raw `dustboy_id` in the public identifier unless it is also the API installation ID returned as `id`.

## Tests

```bash
vendor/bin/phpunit tests/Unit
sudo bash -c 'set -a; . /etc/chiang-mai-air-watch/test.env; set +a; cd /var/www/abergonline/air; exec sudo -E -u www-data vendor/bin/phpunit tests/Integration'
vendor/bin/phpunit tests/Live/ProviderSmokeTest.php
PATH=/opt/node-v22.23.2-linux-x64/bin:$PATH CMAW_BASE_URL=https://air.aberg.online npm exec playwright test -- --config=playwright.config.js
```

The DustBoy live test skips truthfully until the external credential is present. It must not be converted into a fixture pass or use an undocumented token.

## Fast rollback

The fastest data-preserving rollback is to edit the external config to:

```php
'providers' => [
    'observations' => ['air4thai'],
    'dustboy' => ['enabled' => false],
    'openmeteo_weather' => ['enabled' => false],
],
```

Then restore the prior Air artifact and Air-specific cron definition if a code rollback is required. The new tables may remain; no destructive reverse migration is required. `scripts/rollback.sh` validates explicit Air artifact and dump paths before a full Air-only restore. Never alter Ping files, database, cron, configuration, vhost, or certificate.
