# Pharma Collaboration Handoff

## Current checkpoint

- Module: `Pharma`
- Phase: Major Refactor
- Branch: `feat/pharma-admin-dashboard`
- Status: **PHARMA ADMIN DASHBOARD IMPLEMENTED — READY FOR PR REVIEW**
- Date: 2026-08-30
- Application source modified: **YES**
- Production/runtime enablement changed: **NO**

## Foundation already merged

MR-1 Security Foundation and MR-2 Shared Import/Export Hardening were merged to `main` through PR #88. The merged foundation includes capability-aware Pharma route/action boundaries, safe error handling, closure of the broken public Pharma API route, and Shared Import/Export hardening with private temporary exports and Pharma-safe import modes.

## Completed scope — Pharma Admin Dashboard

A dedicated Pharma landing workspace is now implemented so administrators do not need to remember individual Pharma URLs.

### Route and authorization

- Added `GET /admin/pharma` named `admin.pharma.dashboard`.
- Route is inside the existing `web` + `auth:admin` Pharma group.
- Dashboard additionally requires `view_pharma`.

### Dashboard structure

- Uses the canonical `Admin::layouts.master` shell; no new/custom admin layout was introduced.
- Provides four primary workspace cards:
  - Medicine / HSSP;
  - Drug Bid Awards;
  - Supplier Tracking;
  - PriceList / Excel price-list generation.
- Each card navigates to the existing named Pharma route rather than duplicating feature behavior.
- Provides permission-aware quick-create actions. Create actions are only rendered for accounts with `create_pharma`.

### Lightweight operational summary

- Displays Medicine/HSSP count.
- Displays Drug Bid Award count.
- Displays Supplier Tracking count.
- Displays PriceList workbook readiness and only the configured workbook filename, without exposing an absolute filesystem path.
- Dashboard does not load large data tables or unbounded record collections.

### Implementation boundary

- Added a dedicated `PharmaDashboardController` rather than overloading the existing HSSP `PharmaController`.
- Added `PharmaDashboardService` for lightweight summary/readiness data.
- No CRUD semantics, import/export behavior, PriceList generation pipeline, or database schema was changed by this slice.
- Pharma production/module enablement remains unchanged.

## Verification completed

- Focused dashboard test:
  - `php artisan test tests/Feature/Pharma/PharmaAdminDashboardTest.php`
  - **PASS — 4 tests, 18 assertions**.
- Dashboard route gate:
  - `php artisan route:list --name=admin.pharma.dashboard`
  - **PASS — `GET|HEAD admin/pharma` resolves to `PharmaDashboardController`**.
- Focused Pharma regression:
  - `php artisan test tests/Feature/Pharma Modules/Pharma/Tests`
  - **PASS — 16 tests, 73 assertions**.
- Frontend build:
  - `npm run build`
  - **PASS — Vite 7.3.6, 34 modules transformed, production build completed in 3.88s**.
- Manual UI smoke:
  - **PASS**.
  - `/admin/pharma` renders the canonical admin shell, summary cards, four workspace cards, quick actions, and workbook readiness correctly.
  - Runtime workbook was detected as ready (`BANG_GIA_TONG_HOP.xlsx`).

No full-project regression was run; this follows the collaboration workflow preference for focused module and directly impacted regression only.

## Scope intentionally deferred

The dashboard is navigation/operational overview only. The following Major Refactor findings remain intentionally deferred:

- Medicine bounded pagination/page-scoped selection and Admin UI cleanup.
- DrugBidAward bounded pagination and searchable Medicine selection.
- SupplierTracking business-key integrity, duplicate audit and bounded selection.
- PriceList public Livewire analysis state, repeated analysis and deeper pipeline hardening.
- Queue/performance work unless later benchmarking proves it necessary.
- Production enablement.

## Next approved Major Refactor sequence

After this dashboard PR is reviewed and merged, continue with the previously approved sequence:

1. **Medicine Workspace refactor** — next implementation slice.
2. Drug Bid Award Workspace refactor.
3. Supplier Tracking integrity/workspace refactor.
4. PriceList security/pipeline refactor.
5. Final acceptance and closeout.

### Medicine Workspace target

The next slice should use Medicine as the reference implementation for the remaining Pharma admin workspaces:

- bounded pagination using the approved 10/25/50/100 options; no `All`/999999 behavior;
- page-scoped selection and deterministic selection reset when filters/page change;
- explicit permission-aware bulk delete;
- canonical Admin controls, loading states, responsive behavior and accessible destructive confirmation;
- preserve existing Medicine domain/CRUD behavior unless a separately approved defect requires correction.

Do not start the Medicine Workspace implementation until the dashboard PR is merged and the user confirms continuation. Do not change SupplierTracking cascade semantics, enable Pharma in production, or expand into unrelated module cleanup without a separate approved decision.
