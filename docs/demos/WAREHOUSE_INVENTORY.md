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

Implementation note: Save exports JSON; legacy offline HTML picker/buildHtml helpers were removed from the active JavaScript.

## Demo Data Library — ngành nghề

- Feature branch: `feat/warehouse-demo-data-library-20261026` (based on the existing warehouse demo feature branch; this demo is not yet in main).
- Settings contains a public, read-only dataset selector.
- Public endpoints: `GET /demo/quan-ly-xuat-nhap-ton/datasets` and `GET /demo/quan-ly-xuat-nhap-ton/datasets/{dataset}`.
- Datasets are private JSON files on the Laravel `local` disk, in `demo-datasets/warehouse-inventory/<slug>.json` (Laravel 12 default: `storage/app/private/demo-datasets/warehouse-inventory/`).
- Add a new dataset by securely copying a validated `{products:[...],txns:[...]}` JSON file to that directory; filenames must match `[a-z0-9-]{1,80}`. Never expose server-side upload/write endpoints to public demo visitors.
- For the previously prepared phone dataset, copy `du-lieu-kho-xuat-nhap-ton-nganh-dien-thoai-2026.json` to `storage/app/private/demo-datasets/warehouse-inventory/dien-thoai-2026.json` on the demo host. This data file is an **external provisioning artifact**, not yet committed to GitHub; until provisioned, the library can legitimately be empty.
- Loading a dataset confirms replacement, validates JSON, persists in browser localStorage and re-renders. Server files are never mutated by visitors.
- Existing JSON download/import remains available. Browser state is isolated per origin/path/browser profile.
- Focused tests: `php artisan test tests/Feature/WarehouseDemoDatasetLibraryTest.php tests/Feature/StandaloneWarehouseInventoryDemoTest.php`.
- Impacted regression after focused PASS: `php artisan test tests/Feature/StandaloneSalesDemoRouteTest.php`.
- UI gate: PENDING (verify desktop/tablet/iPhone, dataset list, selection, confirmation cancel, backup/import, persistence after refresh, browser console).
- TEST gate: PENDING actual local execution.
- No merge, deploy or production file changes authorized.
