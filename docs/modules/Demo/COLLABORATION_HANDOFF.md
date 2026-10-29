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

## Phase 02 — Hotel Uniform theme-card UI checkpoint (2026-10-29)

- User reported both focused and module regression test tiers PASS for the preceding Phase 02 migration; no raw PHPUnit logs are available in this conversation.
- User requested and approved replacing the five text-only theme buttons with palette preview cards similar to Rental Room.
- Changed only `Modules/Demo/resources/assets/projects/hotel-uniform/js/integrations.js` and `Modules/Demo/resources/assets/projects/hotel-uniform/css/app.css`.
- Cards preview each demo's existing two palette colors, retain `hotel_uniform_theme_v1`, the existing `setTheme()` function, all five palette values and all business logic. Selected card uses `aria-pressed` and a checkmark; keyboard focus and mobile responsive grid included.
- This new UI change has NOT been retested; focused TEST PENDING and UI PENDING for the updated commit. Run `node --check Modules/Demo/resources/assets/projects/hotel-uniform/js/integrations.js`, focused Hotel Uniform tests, then impacted regression. Check five themes on desktop/tablet/iPhone, persistence after reload, menu editor, browser console.
- No PR, merge, production deployment or changes to other demo assets.

## Phase 02 — User acceptance (2026-10-29)

- User explicitly confirmed **TEST + UI PASS** after the Hotel Uniform five-theme card UI update, including the previously reported two test tiers.
- Evidence: user confirmation in collaboration chat; raw local PHPUnit logs and browser automation reports were not attached.
- GitHub comparison against `main` showed feature branch ahead with no divergence at acceptance check.
- Next gate: PR review and tracked working-tree check on Admin. Do not merge until explicit user approval; no production deployment.

## Phase 03 — Equipment Maintenance Git Pull/Test checkpoint (2026-10-29)

- Branch: `refactor/demo-module-equipment-maintenance-20261029` created from Phase 02 accepted commit `7303e9ccfa645f3f5be2ea98cb0af1870a0bf56b`. PR #346 remains OPEN and unmerged.
- REHOME: equipment UI/dataset controllers, Blade, CSS and JS to `Modules/Demo`; KEEP: route names/URLs, original business JS, offline HTML template, browser keys, five themes, menu API and server snapshot directory; DEFER: shared Blade sidebar markup because JS owns the dynamic sidebar; QUARANTINE: private snapshots, untracked files and production.
- Module layout and module-owned shared menu editor are adopted. Module asset controller retains browser asset URLs using a strict allowlist. Legacy tracked files retired.
- Focused tests (PENDING): `php artisan test tests/Feature/EquipmentMaintenanceDemoTest.php tests/Feature/DemoModuleEquipmentMaintenanceOwnershipTest.php`.
- Impacted regression only after focused PASS (PENDING): `php artisan test tests/Feature/HotelUniformDemoTest.php tests/Feature/DemoModuleHotelUniformOwnershipTest.php tests/Feature/DemoModuleRentalRoomOwnershipTest.php tests/Feature/SharedDemoSidebarMenuTest.php`.
- UI PENDING: desktop/tablet/iPhone, dynamic sidebar, menu editor/presets/default preset, all screens and CRUD, JSON import/export and server snapshots, Excel, print, offline HTML export, five themes and persistence after reload, console/network errors.
- No local tests have been executed by the GitHub connector. Do not merge main, close PR #346 or deploy production; proceed to Demo 04 only after user confirms TEST + UI PASS.

## Phase 03 — User acceptance (2026-10-29)

- User explicitly confirmed **TEST + UI PASS** for Equipment Maintenance after the Phase 03 Git Pull/Test checkpoint.
- Focused, impacted regression and manual UI gates are recorded as user-confirmed PASS; raw CLI output/browser artifacts were not attached in this conversation.
- Phase 03 is accepted for continuation, **not merged into main**. PR #346 for Phase 02 remains open and unmerged.
- Next: audit Phase 04 demo from this accepted feature branch, preserve prior accepted changes, seek architecture approval before major changes, then stop at Git Pull/Test checkpoint.

## Phase 04 — Coffee Shop Sales Git Pull/Test checkpoint (2026-10-29)

- Branch: `refactor/demo-module-coffee-shop-sales-20261029` from accepted Phase 03 commit `f0b6c2e91bb406e32e7faed355706ef4735f37bf`. Phase 02 PR #346 stays open; do not merge main.
- REHOME: Coffee Shop Sales UI/dataset controllers, Blade, CSS/JS and routes into `Modules/Demo`; KEEP: original URL, business JS/embedded HTML export, localStorage, IndexedDB, themes, private server JSON library and shared menu API; DEFER: Blade sidebar refactor because JS dynamically owns `#sb`; QUARANTINE: untracked files and runtime data.
- Adopted module layout and module-owned shared menu editor. Asset URLs preserved through strict allowlist. Tracked legacy sources retired.
- Focused tests PENDING: `php artisan test tests/Feature/CoffeeShopSalesDemoTest.php tests/Feature/DemoModuleCoffeeShopSalesOwnershipTest.php`.
- Impacted regression after focused PASS PENDING: `php artisan test tests/Feature/EquipmentMaintenanceDemoTest.php tests/Feature/DemoModuleEquipmentMaintenanceOwnershipTest.php tests/Feature/HotelUniformDemoTest.php tests/Feature/DemoModuleHotelUniformOwnershipTest.php tests/Feature/DemoModuleRentalRoomOwnershipTest.php tests/Feature/SharedDemoSidebarMenuTest.php`.
- UI PENDING: all coffee screens, CRUD/orders, inventory deduction, browser data persistence, IndexedDB, JSON server snapshots, menu editor/presets, themes, Excel/print/offline HTML export, responsive and console errors.
- GitHub connector did not run local PHPUnit or browser checks. No PR merge, no production deploy; next Demo only after user confirms TEST + UI PASS.

