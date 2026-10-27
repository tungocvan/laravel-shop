# Standalone Debt Management Demo — Quản lý công nợ

- Branch: feat/debt-management-blade-20261027 (base main bfc6fc552).
- Route: GET /demo/quan-ly-cong-no, name demo.debt-management.
- Controller: App\\Http\\Controllers\\Demo\\DebtManagementController.
- No Module, database migration, production operation or backend data mutation.
- Blade layout and shell are isolated under resources/views/demo/debt-management.
- CSS and original JS logic are separate under public/demo-assets/quan-ly-cong-no.
- The original app dynamically renders all navigation, sidebar, header, modals and pages into #app. Do not pre-render these with Blade without UI regression.
- Original HTML used a File System Access API to overwrite its own HTML; Laravel Blade cannot safely use that mechanism. Adapt persistence to browser-local localStorage and JSON download/restore.
- Focused test: php artisan test tests/Feature/StandaloneDebtManagementDemoTest.php.
- Impacted regression after focused PASS: php artisan test tests/Feature/StandaloneSalesDemoRouteTest.php tests/Feature/StandaloneWarehouseInventoryDemoTest.php.
- Manual UI gate: dashboard, partners, vouchers, aging, reconciliation, reports, Excel/print, backup/restore, reload persistence, tablet/iPhone, console errors.
- JSON server library and four-color theme Settings implemented on this branch, pending new focused/regression/UI gates.
- TEST PASS: pending local output. UI PASS: pending user confirmation. No PR merge/deploy authorized.

## JSON server library and themes (2026-10-27)

- Public GET list/load: `/demo/quan-ly-cong-no/datasets` and `/demo/quan-ly-cong-no/datasets/{dataset}`.
- Protected POST to `/demo/quan-ly-cong-no/datasets`: CSRF + throttle 5/min + `X-Demo-Backup-Code`; shares existing `config('warehouse_demo.backup_code')` (minimum 16 characters, unset disables writes). Do not expose or log secret.
- Private local disk `demo-datasets/debt-management`, maximum 10 MiB and 100 JSON files; independent of warehouse/sales.
- Data shape: `settings` object, `partners` list with unique string IDs, `vouchers` list with unique string IDs referencing existing partner IDs, optional `_app: congno`.
- The browser can list/load public samples after confirmation. Uploading a named backup requires YES and operator code; downloads local JSON before attempting POST. Browser CRUD never writes to server automatically.
- Appearance settings: Orange original (preserved default), and Ruby, Ocean, Emerald, Indigo alternatives. Stored separately as `localStorage['debt_management_theme_v1']`, independent of business JSON. Palette accent applies to chart.
- Assets intentionally live under `public/demo-assets/quan-ly-cong-no` to prevent Nginx 403 caused by physical directory collision with the GET route.
- Focused: `php artisan test tests/Feature/StandaloneDebtManagementDemoTest.php tests/Feature/DebtManagementDatasetLibraryTest.php`.
- Impacted after focused PASS: `php artisan test tests/Feature/SalesDemoDatasetLibraryTest.php tests/Feature/WarehouseDemoDatasetLibraryTest.php tests/Feature/StandaloneSalesDemoRouteTest.php tests/Feature/StandaloneWarehouseInventoryDemoTest.php`.
- UI: server empty/list/load, cancel/confirm, YES/wrong/valid code, failed server backup vs local download, CRUD/JSON restore, four alternate themes + original, refresh, responsive, chart colors, browser console.
- Previous UI PASS covered the earlier Blade-only version, not this updated feature batch. TEST/UI pending new output. No merge or deploy.

## Sidebar Menu Builder (2026-10-27)

- Entry point: `/demo/quan-ly-cong-no#caidat` → **Chỉnh sửa menu**. No header edit button.
- Dynamic sidebar remains owned by the existing `App` renderer; `menu-builder.js` wraps `App.init` and the Settings view, preserving business routes and DOM event delegation.
- Editor supports drag/drop items within/between groups (desktop HTML5 drag/drop), rename group, add item/group, edit label/icon/href, duplicate, delete, reset default, import/export JSON.
- JSON schema: `{version:1,groups:[{title,items:[{id,label,icon,href}]}]}`. Unique IDs; hash routes or HTTPS external URLs only. Unknown internal hashes are disabled in sidebar; never execute arbitrary URL schemes.
- GET `/demo/quan-ly-cong-no/menu`: read-only public shared configuration (null means original defaults).
- POST `/demo/quan-ly-cong-no/menu`: operator-protected by existing `config('warehouse_demo.backup_code')` (min 16 chars), CSRF, throttle 5/min, max 64 KiB. Never place code in HTML/JS/localStorage/log. Server stores JSON on Laravel private local disk at `demo-settings/debt-management/sidebar-menu.json`; no database or .env edits.
- Import validates locally and requires confirmation; import only edits the pending configuration. **Lưu lên server** applies globally to all browsers after operator authorization. Closing editor reloads server state, discarding unsaved edits. Export downloads current editor JSON.
- Security: shared public GET exposes menu labels/hrefs; do not include sensitive internal links. Only trusted operators should write. Server save is not per-user.
- Focused tests: `php artisan test tests/Feature/StandaloneDebtManagementDemoTest.php tests/Feature/DebtManagementMenuTest.php`.
- After focused PASS, impacted tests: `php artisan test tests/Feature/DebtManagementDatasetLibraryTest.php tests/Feature/StandaloneSalesDemoRouteTest.php tests/Feature/StandaloneWarehouseInventoryDemoTest.php`.
- UI gate: edit entry in Settings, reorder within/across groups, rename/icon/href, duplicate/delete/reset, JSON import/export, failed/wrong/valid admin code, refresh and second browser, external HTTPS link, invalid hash, mobile responsive, keyboard navigation, no JS console errors.
- Pending focused and UI verification on this batch. No merge or deploy.
