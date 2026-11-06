# Repository architecture

The web-only monorepo has seven active project directories. There is one maintained client and one API; no parallel legacy MVC application or native mobile project is retained.

| Directory | Boundary |
| --- | --- |
| `.github` | Read-only CI checks; no automatic deployment. |
| `.planning` | Canonical requirements, ordered tasks and recorded implementation decisions. |
| `backend` | PHP JSON API, domain policy, prepared SQL persistence, schema migrations and reminder worker. |
| `docker` | PHP runtime settings, web build and same-origin reverse proxy. |
| `docs` | English setup, API, design, attribution and source evidence. |
| `scripts` | Cross-cutting repository/integration checks. |
| `web` | Responsive React/TypeScript client and attributed assets. |

## Request boundaries

The browser loads the built client from Nginx on localhost:8080. `/api/` requests are proxied to PHP on the private Compose network. Session cookies and CSRF protect mutations. The API performs ownership, visibility, date/time and version validation; the browser never accesses MariaDB directly.

The separate localhost:8081 API port supports development and health checks. MariaDB 11.4 stores persistent application state in the existing named volume. Non-destructive migrations introduce the schema; the old SQL dump is not imported. The worker uses the same domain state before delivering a reminder.

## Delivery boundaries

Web Push uses explicit permission, an actual browser subscription and server scheduling. A local timer is not substituted for background delivery. The static client is built with Vite and served by Nginx; the PHP development server is intended for this local evaluation setup. Add HTTPS, hardened production serving, real identity provisioning, operational monitoring and backups before public deployment.

The user explicitly removed native mobile and home-screen widget scope. Responsive mobile browsers remain supported.

## Sources

- [Canonical plan](../../.planning/plan.md)
- [API contract](../api.md)
- [Development](../development.md)
- [Vite static build documentation](https://vite.dev/guide/static-deploy.html)
- [Nginx proxy module](https://nginx.org/en/docs/http/ngx_http_proxy_module.html)