## Phase 04 — Focused test failure: obsolete public asset assertion (2026-10-29)

- User reported `CoffeeShopSalesDemoTest::test_assets_do_not_shadow_route` failed: old assertion expected `public/demo-assets/quan-ly-ban-hang-ca-phe/js/app.js` after intentional move to module-owned assets. Focused run: 1 failed, 9 passed (42 assertions); regression not yet run.
- Root cause: stale test contract, not proof of broken browser JS. Updated test to assert module source exists, old public copy is absent, and allowlisted browser asset URL returns 200 with `nosniff`.
- Focused and impacted regression must be rerun. UI PASS must be confirmed after correction. No merge or deployment.

## Phase 04 — Coffee Shop theme UI adjustment (2026-10-29)

- User supplied screenshot of Coffee Shop Settings and requested theme preview cards matching previous demos and removal of the separate `Giao diện & thư viện JSON server` card.
- Replaced the Coffee Shop theme selector with five responsive palette preview cards (Màu gốc, Ruby, Ocean, Emerald, Indigo), `aria-pressed`, keyboard focus and persistent existing `coffee_shop_sales_theme_v1` key. Existing four nondefault CSS palettes remain unchanged; original uses base CSS variables.
- Removed only the redundant Coffee Shop Settings server JSON library UI from `integrations.js`. Existing server dataset API/controller and private saved files are preserved for backward compatibility and data safety. Shared menu editor/preset JSON remains untouched; menu preset JSON and business dataset JSON have different meanings.
- Added focused source contract test. Previous tests/UI (if any) are superseded for changed assets: syntax, focused, impacted regression and user-confirmed UI PENDING. No merge or production changes.

## Phase 04 — User acceptance (2026-10-29)

- User explicitly confirmed **TEST + UI PASS** for Coffee Shop Sales after the five-theme preview and removal of redundant JSON server Settings widget.
- This is user-confirmed acceptance; raw CLI and browser artifacts were not attached in this turn.
- Demo 04 accepted for progression to Demo 05, not merged into main. Existing PR #346 remains unmerged; no production deploy.
- Next phase must branch from this accepted Demo 04 tip, audit the next remaining demo read-only, and preserve all prior accepted demos.

## Phase 05 — Daily Work Report Git Pull/Test checkpoint (2026-10-29)

- Branch `refactor/demo-module-daily-work-report-20261029` created from accepted Demo 04 commit `854b016eac31ea5e13891e0d56db0854a3b1c1c4`; all Demo 01–04 accepted changes retained. Demo 02 PR #346 remains OPEN and unmerged.
- REHOME: UI controller/route, Blade layout/sidebar/eight page partials, CSS/JS and `original.html` into `Modules/Demo`. KEEP: original browser URLs, menu editor component and API, localStorage, IndexedDB, HTML/Excel/JSON export, data/DOM event handlers. DEFER: additional refactors to shared sidebar. QUARANTINE: production, untracked files, private runtime data.
- Original offline HTML template preserved byte-for-byte in module-owned assets; browser URL served via strict allowlist. Legacy tracked public copies retired. Validate web-server fallback and stale public files before any deployment.
- Five theme preview cards including Màu gốc implemented, existing storage key retained; no separate server business JSON library introduced.
- Focused PENDING: `php artisan test tests/Feature/DailyWorkReportDemoTest.php tests/Feature/DailyWorkReportMenuTest.php tests/Feature/DemoModuleDailyWorkReportOwnershipTest.php`.
- Impacted after focused PASS PENDING: `php artisan test tests/Feature/CoffeeShopSalesDemoTest.php tests/Feature/DemoModuleCoffeeShopSalesOwnershipTest.php tests/Feature/EquipmentMaintenanceDemoTest.php tests/Feature/DemoModuleEquipmentMaintenanceOwnershipTest.php tests/Feature/HotelUniformDemoTest.php tests/Feature/DemoModuleHotelUniformOwnershipTest.php tests/Feature/DemoModuleRentalRoomOwnershipTest.php tests/Feature/SharedDemoSidebarMenuTest.php`.
- UI PENDING: desktop/tablet/iPhone, all pages, CRUD, badge and menu editor/presets, five themes and reload, browser persistence, Excel/JSON/offline HTML export, browser console/network errors.
- No tests or UI checks executed by GitHub connector. No merge, no deploy. Stop at Git Pull/Test until user confirms TEST + UI PASS.

## Phase 05 — User acceptance (2026-10-29)

- User explicitly confirmed **TEST + UI PASS** for Daily Work Report after the Phase 05 Git Pull/Test checkpoint; CLI logs and browser screenshots were not attached.
- Demo 05 accepted for sequential progression to Demo 06, without merging main or deploying.
- Demo 06 must branch from this accepted tip and preserve all accepted Demo 01–05 changes.
