# ClientPortal Invoices Partner Report — Closeout

Date: 2026-09-09

Branch: `feat/clientportal-invoices-partner-report`

## Scope delivered

- Added the PWA executive partner report at `/apps/invoices/partners`.
- Added ClientPortal navigation and permission contract for the partner report.
- Reused `Modules/Invoices/Services/InvoicePartnerReportService` for partner aggregation and detail data instead of reusing Admin Blade/Livewire presentation.
- Added partner search by name or tax code with the shared ClientPortal autocomplete experience.
- Added relationship filters for customer/supplier and relationship-aware default sort.
- Added executive KPI cards, Top sold partners, Top purchase partners, partner detail, responsive filters, pagination and partner list/table presentation.
- Responsive behavior is intentionally split as Mobile/Tablet cards below `xl` and Desktop table from `xl` upward.
- Money columns and executive amounts are kept on one line where appropriate.
- Added `Cả năm` to `/apps/invoices/list` month filtering, with backend date range covering the selected whole year and preserved search/export/filter state.

## Architecture boundary

`Modules/Invoices` remains the owner of invoice/partner business data, queries and aggregation. `Modules/ClientPortal/Applications/Invoices` owns PWA routing, web-guard authorization, responsive presentation and client-safe orchestration. No Admin view or Admin authentication boundary is reused by the PWA report.

## Acceptance

Manual UI acceptance recorded as PASS for:

- Partner report Desktop.
- Partner report Tablet.
- Partner report Mobile/PWA.
- Partner autocomplete.
- Relationship-aware sorting.
- Top sold / Top purchase amount visibility on Tablet and Mobile.
- Invoice list `Cả năm` month filter.

Final bounded CLI acceptance:

- `php artisan test tests/Feature/ClientPortal/InvoicesPartnerReportPwaContractTest.php` — PASS.
- Changed-PHP `vendor/bin/pint --test` gate — PASS.

Previously accepted Invoices PWA regression/build scopes were not rerun because this delivery follows the recorded bounded-validation policy: already-PASSed scopes are reused unless later changes invalidate them.

## Merge readiness

The delivery is ready for pull request to `main`. No migration, schema rewrite, destructive operation, GDT credential exposure, Admin auth reuse or Google Drive ownership change is included in this scope.
