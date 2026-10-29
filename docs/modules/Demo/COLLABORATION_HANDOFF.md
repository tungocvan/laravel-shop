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

## Phase 06 — Enterprise Management Report Git Pull/Test checkpoint (2026-10-29)

- User confirmed Demo 05 TEST + UI PASS; acceptance recorded on Demo 05 branch before creating `refactor/demo-module-enterprise-management-report-20261029` from its tip. Demo 01–05 accepted code remains inherited; main unmerged.
- REHOME: enterprise report controller/route, Blade, CSS/JS/preferences and original offline HTML into `Modules/Demo`. KEEP: browser route/asset URLs, business JS, DOM IDs, browser state, Excel/JSON/print/offline export, menu API/presets and operator-code authorization. DEFER: static Blade sidebar refactor because JS owns the sidebar DOM. QUARANTINE: runtime data, untracked files and production.
- Five responsive theme preview cards (Màu gốc/Ruby/Ocean/Emerald/Indigo), existing theme storage key preserved. No new business server JSON library.
- Focused PENDING: `php artisan test tests/Feature/EnterpriseManagementReportDemoTest.php tests/Feature/EnterpriseManagementReportPreferencesTest.php tests/Feature/DemoModuleEnterpriseManagementReportOwnershipTest.php`; `node --check` for both module-owned JS files.
- Impacted regression after focused PASS PENDING: `php artisan test tests/Feature/DailyWorkReportDemoTest.php tests/Feature/DailyWorkReportMenuTest.php tests/Feature/DemoModuleDailyWorkReportOwnershipTest.php tests/Feature/CoffeeShopSalesDemoTest.php tests/Feature/DemoModuleCoffeeShopSalesOwnershipTest.php tests/Feature/EquipmentMaintenanceDemoTest.php tests/Feature/DemoModuleEquipmentMaintenanceOwnershipTest.php tests/Feature/HotelUniformDemoTest.php tests/Feature/DemoModuleHotelUniformOwnershipTest.php tests/Feature/DemoModuleRentalRoomOwnershipTest.php tests/Feature/SharedDemoSidebarMenuTest.php`.
- UI PENDING: desktop/tablet/iPhone; reports, forms, CRUD, browser state, sidebar/menu editor/presets, five themes and persistence after F5, JSON/Excel/print, offline HTML opening with data, console/network errors.
- GitHub connector has not run local PHP tests or browser checks. Stop at checkpoint; no merge or deploy. Keep PR #346 open/unmerged until user explicitly authorizes final merge.

## Phase 06 — UI correction: sidebar editor alignment (2026-10-29)

- User supplied comparison screenshots: Demo 06 menu editor was a long single-panel form; Demo 05 uses tabs Menu/Header/Footer/Thư viện JSON.
- Root cause: Demo 06 still uses its legacy standalone preferences editor even after migrating to the Demo module; merely moving assets did not unify its modal UX.
- Refactored the existing Demo 06 editor presentation to the shared Demo tabbed UX (heading, tabs, scrollable content, fixed actions). Existing menu normalization, localStorage key, operator-code authorization, server preset API, drag/drop, JSON import/export and theme selection remain in their current module-specific adapter. This is **visual/interaction alignment**, not a claim that both demos now share the same editor implementation; a true shared component migration needs a separate adapter and regression plan.
- Added source-level focused test for tab structure and persistence hooks. No local PHP/Node/browser execution available via GitHub connector; focused, impacted and UI gates must be rerun and user-confirmed before Demo 07.

## Phase 07 — Attendance Payroll Git Pull/Test checkpoint (2026-10-29)

