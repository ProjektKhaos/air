#!/usr/bin/env bash
set -euo pipefail
if [[ ${EUID} -ne 0 ]]; then echo "Run as root." >&2; exit 77; fi
if [[ $# -ne 2 ]]; then echo "Usage: $0 /absolute/chiang-mai-air-watch-release.tar.gz /absolute/chiang-mai-air-watch.sql.gz" >&2; exit 64; fi
artifact="$1"; database_dump="$2"; target="/var/www/abergonline/air"
[[ "$artifact" == /var/backups/chiang-mai-air-watch/* && -f "$artifact" ]] || { echo "Artifact must be an existing Air backup." >&2; exit 66; }
[[ "$database_dump" == /var/backups/chiang-mai-air-watch/* && -f "$database_dump" ]] || { echo "Database dump must be an existing Air backup." >&2; exit 66; }
timestamp="$(date -u +%Y%m%dT%H%M%SZ)"; safety="/var/backups/chiang-mai-air-watch/pre-rollback-${timestamp}.tar.gz"
tar -czf "$safety" -C "$(dirname "$target")" "$(basename "$target")"
cron_file="/etc/cron.d/chiang-mai-air-watch"; cron_disabled="/etc/cron.d/.chiang-mai-air-watch.rollback"
if [[ -f "$cron_file" ]]; then mv "$cron_file" "$cron_disabled"; fi
trap 'if [[ -f "$cron_disabled" ]]; then mv "$cron_disabled" "$cron_file"; fi' EXIT
tar -xzf "$artifact" -C "$(dirname "$target")"
gzip -dc "$database_dump" | mariadb chiang_mai_air_watch
apache2ctl configtest
systemctl reload apache2
if [[ -f "$cron_disabled" ]]; then mv "$cron_disabled" "$cron_file"; fi
trap - EXIT
echo "Air-only rollback complete. Safety backup: $safety"
