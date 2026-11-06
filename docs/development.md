# Local development

## Start

Start Docker Desktop using Linux containers and Docker Compose v2. Copy `.env.example` to `.env` once. From the repository root:

```sh
docker compose config --quiet
docker compose up --build --detach --wait
docker compose ps
```

Open http://127.0.0.1:8080. API readiness is http://127.0.0.1:8081/api/health. Fictional evaluation accounts are labelled in the app; do not reuse their credentials for a real deployment. Sample menus and submitted evaluation data must not be presented as operator data or actual occupancy.

| Service | Purpose |
| --- | --- |
| `web` | Built React client and same-origin `/api` proxy, localhost:8080. |
| `backend` | PHP 8.4 JSON API, localhost:8081. |
| `db` | MariaDB 11.4; no host database port. |
| `reminders` | Persistent browser-reminder worker. Delivery stays disabled without configured VAPID keys. |

Change `WEB_PORT` or `BACKEND_PORT` in `.env` if a host port is occupied. Containers use their internal ports independently of those host settings.

## Verify

```sh
docker compose exec -T backend composer check
docker compose exec -T backend composer smoke
python scripts/check_repository.py
```

Feature test commands and actual results are recorded in [the execution record](../.planning/progress.md). Backend tests may create uniquely named fictional test accounts and clean their own data; they must not reset an existing database. Frontend commands run from `web/` with Node 22.20 or a compatible supported version:

```sh
npm ci
npm run check
```

For hot reload, run `npm run dev` in `web/`. Vite proxies `/api` to localhost:8081; rebuild the Docker web image after source changes when using the built client on port 8080.

Use npm **11.6.2**, matching `packageManager` and the Docker build. An npm 10 resolver error occurred with Vitest's optional peers; the npm version is pinned to avoid that failure. A Docker-only frontend verification is also available:

```sh
docker build --target checks -f docker/web.Dockerfile .
```

The default API runs code copied into its image. Rebuild after PHP changes. To opt into live PHP bind mounts instead, use:

```sh
docker compose -f compose.yaml -f compose.dev.yaml up --build --detach --wait
```

Live bind mounts can be substantially slower in Windows OneDrive folders. The default built-image mode avoids those filesystem round trips.

## Browser reminders

See [browser reminder setup](reminders.md). Never commit VAPID private keys or browser subscription data. Permission must be requested through an explicit user action. Localhost is suitable for supported-browser evaluation; remote use needs HTTPS. iOS browser push can require installation as a Home Screen web app, which does not create a native app or native widget.

## Logs and persistence

```sh
docker compose logs --tail=100 backend reminders web
docker compose down
```

Stopping preserves database data. Do not use `down --volumes`, prune Docker volumes or import coursework SQL as a routine troubleshooting step. Old task-owned volumes may remain unused to preserve earlier data.

This is a local evaluation environment. Public deployment, real user enrollment and participant studies require separate operational work and evidence.
