# Standalone employee sales demo

- Route: `GET /demo/doanh-so-nhan-vien` (`demo.sales-employees`).
- Source: `resources/demo/sales-employees.html` (the original customer-supplied standalone HTML, with browser persistence changes only).
- No Laravel Module, SQL table, server-side write API, or shared visitor data.
- Preserve original CSS and JavaScript business logic; only adjust file-save semantics to localStorage.
- Each browser profile and origin keeps its own demo data. Export JSON regularly.
- `localStorage` is synchronous and subject to browser quotas; for large datasets this is a limitation.
- Fonts are loaded from Google Fonts as in the reference file.

## Source artifact installation gate

The reference HTML is a user-supplied ~147 KB attachment and cannot be uploaded through the available GitHub text-file connector in this batch. The prepared `sales-employees-demo.html` artifact must be copied to `resources/demo/sales-employees.html` and committed to this feature branch before PR/merge. Do **not** merge the route without the HTML artifact.

## Verification

1. `php artisan test tests/Feature/StandaloneSalesDemoRouteTest.php`
2. `php artisan route:list --name=demo.sales-employees`
3. Manual compare with the original HTML: desktop, tablet, iPhone; dashboard, sales journal CRUD, employee/product CRUD, reports, Excel export/import, print, JSON backup/restore, refresh persistence, reset sample and empty state.
4. Verify browser isolation in separate browser profiles; no server POST/PUT requests.
5. UI PASS requires user confirmation.

No production deployment is authorized by this document.
