# Pharma Collaboration Handoff

## Current checkpoint

- Module: `Pharma`
- Phase: Major Refactor
- Branch: `refactor/pharma-supplier-tracking-integrity-workspace`
- Status: **MR-5 SUPPLIER TRACKING INTEGRITY + WORKSPACE ACCEPTED — READY FOR PR REVIEW**
- Date: 2026-08-30
- Application source modified: **YES**
- Production/runtime enablement changed: **NO**

## Already merged foundation

- PR #88 merged MR-1 Security Foundation + MR-2 Shared Import/Export Hardening.
- PR #89 merged the dedicated Pharma Admin Dashboard at `/admin/pharma`.
- PR #90 merged MR-3 Medicine/HSSP Workspace.
- PR #91 merged MR-4 Drug Bid Award Workspace + Sync-Ready Foundation.

## Completed scope — MR-5 Supplier Tracking Integrity + Workspace

### Canonical business-key integrity

Supplier Tracking now uses the explicit business identity:

`medicine_id + normalized supplier_name + working_date`

Rules:

- supplier name is normalized with trim, whitespace squish and lowercase semantics;
- duplicate rows are prohibited when `working_date` is non-null;
- multiple rows with the same Medicine/supplier are allowed when `working_date` is null;
- application-level duplicate detection provides a friendly domain error;
- database unique index `supplier_trackings_business_key_unique` remains the final integrity boundary;
- create/update and Shared Import/Export use the same normalized identity semantics;
- the internal normalized supplier field is not exposed as an export column.

The migration is retry-safe for the column-add/audit path and audits existing data before creating the unique constraint.

### Existing-data duplicate audit

A read-only audit command is available:

```bash
php artisan audit:pharma-supplier-tracking-duplicates
```

The audit:

- does not mutate data;
- ignores null `working_date` according to the approved identity rule;
- processes records in bounded chunks;
- reports duplicate business keys and affected IDs;
- returns a clear PASS when no conflicts exist.

The local dataset was audited before migration and passed with no duplicate business-key conflicts.

### Supplier Tracking Admin workspace

- Pagination is bounded to `10`, `25`, `50`, `100`; no `All` / unbounded path exists.
- Selection is page-scoped only.
- Changing search/filter/page/per-page state clears selection deterministically.
- Bulk delete requires an explicit confirmation modal showing the selected count and current-page scope.
- CRUD/import-export/destructive controls remain permission-aware and action-authorized.
- Search covers supplier, representative/area and Medicine-related context.
- Filters include status and working-date range.
- Loading-disabled states, responsive table behavior and empty states follow the Pharma Admin workspace pattern.
- `Quay về Dashboard Pharma` navigation remains available.
- External contract links use safe new-tab attributes.

### Bounded Medicine/HSSP lookup

Supplier Tracking forms no longer require an unbounded Medicine collection.

- Server lookup is bounded to 25 candidates.
- Search supports Medicine name, registration number and active ingredients.
- The currently selected Medicine remains visible while editing even when it falls outside the current candidate search result.

### Financial calculation contract preserved

MR-5 intentionally preserves the existing financial formulas:

- invoice difference amount = invoice price - import price;
- invoice difference fee = invoice difference amount × fee percent / 100;
- cost price = import price + invoice difference fee;
- gross profit percent = `(selling price - cost price) / selling price × 100` when selling price > 0, otherwise 0.

Regression coverage includes normal calculations, invoice price below import price and zero selling price.

### Medicine delete behavior intentionally unchanged

The existing Medicine -> Supplier Tracking `cascadeOnDelete()` behavior is retained in MR-5. This MR does not introduce a retention-policy change. Any future historical-retention requirement must be handled as an explicit data-policy decision rather than silently changing the foreign-key contract.

## Local Supplier Tracking demo pack

A repeatable local-only dataset is available:

```bash
php artisan reset:pharma-supplier-tracking-demo
```

Safety and coverage:

