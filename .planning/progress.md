# Execution record — .planning/plan.md

## 5 November 2026 execution

The user authorized execution of the entire approved plan. Work continues on `chore/professional-workspace`, preserving the existing uncommitted cleanup. No deployment, push or shared-branch merge is included.

- Task 0: complete; existing foundation and history retained.
- Tasks 1–2: complete; persisted schema, server policies, auth and disclosure behavior verified.
- Tasks 3–5: complete; responsive Campus Navy client, planner/menu and friends/invites integrated.
- Task 6: web implementation complete; real browser receipt remains external verification. Native scope removed by user instruction.
- Task 7: automated verification and review fixes complete; real participant sessions remain external work.

Ruling: retain the existing feature branch and working directory instead of creating a worktree from HEAD — the approved cleanup is uncommitted and would be absent from a fresh checkout — cost if wrong: extra care is needed to distinguish inherited cleanup from new product changes.

Ruling: product menu remains explicitly fictional sample data until an authoritative dated menu feed and its reuse terms are integrated — avoids inventing current prices/allergens — cost if wrong: evaluation needs a separate real-menu integration task.

Ruling: do not fabricate a student study or native-device verification — source/build evidence and automated checks are recorded separately — cost if wrong: course submission still needs participants and target-device validation.

## Design execution brief

The approved Campus Navy reference governs the interface. Exact tokens: Ice `#F3F6FB`, White `#FFFFFF`, Navy `#142D4E`, Amber `#F0B44D`, Border `#DBE4F0`, Secondary `#60748B`. Sidebar navigation and a working lunch comparison dominate; food photography is secondary. Left-aligned, readable headings; meaningful chart counts and a single selected arrival time. No decorative dashboard metrics, marketing hero or new design options.

## Verification evidence

- `docker compose exec -T backend composer check`: Composer strict validation and syntax checks passed for 15 PHP files.
- `docker compose exec -T backend composer smoke`: all six health/method/removed-route checks passed.
- `docker compose exec -T backend composer test`: 76 checks passed against actual HTTP/database state: 18 opening-hour/holiday/DST boundaries, 35 API behavior checks, 14 push policy checks, four persistent reminder lifecycle checks and five worker dispatch checks. Fixtures are removed or rolled back.
- Initial frontend production build and nine component tests passed. Audit identified vulnerable development Vitest dependencies; upgraded to Vitest 4.1.12 and pinned npm 11.6.2. Updated audit reports zero vulnerabilities. A final clean Docker install/build/test/audit is still running; the latest additional draft regression makes ten tests.
- Independent backend review found expired identity badges; fixed with historical-count retention regression.
- Independent frontend review found full-form login draft loss, invite-preview races and late camera permission leaks. Fixes and regression tests are present; final built-browser verification remains pending.
- The reminder SQL integration test caught an ambiguous upsert column causing plan-save failure. Qualified the target columns; all database lifecycle/worker and API tests now pass.
- Default Compose now uses copied image code; `compose.dev.yaml` offers optional live bind mounts. Partial aborted host `web/node_modules` was removed after confirming no process used it.
- Preliminary local guest overview measurement (ten sequential requests, Windows Docker Desktop under concurrent build load): direct IPv4 median 144.5 ms, observed maximum/p95 4902.8 ms. This is an end-to-end local round trip, not isolated server time; the proposed consistent 500 ms target is not established. Earlier `localhost` measurements included IPv6 fallback overhead.

Ruling: pin npm 11.6.2 for frontend resolution — npm 10 failed on Vitest optional-peer resolution, while the patched test dependency graph installs with npm 11 — cost if wrong: contributors must use the documented npm version or Docker.

Ruling: serve default PHP code from built images and make source mounts opt-in — repeated Windows OneDrive filesystem round trips caused request latency — cost if wrong: PHP edits need a rebuild unless the development override is used.

Scope update: the user removed native mobile planning. The now-unused mobile placeholder directory was removed; seven active root directories remain. The iOS answer is superseded. Responsive mobile web remains supported.


## 6 November 2026 closeout

- Final backend image: Composer strict validation, 15 PHP syntax checks, six HTTP smoke cases and **78 behavior checks passed**. The latter cover 18 slot/holiday/DST boundaries, 35 API cases, 14 push-policy cases and 11 persistent lifecycle/worker cases. Fixtures are removed or rolled back.
- Final frontend source: **15 component/integration tests passed** with the patched dependency graph, including full guest draft preservation, existing-owner version handling, stale mutation responses, stale callbacks and Friends date races. Verified SHA-256 hashes of all five final changed frontend/configuration files against the bytes tested in the container before building the release image.
- TypeScript and the final Vite production build passed. Fresh npm and Composer advisory audits reported no known vulnerabilities.
- Independent final review findings were fixed: new reminders cannot be scheduled retrospectively inside T−15; captured old-context callbacks cannot initiate reads for a different current date/mensa/auth identity. Existing, previously scheduled delayed reminders retain the documented dispatch policy.
- `.gitignore` audit passed: 16 representative private/generated paths are ignored, while eight representative maintained paths (lockfiles, migrations, source, assets and `.env.example`) remain included. Candidate text files had no detected private-key blocks, GitHub tokens or populated VAPID environment secrets. Matching secret exclusions also apply to Docker build contexts.
- Browser integration covers explicit save/reload/edit/cancel, one radio choice, keyboard navigation/modal containment, deep links without saves, camera fallback, four responsive sizes and automated axe checks. Screenshots are ignored development evidence; they are not product deliverables.
- Browser notification receipt still requires valid deployment VAPID/contact configuration and explicit permission on a supported browser. Real student sessions/SUS results have not been collected; the evaluation protocol is supplied. Native applications/widgets are excluded.

Ruling: use one frontend test worker and bounded 15-second asynchronous query/test deadlines — parallel workers on this Windows Docker host exceeded the former one-second query deadlines despite correct assertions, while isolated runs passed — cost if wrong: CI runs more slowly; these deadlines are not a product performance claim.

The user explicitly requested committing and pushing the completed branch. Preserve the working checkout and Git history; push normally, without force or merging into the default branch.
