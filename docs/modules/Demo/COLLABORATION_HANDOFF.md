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

## Shared layout/sidebar/theme pilot (2026-10-29)

- User confirmed previous Menu Editor batch: focused + shared-menu regression PASS, UI PASS.
- Introduced module shared layout, configurable sidebar shell and theme selector; Rental Room only consumer.
- Preserved sidebar navigation IDs, live badges, occupancy widget, menu editor, theme storage key and five color palettes. Browser verification still required after this batch.
- Added focused structural and asset tests. Rerun focused tests, shared menu regression, then desktop/mobile UI. No tests executed remotely.
- Do not merge/deploy without approval; 11 other demos unchanged.

## Runtime JavaScript regression hotfix

- Browser console showed `integrations.js: input is not defined` after theme extraction despite previous test/UI PASS.
- Reintroduced locally scoped input factory and authenticated dataset fetch helper in Rental Room integrations; prompt secret per operation, never store it. Cancelled prompt returns null and must not report a successful save.
- Added focused source contract assertions. New focused, shared menu regression and browser smoke are pending; prior UI PASS is superseded for this hotfix.
- Browser-extension `runtime.lastError` messages are separate from the confirmed first-party ReferenceError.

## Validation checkpoint — user-confirmed (2026-10-29)

- User reported focused tests PASS and second-level shared-menu regression PASS for the shared layout/sidebar/theme batch.
- Subsequent browser report identified `ReferenceError: input is not defined` in Rental Room integrations. Commit `3c7674e3650e2768c16078c813958a8115730b70` restores the form input factory and authenticated dataset API helper, and adds a focused regression contract test.
- User reported that Chrome Incognito Console has no errors after the hotfix; repeated `runtime.lastError` and `pdf_content.js` errors in normal Chrome are consistent with browser extension messaging rather than app code.
- **Gate qualification:** prior two-layer test PASS predates the hotfix; fresh focused/regression test results for the hotfix have not yet been supplied. Incognito Console clean is not a complete UI functional regression confirmation for all server JSON actions. Require new test outputs and functional UI PASS before merge.
- PR review is authorized, but merge and production deployment remain blocked pending explicit user consent and all gates.

## Final validation confirmation (2026-10-29)

- After hotfix and checkpoint, user explicitly confirmed: **focused tests PASS, shared-menu regression PASS, UI PASS**.
- User explicitly authorized merging PR #345 into main after review.
- These are user-reported local test/UI results; GitHub connector did not execute the local test suite or browser UI.
- Preserve untracked local files and do not deploy production. After merge, synchronize local main and verify HEAD equals origin/main.

## Phase 02 — Hotel Uniform implementation checkpoint (2026-10-29)

- Branch: `refactor/demo-module-hotel-uniform-20261029`, based on `main` commit `e7bb8f3a3adfbe5f95452554a0046adaeff36a62`.
- REHOME: Hotel Uniform UI/dataset controllers, Blade, CSS and JS to `Modules/Demo`; KEEP: original URL, dataset storage directory, browser keys, five original Hotel Uniform themes, shared menu endpoints; DEFER: theme-selector palette generalization; QUARANTINE: runtime snapshots, untracked files and production.
- Shared Blade layout, sidebar shell and module menu editor are reused. Hotel-specific nav/brand/card/footer remain in demo partials. Browser asset URLs are preserved via allowlisted controller; tracked public CSS/JS removed.
- Test gate: `HotelUniformDemoTest.php` + `DemoModuleHotelUniformOwnershipTest.php` focused, then `SharedDemoSidebarMenuTest.php` + `DemoModuleRentalRoomOwnershipTest.php` regression. No local tests or browser UI were executed by GitHub connector. Both TEST and UI remain PENDING.
- Validate all 13 screens, CRUD, Excel, print, localStorage, JSON backup/restore, server snapshots, five themes, menu editor and mobile drawer. Check browser console, HTTP 403/419/422/503, CSS/JS URLs and Nginx static fallback. No merge or deploy without explicit authorization.
