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
