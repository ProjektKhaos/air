#!/usr/bin/env bash
set -euo pipefail
if [[ ${EUID} -ne 0 ]]; then echo "Run this script as root." >&2; exit 1; fi
project_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
install -m 0644 "$project_dir/config/cron/chiang-mai-air-watch" /etc/cron.d/chiang-mai-air-watch
install -m 0644 "$project_dir/config/logrotate/chiang-mai-air-watch" /etc/logrotate.d/chiang-mai-air-watch
install -d -m 0750 -o root -g www-data "$project_dir/storage"
install -d -m 0770 -o www-data -g www-data "$project_dir/storage/logs" "$project_dir/storage/cache" "$project_dir/storage/locks"
find "$project_dir" \( -path "$project_dir/storage" -o -path "$project_dir/vendor" -o -path "$project_dir/node_modules" \) -prune -o -type d -exec chmod 0755 {} +
find "$project_dir" \( -path "$project_dir/storage" -o -path "$project_dir/vendor" -o -path "$project_dir/node_modules" \) -prune -o -type f -exec chmod 0644 {} +
chmod 0755 \
  "$project_dir/cron/collect_air.php" \
  "$project_dir/cron/collect_forecast.php" \
  "$project_dir/cron/collect_weather.php" \
  "$project_dir/cron/backfill_dustboy.php" \
  "$project_dir/cron/rebuild_daily_summaries.php" \
  "$project_dir/cron/dispatch_push.php" \
  "$project_dir/cron/retention.php" \
  "$project_dir/scripts/backup.sh" \
  "$project_dir/scripts/migrate.sh" \
  "$project_dir/scripts/rollback.sh" \
  "$project_dir/scripts/sync_dustboy_stations.php" \
  "$project_dir/scripts/upgrade-config-v1.1.php"
echo "Cron, logrotate, and permissions installed."
