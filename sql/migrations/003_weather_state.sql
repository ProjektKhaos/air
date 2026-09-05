-- Chiang Mai Air Watch V1.1 weather context · 2026-09-03
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS weather_state (
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
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
