# Docker environment

Compose runs `web`, `backend`, `db` and `reminders`. Nginx serves the Vite build and proxies `/api` to PHP, preserving same-origin sessions. The database is MariaDB inside Docker, not a host installation.

- `web.Dockerfile`: locked npm install, Vite build and unprivileged Nginx runtime.
- `nginx.conf`: SPA routing, API proxy and browser security headers.
- `php/development.ini`: PHP settings for local evaluation.

Run commands, ports and persistence rules are in [development](../docs/development.md). Do not delete volumes as a routine reset. Native mobile containers/services are not part of this web-only scope.
