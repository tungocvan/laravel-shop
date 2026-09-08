# Invoices Collaboration Handoff

## Current Status

- Module: `Invoices`
- Major/Clean Module Refactor: **COMPLETE / MERGED** through PR #157.
- Current follow-up branch: `feat/invoices-auto-upload-google-drive`.
- Current PR: **#171 — OPEN / NOT MERGED**.
- Current follow-up scope: **GDT existence preflight + manual Local ↔ Google Drive transfer + explicit database sync + Partner candidate staging**.

## Canonical Ownership Contract

Invoices remains the canonical domain owner for:

- electronic invoice ingestion and local persistence;
- GDT authentication/data synchronization boundaries;
- invoice filtering/listing/reporting;
- Excel import/export;
- invoice PDF retrieval and file metadata;
- invoice backup execution metadata.

Canonical Invoices persistence ownership remains:

```text
invoices
invoice_files
invoice_backup_runs
```

Partner master data is not owned by Invoices. Invoice import may detect counterparties and submit normalized candidates to the Partner-owned intake boundary, but it does not create or update `partners` directly.

## GDT Existence Preflight

The queued GDT job derives the deterministic workbook name for the selected range/direction and applies this order before calling GDT:

```text
1. Check the expected local workbook.
2. If local is missing and Google Drive is connected, check Laravel-Backup/Invoices.
3. Call GDT only when the workbook is absent from both locations.
```

Behavior:

- local workbook exists -> skip GDT;
- local missing + same workbook exists on Drive -> skip GDT and allow operator to restore it through the manual Drive panel;
- local missing + Drive disconnected -> call GDT;
- local missing + Drive connected + remote absent -> call GDT;
- Drive is connected but existence verification fails -> fail safely without calling GDT, because absence was not proven;
- after a new GDT export, the workbook remains local; it is not automatically uploaded to Drive.

Important runtime boundary: the above existence preflight currently belongs to the queued `ProcessGdtInvoicesJob`. The legacy non-queue `SearchHoadon::run()` path still calls `GdtInvoiceService::processRange()` directly and therefore does not yet share the queue preflight orchestration.

## Manual Local ↔ Google Drive

`/admin/invoices/hoadon` now includes an explicit operator-controlled Local ↔ Google Drive panel.

Contract:

- list local `vat_in_*.xlsx` / `vat_out_*.xlsx` files;
- list invoice workbooks under `Laravel-Backup/Invoices` when Drive is connected;
- selected Local -> Drive upload;
- same-name Drive workbook is updated instead of duplicated;
- selected Drive -> Local restore;
- Drive -> Local never overwrites an existing same-name local workbook;
- connection state and refresh/error states are visible;
- existing public-link Google Drive import remains available as a compatibility path.

User acceptance for the manual Local ↔ Drive UI was reported **PASS** before the Partner candidate follow-up was added.

## Explicit Database Sync

The local workbook action on `/admin/invoices/hoadon` is presented as **Đồng bộ vào CSDL**.

The existing `InvoiceImportService` / `InvoiceImportExportService` remains the invoice import engine:

- duplicate business identities are skipped rather than overwritten;
- import reports total/success/skipped/error information;
- the UI action does not itself mutate Partner master data.

The invoice list route `/admin/invoices/hoadon-list` remains the workspace for records already persisted in the Invoices database.

## Partner Candidate Staging

After invoice database import completes, `InvoiceImportService` performs a secondary best-effort candidate stage:

```text
Invoice workbook
    -> InvoiceImportExportService
    -> Invoices DB
    -> InvoicePartnerCandidateExtractor
    -> PartnerCandidateIntakeService
    -> partner_sync_candidates
```

Rules:

- candidate extraction only accepts workbook rows whose invoice business identity exists in the Invoices table after import;
- candidate identity validation is batch-oriented rather than one invoice query per workbook row;
- `sold` counterparty -> `customer`;
- `purchase` counterparty -> `supplier`;
- candidates are deduplicated by MST for the source submission;
- Invoices does not create/update `Partner`;
- candidate staging failure is logged and surfaced as a warning but does not roll back an invoice import that already succeeded.

Partner owns the staging table and review workflow. See `docs/modules/Partner/MODULE.md` and `docs/modules/Partner/COLLABORATION_HANDOFF.md`.

## Partner Review Handoff

The new Partner-owned review workspace is:

```text
/admin/partners/sync/invoices
```

It supports explicit human review for pending/matched/conflict/ignored invoice-derived candidates. Create/merge/ignore decisions happen in Partner, not Invoices.

## Validation Status

Previously user-reported acceptance within this branch:

```text
Focused Invoices automated suite               PASS
Manual Local ↔ Google Drive UI                 PASS
```

The newly added database-sync wording + Partner candidate staging/review batch still requires one final local verification round before PR #171 is merge-ready:

```text
Pint changed PHP files                         PENDING
Invoices focused tests                         PENDING after candidate additions
InvoicesPartnerCandidateSyncTest               PENDING
Partner focused tests                          PENDING
Admin Invoices/Partner route inspection        PENDING
Frontend production build                      PENDING
Invoice DB-sync UI                             PENDING
Partner invoice-candidate review UI            PENDING
```

No full-project regression is required unless a focused failure proves a wider impact.

## Compatibility / Non-Goals Preserved

This follow-up does not:

- rename Invoices persistence tables;
- rename canonical `/admin/invoices/*` routes;
- remove legacy invoice compatibility routes;
- rename existing invoice permissions;
- expose protected invoice PDFs publicly;
- integrate Invoices into ClientPortal/PWA;
- automatically create/update/merge Partner master rows during invoice ingestion;
- automatically overwrite existing Partner conflicts.

The cross-module schema addition is Partner-owned: `partner_sync_candidates`.

## Deferred Existing Debt

Still deferred unless separately approved:

- unifying the non-queue GDT path with the queued Local -> Drive -> GDT existence preflight;
- runtime GDT `.env` mutation;
- high-volume import/export/ZIP/backup performance work beyond the current bounded changes;
- public export storage for financial spreadsheets;
- persisted global GDT job registry;
- database uniqueness for invoice business identity pending separate proof;
- ClientPortal/PWA presentation and protected PDF handoff.

## Closeout Gate for PR #171

Before merge:

1. run Partner migration;
2. run Pint on changed PHP files;
3. run focused Invoices + new candidate tests + Partner focused tests;
4. inspect affected routes and run Vite build;
5. UI-check `/admin/invoices/hoadon` database sync and `/admin/partners/sync/invoices` review flow;
6. update this handoff with final PASS results;
7. merge only after normal user acceptance under `docs/GITHUB_COLLABORATION_WORKFLOW.md`.
