# MensaTogether web

The responsive English Campus Navy client uses React, TypeScript and Vite. It calls the same-origin PHP JSON API at `/api`; no browser database credentials or private-plan filtering belong in this client.

## Development

Use the root Docker Compose setup for the integrated application. For standalone development with Node 22.12 or later and npm 11.6.2:

```sh
npm ci
npm run dev
npm run check
```

The development proxy forwards `/api` to `http://localhost:8081`. Build output is `dist/`. Dependency resolution is committed in `package-lock.json`.

The Docker builder pins npm 11.6.2. npm 10 encounters an upstream peer-resolution error (`edgesOut`) with the patched Vitest dependency graph; no legacy-peer-dependency bypass is used.

## Structure

- `src/api.ts`: typed API client, session CSRF and error handling.
- `src/App.tsx`: context selection, navigation, authentication and server state.
- `src/features/planner/`: accessible single-slot comparison and explicit confirmation/edit/cancel.
- `src/features/menu/`: clearly labelled sample menu and diet filter.
- `src/features/friends/`: accepted friends, request acceptance and QR/manual-code invitations.
- `public/sw.js`: Web Push notification display and same-origin plan navigation.
- `src/styles.css`: approved 60/30/10 palette and desktop/mobile layouts.

Typography uses system `Segoe UI`/sans-serif fallbacks with Manrope and Source Sans 3 named as optional local faces. There are no external font requests. Food photographs are illustrative sample assets; see [asset attribution](../docs/reference/assets.md).

## Behavior and verification

One selected radio is a draft. Explicit confirmation stores one plan on the server. Login preserves the draft; changing date or mensa clears it. Owner versions prevent stale-tab overwrites. Visibility defaults to private; public counts do not imply measured attendance. Device reminders need explicit browser permission, a subscription and configured server delivery. The scope is a responsive website, without native apps or OS widgets.

Vitest covers radio replacement, unavailable slots, explicit saves, cross-mensa meeting-point safety and login/context draft handling. Integrated browser checks belong to the root verification scripts. Detailed requirements remain in [the plan](../.planning/plan.md).
