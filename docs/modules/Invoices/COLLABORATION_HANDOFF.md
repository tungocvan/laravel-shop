# Invoices Collaboration Handoff

## Current Status

- Module: `Invoices`
- Mode: **Major / Clean Module Refactor**
- Contract bootstrap PR: `#156` — **MERGED**
- Runtime cleanup PR: `#157` — **MERGED**
- Runtime cleanup merge checkpoint: `main@3334d773dea6a7c2ee0b475b53a7617ad9ffb56e`
- Refactor status: **COMPLETE — MERGED TO MAIN**
- Current follow-up: **GDT sync existence preflight + Google Drive backup — IMPLEMENTED ON FEATURE BRANCH / PENDING VALIDATION**

PR #156 established `docs/modules/Invoices/MODULE.md` and aligned the module manifest with the three canonical persistence tables. PR #157 completed the approved runtime cleanup without schema changes, route renames or ClientPortal/PWA presentation changes.

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

## Completed Runtime Cleanup

### Dead placeholder removal

Caller/reachability review found no canonical routes, pages, service-provider registration or test callers for the empty `InvoiceList` / `InvoiceManager` Livewire placeholders, so they and their empty Blade views were removed. No canonical or compatibility route was removed.

### Invoice list workspace boundary

`HoadonList` remains the Livewire presentation/controller boundary and keeps the public state/actions used by Blade. `InvoiceWorkspaceService` now owns cohesive list-workspace read/orchestration concerns including paginator/view data assembly, current-page IDs, all-filtered IDs and selected-vs-filtered export record resolution.

### Export contract

The required export behavior is preserved and regression-covered:

- non-empty checkbox selection exports exactly selected invoice IDs;
- empty selection exports the complete current approved filtered scope;
- empty selection never silently exports only the current paginator page.

### PDF status/filter contract

Canonical query semantics use `invoice_files.status` for `available / missing / error`. Active PDF filters reconcile metadata against physical storage so legacy/stale metadata cannot leave a physically available PDF inside **Chưa có PDF** results. `statusForInvoice()` treats an existing readable PDF as `available` before evaluating provider-resolution capability.

The previously reported **Chưa có PDF** defect was re-tested after correction and accepted as **UI PASS**.

### PDF failure boundary

Provider exceptions from GDT/MeInvoice remain available server-side for diagnostics but are no longer propagated verbatim through list UI/batch output. User-facing failures are sanitized while provider fallback behavior is preserved.

### Admin UI normalization

The invoice list filter workspace now follows the Admin UI contract with visible bordered controls, consistent control height/focus state, explicit labels, responsive filter grid, bounded `10 / 25 / 50 / 100` page sizes and the existing explicit module pagination partial.

Selection/destructive contracts remain intact:

- header checkbox selects the current page only;
- all-filtered selection is an explicit separate action;
- destructive PDF deletion remains confirmation-gated.

User acceptance for the corrected runtime UI: **PASS**.

## GDT Sync Existence Preflight + Google Drive Follow-up

Scope: `/admin/invoices/hoadon` GDT synchronization only.

Before calling GDT, the queue job now derives the deterministic workbook name for the selected date range and direction, then applies this order:

```text
1. Check local Excel file.
2. If local is missing and Google Drive is connected, check Laravel-Backup/Invoices.
3. Call GDT only when the workbook is absent from both locations.
4. After a new GDT export is created, upload it to Laravel-Backup/Invoices when Drive is connected.
```

Behavior contract:

- local workbook exists: do not call GDT again; when Drive is connected and the backup is missing, back up the existing local workbook;
- local workbook missing + same workbook exists in `Laravel-Backup/Invoices`: do not call GDT, avoiding a duplicate synchronization;
- local workbook missing + Drive disconnected: call GDT because there is no connected remote backup source to verify;
- local workbook missing + Drive connected + workbook absent remotely: call GDT, then upload the generated workbook;
- Drive connected but remote existence verification fails: do not call GDT because absence has not been proven; fail the preflight safely to avoid duplicate synchronization;
- same workbook name already present during upload: update its Drive content instead of creating another duplicate;
- Drive upload failure after a successful new GDT export remains non-fatal to the local export;
- the existing manual public-link Google Drive import workflow is unchanged.

The implementation reuses `Modules\System\Services\Cloud\GoogleDriveConnectionService` for OAuth status, connection verification and access-token refresh. Invoices owns only deterministic export naming and the invoice-specific folder/file adapter.

Feature branch: `feat/invoices-auto-upload-google-drive`.

## Validation Result

User-reported validation before PR #157 merge:

```text
Pint changed PHP files                         PASS
InvoicesFilterSortTest                         PASS — 4 tests, 11 assertions
InvoicesWorkspaceServiceTest                   PASS — 3 tests, 4 assertions
Admin Invoices route inspection                PASS — 8 routes
Frontend production build                      PASS
Invoice filter/input UI acceptance             PASS
PDF "Chưa có PDF" functional UI check          PASS
Working tree                                   CLEAN
```

Current follow-up validation report:

```text
Invoices suite                                 29 PASS, 1 FAIL
Failing test                                   invoice export honors filters and selected ids
Failure                                        generated public export path no longer existed when test reader opened it
Admin Invoices route inspection                PASS — 8 routes
```

The reported failing test exercises the general invoice-list export path under `storage/app/public/exports`; it is separate from the GDT workbook storage path and the Google Drive preflight implementation. It remains to be investigated before merge rather than being ignored.

No full-project regression is required; validation remains scoped to Invoices plus directly relevant route/build/UI behavior.

## Compatibility / Non-Goals Preserved

The completed refactor did not:

- rename or merge migrations;
- rename persistence tables;
- add a database unique constraint;
- remove legacy `/invoices/*` compatibility aliases;
- rename canonical `/admin/invoices/*` routes;
- rename permissions;
- integrate Invoices into ClientPortal/PWA;
- expose protected invoice PDFs through public URLs.

ClientPortal/PWA integration remains **DEFERRED** and must use ClientPortal-owned routes/auth/navigation/presentation plus the approved authenticated external-file handoff pattern.

## Deferred Existing Debt

Still deferred unless separately approved:

- runtime GDT `.env` mutation;
- broad/mixed synchronization workspace responsibilities;
- unbounded or high-volume import/export/ZIP/backup paths requiring separate performance work;
- public export storage for financial spreadsheets;
- public-link Google Drive flow beyond the existing manual import boundary;
- lack of a persisted global GDT job registry;
- database uniqueness for invoice business identity pending duplicate/business-key proof;
- ClientPortal/PWA presentation and protected PDF handoff implementation.

## Closeout

1. Contract bootstrap PR #156: **COMPLETE / MERGED**.
2. Runtime cleanup PR #157: **COMPLETE / MERGED**.
3. Focused automated validation: **PASS** for prior runtime cleanup.
4. Route/build validation: **PASS** for prior runtime cleanup.
5. UI/PDF-filter acceptance: **PASS**.
6. Canonical runtime merge checkpoint: `3334d773dea6a7c2ee0b475b53a7617ad9ffb56e`.
7. Handoff closeout: **COMPLETE ON MAIN** for the major refactor.
8. Invoices Major/Clean Module Refactor: **CLOSED**.
9. GDT existence preflight + Google Drive follow-up: **IMPLEMENTED / PENDING FOCUSED TEST FIX AND UI VALIDATION**.
