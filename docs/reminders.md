# Web Push reminders

The web app uses browser Web Push subscriptions, an active service worker and a persistent backend worker. It does not use browser timers to claim delivery while closed. This project is web-only; no native widgets are required.

## Configuration

Generate a deployment-specific VAPID key pair using the installed library:

```sh
docker compose exec backend php -r "require 'vendor/autoload.php'; echo json_encode(Minishlink\WebPush\VAPID::createVapidKeys());"
```

Put `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY` and a real `VAPID_SUBJECT` contact URI in the ignored `.env` file, then recreate backend and reminder-worker containers. Keep the private key server-side. An unconfigured server returns `supported: false`; subscriptions are not falsely reported as enabled.

Run one pass with `docker compose exec backend php scripts/reminders.php`, or continuously with `php scripts/reminders.php --watch`. The Compose worker uses watch mode. Deploy through HTTPS; localhost is suitable for development on compatible browsers.

## Lifecycle and guarantees

An explicit user action requests browser permission. The service worker subscription is stored for the authenticated owner with CSRF protection. HTTPS endpoints are restricted to supported browser vendors, with redirects disabled. Other vendors require a reviewed allowlist change. The server validates browser key format and never logs endpoint secrets.

A saved confirmed plan creates one job per active subscription and plan version at `starts_at - 15 minutes`, using UTC. Edits invalidate previous versions, cancellation/opt-out invalidates pending jobs, and the worker checks the current version, permission state and future start before sending. Subscribing after saving also schedules that user's future plans. If the scheduled time has already passed but lunch has not started, the next worker pass sends a late reminder immediately. Jobs after lunch starts are cancelled.

A database advisory lock serializes workers; the current plan is row-locked through final validation and dispatch. A unique plan/version/device key prevents normal duplicate jobs. There is still an unavoidable crash window between external service acceptance and database commit: exactly-once external delivery cannot be guaranteed. Notification tags coalesce repeated visible reminders. Push-service acceptance is stored as `sent`; it does not prove display or device receipt. Expired subscriptions are deactivated, service failures retry up to three attempts, and overdue jobs expire at lunch time. Payloads contain no friend identities or meeting-point details. Opening a notification fetches the current plan, which may have changed after dispatch.

Test policy boundaries with `docker compose exec backend php tests/reminders.php`. Real permission prompts, browser-vendor acceptance and device display require a configured VAPID deployment and consenting real device; these must not be inferred from unit or database tests.

Implementation references: [Web Push PHP v9 source](https://github.com/web-push-libs/web-push-php/tree/v9.0.4), [Push API](https://developer.mozilla.org/en-US/docs/Web/API/Push_API).
