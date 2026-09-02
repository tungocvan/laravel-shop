# Invoices Collaboration Handoff

## Current Status

- Module: `Invoices`
- Mode: **Major / Clean Module Refactor**
- Current branch: `refactor/invoices-runtime-cleanup`
- Base checkpoint: `main@71cae9dc8b3ed99395ad15a7178c98436b1f95a5`
- Contract bootstrap PR: `#156` — **MERGED**
- Runtime cleanup PR: `#157`
- Runtime cleanup status: **VALIDATED — READY FOR REVIEW / MANUAL MERGE**
- Merge status: **NOT YET MERGED**

PR #156 established `docs/modules/Invoices/MODULE.md` and aligned the module manifest with the three canonical persistence tables. PR #157 completes the approved runtime cleanup without schema changes, route renames or ClientPortal/PWA presentation changes.

## Canonical Ownership Contract

Invoices remains the canonical domain owner for:

- electronic invoice ingestion and local persistence;
- GDT authentication/data synchronization boundaries;
- invoice filtering/listing/reporting;
- Excel import/export;
- invoice PDF retrieval and file metadata;
- invoice backup execution metadata.

Canonical persistence ownership:

```text
invoices
invoice_files
invoice_backup_runs
```

Invoices does **not** own Admin authentication/shell or ClientPortal authentication/navigation/PWA presentation.

## Runtime Cleanup Implemented

### Dead placeholder removal

Caller/reachability review found no canonical routes, pages, service-provider registration or test callers for these empty Livewire placeholders. They were removed:

```text
Modules/Invoices/Livewire/InvoiceList.php
Modules/Invoices/Livewire/InvoiceManager.php
Modules/Invoices/resources/views/livewire/invoice-list.blade.php
Modules/Invoices/resources/views/livewire/invoice-manager.blade.php
```

No canonical or compatibility route was removed.

### Invoice list workspace boundary

`HoadonList` remains the Livewire presentation/controller boundary and keeps its existing public state/actions used by Blade.

A cohesive `InvoiceWorkspaceService` now owns list-workspace read/orchestration concerns including:

- paginator/view data assembly;
- current-page invoice IDs;
- all IDs in the current filtered scope;
- selected-vs-filtered export record resolution.

This reduces direct query/orchestration responsibility in `HoadonList` without introducing multiple tiny services.

### Export contract

The required export behavior is preserved and regression-covered:

- when checkbox selection is non-empty, export exactly the selected invoice IDs;
- when selection is empty, export the complete current approved filtered scope;
- empty selection must never silently export only the current paginator page.

### PDF status/filter contract

Canonical query semantics use `invoice_files.status`:

- `available` -> metadata status `available`;
- `error` -> metadata status `error`;
- `missing` -> metadata status `missing` or no `invoice_files` metadata row.

The UI additionally reconciles active PDF filters against physical storage before rendering/filtering so legacy or stale metadata cannot leave a physically available PDF inside **Chưa có PDF** results. `statusForInvoice()` treats a physically existing readable PDF as `available` before evaluating provider-resolution capability.

The user re-tested the previously failing **Chưa có PDF** case after this correction and reported **UI PASS**.

### PDF failure boundary

Provider exceptions from GDT/MeInvoice are retained server-side for diagnostics but are no longer propagated verbatim through the list UI/batch result contract. User-facing failures are generic/sanitized while provider fallback behavior is preserved.

### Admin UI normalization

The invoice list filter workspace was normalized to the Admin UI contract:

- explicit visible borders on ordinary controls;
- consistent `h-11` control height;
- consistent white background, gray border and indigo focus state;
- explicit labels for invoice type, partner, tax code, PDF status, year, month, tax rate, sort and date range;
- responsive 4-column desktop / 2-column tablet / stacked mobile grid;
- bounded `10 / 25 / 50 / 100` page-size options;
- explicit module pagination partial retained;
- header checkbox remains current-page selection only;
- separate action remains available for selecting the complete filtered scope;
- destructive PDF deletion remains confirmation-gated.

User acceptance for the corrected runtime UI: **PASS**.

## Compatibility / Non-Goals

This runtime cleanup deliberately does not:

- rename or merge migrations;
- rename persistence tables;
- add a database unique constraint;
- remove legacy `/invoices/*` compatibility aliases;
- change canonical `/admin/invoices/*` route names;
- change permission names;
- integrate Invoices into ClientPortal/PWA;
- expose protected invoice PDFs through public URLs.

ClientPortal/PWA integration remains **DEFERRED** and must use ClientPortal-owned routes/auth/navigation/presentation plus the approved authenticated external-file handoff pattern.

## Tests Added / Strengthened

Source coverage added for:

- selected IDs taking precedence over filtered export scope;
- empty selection exporting the complete filtered scope;
- all-filtered selection not being limited to the current page;
- canonical PDF `available / missing / error` filtering;
- missing PDF including invoices with explicit `missing` metadata and invoices without metadata;
- available/error invoices being excluded from the missing filter.

## Validation Result

User-reported local validation for PR #157:

```text
Pint changed PHP files                         PASS
InvoicesFilterSortTest                         PASS — 4 tests, 11 assertions
InvoicesWorkspaceServiceTest                   PASS — 3 tests, 4 assertions
Admin Invoices route inspection                PASS — 8 routes
Frontend production build                      PASS
Invoice filter/input UI acceptance             PASS
PDF "Chưa có PDF" functional UI check          PASS
Working tree before final fix pull             CLEAN
```

No full-project regression was run or required. No additional Admin module runtime source was changed by this refactor, so validation remained scoped to Invoices plus route/build/UI behavior.

## Manual UI Acceptance Covered

On `/admin/invoices/hoadon-list`, acceptance covered the corrected filter/input layout and the reported PDF status defect. The critical requirement is now satisfied: selecting **Chưa có PDF** no longer leaves physically available PDF rows displayed as **Đã có PDF** inside that result set.

The existing contracts remain unchanged:

1. page-size options are bounded to `10 / 25 / 50 / 100`;
2. header checkbox means current page only;
3. explicit all-filtered selection remains separate;
4. export with selection exports selected invoices;
5. export without selection exports the complete filtered scope;
6. destructive PDF deletion remains confirmation-gated.

## Deferred Existing Debt

Still deferred unless separately approved:

- runtime GDT `.env` mutation;
- broad/mixed synchronization workspace responsibilities;
- unbounded or high-volume import/export/ZIP/backup paths requiring separate performance work;
- public export storage for financial spreadsheets;
- public-link Google Drive flow;
- lack of a persisted global GDT job registry;
- database uniqueness for invoice business identity pending duplicate/business-key proof;
- ClientPortal/PWA presentation and protected PDF handoff implementation.

## Merge Gate

1. Contract bootstrap PR #156: **COMPLETE / MERGED**.
2. Runtime cleanup implementation: **COMPLETE**.
3. Focused automated validation: **PASS**.
4. Route/build validation: **PASS**.
5. UI/PDF-filter acceptance: **PASS**.
6. PR #157 manual review: **READY**.
7. Manual merge to `main`: **PENDING USER ACTION**.
8. Post-merge handoff closeout / clean-main verification: **PENDING**.
