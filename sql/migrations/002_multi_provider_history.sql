-- Chiang Mai Air Watch V1.1 multi-provider history · 2026-09-03
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS daily_air_summary (
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
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (station_id, summary_date),
    KEY idx_daily_air_summary_date (summary_date),
    CONSTRAINT fk_daily_summary_station FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS provider_api_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(64) NOT NULL,
    request_class VARCHAR(32) NOT NULL,
    requested_at DATETIME NOT NULL,
    outcome VARCHAR(24) NOT NULL,
    KEY idx_provider_api_request_window (provider, requested_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
