-- Chiang Mai Air Watch V1.1 schema · 2026-09-03
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS schema_migrations (version VARCHAR(64) PRIMARY KEY, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, provider VARCHAR(64) NOT NULL, provider_station_code VARCHAR(64) NOT NULL,
 display_name_en VARCHAR(160) NOT NULL, display_name_th VARCHAR(160) NOT NULL, area_en VARCHAR(255) NOT NULL, area_th VARCHAR(255) NOT NULL,
 province_en VARCHAR(100) NOT NULL, province_th VARCHAR(100) NOT NULL, district_en VARCHAR(100) NOT NULL, district_th VARCHAR(100) NOT NULL,
 latitude DECIMAL(10,7) NOT NULL, longitude DECIMAL(10,7) NOT NULL, station_type VARCHAR(32) NOT NULL DEFAULT 'GROUND',
 is_primary TINYINT(1) NOT NULL DEFAULT 0, affects_official_status TINYINT(1) NOT NULL DEFAULT 1, enabled TINYINT(1) NOT NULL DEFAULT 1,
 sort_order INT NOT NULL DEFAULT 0, source_metadata_json JSON NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_station_provider_code (provider, provider_station_code), KEY idx_station_enabled_order (enabled, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS measurements (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, station_id BIGINT UNSIGNED NOT NULL, provider VARCHAR(64) NOT NULL,
 measured_at DATETIME NOT NULL, source_measured_at VARCHAR(64) NOT NULL, received_at DATETIME NOT NULL,
 source_aqi SMALLINT UNSIGNED NULL, source_aqi_scale VARCHAR(32) NULL, source_aqi_pollutant VARCHAR(32) NULL,
 pm25_ug_m3 DECIMAL(10,3) NULL, pm10_ug_m3 DECIMAL(10,3) NULL, pm25_unit VARCHAR(16) NULL, pm10_unit VARCHAR(16) NULL,
 ozone_value DECIMAL(12,3) NULL, ozone_unit VARCHAR(16) NULL, carbon_monoxide_value DECIMAL(12,3) NULL, carbon_monoxide_unit VARCHAR(16) NULL,
 nitrogen_dioxide_value DECIMAL(12,3) NULL, nitrogen_dioxide_unit VARCHAR(16) NULL, sulphur_dioxide_value DECIMAL(12,3) NULL, sulphur_dioxide_unit VARCHAR(16) NULL,
 temperature_c DECIMAL(6,2) NULL, humidity_pct DECIMAL(6,2) NULL, source_status VARCHAR(32) NOT NULL,
 source_hash CHAR(64) NOT NULL, raw_payload_json JSON NULL, revised_at DATETIME NULL, revision_count INT UNSIGNED NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_measurement_station_time (station_id, measured_at), KEY idx_measurement_time (measured_at),
 CONSTRAINT fk_measurement_station FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS measurement_revisions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, measurement_id BIGINT UNSIGNED NOT NULL, previous_payload_json JSON NULL,
 replacement_payload_json JSON NULL, previous_hash CHAR(64) NOT NULL, replacement_hash CHAR(64) NOT NULL, revised_at DATETIME NOT NULL,
 CONSTRAINT fk_measurement_revision FOREIGN KEY (measurement_id) REFERENCES measurements(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS station_state (
 station_id BIGINT UNSIGNED PRIMARY KEY, latest_measurement_id BIGINT UNSIGNED NULL, freshness_status VARCHAR(24) NOT NULL DEFAULT 'offline',
 change_1h_pm25 DECIMAL(10,3) NULL, change_3h_pm25 DECIMAL(10,3) NULL, change_24h_pm25 DECIMAL(10,3) NULL, updated_at DATETIME NOT NULL,
 CONSTRAINT fk_station_state_station FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE,
 CONSTRAINT fk_station_state_measurement FOREIGN KEY (latest_measurement_id) REFERENCES measurements(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS daily_air_summary (
 station_id BIGINT UNSIGNED NOT NULL, summary_date DATE NOT NULL, samples INT UNSIGNED NOT NULL,
 pm25_avg DECIMAL(10,3) NULL, pm25_min DECIMAL(10,3) NULL, pm25_max DECIMAL(10,3) NULL,
 pm10_avg DECIMAL(10,3) NULL, pm10_min DECIMAL(10,3) NULL, pm10_max DECIMAL(10,3) NULL,
 source_aqi_max SMALLINT UNSIGNED NULL, temperature_avg DECIMAL(6,2) NULL, humidity_avg DECIMAL(6,2) NULL,
 first_measured_at DATETIME NULL, last_measured_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY (station_id, summary_date), KEY idx_daily_air_summary_date (summary_date),
 CONSTRAINT fk_daily_summary_station FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS provider_api_requests (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, provider VARCHAR(64) NOT NULL, request_class VARCHAR(32) NOT NULL,
 requested_at DATETIME NOT NULL, outcome VARCHAR(24) NOT NULL, KEY idx_provider_api_request_window (provider, requested_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS forecast_zones (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, code VARCHAR(64) NOT NULL UNIQUE, display_name_en VARCHAR(160) NOT NULL,
 display_name_th VARCHAR(160) NOT NULL, latitude DECIMAL(10,7) NOT NULL, longitude DECIMAL(10,7) NOT NULL,
 enabled TINYINT(1) NOT NULL DEFAULT 1, sort_order INT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS forecast_runs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, provider VARCHAR(64) NOT NULL, received_at DATETIME NOT NULL, model_time DATETIME NULL,
 payload_hash CHAR(64) NOT NULL, raw_payload_json JSON NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_forecast_provider_hash (provider, payload_hash), KEY idx_forecast_received (received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS air_forecast_points (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, forecast_run_id BIGINT UNSIGNED NOT NULL, forecast_zone_id BIGINT UNSIGNED NOT NULL,
 valid_at DATETIME NOT NULL, pm25_ug_m3 DECIMAL(10,3) NULL, pm10_ug_m3 DECIMAL(10,3) NULL, ozone_ug_m3 DECIMAL(12,3) NULL,
 no2_ug_m3 DECIMAL(12,3) NULL, so2_ug_m3 DECIMAL(12,3) NULL, co_ug_m3 DECIMAL(12,3) NULL,
 us_aqi DECIMAL(8,2) NULL, us_aqi_pm25 DECIMAL(8,2) NULL, us_aqi_pm10 DECIMAL(8,2) NULL, source_status VARCHAR(32) NOT NULL,
 UNIQUE KEY uq_air_forecast_point (forecast_run_id, forecast_zone_id, valid_at), KEY idx_air_forecast_valid (forecast_zone_id, valid_at),
 CONSTRAINT fk_air_forecast_run FOREIGN KEY (forecast_run_id) REFERENCES forecast_runs(id) ON DELETE CASCADE,
 CONSTRAINT fk_air_forecast_zone FOREIGN KEY (forecast_zone_id) REFERENCES forecast_zones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS forecast_state (
 forecast_zone_id BIGINT UNSIGNED PRIMARY KEY, latest_forecast_run_id BIGINT UNSIGNED NULL, latest_forecast_received_at DATETIME NULL,
 freshness_status VARCHAR(24) NOT NULL DEFAULT 'offline', updated_at DATETIME NOT NULL,
 CONSTRAINT fk_forecast_state_zone FOREIGN KEY (forecast_zone_id) REFERENCES forecast_zones(id) ON DELETE CASCADE,
 CONSTRAINT fk_forecast_state_run FOREIGN KEY (latest_forecast_run_id) REFERENCES forecast_runs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS provider_health (provider VARCHAR(64) PRIMARY KEY, provider_type VARCHAR(24) NOT NULL, last_success_at DATETIME NULL, last_failure_at DATETIME NULL, consecutive_failures INT UNSIGNED NOT NULL DEFAULT 0, last_error_code VARCHAR(64) NULL, last_error_message VARCHAR(500) NULL, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS weather_state (zone_code VARCHAR(64) PRIMARY KEY, provider VARCHAR(64) NOT NULL, source_time DATETIME NULL, received_at DATETIME NOT NULL, temperature_c DECIMAL(6,2) NULL, wind_speed_kmh DECIMAL(8,2) NULL, wind_direction_deg DECIMAL(6,2) NULL, wind_gust_kmh DECIMAL(8,2) NULL, precipitation_mm DECIMAL(8,2) NULL, source_status VARCHAR(32) NOT NULL, raw_payload_json JSON NULL, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS collector_runs (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, collector VARCHAR(64) NOT NULL, status VARCHAR(24) NOT NULL, started_at DATETIME NOT NULL, finished_at DATETIME NULL, records_inserted INT UNSIGNED NOT NULL DEFAULT 0, records_updated INT UNSIGNED NOT NULL DEFAULT 0, error_code VARCHAR(64) NULL, message VARCHAR(500) NULL, KEY idx_collector_time (collector, started_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS risk_state (risk_type VARCHAR(24) PRIMARY KEY, severity VARCHAR(24) NOT NULL, reason_codes_json JSON NOT NULL, message_key VARCHAR(160) NOT NULL, context_json JSON NOT NULL, calculated_at DATETIME NOT NULL, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS air_quality_transitions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, from_status VARCHAR(24) NULL, to_status VARCHAR(24) NOT NULL, station_id BIGINT UNSIGNED NULL, source_aqi SMALLINT UNSIGNED NULL, notify_eligible TINYINT(1) NOT NULL DEFAULT 0, occurred_at DATETIME NOT NULL, KEY idx_transition_time (occurred_at), CONSTRAINT fk_transition_station FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE SET NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS alerts (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, severity VARCHAR(24) NOT NULL, status VARCHAR(24) NOT NULL, title_key VARCHAR(160) NOT NULL, message_key VARCHAR(160) NOT NULL, reason_codes_json JSON NOT NULL, context_json JSON NOT NULL, triggered_at DATETIME NOT NULL, last_seen_at DATETIME NOT NULL, pending_since DATETIME NULL, cleared_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, KEY idx_alert_status_time (status, triggered_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS alert_events (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, alert_id BIGINT UNSIGNED NOT NULL, event_type VARCHAR(32) NOT NULL, from_severity VARCHAR(24) NULL, to_severity VARCHAR(24) NULL, reason_codes_json JSON NOT NULL, occurred_at DATETIME NOT NULL, CONSTRAINT fk_alert_event_alert FOREIGN KEY (alert_id) REFERENCES alerts(id) ON DELETE CASCADE, KEY idx_alert_event_time (alert_id, occurred_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS alert_stations (alert_id BIGINT UNSIGNED NOT NULL, station_id BIGINT UNSIGNED NOT NULL, measurement_id BIGINT UNSIGNED NULL, PRIMARY KEY (alert_id, station_id), CONSTRAINT fk_alert_station_alert FOREIGN KEY (alert_id) REFERENCES alerts(id) ON DELETE CASCADE, CONSTRAINT fk_alert_station_station FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE, CONSTRAINT fk_alert_station_measurement FOREIGN KEY (measurement_id) REFERENCES measurements(id) ON DELETE SET NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS push_subscriptions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, endpoint_hash CHAR(64) NOT NULL, endpoint TEXT NOT NULL, p256dh VARCHAR(255) NOT NULL, auth VARCHAR(255) NOT NULL, content_encoding VARCHAR(32) NOT NULL DEFAULT 'aes128gcm', language CHAR(2) NOT NULL DEFAULT 'en', client_class VARCHAR(32) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, last_success_at DATETIME NULL, failure_count INT UNSIGNED NOT NULL DEFAULT 0, disabled_at DATETIME NULL, UNIQUE KEY uq_push_subscription_endpoint_hash (endpoint_hash), KEY idx_push_subscription_active (disabled_at, created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS notification_outbox (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, alert_id BIGINT UNSIGNED NOT NULL, alert_event_id BIGINT UNSIGNED NOT NULL, event_type VARCHAR(32) NOT NULL, severity VARCHAR(24) NOT NULL, payload_json JSON NOT NULL, subscription_max_id BIGINT UNSIGNED NOT NULL DEFAULT 0, status VARCHAR(24) NOT NULL DEFAULT 'pending', available_at DATETIME NOT NULL, attempt_count INT UNSIGNED NOT NULL DEFAULT 0, claim_token CHAR(32) NULL, claimed_at DATETIME NULL, last_attempt_at DATETIME NULL, last_error_code VARCHAR(64) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, completed_at DATETIME NULL, superseded_at DATETIME NULL, expired_at DATETIME NULL, UNIQUE KEY uq_notification_outbox_event (alert_event_id), KEY idx_notification_outbox_claim (status, available_at, claimed_at), CONSTRAINT fk_outbox_alert FOREIGN KEY (alert_id) REFERENCES alerts(id) ON DELETE CASCADE, CONSTRAINT fk_outbox_alert_event FOREIGN KEY (alert_event_id) REFERENCES alert_events(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS push_deliveries (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, outbox_id BIGINT UNSIGNED NOT NULL, alert_event_id BIGINT UNSIGNED NOT NULL, subscription_id BIGINT UNSIGNED NOT NULL, status VARCHAR(32) NOT NULL DEFAULT 'pending', attempt_count INT UNSIGNED NOT NULL DEFAULT 0, available_at DATETIME NOT NULL, http_status SMALLINT UNSIGNED NULL, error_code VARCHAR(64) NULL, attempted_at DATETIME NULL, delivered_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uq_push_delivery_event_subscription (alert_event_id, subscription_id), KEY idx_push_delivery_work (outbox_id, status, available_at), CONSTRAINT fk_delivery_outbox FOREIGN KEY (outbox_id) REFERENCES notification_outbox(id) ON DELETE CASCADE, CONSTRAINT fk_delivery_alert_event FOREIGN KEY (alert_event_id) REFERENCES alert_events(id) ON DELETE CASCADE, CONSTRAINT fk_delivery_subscription FOREIGN KEY (subscription_id) REFERENCES push_subscriptions(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS api_rate_limits (route VARCHAR(64) NOT NULL, client_hash CHAR(64) NOT NULL, window_started_at DATETIME NOT NULL, request_count INT UNSIGNED NOT NULL DEFAULT 1, expires_at DATETIME NOT NULL, PRIMARY KEY (route, client_hash), KEY idx_api_rate_limit_expiry (expires_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations (version) VALUES ('001_initial_air_watch');
INSERT IGNORE INTO schema_migrations (version) VALUES ('002_multi_provider_history'),('003_weather_state');
