# Public API

All live endpoints send `Cache-Control: no-store`. Success is `{"ok":true,"data":...,"meta":...}`; error is `{"ok":false,"error":{"code":"...","message":"..."}}`. Times are UTC in the API and rendered in `Asia/Bangkok` by the client. Raw provider payloads never leave the database.

- `GET /api/current.php?lang=en|th`: official area status, provider-aware observations and trends, `local_sensor_summary` with separate `live_count` and `valid_pm25_count`, forecast summary, nullable cached `weather`, and combined advice.
- `GET /api/stations.php?lang=en|th`: normalized station list with a backward-compatible `source` object: `provider`, localized `label`, `classification`, and `official`, plus normalized `source_status`.
- `GET /api/history.php?station=provider:id&period=24h|72h|7d|30d|90d|1y|5y`: raw points for 24h/72h, 3-hour means for 7d, 12-hour means for 30d, daily summaries for 90d/1y, and sample-weighted ISO-weekly points for 5y. The response states its aggregation.
- `GET /api/forecast.php?lang=en|th`: separate CAMS Global model points and risk band. Model US AQI is never presented as TH AQI.
- `GET /api/alerts.php?lang=en|th`: active/recent incidents and verified category transitions.
- `GET /api/status.php`: monitoring state for UI/status use.
- `GET /api/health.php`: 200 when healthy; otherwise 503/degraded. It reports official and supplementary observation timestamps separately, so fresh local data cannot mask stale Air4Thai data. Enabled stale/failing DustBoy or weather is named separately.
- `POST /api/push-subscribe.php` and `push-unsubscribe.php`: same-origin JSON, validated and rate limited.

Public requests never call Air4Thai, DustBoy, or Open-Meteo. DustBoy AQI remains null unless a future verified upstream field explicitly supplies an official scale. Source metadata and normalized values are public; raw payloads and request credentials are not.
