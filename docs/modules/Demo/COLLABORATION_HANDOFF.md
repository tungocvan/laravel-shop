# Demo — Collaboration Handoff

Branch: `refactor/demo-module-rental-room-20261029`.

Scope: Phase 0 bootstrap and Phase 01 Rental Room only. Shared sidebar and other demos stay in place.

Checkpoint: focused tests, impacted regression and UI review are pending. Do not merge or deploy.

## Asset Route batch (2026-10-29)

- Rental Room CSS/JS canonical source: `Modules/Demo/resources/assets/projects/rental-room/`.
- New allowlisted GET `/demo-assets/quan-ly-phong-tro/{asset}` preserves existing URLs and sends CSS/JS MIME, nosniff, short cache headers.
- Remove only 3 tracked Rental Room files from `public/demo-assets/`. Other 11 demos remain untouched.
- Existing shared sidebar endpoints remain outside module. No database, localStorage or JSON snapshot migration.
- Prior test/UI PASS predates this batch: rerun focused tests, shared menu regression and rendered UI smoke. Never claim PASS before new results.
- Deployment caveat: verify web-server fallback routes missing static files to Laravel; stale public files on servers can bypass PHP route. No production changes authorized.

## Shared menu editor pilot — Rental Room

- Audited canonical legacy editor `public/demo/shared/sidebar-menu/menu-builder.{js,css}` and 11 legacy demo paths.
- New module-owned reusable `resources/assets/shared/menu-editor.{js,css}` mirrors legacy editor UI (dialog, tabs, server JSON library, footer actions); rental room opts in via `data-demo-menu`.
- Removed rental-only menu modal logic from rental `integrations.js`; retained theme and dataset logic. Legacy menu APIs remain unchanged.
- Server file operations use existing shared menu API and `X-Demo-Backup-Code`. Secret must be configured and valid; do not log/store it in browser storage.
- Verify UI interactions, menu icons/badges, 5 themes, JSON save/load/delete, default preset and server response statuses. No tests executed on GitHub; user must confirm.
- Other 11 demos untouched; broader migration deferred to independent batches.
