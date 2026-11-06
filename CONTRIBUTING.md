# Contributing

## Source of truth

Read [.planning/plan.md](.planning/plan.md) before implementation. Campus Navy is approved; do not reopen the visual-option selection. The current approved scope is web only, including responsive mobile browsers.

All maintained documentation and new user-facing copy use English.

## Workspace boundaries

- `backend/`: JSON API, server validation, persistence, database migrations and browser-reminder worker.
- `web/`: responsive English Campus Navy client; server templates do not belong here.
- `docker/`: infrastructure configuration only.
- `.planning/`: decisions, constraints, and ordered implementation tasks.
- `docs/`: development instructions, design reference, and source evidence.
- `scripts/`: checks and repeatable developer operations.
- `.github/`: CI.

Do not reintroduce the old Blade/MVC router, duplicated SQL dumps, coursework examples, runtime logs, or an archive directory. Original tracked code remains in Git history.

## Development workflow

Use focused feature/fix/chore branches. Keep `.env`, dependency folders, database exports and build output out of Git. Commit dependency lockfiles alongside manifest changes. Follow `.editorconfig`.

Run:

```sh
docker compose config --quiet
docker compose exec backend composer check
docker compose exec backend composer smoke
```

Check documentation links with `python scripts/check_repository.py` when Python 3 is available. CI runs this check as well.

For future feature changes, add meaningful behavior tests and follow the plan's acceptance checks. Review ownership, privacy, one-slot validation, timezones, closing/break boundaries, and count consistency.

Use concise Conventional Commit subjects and describe behavior, motivation, tests, and limitations in pull requests. Keep planned features distinct from delivered behavior.
