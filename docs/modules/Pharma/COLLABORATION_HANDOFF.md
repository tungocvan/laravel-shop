# Pharma Collaboration Handoff

## Current checkpoint

- Module: `Pharma`
- Phase: Major Refactor
- Branch: `refactor/pharma-security-import-export-foundation`
- Status: **MR-1 + MR-2 IMPLEMENTED — READY FOR PR REVIEW**
- Date: 2026-08-30
- Application source modified: **YES**
- Production/runtime enablement changed: **NO**

## Completed scope

This branch combines the first two approved Major Refactor slices because they share the same security/import-export boundary.

### MR-1 — Security Foundation

- Added capability-aware authorization at Pharma admin route boundaries:
  - `view_pharma` for list/workspace routes;
  - `create_pharma` for create and PriceList creation routes;
  - `edit_pharma` for edit routes.
- Added action-level authorization inside Pharma Livewire components so direct Livewire calls cannot bypass route middleware.
- Delete and bulk-delete mutations require `delete_pharma`.
- Replaced raw user-facing exception messages in touched Pharma flows with safe messages while still reporting exceptions server-side.
- Removed the broken public `/api/pharma` route that targeted an API action without a valid consumer/implementation contract.
- Removed the unreachable SupplierTracking import/export route; the Shared import/export panel remains embedded in the SupplierTracking workspace.

### MR-2 — Shared Import/Export Hardening

- Locked Shared Import/Export component state that must remain server-owned, including service selection, allowed modes and configured permission.
- Revalidates service type and permission at action time.
- Added server-owned `allowedImportModes()` contract while preserving broad defaults for backward compatibility.
- Pharma SupplierTracking explicitly excludes destructive `replace` import mode until its business/data-integrity contract is settled.
- SupplierTracking explicitly requires `edit_pharma` for Shared import/export actions.
- Shared export/template files now use private local storage and authorized download responses with delete-after-send cleanup.
- Added normalized-path containment checks for export files.

## PriceList regression reliability correction

The pre-existing `PriceListServiceTest` depended on runtime file `storage/app/excel/BANG_GIA_TONG_HOP.xlsx`, causing the module regression suite to fail whenever that operational file was absent.

The test is now self-contained: it creates its own temporary XLSX fixture and injects that source into `PriceListService`. Runtime behavior keeps the existing default source path unchanged.

The actual runtime workbook was subsequently provisioned locally at:

`storage/app/excel/BANG_GIA_TONG_HOP.xlsx`

and the PriceList UI smoke passed. This operational workbook is not committed as application source.

## Verification completed

- Changed-files Pint:
  - `git diff --name-only origin/main...HEAD -- '*.php' | xargs ./vendor/bin/pint --test`
  - **PASS — 17 PHP files** at the time of the formatting gate.
- Focused contract test:
  - `php artisan test tests/Feature/Pharma/PharmaSecurityImportExportFoundationTest.php`
  - **PASS — 4 tests, 20 assertions**.
- Pharma regression:
  - `php artisan test Modules/Pharma/Tests`
  - **PASS — 3 tests, 14 assertions**.
- Route gate:
  - `php artisan route:list --path=admin/pharma`
  - **PASS — 10 Pharma admin routes present**.
  - `php artisan route:list --path=api/pharma`
  - **PASS — no public Pharma API route remains**.
- Frontend build:
  - `npm run build`
  - **PASS — Vite production build completed**.
- Manual UI smoke:
  - **PASS**.
  - PriceList successfully analyzed the provisioned runtime workbook after it was placed at the expected storage path.

No full-project regression was run; this follows the collaboration workflow preference for focused module and directly impacted regression only.

## Scope intentionally deferred

The following findings remain for later Major Refactor slices and were not broadened into this foundation branch:

- Medicine bounded pagination/page-scoped selection and Admin UI cleanup.
- DrugBidAward bounded pagination and searchable Medicine selection.
- SupplierTracking business-key integrity, duplicate audit and bounded selection.
- PriceList public Livewire analysis state, repeated analysis and deeper pipeline hardening.
- Queue/performance work unless later benchmarking proves it necessary.
- Production enablement.

## Approved next slice after this PR merges

### Pharma Admin Dashboard

User approved adding a dedicated `/admin/pharma` landing dashboard before continuing the remaining workspace refactors.

Planned dashboard scope:

- canonical Admin layout; no custom shell;
- route `GET /admin/pharma` named `admin.pharma.dashboard` guarded by `view_pharma`;
- navigation cards for Medicine/HSSP, Drug Bid Awards, Supplier Tracking and PriceList;
- permission-aware quick actions;
- lightweight operational summary/counts only;
- workbook readiness indicator for PriceList without exposing sensitive filesystem paths;
- no large data tables or expensive unbounded queries on the dashboard.

This dashboard must be implemented in a separate branch/PR after the current security/import-export foundation PR is merged.

## Remaining Major Refactor sequence

After the dashboard slice, continue with the previously approved boundaries:

1. Medicine Workspace refactor.
2. Drug Bid Award Workspace refactor.
3. Supplier Tracking integrity/workspace refactor.
4. PriceList security/pipeline refactor.
5. Final acceptance and closeout.

Do not change SupplierTracking cascade semantics, enable Pharma in production, or expand into unrelated module cleanup without a separate approved decision.
