# Alert lifecycle and push

Every verified per-station category transition is recorded. Notifications are not sent for Very good, Good, or Moderate.

An incident opens when official area status becomes Unhealthy, escalates immediately at Very unhealthy, and clears only after 90 continuous minutes below Unhealthy. Unknown data never clears an incident. Open, escalation and one recovery event are placed transactionally into an outbox; repeated unchanged evaluations do not create duplicate jobs. Push subscriptions are same-origin validated and rate limited.

Production push uses a unique Air VAPID key in the external config. Browser automation verifies capability/state and server behavior. Physical Android push, iPhone installed-PWA push and Home Screen installation were completed and confirmed by the operator on 2026-09-05.
