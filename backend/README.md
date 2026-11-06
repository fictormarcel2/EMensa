# Backend

PHP 8.4 JSON API for MensaTogether, running as a non-root Docker user with MariaDB 11.4. See the [API contract](../docs/api.md) and [reminder delivery notes](../docs/reminders.md).

## Implemented behavior

- Session authentication with hashed fictional evaluation passwords, session rotation, CSRF on mutations, route/method allowlists and login/invite rate limits.
- Verified location-specific service windows, Berlin dates, public-holiday closures and maintained date overrides; 15-minute arrival buckets never bridge Eupener's service break.
- One plan per owner/date across all mensas, scalar slot validation, transactional create/edit/cancel, stale-version rejection and intent-bound retry IDs.
- Anonymous aggregate counts with server-side private/friends/public filtering; accepted-friend requests, opaque expiring/revocable codes and explicit acceptance.
- Clearly labelled sample menu data with unverified diet/allergen facts.
- Real Web Push subscription storage and T-15 job scheduling, with delivery capability explicitly unavailable until VAPID is configured. Editing/cancelling invalidates old jobs.
- Database health/readiness endpoints and sanitized failure responses.

From the repository root:

```sh
docker compose up --build -d --wait
docker compose exec backend composer check
docker compose exec backend composer test
docker compose exec backend composer smoke
```

Startup applies non-destructive, tracked SQL migrations and inserts missing fictional accounts. Reruns preserve existing plans, credentials and friendships. Do not use evaluation accounts for real student data. The old coursework schema is not imported.

For explicit maintenance:

```sh
docker compose exec backend php scripts/migrate.php
docker compose exec backend php scripts/seed.php
```

## Structure

```text
database/migrations/    Ordered, tracked, non-destructive schema changes
database/closures.json Operator-confirmed dated service overrides
public/index.php       JSON entry point, session and sanitized errors
src/Api.php            Explicit HTTP routes and authorization boundaries
src/Domain/            Berlin service-window policy and generated slots
src/Infrastructure/    Prepared mysqli database connectivity
src/Delivery/          Subscription validation, scheduling and push worker
scripts/               Migrations, evaluation seed, lint and worker
tests/                 Domain, real HTTP API, delivery and smoke checks
```

HTTP feature tests create isolated random accounts and remove only their own fixtures. Delivery database tests roll back their fixtures. Tests never reset the database or mutate the three evaluation accounts. This development implementation is not an independent production-security audit.