- refuses to run outside `APP_ENV=local`;
- deletes only records carrying dedicated `DEMO-PHARMA-SUPPLIER-` / `DEMO-PHARMA-SUP-HSSP-` identifiers;
- does not truncate Pharma tables or run destructive database reset commands;
- creates 8 identifiable demo Medicine/HSSP records;
- creates 36 Supplier Tracking rows across 6 suppliers, 3 areas and 4 statuses;
- produces four pages at the default 10 records/page;
- records are created through the Supplier Tracking service so normalized identity and financial calculations are exercised.

## Import/Export regression alignment

During MR-5 regression, the older `PharmaImportExportTest` fixture was found to have drifted from canonical Pharma workbook/schema contracts established by earlier refactors. The fixture was corrected rather than weakening production behavior:

- test setup now applies the current five Pharma migrations used by the tested models, including MR-4 Drug Bid Award source identity and MR-5 Supplier Tracking business identity;
- Medicine workbook fixtures follow the current A-U positional mapping and required Medicine fields;
- Drug Bid Award fixtures follow the current A-L positional mapping;
- Drug Bid Award tests now correctly assert the MR-4 contract: deterministic Medicine matches populate nullable `medicine_id`, while unmatched awards remain valid canonical award snapshots with `medicine_id = null`; no fuzzy auto-match is introduced.

## Verification completed

- Existing-data duplicate audit:
  - `php artisan audit:pharma-supplier-tracking-duplicates`
  - **PASS — no duplicate Supplier Tracking business key detected**.
- MR-5 integrity migration:
  - `2026_08_30_020000_add_business_key_to_supplier_trackings_table`
  - **PASS — applied successfully**.
- Focused Supplier Tracking workspace test:
  - `php artisan test tests/Feature/Pharma/PharmaSupplierTrackingWorkspaceTest.php`
  - **PASS — 6 tests, 39 assertions**.
- Import/Export corrective regression:
  - `php artisan test tests/Feature/Pharma/PharmaImportExportTest.php`
  - **PASS — 6 tests, 27 assertions**.
- Import/Export Pint gate:
  - `./vendor/bin/pint --test tests/Feature/Pharma/PharmaImportExportTest.php`
  - **PASS**.
- MR-5 application/migration Pint gates:
  - **PASS**.
- Focused Pharma impacted regression:
  - `php artisan test tests/Feature/Pharma Modules/Pharma/Tests`
  - **PASS — 36 tests, 212 assertions**.
- Local demo reset runtime:
  - **PASS — 36 Supplier Tracking rows, 8 HSSP, 6 suppliers, 3 areas, 4 statuses, 4 pages at 10/page**.
- Frontend production build after MR-5 application/UI changes:
  - `npm run build`
  - **PASS — Vite production build completed**.
- Manual Supplier Tracking UI smoke:
  - **PASS**.
  - Verified bounded pagination, search/filtering, page-scoped selection, destructive confirmation, CRUD, bounded Medicine lookup, duplicate business-key behavior, financial calculations, Import/Export presence, contract link behavior, responsive/loading states and Dashboard navigation.

No full-project regression was run; testing remains intentionally focused on Pharma and directly impacted behavior.

## Scope intentionally deferred

- Actual Muasamcong -> Pharma production synchronization/wiring.
- Final numeric/nullability/structured-name mapping policy for Muasamcong source data.
- Automated fuzzy Medicine matching.
- Local edit/delete lock policy for externally sourced Drug Bid Award rows unless separately approved.
- Changing the existing Medicine -> Supplier Tracking cascade delete policy.
- PriceList public Livewire analysis state, repeated analysis and deeper pipeline hardening.
- Queue/performance work unless later benchmarking proves it necessary.
- Production enablement.

## Next approved Major Refactor sequence

After this MR-5 PR is reviewed and merged, continue only after explicit user confirmation with:

1. **MR-6 PriceList Security + Pipeline**.
2. **MR-7 Final Acceptance + closeout**.

The actual Muasamcong -> Pharma Drug Bid Award integration remains a separate integration slice and must not be silently folded into MR-6. Before implementing it, explicitly approve the source normalization/mapping policies already documented during MR-4.

Do not begin MR-6, production sync, production enablement or unrelated module cleanup until this MR-5 PR is merged and the user confirms continuation.
