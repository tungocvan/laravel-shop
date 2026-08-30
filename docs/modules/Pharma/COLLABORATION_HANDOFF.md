# Pharma Collaboration Handoff

## Current checkpoint

- Module: `Pharma`
- Phase: Major Refactor
- Branch: `refactor/pharma-price-list-secure-pipeline`
- Status: **MR-6 PRICE LIST SECURITY + PIPELINE ACCEPTED — READY FOR PR REVIEW**
- Date: 2026-08-30
- Application source modified: **YES**
- Production/runtime enablement changed: **NO**

## Already merged foundation

- PR #88 merged MR-1 Security Foundation + MR-2 Shared Import/Export Hardening.
- PR #89 merged the dedicated Pharma Admin Dashboard at `/admin/pharma`.
- PR #90 merged MR-3 Medicine/HSSP Workspace.
- PR #91 merged MR-4 Drug Bid Award Workspace + Sync-Ready Foundation.
- PR #92 merged MR-5 Supplier Tracking Integrity + Workspace (`68bf4f5fd022a1a329cdb85cfc976435a4d5c434`).

## Completed scope — MR-6 Price List Security + Pipeline

### Server-only workbook analysis state

- The PriceList Livewire component no longer exposes the full workbook analysis as public Livewire state.
- Workbook metadata and product rows are resolved server-side for render/action work.
- Only primitive UI state remains public: sheet, search, pagination, selection, column expression and recipient/signature values.
- This removes the previous client round-trip of complete workbook analysis/product cell values.

### Bounded PriceList workspace

- Product list pagination is bounded to `10`, `25`, `50`, `100`; there is no `All` path.
- The default page size is 10.
- Opening/reloading the workbook no longer auto-selects every product.
- Header selection is page-scoped only.
- Search/per-page/page changes clear page-selection state deterministically.
- Users can clear the complete current selection explicitly.
- Search remains based on STT, product name, active ingredient and registration number.

### Canonical generation boundary

- The Livewire component no longer performs a second independent column-validation pass before generation.
- `PriceListService::generate()` is the canonical execution boundary for:
  - workbook analysis;
  - column-expression validation;
  - selected-product-row validation;
  - output allocation;
  - workbook build.
- The existing XLSX business output contract is preserved.

### Private output-path hardening

- Production generation no longer accepts arbitrary `output_path` input from request/component data.
- Generated files are allocated only under:

  `storage/app/private/exports/price-lists`

- Filenames remain timestamped and randomized.
- Partial output is removed when workbook building fails.
- Successful UI downloads continue to use `deleteFileAfterSend(true)`.
- Tests can still exercise the generated workbook through the service's private export contract without opening a request-controlled filesystem path.

### PriceList UI polish

- The workspace keeps the canonical Pharma/Admin shell and `Quay về Dashboard Pharma` navigation.
- Workbook readiness/status is presented as a compact summary instead of exposing raw analysis data.
- Recipient/signature settings and source-column selection remain explicit.
- Product selection is presented as a bounded searchable workspace with page-size controls and page-scoped selection wording.
- Selection count, filtered result count and page range are visible before generation.
- Loading/disabled states cover workbook reload, selection changes and generation.
- Error and unavailable-workbook states remain user-readable while exceptions are reported to system logs.

## Verification completed

### Focused PriceList gate

```bash
./vendor/bin/pint --test \
  Modules/Pharma/Livewire/PriceList/Create.php \
  Modules/Pharma/Services/PriceListService.php \
  Modules/Pharma/Tests/Unit/PriceListServiceTest.php \
  tests/Feature/Pharma/PharmaPriceListPipelineTest.php

php artisan test \
  tests/Feature/Pharma/PharmaPriceListPipelineTest.php \
  Modules/Pharma/Tests/Unit/PriceListServiceTest.php
```

Result:

- Pint: **PASS — 4 files**.
- Focused PriceList tests: **PASS — 8 tests, 42 assertions**.
- Coverage includes server-only analysis-state contract, service-only validation boundary, private output-path confinement, bounded/page-scoped workspace behavior, workbook analysis/filtering and generated XLSX correctness.

### Pharma impacted regression

```bash
php artisan test tests/Feature/Pharma Modules/Pharma/Tests
```

Result: **PASS — 41 tests, 240 assertions**.

No full-project regression was run; verification remains intentionally focused on Pharma and directly impacted behavior.

### Frontend build

```bash
npm run build
```

Result: **PASS — Vite production build completed, 34 modules transformed**.

### Manual UI acceptance

Final manual PriceList UI smoke: **PASS**.

Accepted behavior includes:

- compact PriceList workspace hierarchy;
- no automatic all-workbook selection;
- bounded `10/25/50/100` pagination;
- page-scoped checkbox selection;
- correct search/pagination interaction;
- explicit clear-selection behavior;
- recipient/column/signature workflow;
- successful XLSX generation/download;
- preserved generated workbook content/format;
- loading/error states and Dashboard navigation.

## Scope intentionally deferred

- Creating a PriceList database entity/table.
- Queue/background generation unless future benchmarking proves it necessary.
- User upload/replacement of the source workbook.
- Switching PriceList source data to Medicine database records.
- Actual Muasamcong -> Pharma production synchronization/wiring.
- Automated fuzzy Medicine matching.
- Changing Medicine -> Supplier Tracking cascade-delete policy.
- Production enablement.

## Next approved Major Refactor sequence

After this MR-6 PR is reviewed and merged, continue only after explicit user confirmation with:

1. **MR-7 Final Acceptance + closeout**.

MR-7 should validate the completed Pharma Major Refactor as a whole, confirm remaining intentional deferrals/non-goals, run the agreed focused acceptance gates, update final documentation and close the refactor program. It must not silently introduce the deferred Muasamcong production sync or production enablement.

Do not begin MR-7, production synchronization, production enablement or unrelated module cleanup until the MR-6 PR is merged and the user explicitly confirms continuation.
