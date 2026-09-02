# Invoices Collaboration Handoff

## Current Status

- Module: `Invoices`
- Mode: **Major / Clean Module Refactor**
- Current branch: `refactor/invoices-runtime-cleanup`
- Base checkpoint: `main@71cae9dc8b3ed99395ad15a7178c98436b1f95a5`
- Contract bootstrap PR: `#156` — **MERGED**
- Runtime cleanup status: **IMPLEMENTED — PENDING LOCAL VALIDATION / UI ACCEPTANCE**
- Merge status: **NOT YET MERGED**

PR #156 established `docs/modules/Invoices/MODULE.md` and aligned the module manifest with the three canonical persistence tables. The current branch continues the approved runtime cleanup without schema changes, route renames or ClientPortal/PWA presentation changes.

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

Canonical filter semantics now use `invoice_files.status` consistently:

- `available` -> metadata status `available`;
- `error` -> metadata status `error`;
- `missing` -> metadata status `missing` or no `invoice_files` metadata row.

This fixes the reported UI case where selecting **Chưa có PDF** could still show rows represented as **Đã có PDF** by a different status interpretation.

Physical storage reconciliation remains the responsibility of the existing metadata scan/reconcile action.

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

No aesthetic-only dashboard redesign was introduced.

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

## Validation Gate

The source changes and tests are committed, but no GitHub CI workflow is attached to the current head and local runtime validation has not yet been reported by the user.

Current gate:

```text
Pint changed PHP files                         PENDING
Focused Invoices tests                         PENDING
Directly impacted Admin regression             PENDING
Route inspection                               PENDING
Frontend production build                      PENDING
Desktop UI acceptance                          PENDING
Mobile/responsive UI acceptance                PENDING
PDF "Chưa có PDF" functional UI check          PENDING
Working tree clean                             PENDING
```

Do not mark this refactor complete or merge the runtime cleanup PR until the relevant validation and UI acceptance are reported PASS.

Recommended local validation scope remains limited to Invoices and directly impacted Admin behavior; full-project regression is not required unless scope expands.

## Manual UI Acceptance Focus

On `/admin/invoices/hoadon-list`, verify at minimum:

1. all filter/select/date controls have consistent visible borders and focus states;
2. desktop/tablet/mobile filter layout remains readable and does not overflow;
3. selecting **Chưa có PDF** shows no row labeled **Đã có PDF**;
4. selecting **Đã có PDF** shows only available PDF rows;
5. selecting **Lỗi tải PDF** shows only error rows;
6. page-size options remain bounded to `10 / 25 / 50 / 100`;
7. header checkbox selects the current page only;
8. explicit all-filtered selection still works;
9. export with selection exports selected invoices;
10. export without selection exports the complete current filtered scope;
11. destructive PDF deletion still requires confirmation.

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
2. Runtime cleanup implementation: **COMPLETE, awaiting validation**.
3. Focused automated validation: **PENDING USER/LOCAL RESULT**.
4. Desktop/mobile UI acceptance: **PENDING USER RESULT**.
5. Runtime cleanup PR review: **PENDING**.
6. Manual merge to `main`: **PENDING**.
7. Post-merge handoff closeout / clean-main verification: **PENDING**.
