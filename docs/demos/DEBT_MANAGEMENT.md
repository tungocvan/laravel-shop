# Standalone Debt Management Demo — Quản lý công nợ

- Branch: feat/debt-management-blade-20261027 (base main bfc6fc552).
- Route: GET /demo/quan-ly-cong-no, name demo.debt-management.
- Controller: App\\Http\\Controllers\\Demo\\DebtManagementController.
- No Module, database migration, production operation or backend data mutation.
- Blade layout and shell are isolated under resources/views/demo/debt-management.
- CSS and original JS logic are separate under public/demo/quan-ly-cong-no.
- The original app dynamically renders all navigation, sidebar, header, modals and pages into #app. Do not pre-render these with Blade without UI regression.
- Original HTML used a File System Access API to overwrite its own HTML; Laravel Blade cannot safely use that mechanism. Adapt persistence to browser-local localStorage and JSON download/restore.
- Focused test: php artisan test tests/Feature/StandaloneDebtManagementDemoTest.php.
- Impacted regression after focused PASS: php artisan test tests/Feature/StandaloneSalesDemoRouteTest.php tests/Feature/StandaloneWarehouseInventoryDemoTest.php.
- Manual UI gate: dashboard, partners, vouchers, aging, reconciliation, reports, Excel/print, backup/restore, reload persistence, tablet/iPhone, console errors.
- JSON server library and four-theme Settings require separate tested feature batch; not claimed implemented in this checkpoint.
- TEST PASS: pending local output. UI PASS: pending user confirmation. No PR merge/deploy authorized.
