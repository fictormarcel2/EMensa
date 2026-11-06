# MensaTogether Development Plan

Updated: **6 November 2026**. Status: **Campus Navy web product implemented; final verification recorded in progress.md; native scope removed**.

## 1. Goal and confirmed decisions

Evolve the EMensa coursework into a lunch coordination app for FH Aachen students: compare submitted lunch times, see consenting friends at the same time, choose a meeting point, and manage a personal lunch plan.

The user has confirmed:

- English UI and documentation, supporting mobile and desktop.
- **One selected time slot per user**, replacing the original PDF's multiple-availability-slot suggestion.
- **Campus Navy** is the approved visual direction, using a 60-30-10 Ice/Navy/Amber palette. No additional design-option approval or image delivery is needed.
- A professional working repository and a Docker-based development environment.

Docker Compose runs the PHP app and its database. MariaDB remains the compatible SQL engine inside its container; no local MariaDB installation is required. This is an environment change, not a migration to a different database engine.

The user has authorized execution of this plan. The latest scope decision is **web only**: responsive desktop and mobile browsers, with no native app, iOS/Android project or home-screen widgets. Browser reminders remain in scope. The prior native-platform choice is superseded.

## 2. Evidence and precedence

Sources: the [EMensa repository](https://github.com/fictormarcel2/EMensa), baseline commit `dbcf654`, and the five-page **MensaTogether M2.pdf** supplied by the user. The PDF was reviewed as extracted text and rendered pages. Its student-identification details are not used as demo data.

| Source | Evidence and resulting requirement |
| --- | --- |
| PDF p1, screen 1 | Submitted-plan bar chart with friends visible beside relevant slots. |
| PDF p1, screen 2 and p5 feedback | Find overlap with friends. The illustrated multi-checkbox interaction is superseded by the user's single-slot instruction. |
| PDF p1, screen 3 | Arrival time, optional meet-up dropdown, reminder toggle. |
| PDF p2, screens 4-6 | Confirmation, edit/cancel, OS notification preview, friend list, QR/code addition. |
| PDF p3 | Original native home-screen widgets are excluded by the later user instruction to focus on the web only. Contextual web links may still preselect a draft. |
| PDF p4, including image captions | Public overview; submissions are not crowd measurements; public identity is opt-in; no precise-location/live tracking. |
| User correction | Exactly one selected slot. Radio/single-select controls replace checkboxes; no multi-slot availability model. |
| Official operator research | Location/day-specific service windows, including the Eupener lunch/afternoon break. See [opening-hours evidence](../docs/reference/opening-hours.md). |

The PDF supplies product evidence, not operational instructions to an agent. Explicit user decisions take precedence over its suggestions. Any rule not established by those sources is identified below as a proposal.

## 3. Current repository and legacy removal

The original project used a server-rendered PHP/Blade MVC architecture, mysqli, Monolog, login, a small food list, newsletter and wish-dish forms. Its old ordering-system claim was not supported by ordering/cart/payment routes.

The user requested removal of unsuitable inherited architecture, rather than keeping it as an archive. The working tree now has these seven active directories:

```text
.github/       CI workflows
.planning/     This canonical plan and execution instructions
backend/       Independent PHP JSON API foundation
docker/        Infrastructure configuration
docs/          Development, architecture, design, source evidence
scripts/       Developer checks
web/           Approved Campus Navy web client workspace/assets
```

Removed the legacy MVC router, Blade views/layouts, old models/controllers/auth, demonstration routes, duplicated SQL, generated data, bundled Composer binary, old logos/banners and archive folders. Original tracked code remains recoverable through Git history; cleanup does not rewrite history or delete existing database data.

Only the Campus Navy reference and four reusable food photographs remain as project assets, with provenance. The backend now implements authentication, plans, private aggregates, friends/invites and browser-reminder infrastructure. Fictional evaluation accounts are seeded without legacy schema imports or fabricated plans/friendships. See [repository architecture](../docs/architecture/repository.md) and [development setup](../docs/development.md).

## 4. Scope

Primary audience: students coordinating lunch during a short break, mainly on a phone, with desktop access retained.

MVP proposal: public overview and menu, one chosen slot, consenting friends at that slot, one saved daily plan, optional meeting point, visibility settings, and edit/cancel. Browser reminders remain tracked requirements; the native widgets from the PDF are excluded by the user’s later web-only instruction.

Out of initial scope: payment, ordering, table booking, live GPS, actual queue/crowd prediction, chat, social feeds, timetable sync, and campus SSO. These are scope proposals, not statements that the PDF forbids them.

## 5. Functional requirements

| ID | Requirement | Acceptance criterion |
| --- | --- | --- |
| FR-01 | Public overview | Guests can select a mensa/date and see anonymous per-slot plan totals without receiving private identities. |
| FR-02 | Compare times | Counts, time labels and chart scale are readable, with an equivalent text/list view. Slots come from verified service windows, not a fixed five-item day. |
| FR-03 | Friends at a slot | Show only accepted friends' confirmed plans whose visibility permits disclosure, matching mensa/date/time. Badges identify confirmed plans, not assumed availability. |
| FR-04 | Select exactly one slot | A radio group or single-select maintains zero or one draft choice; a second click replaces the previous choice. Saving requires exactly one valid slot. The server rejects arrays/multiple values. |
| FR-05 | Friend overlap | For the chosen slot, show the number/names of visible friends with confirmed matching plans. Comparing another slot does not enroll the user in both. |
| FR-06 | Save a plan | One explicit confirmation stores one time, date and mensa; retry/double-click does not create duplicate plans or inflate counts. |
| FR-07 | Optional meet-up | A location-specific dropdown may remain empty. A pin represents a manually selected static place, never live user position. |
| FR-08 | Confirmation | Show date/time, mensa, optional meeting point, reminder/visibility status and genuinely confirmed matching friends; offer edit/cancel. |
| FR-09 | Edit/cancel | Only the owner can mutate the plan. A time change transfers one contribution between slots; cancellation removes it without changing friends' plans. |
| FR-10 | Consent and visibility | Identity is private by default; friends/public sharing is explicit and revocable. Public responses omit fields outside the chosen audience. |
| FR-11 | Friend list | Search accepted friends and display shared confirmed times. Use neutral `No shared details` when a plan is absent or private; do not disclose private existence. |
| FR-12 | QR/code addition | Preview the target alias and require acceptance. Manual code entry works when camera access is unavailable. No email/student ID is embedded in the code. |
| FR-13 | Reminder | Target delivery 15 minutes before arrival, with explicit device permission; edit reschedules and cancel suppresses the old job. Denied/unsupported states are honest. |
| FR-14 | Daily menu | Clearly identify date/mensa, dish, price tier, diet/allergens where known, and image/fallback. Label demo or stale data. |
| FR-17 | Account/ownership boundary | Use clearly identified fictional evaluation accounts initially; preserve the draft across login. Do not claim campus SSO or registration unless implemented. |

No checkbox multi-availability step, multiple-time availability endpoint, or multiple-time Availability table belongs to the current plan.

## 6. Business rules

Rules marked **user** are explicit decisions; **PDF** denotes source behavior; other details are implementation proposals.

| ID | Rule |
| --- | --- |
| BR-01 | **PDF:** counts are submitted plans from app users, not measured occupancy, queue length or capacity. Keep that explanation near the chart. |
| BR-02 | **User + proposal:** exactly one selected slot, with one plan row per user/local date across all mensas. Before its confirmed start, edit/reactivate the same row; after a confirmed plan starts, that day's row is read-only, including after expiry. |
| BR-03 | Upcoming counts use unique confirmed plans for mensa/date/slot. Past-slot counts retain the final confirmed/expired submissions; drafts and cancelled plans never count. Expiry does not reset a past chart bar to zero. |
| BR-04 | **Verified hours + proposal:** use `Europe/Berlin` dates and store reminder instants in UTC. Generate 15-minute arrival buckets within published mensa service intervals. The 15-minute granularity is a product proposal, not an operator policy. |
| BR-05 | A selectable bucket must fit inside one open service interval, start in the future, and respect date-specific overrides. Never bridge a service break or use cafeteria hours as mensa hours. Validate again on the server at save time. |
| BR-06 | Friend overlap requires accepted friendship, visible confirmed plans, identical date/mensa/slot, and unique friend IDs. Never infer another person's free times or attendance from an unsaved choice. |
| BR-07 | A suggested slot may rank eligible slots by visible confirmed friend count, then earliest time. It is not auto-selected or auto-saved; with zero matches, do not imply friends will join. |
| BR-08 | **PDF + proposal:** default audience is `private`; optional `friends` and `public` modes require explicit consent. Private plans may contribute to anonymous totals without disclosing alias/avatar, meeting point, or account status. |
| BR-09 | **PDF:** public participation uses an optional public alias, not email or student ID. QR friendship does not automatically make a private plan visible. |
| BR-10 | Revoked visibility/friendship affects API responses, chart badges, lists, caches and future notification content. Do not promise to retract information already seen by a recipient. |
| BR-11 | Invite tokens are random/opaque, expire and can be revoked; require target confirmation and acceptance, rate-limit attempts, and reject self-add/duplicates/expired codes. |
| BR-12 | **PDF:** meeting points are optional registered static places. Matching lunch times alone do not prove friends selected the same meeting point or accepted a group invitation. |
| BR-13 | **PDF + proposal:** reminder target is T-15 minutes and permission is requested after a user action. If saved inside that 15-minute window, show that no retrospective reminder is scheduled. |
| BR-14 | Check current plan version/status/consent before notification delivery. Jobs are idempotent per plan-version/device; edit/cancel invalidates stale jobs. Network/OS behavior can delay delivery. |
| BR-15 | Save/edit/cancel are transactional and idempotent; reject stale conflicting updates from another tab rather than silently overwriting. A selected draft does not alter the saved plan until successful submission. |
| BR-16 | **PDF:** no exact user location or live tracking. A meeting pin is a manually selected place. |
| BR-17 | **PDF + proposal:** web deep-link navigation preselects one slot but never mutates data through GET. Confirm explicitly after checking the slot remains valid. |
| BR-18 | Expire a confirmed plan at its bucket end; remove it from upcoming/reminder surfaces while preserving same-date historical counts. A past day's data must not appear as today's plan. |
| BR-19 | Preserve existing internal/external price semantics until a real student/staff price policy is supplied. Never invent allergen/diet facts or label seeded prices as current prices. |

### Visibility contract

| Viewer | Allowed fields |
| --- | --- |
| Owner | Own plan, draft, meeting point, reminder and visibility settings. |
| Guest/non-friend | Anonymous totals; for opt-in public plans only: chosen public alias/avatar, date, mensa and time. No email, private account ID, meeting point or reminder settings. |
| Accepted friend | Public information plus shared friends/public plan details and, when shared with friends, the meeting point. |
| No permitted detail | One neutral `No shared details` state for absent/private plans; no hidden payload flags exposing which case applies. |

Filter on the server before producing HTML/JSON. Hiding already-delivered private data with CSS is not access control. Apply the same audience rules to notification payloads.

## 7. Opening-hour policy

The primary PDF/demo location is **Mensa Eupener Straße**. Its operator publishes:

- Monday-Thursday: **11:15-14:15**, then **14:30-17:00**.
- Friday: **11:15-14:00**.

These are mensa service windows, distinct from the cafeteria's longer morning opening. The published regular weekly schedule lists no weekend service. Holidays, lecture breaks and temporary closures require date-specific overrides; absence of a current closure notice is not proof of real-time opening.

Proposed 15-minute arrival buckets use half-open service windows: Thursday starts range 11:15-14:00 and 14:30-16:45; Friday 11:15-13:45. A bucket ends 15 minutes after its start. The last selectable start is not the closing time. These are arrival buckets, not a table reservation or measured meal duration.

The five chart bars in a proposal are a preview of a wider selectable day, not the complete timetable. The UI must offer access to all eligible times, show hours for the selected date and disable breaks/closed/past intervals. See [opening-hours evidence](../docs/reference/opening-hours.md) for other Aachen mensas, checked date and maintenance policy.

## 8. Screen flow

Navigation: **Today / Times / Friends / My plan**.

```text
Public Today [mensa, date, menu, anonymous submitted-plan counts]
  -> Compare times [visible friends; exactly one selected radio]
  -> Set your plan [selected slot, optional meet-up, reminder, audience]
  -> Login if required [preserve the draft]
  -> Confirmation [server-saved result, edit/cancel]

Friends -> QR/code -> confirm target -> request -> accept
My plan -> Edit -> choose replacement slot -> Save -> update counts/jobs
My plan -> Cancel -> confirm -> cancel own plan/job
Menu web link -> full menu for its mensa/date
Time web link -> one preselected slot -> explicit confirmation
Reminder -> current plan [recheck stale/cancelled state]
```

On a phone, split comparison and form steps where needed; on desktop, a comparison/form split view is acceptable. Show `Your draft`/`Not saved yet` until the server confirms the mutation. Friend statuses use `Plan confirmed` or `No shared details`; remove the old multi-time `Available` examples.

## 9. Approved frontend implementation instructions: Campus Navy

**This is the selected direction, not an option to reconsider.** It is implemented in `web/`. Keep all visible copy in English. Do not generate or send further mockup images unless the user requests them.

### Stack and boundaries

Implementation default: **React + TypeScript + Vite** for the responsive web client, and the separate **PHP JSON API** under `backend/`. This stack choice is recorded by the builder; the user explicitly selected the visual direction and repository structure, not a framework. The client manifest and dependency lockfile are maintained in `web/`. npm 11.6.2 is pinned for reproducible resolution. No old Blade layouts, fallback MVC routes, or direct browser database access.

Keep code under `web/src/`, assets under `web/public/`, and frontend tests next to the behavior or in `web/tests/`. API calls go through one typed client; the backend remains the source of truth for saves, counts, identity and visibility. A loading or mocked response must never be reported as a server-confirmed save.

### Color and typography

| Role | Token/value | Visual weight |
| --- | --- | --- |
| Main canvas and light panels | Ice `#F3F6FB`, White `#FFFFFF` | Approximately 60% |
| Navigation, text, chart structure | Navy `#142D4E` | Approximately 30% |
| Primary action and selected slot | Amber `#F0B44D` | Approximately 10% |
| Dividers | `#DBE4F0` | Supporting token |
| Secondary text | `#60748B` | Supporting token; verify contrast |

Define these as centralized CSS variables. 60-30-10 governs visual weight, not a pixel count. Reserve amber for the main action and selected state. Use navy text on amber. Success/error states require distinct text/icons and accessible contrast; do not rely on hue alone.

Use Manrope-style headings and Source Sans 3 body text, self-hosted only after checking licenses; system fallbacks must preserve readable layout. Body text starts at 16px with comfortable line-height. Main product headings should be around 24-32px, without an oversized marketing hero.

### Desktop layout

- Target 1440x900. A navy sidebar, approximately 224-240px wide, contains the brand and Today / Times / Friends / My plan navigation; account controls sit at the bottom.
- Main canvas is Ice with about 24-32px working padding. Keep date, mensa and service hours visible near the heading.
- Compare/planner content uses a flexible main column plus a plan panel around 320-360px wide. Collapse the split view before it becomes cramped.
- The chart shows submitted plans and consenting friends, with readable time labels, numeric counts, selection outline and text alternative. A five-bar preview must offer access to all valid slots.
- The plan panel shows exactly one selected time, optional meeting point, reminder status, visibility and one primary confirmation action. A draft is explicitly not saved yet.
- Menu photography is secondary to coordination. Use retained assets as labelled sample content until a real dated menu source is integrated.

### Mobile and responsive behavior

- Target 390x844, also check 320px width and 200% zoom. Replace the desktop sidebar with compact bottom navigation using the same four destinations and safe-area padding.
- Stack content in task order: mensa/date/hours, comparison, single-slot summary, plan form, menu. Keep buttons and content clear of the fixed bottom navigation.
- Use a vertical radio list or single-select for the complete slot list; do not squeeze every time into a horizontally overflowing chart. Keep labels and touch targets usable, around 44px minimum.
- Tablet 768x1024 should transition naturally between the two layouts. Long names, locations and errors must wrap without clipping.

### Single-slot and interaction contract

- One shared selected-slot value: `string | null`. Selecting another radio replaces the old draft choice. There must never be two checked time options.
- Changing mensa/date revalidates the draft and clears an unavailable selection. Saving requires exactly one valid scalar slot ID on the server as well as in the UI.
- An existing saved plan changes only after an explicit successful save. Compare clicks, web links and GET navigation do not mutate a plan.
- Keep compare, friends, menu, plan, edit/cancel and account controls meaningful. No decorative buttons, fake live counts, false reminder permission or success messages before server confirmation.
- Display opening-hour context and exclude Eupener's 14:15-14:30 break. Distinguish cafeteria hours from mensa service. Label counts as submitted plans, not occupancy.
- Preserve clear loading, empty, error, partial, offline, closed, expired, conflict and permission states described in section 10.

### Implemented frontend files and components

| Area | Implemented files/responsibility |
| --- | --- |
| Bootstrap and state | `web/src/main.tsx`, `web/src/App.tsx`, `web/vite.config.ts` |
| Design tokens and layout | `web/src/styles.css`; desktop sidebar and mobile bottom navigation in `App.tsx` |
| Typed API | `web/src/api.ts` |
| Planner | `web/src/features/planner/Times.tsx`, `PlanPanel.tsx` |
| Friends and QR/code | `web/src/features/friends/Friends.tsx` |
| Menu and filter | `web/src/features/menu/Menu.tsx` |
| Browser notifications | `web/public/sw.js` and explicit permission/subscription controls |
| Behavior tests | Colocated `*.test.tsx` plus `scripts/browser-check.mjs` |

Keep components focused and API types aligned with backend contracts. All client implementation is in `web/`. Native applications and home-screen widgets are outside the approved scope.

### Frontend acceptance checks

Test selecting 12:30 then 13:00 leaves exactly one checked option; changing location/date clears an invalid slot; a failed save preserves input and does not show success; edit/cancel updates the saved result and counts; private friend details never reach the client; mobile navigation remains usable with keyboard/zoom/long content.

Use component/integration tests for those behaviors and browser checks at desktop/mobile sizes. Inspect rendering privately during development; do not deliver screenshots or generated images to the user as part of this request.

## 10. UI states and quality

| State | Feedback and safe next action |
| --- | --- |
| Loading | Stable structure and progress; never show a fake zero count as loaded data. |
| Empty | Explain no menu/plans/friends and offer the relevant next action. |
| Error | Specific failed operation, preserved input, safe retry; reconcile an uncertain save before retrying. |
| Partial | Keep available menu/slot data visible; identify the failed section and retry it. |
| Success | Show saved date/mensa/time and edit/cancel controls. |
| Permission | Explain login/camera/push boundaries; support manual code entry or continuing without reminders. |
| Offline/stale | Mark freshness/read-only status; preserve a draft without falsely reporting a save. |
| Closed/expired/conflict | Explain the invalid slot/date and offer refreshed, still-open alternatives. |

Acceptance targets: complete keyboard flows; visible unobscured focus; associated form labels/errors; normal text contrast at least 4.5:1 and large text 3:1; approximately 44px touch targets; text alternative for charts; reduced-motion support; long names and zoom without overflow. These are targets, not a completed accessibility audit.

Before real-account use: standard password hashing, regenerated sessions, CSRF on mutations, prepared statements, explicit route/method allowlists, owner checks, output escaping, invite/auth rate limits, environment secrets, and logs without credentials/tokens/private payloads. Do not infer production readiness from a working demo.

Proposed performance target: normal planner server operations within 500ms on a specified development seed/environment, excluding network time. Measure before reporting results.

## 11. Conceptual data model

| Entity | Implementation and constraints |
| --- | --- |
| User | Persisted fictional alias/email/password hash; regenerated sessions and CSRF. |
| Cafeteria/MeetingPoint | Code-owned catalogue in `SlotPolicy`; static sample meeting-point labels, verified service windows and maintained `backend/database/closures.json` exceptions. |
| TimeSlot | Derived from location/date/service windows; not a mutable SQL slot table. One scalar `HH:MM` is validated with its mensa/date. |
| LunchPlan | Persisted unique user/local-date row; slot, mensa, optional meeting point, private-default audience, reminder, UTC instants, status and version. |
| Friendship | Persisted canonical unique user pair, initiator and requested/accepted/revoked state. |
| FriendInvite | Persisted hashed opaque token, owner, expiry/revocation and single-use acceptance. |
| Menu/Dish | Explicit sample catalogue with unknown diet/allergens and fictional internal/external prices; real dated operator-menu persistence is not claimed. |
| PushSubscription | Persisted user/device endpoint and keys; provider allowlist, active lifecycle and no public disclosure. |
| ReminderJob | Persisted plan/version/device key, UTC due time, status and bounded retry attempts. |
| MutationRequest | Persisted user/request ID, intent fingerprint and response for transactional idempotency. |
| RateLimit | Persisted bounded-window auth/invite attempt counters. |

There is **no multi-time Availability entity**. An unsaved single-slot choice is a draft, not another public submission. Index slot/date/cafeteria, plan status/slot, and accepted friendship pairs. Derive counts from confirmed/expired records according to BR-03 rather than independent frontend counters.

## 12. Architecture and delivery

Use a monorepo with a separate PHP JSON API in `backend/`, a responsive React/TypeScript/Vite web client in `web/`. The old server-rendered Blade architecture is removed. Use PSR-4 backend code and introduce domain services/repositories/controllers only as their feature tasks require them; do not recreate the coursework router or copy its insecure authentication.

The stack uses PHP 8.4, locked Composer dependencies, environment-based database configuration and a React/TypeScript/Vite client served by unprivileged Nginx. Docker Compose runs `web`, `backend`, `db` and `reminders`; MariaDB 11.4 persists the schema introduced by non-destructive migrations. Startup runs idempotent migration and fictional-account seeding scripts. Default code runs from built images; `compose.dev.yaml` enables optional live PHP source mounts.

Keep configuration in environment variables, lock dependency resolution, avoid committing runtime files, and retain local database data in a named volume. See [development documentation](../docs/development.md) for actual commands and current validation limits.

### Browser reminders

Use real Web Push subscriptions, server-side T-15 scheduling and a persistent worker. Delivery requires explicit browser permission and configured VAPID keys. Denied, unsupported and unconfigured states must remain honest. Browser timers alone cannot guarantee reminders with the app closed. No native app or home-screen widget delivery is planned.

Official reference: [Apple Web Push for web apps](https://developer.apple.com/documentation/usernotifications/sending-web-push-notifications-in-web-apps-and-browsers). Mobile browser support and installation requirements vary by platform.

## 13. Ordered execution plan

Execute all approved web tasks below. Campus Navy is already approved. Native mobile applications and home-screen widgets are removed from the execution plan.

### Task 0 - clean monorepo foundation (complete)

- [x] Use the active project directories from section 3, plus necessary root configuration files.
- [x] Remove legacy MVC/coursework/archive architecture and unused dependencies; preserve original Git history.
- [x] Keep all maintained documentation in English; use `.planning/plan.md` as the canonical plan.
- [x] Record Campus Navy frontend instructions, one-slot behavior and verified opening-hour evidence.
- [x] Keep only relevant, attributed assets and one internal design reference; no new image delivery.
- [x] Validate Composer/Compose, documentation links and backend health: image built, both services healthy, six HTTP smoke cases passed, and database failure returned sanitized JSON 503 before recovery.

### Task 1 - API schema and contracts

Files: `backend/database/migrations/`, `backend/src/Domain/`, `backend/src/Infrastructure/`, `docs/api.md`.

- [x] Create non-destructive migrations for section 11's persisted entities; keep the derived-slot/static-catalogue boundary explicit, with no multi-slot Availability table or legacy SQL import.
- [x] Define request/response contracts for mensas, daily menu, counts, session, plans, friends and invites, with public/private field filtering.
- [x] Test the slot-window policy first: Thursday 11:15/14:00/14:30/16:45 allowed, 14:15/17:00 rejected; Friday 13:45 allowed and 14:00 rejected.
- [x] Implement unique daily plan, scalar-slot validation, transactional count changes, version conflicts and timezone rules; prove tests fail before implementation and pass afterward.

### Task 2 - authentication and privacy

Files: backend session/auth handlers, visibility policies and corresponding tests.

- [x] Use modern password hashing, secure session handling, CSRF, ownership checks and explicit allowed methods/routes.
- [x] Test guest/owner/friend/public access and non-disclosing private friend states. Never send private data and hide it only in the browser.
- [x] Seed fictional evaluation accounts only; document them as demo data, not production credentials.

### Task 3 - Campus Navy web shell

Files: section 9's bootstrap, styles, layout and API client; `web/package.json` and its lockfile; web service/proxy in Compose.

- [x] Scaffold React/TypeScript/Vite, centralize exact color tokens and English copy, and implement desktop sidebar/mobile bottom navigation.
- [x] Add typed API/error handling and shared loading/empty/error/permission feedback.
- [x] Verify 390x844, 768x1024, 1440x900, 320px width and 200% zoom; inspect privately without sending image outputs.

### Task 4 - working planner and menu

Files: `web/src/features/planner/`, `web/src/features/menu/`, backend planner/menu handlers and tests.

- [x] Implement full valid-time radio selection and chart, optional meeting point, visibility, saved confirmation, edit and cancel.
- [x] Test one checked time after replacement, invalid-date/location reset, double-submit prevention, count transfer/cancel and stale conflicts.
- [x] Add dated menu/filter behavior with explicit sample/stale labels and actual source provenance; do not claim inherited photos represent current dishes.
- [x] Run end-to-end public browse, login, save, reload, edit and cancel against real backend persistence.

### Task 5 - friends and invite flow

Files: `web/src/features/friends/`, backend friendship/invite handlers and tests.

- [x] Implement accepted-friend list/search, single confirmed-time status, QR/code target verification and manual-code fallback.
- [x] Test expired/revoked/self/duplicate invites, camera denial, consent withdrawal and removal of friend badges from unauthorized views.

### Task 6 - browser reminders

Files: backend subscription handlers, persistent reminder jobs/worker, web service worker and browser permission controls.

- [x] Implement real T-15 scheduling, idempotency, permission handling and edit/cancel invalidation; test delayed/denied/unsupported states honestly.
- [ ] External/device verification: configure deployment VAPID keys/contact and confirm receipt on a consenting supported browser. The transport, subscription UI and persistent worker are implemented and tested with injected provider outcomes; no physical-device receipt is claimed.
- [x] Keep the web app usable when reminders are denied or unsupported.

### Task 7 - evaluation and completion

- [x] Run automated behavior, keyboard, contrast and responsive checks; see execution evidence.
- [ ] External participant study: recruit actual students and record results using `docs/evaluation.md`; no participant outcomes or SUS scores have been fabricated.
- [x] Record actual results, fix reviewed failures, update docs/CI and distinguish remaining browser-delivery limitations from completed web work.

Do not assign a deadline estimate as a commitment before the course deadline/rubric is known.

## 14. Verification and evaluation

Meaningful behavior checks: single-value validation including array rejection; unique daily plan; double submit; count transfer/cancel/past retention; stale-tab conflicts; timezone/day boundary; service opening/closing/breaks and exceptions; private-data filtering/revocation; invite errors; reminder idempotency/reschedule/cancel.

Course-task proposal:

1. As a guest, choose a date/mensa and explain what a count of 23 means: submitted plans, not actual crowd size.
2. Compare friend counts, select 12:30 only, then select 13:00 and verify 12:30 is no longer selected. Confirm one final slot.
3. Choose an optional meeting point and reminder, then read the saved confirmation.
4. Edit to another valid slot and cancel; verify the correct counts and unaffected friends' plans.
5. Add a friend via code, including the camera-denied path.
6. Verify identity exposure before/after opt-in and after revoking consent from another account.
7. Open a contextual web deep link and verify one preselected slot without an automatic save.
8. Try Eupener's 14:15-14:30 break and Friday closing boundary; the app must not offer invalid buckets.

Proposed formative study: five students, task success/time/errors, status/privacy comprehension, brief post-task feedback and SUS after the whole flow. Adapt to the rubric; do not invent study results or treat five participants as statistical validation.

Definition of done: every approved requirement has implementation/test evidence or an explicit incomplete status; no false save success; correct public/private boundaries; mobile/desktop/keyboard checks; clear demo/stale labels; usable setup docs; no unsupported browser-delivery claims.

## 15. Confirmed decisions and remaining questions

Confirmed: Campus Navy, English documentation/UI, mobile and desktop, exactly one slot, Docker development, the web-only monorepo, removal of obsolete legacy architecture, and no further image delivery. Current sequencing: the approved web implementation is delivered; follow the recorded setup and verification commands. Native mobile and home-screen widget scope has been removed by explicit user instruction.

Remaining external inputs are any course deadline/rubric constraints and a consenting browser/device for delivery verification. Do not re-ask confirmed choices. Use sensible implementation defaults where the plan explicitly leaves a decision to the builder.
