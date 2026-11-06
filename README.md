# MensaTogether

A responsive lunch coordination web app for FH Aachen. Compare submitted lunch times, choose exactly one arrival slot, and share a confirmed plan with consenting friends.

The approved **Campus Navy** interface uses Ice, Navy and Amber in a 60–30–10 balance. UI and documentation are English. Desktop and mobile browsers are supported; native applications and home-screen widgets are outside the current scope.

## Run locally

Docker Desktop with Linux containers and Docker Compose v2 is required. Copy `.env.example` to `.env`, then run from this directory:

```sh
docker compose up --build --detach --wait
```

Open **http://127.0.0.1:8080**. The separate API health endpoint is http://127.0.0.1:8081/api/health. Use the explicitly labelled fictional evaluation accounts shown by the app. They are for local evaluation only; this is not campus SSO or a production identity system.

The stack runs the web client, PHP API, MariaDB and a browser-reminder worker. No host PHP, Composer or database installation is needed. The database port is internal. Data survives `docker compose down`.

## Product rules

- Exactly one confirmed slot per user and Berlin-local date, across all mensas.
- Only future slots fitting published service intervals can be saved. Eupener’s 14:15–14:30 service break is excluded.
- Counts represent submitted plans, not occupancy or queue length.
- Plans are private by default. Accepted friends only receive explicitly shared details.
- Dates, mensa, meeting point, privacy and reminder choices are confirmed before saving. Comparing a time does not save it.
- Menu dishes and prices are clearly labelled samples, not the current operator menu.
- Browser reminders require permission, configured Web Push transport and a supported browser. Native widgets are excluded.

## Repository

```text
.github/       CI workflows
.planning/     Canonical plan and execution evidence
backend/       PHP JSON API, schema, tests and worker
docker/        Web build, reverse proxy and PHP configuration
docs/          Setup, API, design, source evidence and evaluation
scripts/       Repository and integration checks
web/           React, TypeScript and Vite client
```

The previous EMensa coursework architecture was removed from the working tree and remains recoverable in Git history.

## Documentation

- [Development and verification](docs/development.md)
- [API contract](docs/api.md)
- [Canonical plan](.planning/plan.md) and [execution record](.planning/progress.md)
- [Architecture](docs/architecture/repository.md)
- [Campus Navy design](docs/design/README.md)
- [Official opening-hour evidence](docs/reference/opening-hours.md)
- [Documentation index](docs/README.md)

See the execution record for actual verification results and remaining limitations. A working local demo is not evidence of production readiness or a completed participant study.
