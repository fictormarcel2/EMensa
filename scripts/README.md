# Developer scripts

Run commands from the repository root.

- `check.ps1`: Docker-only Compose, PHP syntax, health, API/domain/reminder behavior, frontend build/component tests and dependency audit. Requires the stack to be running. Add `-Browser` to run the separate browser checks as well.
- `check_repository.py`: read-only root-layout and local Markdown-link validation. Requires Python 3; CI provides it.
- `browser-check.mjs`: real integrated save/reload/edit/cancel, deep links, single-choice selection, camera fallback and responsive/accessibility checks. It uses Playwright and axe from `web/` dependencies. Screenshots stay in ignored `web/test-results/`; they are development evidence, not user deliverables.

```powershell
./scripts/check.ps1
```

```sh
python scripts/check_repository.py
```

Backend-specific PHP checks live in `backend/scripts/` and `backend/tests/`. No script resets or deletes the database as part of a normal check.

For browser checks, install the locked `web/` dependencies with npm 11.6.2. Windows checks use installed Google Chrome; on Linux run `npx playwright install --with-deps chromium` in `web/`. Then run `node scripts/browser-check.mjs` from the repository root or `./scripts/check.ps1 -Browser`. `APP_URL` overrides the default `http://127.0.0.1:8080`.
