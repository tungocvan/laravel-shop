# Standalone employee sales demo

- Route: `GET /demo/doanh-so-nhan-vien` (`demo.sales-employees`).
- Source: `resources/views/demo/sales-employees/` (Blade layout, 9 page partials, shared partials), `public/demo/sales-employees/css/app.css`, `public/demo/sales-employees/js/app.js`. The prior standalone HTML remains in `resources/demo/sales-employees.html` as a read-only comparison baseline.
- No Laravel Module, SQL table, server-side write API, or shared visitor data.
- Preserve original CSS and JavaScript business logic; only adjust file-save semantics to localStorage.
- Each browser profile and origin keeps its own demo data. Export JSON regularly.
- `localStorage` is synchronous and subject to browser quotas; for large datasets this is a limitation.
- Fonts are loaded from Google Fonts as in the reference file.

## Source artifact

The HTML was split into a dedicated controller and Blade views without introducing any new route or server persistence. Do not deploy until Laravel focused tests and manual UI comparison pass. The prior standalone HTML remains the visual and functional reference.

## Verification

1. `php artisan test tests/Feature/StandaloneSalesDemoRouteTest.php`
2. `php artisan route:list --name=demo.sales-employees`
3. Manual compare with the original HTML: desktop, tablet, iPhone; dashboard, sales journal CRUD, employee/product CRUD, reports, Excel export/import, print, JSON backup/restore, refresh persistence, reset sample and empty state.
4. Verify browser isolation in separate browser profiles; no server POST/PUT requests.
5. UI PASS requires user confirmation.

No production deployment is authorized by this document.

## Blade refactor handoff

- Controller: `App\\Http\\Controllers\\Demo\\SalesEmployeesController` (`__invoke`).
- Single route name: `demo.sales-employees`; same GET URI.
- Layout: `resources/views/demo/sales-employees/layouts/app.blade.php`.
- Main view: `resources/views/demo/sales-employees/index.blade.php`.
- Nine pages under `pages/`; shared sidebar/header/modal under `partials/`.
- The journal, employees, products, guide and settings sections are intentionally empty Blade mount points because the existing JavaScript generates their markup dynamically. Do not move this dynamic UI into Blade until a separately tested phase.
- JS remains shared and loaded after the JSON seed. Preserve the localStorage key.
- UI PASS for the previous standalone version does **not** imply UI PASS for this refactor.

## Sales demo JSON library and server backup (2026-10-26)

- Feature branch: `feat/sales-demo-dataset-backup-20261026` from current `main`.
- Public read-only library: `GET /demo/doanh-so-nhan-vien/datasets` and `GET /demo/doanh-so-nhan-vien/datasets/{dataset}`.
- Protected write: `POST /demo/doanh-so-nhan-vien/datasets` (CSRF, throttle 5/min, operator header `X-Demo-Backup-Code`).
- Shared operator secret with the existing warehouse demo: `WAREHOUSE_DEMO_BACKUP_CODE` via `config('warehouse_demo.backup_code')`; minimum 16 characters. Unset disables writes (503). No .env change in this feature.
- JSON stored on Laravel local disk at `demo-datasets/sales-employees`, completely separate from warehouse datasets. Limit 10 MiB and 100 files. Reject invalid tables and duplicate/missing row IDs.
- The Settings page is rendered dynamically by `renderSettings()` in JS, not static Blade. It now contains a read-only library, a YES + operator code confirmation modal and dual-destination backup. Existing local-only backup, Excel, CRUD, printing and restore are preserved.
- `YES` is a confirmation only, not an authorization credential. Do not publish the operator code in JS/Blade.
- Focused: `php artisan test tests/Feature/SalesDemoDatasetLibraryTest.php tests/Feature/StandaloneSalesDemoRouteTest.php`.
- Impacted after focused PASS: `php artisan test tests/Feature/WarehouseDemoDatasetLibraryTest.php tests/Feature/StandaloneWarehouseInventoryDemoTest.php`.
- UI regression: desktop/tablet/iPhone, Settings, empty/listed datasets, load confirmation cancel/success, localStorage isolation, backup cancel/YES/wrong code/valid code, Excel, print, CRUD and console.
- Gates: TEST pending actual local output, UI pending user confirmation. Do not merge or deploy without separate approval.

## Settings theme selector (same feature branch)

- Four palettes: Ruby (default), Ocean, Emerald, Indigo. Switch immediately in dynamically rendered Settings via accessible buttons.
- CSS variables scoped under `:root[data-sales-theme]` control sidebar, primary actions and accent colors; `MAU` JS palette is synchronized for existing chart rendering.
- User preference is stored separately at `localStorage['sales_employees_theme_v1']`, not inside exported/imported business JSON. Reload and dataset restoration preserve the selected theme.
- Mobile-first selector: 4 columns desktop, 2 tablet, 1 on narrow phones. Test color contrast, charts, desktop/tablet/iPhone and refresh persistence manually.
- No database, module, route or environment changes for theme selector. TEST and UI gates remain pending until user provides actual results.
