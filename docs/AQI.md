# AQI, freshness, trends and model risk

TH AQI categories follow PCD 2023 source values: Very good `0–25`, Good `26–50`, Moderate `51–100`, Unhealthy `101–200`, Very unhealthy `>200`. Missing source AQI remains null and the UI says “AQI unavailable”; PM2.5 remains visible. No local TH AQI and no US-to-TH conversion is performed.

Freshness is Live through 90 minutes, Delayed above 90 through 180, Stale above 180, and Offline with no observation. Official area status needs at least one Live official station and takes the worst category; otherwise it is unknown.

PM2.5 trends compare 1, 3 and 24 hours with 20-minute tolerance. Positive means worsening. Missing history produces null.

The next-24-hour model PM2.5 mean needs 75% coverage: Low `≤25.0`, Moderate `25.1–37.5`, High `37.6–75.0`, Very high `>75.0 µg/m³`. Direction compares hours 0–6 with 18–24; both `3 µg/m³` and `10%` must be exceeded, otherwise it is broadly stable. This is a configurable product heuristic, not TH AQI.
