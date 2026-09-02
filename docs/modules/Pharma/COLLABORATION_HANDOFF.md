# Pharma Collaboration Handoff

## Current checkpoint

- Module: `Pharma`
- Mode: **Refactor Module — corrective contract/UI/export alignment**
- Status: **COMPLETE — MERGED TO MAIN**
- Date: 2026-09-02
- Delivery PR: **#150 — Refactor Pharma contract, UI, and export alignment**
- Merge commit: `7c7691c671a73ede6725418e8e15d648a4ff3f90`
- Consolidation rule: **single branch / single PR — satisfied**
- Schema/database migration change: **NO**
- Route change: **NO**
- Authorization contract change: **NO**
- Cross-module source change: **NO**

## Outcome

The corrective Pharma refactor is complete and merged into `main`.

The accepted architecture from the prior Pharma Major Refactor was preserved. This corrective pass addressed contract drift, Admin UI consistency, pagination, selection permissions and export semantics without reopening schema, routing or authorization boundaries.

The explicit export rule now enforced across Medicine/HSSP, Drug Bid Awards and Supplier Tracking is:

- selected checkboxes non-empty -> export exactly the selected records;
- no selection -> export the complete current filtered dataset, not only the visible page;
- selected IDs take precedence over ordinary list filters for determining the exported record set;
- export selection is available to edit/export-capable users and is no longer coupled to delete permission;
- destructive selection remains page-scoped and delete-permission-gated.

## Delivered changes

### Durable module contract

`docs/modules/Pharma/MODULE.md` is now the durable Pharma module contract. It records canonical ownership, dependency on `Shared`, persistence/auth boundaries, bounded pagination, Admin input conventions, page-scoped destructive selection and selected/all export semantics.

### Medicine / HSSP

- `MedicineImportExport` honors normalized `selected_ids`.
- No-selection export returns the complete filtered dataset.
- Workspace passes selected IDs to Shared Import/Export.
- Selection checkboxes are available to edit/export-capable users as well as delete-capable users.
- Destructive actions remain delete-only.
- Input/select treatment is aligned with the Admin UI standard.
- Pagination is numbered with previous/next controls and `aria-current`.

### Drug Bid Awards

- `DrugBidAwardImportExport` honors selected IDs.
- No-selection export mirrors workspace semantics for search, partial investor/company filters and source filtering.
- Workspace passes search, investor, company, source and selected IDs to Shared Import/Export.
- Selection permission is decoupled from delete permission.
- Inputs and pagination are aligned with the Admin UI standard.

### Supplier Tracking

- Supplier export honors selected IDs.
- No-selection export includes search, status and working-date range filters.
- Workspace passes search, status, working-date range and selected IDs to Shared Import/Export.
- Selection permission is decoupled from delete permission while delete remains separately protected.
- Input boundaries/focus treatment, table colspans and numbered pagination are normalized.

### Focused regression coverage

`tests/Feature/Pharma/PharmaImportExportTest.php` covers:

- Medicine selected IDs overriding ordinary filters;
- Drug Bid Award partial investor/company + source filter parity;
- Drug Bid Award selected export precedence;
- Supplier Tracking working-date/status filtering;
- Supplier Tracking selected export precedence.

No Shared source file was changed; the existing Shared import/export panel already supported reactive filter payloads and selected-ID messaging.

## Final verification evidence

Verification was executed on the corrective branch immediately before PR creation and merge.

```bash
vendor/bin/pint --dirty
```

Result: **PASS — 0 dirty PHP files required formatting changes**.

```bash
php artisan test tests/Feature/Pharma Modules/Pharma/Tests
```

Result: **PASS — 44 tests, 245 assertions** in 2.11s.

```bash
php artisan route:list --path=admin/pharma
```

Result: **PASS — 11 Pharma Admin routes**. No route surface changed.

```bash
npm run build
```

Result: **PASS — Vite production build, 34 modules transformed** in 1.60s.

Manual UI acceptance: **PASS** for Medicine/HSSP, Drug Bid Awards and Supplier Tracking, including input visibility/focus, numbered pagination and selected/all export behavior.

No full-project suite was run; verification remained intentionally scoped to Pharma and directly impacted behavior.

## Accepted architecture retained

The earlier Pharma Major Refactor remains the architecture baseline:

- PR #88 — Security Foundation + Shared Import/Export Hardening;
- PR #89 — Pharma Admin Dashboard;
- PR #90 — Medicine/HSSP Workspace;
- PR #91 — Drug Bid Award Workspace + sync-ready source identity;
- PR #92 — Supplier Tracking integrity/workspace;
- PR #93 — PriceList security/pipeline.

Pharma Admin routes remain behind `web` + `auth:admin`; capabilities remain `view_pharma`, `create_pharma`, `edit_pharma`, `delete_pharma`. Pharma exposes no public API contract. Production list workspaces retain bounded `10/25/50/100` pagination. PriceList and previously accepted domain boundaries are unchanged.

## Intentional deferred scope / non-goals

The following remain outside this completed corrective refactor and are not blockers:

- Muasamcong -> Pharma production synchronization;
- automated fuzzy Medicine matching;
- production/runtime enablement of Pharma;
- PriceList database entity/table or queue redesign;
- user replacement/upload of the PriceList source workbook;
- changing Medicine -> Supplier Tracking cascade-delete behavior;
- unrelated project-wide refactoring or full regression.

The tracked Pharma manifest remains disabled.

## Closeout decision

**Pharma corrective Refactor Module work is complete.**

No automatically authorized implementation work remains from this handoff. Any future Pharma change should begin from a new concrete objective and scope review.