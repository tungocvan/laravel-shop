# Invoices Collaboration Handoff

## Current Status

- Module: `Invoices`
- Mode: **Major / Clean Module Refactor**
- Contract bootstrap PR: `#156` — **MERGED**
- Runtime cleanup PR: `#157` — **MERGED**
- Runtime cleanup merge checkpoint: `main@3334d773dea6a7c2ee0b475b53a7617ad9ffb56e`
- Refactor status: **COMPLETE — MERGED TO MAIN**

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

No full-project regression was required; validation remained scoped to Invoices plus directly relevant route/build/UI behavior.

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
- public-link Google Drive flow;
- lack of a persisted global GDT job registry;
- database uniqueness for invoice business identity pending duplicate/business-key proof;
- ClientPortal/PWA presentation and protected PDF handoff implementation.

## Closeout

1. Contract bootstrap PR #156: **COMPLETE / MERGED**.
2. Runtime cleanup PR #157: **COMPLETE / MERGED**.
3. Focused automated validation: **PASS**.
4. Route/build validation: **PASS**.
5. UI/PDF-filter acceptance: **PASS**.
6. Canonical runtime merge checkpoint: `3334d773dea6a7c2ee0b475b53a7617ad9ffb56e`.
7. Handoff closeout: **COMPLETE ON MAIN**.
8. Invoices Major/Clean Module Refactor: **CLOSED**.
