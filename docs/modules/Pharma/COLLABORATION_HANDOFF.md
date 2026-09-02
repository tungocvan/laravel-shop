# Pharma Collaboration Handoff

## Current checkpoint

- Module: `Pharma`
- Mode: **Refactor Module — corrective contract/UI/export alignment**
- Branch: `refactor/pharma-contract-ui-export-alignment`
- Base: `main@a3fd01330c9662e26a0998feb7c04a40fe58eb6d`
- Status: **IMPLEMENTATION COMPLETE — LOCAL VERIFICATION + UI ACCEPTANCE PENDING**
- Date: 2026-09-02
- Consolidation rule: **single branch / single PR**
- Schema/database migration change: **NO**
- Route change: **NO**
- Authorization contract change: **NO**
- Cross-module source change: **NO**

## Objective

Correct post-refactor drift without reopening the accepted Pharma architecture. The approved corrective scope is limited to durable module contract, Admin UI consistency, selection/export semantics, focused regression coverage and closeout documentation.

The explicit export rule is now:

- selected checkboxes non-empty -> export exactly the selected records;
- no selection -> export the complete current filtered dataset, not only the visible page;
- selected IDs take precedence over ordinary list filters for determining the exported record set;
- export selection is available to edit/export-capable users and is no longer coupled to delete permission.

## Implemented changes

### Durable module contract

Added `docs/modules/Pharma/MODULE.md` before source implementation. It records canonical ownership, dependency on `Shared`, persistence/auth boundaries, bounded pagination, Admin input conventions, page-scoped destructive selection and the selected/all export contract.

### Medicine / HSSP

- `MedicineImportExport` honors normalized `selected_ids` and otherwise exports the full filtered Medicine dataset.
- Medicine workspace passes `selected_ids` to Shared Import/Export.
- Selection checkboxes are visible when the user can edit/export or delete; destructive actions remain delete-only.
- Search/select controls were normalized to the current Admin input treatment.
- Previous/current/next-only pagination was replaced with bounded numbered pagination plus previous/next controls and `aria-current`.

### Drug Bid Awards

- `DrugBidAwardImportExport` honors selected IDs.
- No-selection export now mirrors workspace semantics for partial investor/company filters and includes `source_type` filtering.
- Workspace passes search, investor, company, source and selected IDs to Shared Import/Export.
- Selection permission was decoupled from delete permission.
- Inputs and pagination were normalized to the Admin standard.

### Supplier Tracking

- Supplier export honors selected IDs.
- No-selection export now includes the workspace working-date range filters in addition to search/status.
- Workspace passes search, status, working-date range and selected IDs to Shared Import/Export.
- Selection permission was decoupled from delete permission while delete controls remain delete-only.
- Input boundaries/focus treatment, table colspans and numbered pagination were normalized.

### Focused regression coverage

`tests/Feature/Pharma/PharmaImportExportTest.php` now covers:

- Medicine selected IDs overriding ordinary filters;
- Drug Bid Award partial investor/company + source filter parity;
- Drug Bid Award selected export precedence;
- Supplier Tracking working-date/status filtering;
- Supplier Tracking selected export precedence.

No Shared source file was changed; the existing Shared panel already supports reactive filter payloads and selected-ID messaging.

## Current diff against main

Current branch is ahead of `main` by the corrective commits and changes only Pharma source/tests/docs:

- `Modules/Pharma/Services/MedicineImportExport.php`
- `Modules/Pharma/Services/DrugBidAwardImportExport.php`
- `Modules/Pharma/Services/ImportExport.php`
- `Modules/Pharma/resources/views/livewire/medicine/index.blade.php`
- `Modules/Pharma/resources/views/livewire/drug-bid-award/index.blade.php`
- `Modules/Pharma/resources/views/livewire/supplier-trackings/index.blade.php`
- `tests/Feature/Pharma/PharmaImportExportTest.php`
- `docs/modules/Pharma/MODULE.md`
- `docs/modules/Pharma/COLLABORATION_HANDOFF.md`

## Verification gate before PR

Run only the approved focused scope; do not run the full project suite.

```bash
vendor/bin/pint --dirty
php artisan test tests/Feature/Pharma Modules/Pharma/Tests
php artisan route:list --path=admin/pharma
npm run build
```

Manual Admin UI acceptance must cover Medicine, Drug Bid Award and Supplier Tracking on desktop and a narrow/mobile viewport, with special attention to:

- clearly visible input borders/focus state;
- numbered pagination, previous/next disabled states and page navigation;
- edit/export user without delete permission can still select rows;
- selected rows -> export only selected rows;
- no selected rows -> export all rows matching the current filters across pages;
- delete remains page-scoped and permission-gated.

Do not create the final PR until focused tests/build and manual UI acceptance have passed or any resulting defects have been corrected on this same branch.

## Accepted architecture retained

The previous Pharma Major Refactor remains the architecture baseline:

- PR #88 — Security Foundation + Shared Import/Export Hardening;
- PR #89 — Pharma Admin Dashboard;
- PR #90 — Medicine/HSSP Workspace;
- PR #91 — Drug Bid Award Workspace + sync-ready source identity;
- PR #92 — Supplier Tracking integrity/workspace;
- PR #93 — PriceList security/pipeline.

Pharma Admin routes remain behind `web` + `auth:admin`; capability checks remain `view_pharma`, `create_pharma`, `edit_pharma`, `delete_pharma`. Pharma exposes no public API contract. Production list workspaces retain bounded `10/25/50/100` pagination. PriceList and previously accepted domain boundaries are unchanged.

## Intentional deferred scope / non-goals

Still outside this corrective refactor:

- Muasamcong -> Pharma production synchronization;
- automated fuzzy Medicine matching;
- production/runtime enablement of Pharma;
- PriceList database entity/table or queue redesign;
- user replacement/upload of the PriceList source workbook;
- changing Medicine -> Supplier Tracking cascade-delete behavior;
- unrelated project-wide refactoring or full regression.

The tracked Pharma manifest remains disabled. Any future work outside the approved corrective scope requires a new objective and risk review.