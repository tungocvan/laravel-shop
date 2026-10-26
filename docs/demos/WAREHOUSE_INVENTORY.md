# Warehouse Inventory — Standalone Blade Demo

- Branch: `feat/standalone-warehouse-inventory-blade-20261026`
- Source: user-provided `quan-ly-xuat-nhap-ton.html`
- Route: `GET /demo/quan-ly-xuat-nhap-ton`, name `demo.warehouse-inventory`
- Controller: `App\\Http\\Controllers\\Demo\\WarehouseInventoryController`
- Views: `resources/views/demo/warehouse-inventory`
- Assets: `public/demo/warehouse-inventory`
- No Module, API, migration, or database persistence.
- Browser data remains localStorage-based; the initial HTML embedded JSON and sample images are retained.
- Option A: the Save action exports JSON instead of a self-contained offline HTML document.
- Important: existing browser data is origin/path scoped; use JSON backup/import for migration.
- Focused test: `php artisan test tests/Feature/StandaloneWarehouseInventoryDemoTest.php`.
- Regression: `php artisan test tests/Feature/StandaloneSalesDemoRouteTest.php` after focused PASS.
- UI gate: NOT VERIFIED. Test desktop/tablet/iPhone, CRUD, modal, localStorage, JSON import/export, Excel, printing and console errors.
- TEST gate: NOT VERIFIED until local command output is supplied.
- Rollback: revert this feature commit or PR before deployment; no schema changes.
- Do not merge or deploy without explicit approval.
