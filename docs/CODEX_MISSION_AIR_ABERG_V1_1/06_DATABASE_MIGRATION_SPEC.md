# DATABASE + MIGRATION SPEC
<!-- Senast uppdaterad: 2026-09-03 12:17 Asia/Bangkok | KlⒶssⓔ & Ⓐberg -->

# 1. Grundregel

Kör inte om `schema.sql` mot produktion som enda migrationsstrategi.

Skapa versionerade migrationer i:

```text
sql/migrations/
```

Exempel:
```text
002_multi_provider_history.sql
003_weather_state.sql
```

Använd `schema_migrations`.

Migrationer ska vara säkra att köra på befintlig V1.0-data.

---

# 2. DustBoy stationer

Ingen ny tabell krävs.

Använd befintlig `stations`.

DustBoyrad:

```text
provider = dustboy
provider_station_code = ID
is_primary = 0
affects_official_status = 0
source_metadata_json = ...
```

---

# 3. Daily summary

Skapa:

```sql
CREATE TABLE daily_air_summary (
    station_id BIGINT UNSIGNED NOT NULL,
    summary_date DATE NOT NULL,
    samples INT UNSIGNED NOT NULL,

    pm25_avg DECIMAL(10,3) NULL,
    pm25_min DECIMAL(10,3) NULL,
    pm25_max DECIMAL(10,3) NULL,

    pm10_avg DECIMAL(10,3) NULL,
    pm10_min DECIMAL(10,3) NULL,
    pm10_max DECIMAL(10,3) NULL,

    source_aqi_max SMALLINT UNSIGNED NULL,

    temperature_avg DECIMAL(6,2) NULL,
    humidity_avg DECIMAL(6,2) NULL,

    first_measured_at DATETIME NULL,
    last_measured_at DATETIME NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (station_id, summary_date),
    KEY idx_daily_air_summary_date (summary_date),

    CONSTRAINT fk_daily_summary_station
      FOREIGN KEY (station_id)
      REFERENCES stations(id)
      ON DELETE CASCADE
);
```

Justera syntax till MariaDB-version om nödvändigt.

---

# 4. Summary UPSERT

Rebuild ska vara deterministisk.

För en dag/station:
```text
DELETE+INSERT
```
eller:
```text
INSERT ... ON DUPLICATE KEY UPDATE
```

Föredra UPSERT.

---

# 5. Weather state

Skapa kompakt tabell:

```sql
CREATE TABLE weather_state (
    zone_code VARCHAR(64) PRIMARY KEY,
    provider VARCHAR(64) NOT NULL,
    source_time DATETIME NULL,
    received_at DATETIME NOT NULL,

    temperature_c DECIMAL(6,2) NULL,
    wind_speed_kmh DECIMAL(8,2) NULL,
    wind_direction_deg DECIMAL(6,2) NULL,
    wind_gust_kmh DECIMAL(8,2) NULL,
    precipitation_mm DECIMAL(8,2) NULL,

    source_status VARCHAR(32) NOT NULL,
    raw_payload_json JSON NULL,

    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
      ON UPDATE CURRENT_TIMESTAMP
);
```

En rad per zone räcker i V1.1.

---

# 6. Index

Verifiera index för:
- `measurements(station_id, measured_at)`
- daily summary primary key.
- station provider lookup.

Om `measurements` saknar optimalt composite index för history:
lägg till:

```sql
KEY idx_measurements_station_time (station_id, measured_at)
```

men först kontrollera befintligt schema så index inte dupliceras.

---

# 7. Retention

Refaktorera `cron/retention.php` att läsa:

```php
Config::get('retention.measurement_months', 24)
Config::get('retention.forecast_months', 12)
Config::get('retention.operations_months', 12)
```

Lägg:
```php
Config::get('retention.daily_summary_years', 10)
```

eller behåll summaries utan purge.

---

# 8. Migration execution

Codex ska skapa dokumenterade kommandon.

Exempel:

```bash
mariadb -u ... -p chiang_mai_air_watch < sql/migrations/002_multi_provider_history.sql
mariadb -u ... -p chiang_mai_air_watch < sql/migrations/003_weather_state.sql
```

Men använd projektets faktiska DB-rutin om sådan finns.

---

# 9. Backup före migration

Codex ska inte automatiskt radera eller ersätta data.

Releaseinstruktionen ska kräva DB-backup före migration.

Ingen hemlig DB credential ska skrivas i dokumentation.

---

# 10. Rollback

Om SQL är lätt reversibel:
skapa separat rollback eller dokumentera steg.

Minst:
- vilka tabeller/index som lagts till.
- hur nya cron entries kan avaktiveras.
- hur DustBoy kan disable via config utan kodrollback.

Viktig snabb rollback:

```php
'providers' => [
    'observations' => ['air4thai'],
    'dustboy' => ['enabled' => false],
]
```

Sajten ska då fortsätta fungera i Air4Thai-only mode.
