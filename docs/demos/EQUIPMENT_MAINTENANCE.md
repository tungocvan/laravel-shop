# Quản lý thiết bị và bảo trì — standalone Laravel Blade

## Scope and architecture
- Branch: `feat/equipment-maintenance-blade-20261029`.
- GET: `/demo/quan-ly-thiet-bi-bao-tri`, controller `EquipmentMaintenanceController`.
- Views: `resources/views/demo/quan-ly-thiet-bi-bao-tri/`; public assets: `public/demo-assets/quan-ly-thiet-bi-bao-tri/`.
- Original HTML dynamic shell, business JS, DOM IDs, Excel, JSON backup/restore, printing, offline HTML export retained.
- Browser data key: `thietbi_baotri_phongexcel_v1|<pathname>`; browser state is authoritative until user confirms restoration.
- Shared sidebar editor: `x-demo.sidebar-menu-builder`, existing server menu endpoints and private preset library. Shared allowlist expanded only for this demo.
- Themes: original, Ruby, Ocean, Emerald, Indigo; localStorage key `equipment_maintenance_theme_v1`. Preview tiles appear in Settings.
- Private server snapshots: GET/POST `/demo/quan-ly-thiet-bi-bao-tri/datasets`, GET `/datasets/{dataset}`; authenticated via `warehouse_demo.backup_code` (16+ characters), header `X-Demo-Backup-Code`, CSRF on writes, 10 MB limit, 100 files, rate-limited. Stored under `storage/app/demo-datasets/equipment-maintenance`, not public; no database.
- No automatic server restore, overwrite, or delete. Explicit confirmation required before restoring a selected snapshot. Browser JSON backup is retained.

## Collaboration handoff — 2026-10-29
- Source assets committed by user: `732248c9a`.
- Subsequent GitHub commits: menu allowlist, menu component, script order/CSRF, themes, server snapshots, CSS and tests.
- Focused tests: **NOT RUN**. Run `php artisan test tests/Feature/EquipmentMaintenanceDemoTest.php`.
- Impacted regression only after focused PASS: `php artisan test tests/Feature/ProductCostingDemoTest.php tests/Feature/CoffeeShopSalesDemoTest.php` and shared menu focused tests if present.
- JS syntax: user ran `node --check` on original asset before integrations; integrations pending local check.
- UI: **PENDING**, user must verify desktop/tablet/iPhone, all 10 pages, CRUD, calendar, Excel, JSON, print, offline HTML export, menu editor, 5 themes, server snapshots and browser reload.
- Tracked working tree: verify locally. Existing unrelated untracked files and safety stash must be preserved.
- Rollback: revert feature branch commits/PR; preserve browser localStorage, server snapshot files, and unrelated untracked files.
- Next: pull feature branch, run focused test and syntax checks; stop and debug on FAIL; only then impacted regression; user confirms UI PASS; create/review PR and merge only after explicit approval.

## Default sidebar menu JSON — 2026-10-29
- In Menu editor → Thư viện JSON, select a saved JSON file and choose **Đặt làm mặc định**.
- Server pointer: `storage/app/demo-settings/equipment-maintenance/default-preset.json` (private). The selected preset remains in `presets/`; GET menu resolves it dynamically.
- Authorization: existing `warehouse_demo.backup_code` and CSRF, with throttled PUT endpoint. Only equipment demo exposes this operation.
- The selected preset is labeled `(Mặc định)`; deletion of the active default returns HTTP 409. No automatic deletion of other presets.
- Browser override is cleared when setting default; existing other demo menu behavior remains unchanged.
- Test/UI: pending Admin pull, focused tests, impacted shared-menu tests, and user UI confirmation.

## Selected JSON editing — 2026-10-29
- Selecting a menu preset populates the name input with its saved display name; the name is informational for an existing file (no rename).
- **Lưu file** updates the selected JSON using PUT after confirmation; with no selection it creates a new JSON file.
- **Lưu lên server** writes active sidebar config and also updates the selected JSON (two separately authorized writes). Partial failure must be reported and retried; no silent claim of full success.
- Both writes serialize the full `config`, including `header`, `footer`, and `groups`.
- **Mặc định** reloads the server's selected default config into the editing draft for this demo, or original menu if no server default exists; **Cập nhật giao diện** applies it.
- Tests and UI for these changes are **NOT YET RUN**; verify selected file name, save/update, header/footer persistence and reload, default behavior, and unaffected other demos.

## Phase 03 — Modules/Demo ownership migration (2026-10-29)

- Branch `refactor/demo-module-equipment-maintenance-20261029` is based on accepted Phase 02 commit, not `main`. Demo 02 PR #346 remains unmerged.
- Canonical Controller/API: `Modules/Demo/Http/Controllers/Projects/EquipmentMaintenance/`; views: `Modules/Demo/resources/views/projects/equipment-maintenance/`; source CSS/JS: `Modules/Demo/resources/assets/projects/equipment-maintenance/`.
- The module layout is shared; sidebar/navigation is still dynamically constructed by the original JS and cannot be safely replaced with a static Blade sidebar without a separate UI refactor.
- Original browser asset URLs remain through an allowlisted Laravel route. All browser state, offline HTML export, server snapshots and five themes are preserved.
- Focused: `php artisan test tests/Feature/EquipmentMaintenanceDemoTest.php tests/Feature/DemoModuleEquipmentMaintenanceOwnershipTest.php`.
- Regression after focused PASS: `php artisan test tests/Feature/HotelUniformDemoTest.php tests/Feature/DemoModuleHotelUniformOwnershipTest.php tests/Feature/DemoModuleRentalRoomOwnershipTest.php tests/Feature/SharedDemoSidebarMenuTest.php`.
- TEST and UI PENDING. Do not merge or deploy.
