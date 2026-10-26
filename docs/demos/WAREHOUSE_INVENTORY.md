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

## Named dual-destination server backup (2026-10-26)

- Feature branch: `feat/warehouse-demo-server-backup-20261026` based on the dataset library branch.
- Settings `Sao lưu dữ liệu`: downloads current browser JSON first, then prompts for operator code and POSTs a named dataset to the server. A failed POST never claims server success; local download remains available.
- `POST /demo/quan-ly-xuat-nhap-ton/datasets` is throttled (5 requests/minute), CSRF-protected and requires a server-side secret supplied in `X-Demo-Backup-Code`.
- Server config: `config/warehouse_demo.php` reads `WAREHOUSE_DEMO_BACKUP_CODE`; **unset or under 16 characters disables writes (HTTP 503)**. No secret is emitted in HTML or JS, and no code changes or .env edits are made automatically. Operator must approve and configure this value separately on the demo host before UI server-write testing.
- JSON size limit: 10 MiB; library limit: 100 JSON files. Reject malformed product/transaction structures, missing product references and duplicate product IDs.
- Storage: `Storage::disk('local')` under `demo-datasets/warehouse-inventory`; actual root is determined by `config('filesystems.disks.local.root')`, currently reported as `/var/www/laravel-shop/storage/app` on the Admin host.
- File names are sanitized, unique and append timestamp + random suffix; no existing JSON is overwritten. The custom name is stored as `datasetName` inside the JSON and shown in the public read-only library.
- Public GET list/load remains anonymous; users' localStorage edits never alter server JSON unless an operator explicitly authorizes a POST.
- Focused tests: `php artisan test tests/Feature/WarehouseDemoDatasetLibraryTest.php tests/Feature/StandaloneWarehouseInventoryDemoTest.php`.
- Impacted regression after focused PASS: `php artisan test tests/Feature/StandaloneSalesDemoRouteTest.php`.
- UI gate: PENDING. Check download, canceled code prompt, wrong code, valid code, list refresh, selection, no accidental overwrites and console/network errors.
- Test gate: PENDING actual local output. Do not merge/deploy until gates and approval.

## YES confirmation modal and manager-only server write (2026-10-26)

- Feature branch: `feat/warehouse-demo-confirm-modal-20261026`, based on the unmerged server-backup branch.
- Settings server backup now opens an in-page modal. User must type exact `YES` and the operator-only backup code before a POST is made. Modal has Cancel and Escape behavior, and clears the code after submission.
- `YES` is **confirmation only, not authentication**. Existing backend secret requirement (`WAREHOUSE_DEMO_BACKUP_CODE`, minimum 16 characters) remains unchanged. A 3-character code or an unprotected public POST is not implemented.
- Visitors can still use the separate **Tải JSON về máy** button without authentication. The server-backup modal downloads a local JSON copy before attempting authorized POST, and reports any server error explicitly.
- No Laravel administrator session is presumed or added. A future seamless manager-session workflow would require a separate authenticated admin entry point and approval.
- Focused tests: `php artisan test tests/Feature/WarehouseDemoDatasetLibraryTest.php tests/Feature/StandaloneWarehouseInventoryDemoTest.php`.
- After focused PASS, impacted regression: `php artisan test tests/Feature/StandaloneSalesDemoRouteTest.php`.
- UI regression: modal YES invalid/cancel/Escape, missing/wrong code, authorized success, server unavailable, public local-only export, mobile responsive and console errors. Previous UI PASS does not cover this changed modal.
- TEST and UI gates for this branch: pending actual results. No merge or deployment.
