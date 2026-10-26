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
