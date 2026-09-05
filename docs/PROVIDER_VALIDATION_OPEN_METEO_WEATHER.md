# Open-Meteo weather-context validation

Status: `VERIFIED LIVE` on 2026-09-03.

The server-side provider requests `/v1/forecast` for `18.7883,98.9853` with UTC current variables for temperature, wind speed, wind direction, wind gust and precipitation. The response schema, `current_units`, UTC timestamp and normalized ranges are validated before the one-row-per-zone cache is updated. Provider health and collector health are recorded separately.

The public API reads only `weather_state`; it never calls Open-Meteo during a visitor request. The UI identifies the source, reports its observation time, and presents weather as context without claiming it caused the measured air conditions. A missing or failed response degrades the enabled provider health but does not remove stored public air observations.

Documentation source: <https://open-meteo.com/en/docs>.