- Branch: `refactor/demo-module-attendance-payroll-20261029`, from verified `main` merge commit `3e61998f8e499b7b1be1e9d050405c8d5a6ca346` (PR #347).
- REHOME: attendance/payroll UI and dataset controllers, Blade layout/app partial, CSS and JS under `Modules/Demo`; remove only their tracked legacy counterparts and route declarations. KEEP: `/demo/cham-cong-tinh-luong` and all dataset names, existing browser asset URLs, salary calculation engine, localStorage key, server JSON directory, auth header, CSRF and throttling.
- Existing `SharedSidebarMenuController` endpoints remain canonical. Module shared menu editor CSS/JS is served through the new allowlisted payroll asset controller, using the existing payroll bridge. The old standalone Blade menu component is replaced; no duplicate menu persistence.
- Five theme preview cards (Màu gốc, Ruby, Ocean, Emerald, Indigo); preserve the existing `attendance_payroll_theme_v1` key and previous selected values, with the previous default `ocean` unchanged.
- Focused TEST PENDING: `php artisan test tests/Feature/AttendancePayrollDemoTest.php tests/Feature/DemoModuleAttendancePayrollOwnershipTest.php`; JS syntax checks for `core.js`, `payroll.js`, `extensions.js`.
- Impacted regression only after focused PASS: `php artisan test tests/Feature/SharedDemoSidebarMenuTest.php tests/Feature/EnterpriseManagementReportDemoTest.php tests/Feature/DemoModuleEnterpriseManagementReportOwnershipTest.php`. Review test names if files differ.
- UI PENDING: desktop/tablet/iPhone, all attendance/payroll screens, employee CRUD, attendance edits, payroll/tax calculations, save/reload, Excel/print, browser JSON import/export, server dataset index/load/save and authorization failures, shared menu tabs/presets, all five themes and persistence, console/network. UI PASS requires user confirmation.
- No local PHP/Node/browser tests executed through GitHub. No PR merge, production deploy, database migration, `.env` changes or runtime snapshot changes. Static web server must fall back to Laravel for removed public asset paths; stale untracked public copies must not be deleted without permission.

- **2026-10-29 acceptance:** User explicitly confirmed **TEST + UI PASS** for Demo 07 at checkpoint. Both focused and impacted test commands were provided; individual terminal output was not attached to this turn. Await PR review and explicit merge authorization; no production deployment.

## Phase 08 — Human Resources checkpoint (2026-10-29)

- Branch: `refactor/demo-module-human-resources-20261029` inherits accepted Demo 07 changes; PR #348 remains OPEN and unmerged. No main merge until all 12 demos accepted.
- Rehome HR controllers, Blade, CSS/JS, routes; preserve dataset storage `demo-datasets/human-resources`, browser key `qlns_data_v2`, theme key `qlns_theme_v1`, menu API and HR dynamic department/status navigation.
- Shared module menu editor and HR-specific bridge remain responsible for sidebar customization; added fifth theme Màu gốc without changing default Emerald or saved selections.
- Focused test checkpoint: `php artisan test tests/Feature/HumanResourcesDemoRouteTest.php tests/Feature/DemoModuleHumanResourcesOwnershipTest.php`.
- Impacted regression after focused PASS: `php artisan test tests/Feature/SharedDemoSidebarMenuTest.php tests/Feature/AttendancePayrollDemoTest.php tests/Feature/DemoModuleAttendancePayrollOwnershipTest.php`.
- UI pending: desktop/tablet/iPhone, dashboard, employee CRUD, filters/pagination, menu editor and server presets, five themes, JSON import/export/server snapshots, print and console/network errors.
- No local tests or browser UI were run through GitHub connector. No main merge or production deployment.

## Phase 09 — Debt Management Git Pull/Test checkpoint (2026-10-29)

- User confirmed Demo 08 **TEST + UI PASS** before Phase 09. Branch `refactor/demo-module-debt-management-20261029` inherits the accepted Demo 08 branch; no merge to main until all 12 demos are accepted.
- REHOME: Debt Management UI, menu, dataset controllers, module views and original business CSS/JS. KEEP: canonical `/demo/quan-ly-cong-no`, menu/preset/dataset API, existing names, operator-code security, JSON storage, dynamic `#app` shell, business behavior, localStorage, five themes and exports.
- Shared module menu editor consumes the existing `DebtDemoMenuBridge`; debt-specific server menu API is not replaced or duplicated. Original menu adapter is loaded before business app initialization; shared editor loads after app initialization.
- Module asset allowlist retains existing URLs, removes tracked legacy copies only. Web-server fallback to Laravel must be checked before any eventual deployment; do not delete stale untracked files.
- Focused PENDING: `php artisan test tests/Feature/DemoModuleDebtManagementOwnershipTest.php` and any existing debt-specific feature tests. JS syntax checks: `node --check Modules/Demo/resources/assets/projects/debt-management/js/core.js`, `menu-builder.js`, `app.js`, `server-datasets.js`.
- Impacted after focused PASS: `php artisan test tests/Feature/SharedDemoSidebarMenuTest.php tests/Feature/HumanResourcesDemoRouteTest.php tests/Feature/DemoModuleHumanResourcesOwnershipTest.php`.
- UI PENDING: desktop/tablet/iPhone, debt dashboard, partners, vouchers, ledgers, age reports, calculations, JSON/Excel/print, browser/server datasets, menu tabs and server presets, all five themes and refresh persistence, console/network.
- GitHub connector has not run local PHP/Node/browser tests. No merge, production deploy, runtime or environment modifications.

- Phase 09 impacted regression reported by user: 21 PASS, 1 FAIL in `SharedDemoSidebarMenuTest::test_debt_demo_mounts_shared_editor_without_replacing_legacy_api` due to outdated legacy script URL assertion. Read-only audit confirmed module layout loads module shared editor URL and retains debt-specific bridge and menu API. Updated only that assertion to the module-owned shared editor URL in commit `e2084b35c8c537dbda46f30dab73cb1007d88214`. Focused/impacted rerun and manual menu UI verification PENDING; no PASS claim for this fix.

## Phase 10 — Sales Employees checkpoint (2026-10-29)

- User confirmed Demo 09 TEST + UI PASS. Branch `refactor/demo-module-sales-employees-20261029` inherits accepted Demo 09; no main merge before all 12 demos pass.
- Rehome sales UI, datasets, 12 Blade partials/pages, JS/CSS, routes to Modules/Demo. Keep original business calculations, localStorage, JSON server snapshots and menu API; shared menu editor is module-owned.
- Add fifth Original palette while retaining Ruby default and saved theme preferences. Asset allowlist uses /demo-assets/doanh-so-nhan-vien; check server fallback before any future deployment.
- Focused PENDING: `php artisan test tests/Feature/StandaloneSalesDemoRouteTest.php tests/Feature/DemoModuleSalesEmployeesOwnershipTest.php`. Syntax PENDING: `node --check Modules/Demo/resources/assets/projects/sales-employees/js/app.js`.
- Impacted after focused PASS: `php artisan test tests/Feature/SharedDemoSidebarMenuTest.php tests/Feature/DemoModuleDebtManagementOwnershipTest.php`.
- UI PENDING: desktop/tablet/iPhone, sales journal CRUD, employee/product lists, dashboards/reports, import/export, server snapshots, shared menu editor, five themes, browser refresh, console/network.
- No PHP/Node/browser tests run by GitHub connector; do not declare PASS or merge/deploy.

## Phase 11 — Warehouse Inventory checkpoint (2026-10-29)

- User confirmed Demo 10 TEST + UI PASS. Branch `refactor/demo-module-warehouse-inventory-20261029` inherits accepted Demo 10; main remains unmerged until 12/12 acceptance.
- Warehouse inventory UI and dataset controllers, Blade layout/pages/partials, CSS/JS and routes moved to Modules/Demo. Preserve stock in/out, product CRUD, history, inventory report, browser/server JSON, menu API, operator-code authorization, exports and persistence.
- Add Original theme as fifth palette while retaining Indigo default. Module asset allowlist serves original browser URLs via Laravel; web-server fallback must be validated before any future deployment.
- Focused PENDING: `php artisan test tests/Feature/DemoModuleWarehouseInventoryOwnershipTest.php`; syntax `node --check Modules/Demo/resources/assets/projects/warehouse-inventory/js/app.js`.
- Impacted after focused PASS: `php artisan test tests/Feature/SharedDemoSidebarMenuTest.php tests/Feature/StandaloneSalesDemoRouteTest.php tests/Feature/DemoModuleSalesEmployeesOwnershipTest.php`.
- UI PENDING: desktop/tablet/iPhone, stock in/out, CRUD products, history, inventory reports, JSON import/export and server library, menu editor, five themes and refresh persistence, console/network.
- No local PHP/Node/browser tests run by GitHub connector; no TEST/UI PASS claim and no merge/deploy.

- Phase 11 impacted regression user report: 10 PASS / 1 FAIL (`SharedDemoSidebarMenuTest::test_shared_assets_are_mounted_in_both_demo_views`): outdated assertion expected legacy `demo/shared/sidebar-menu/menu-builder.js`. Audited Sales/Warehouse module Blade: both load their own module-owned shared menu editor asset, with preserved data-demo-menu markers. Updated only assertion to `demo-assets/{demo}/shared/menu-editor.js` in `d6e9da94014bd21ccca011e6209104ab5f24ae14`. Re-run focused + impacted regression and verify menu UI before PASS. No application code or API changed.
