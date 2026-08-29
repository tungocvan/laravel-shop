# Invoices Module Information

## Purpose

`Modules/Invoices` owns electronic-invoice integration, local sold/purchase invoice data, PDF lifecycle, import/export, partner reporting and automated PDF backup.

It is a `domain` module registered through `Modules/ModuleServiceProvider.php` and `Modules/Invoices/config/module.php`.

## Admin Workspaces

| Route name | URI | Capability | Purpose |
|---|---|---|---|
| `admin.invoices.dashboard` | `/admin/invoices/dashboard` | `invoices-list` | Read-only safe overview and workspace navigation |
| `admin.invoices.index` | `/admin/invoices` | `invoices-list` | Preserved redirect to the invoice list |
| `admin.invoices.create-token` | `/admin/invoices/create-token` | `invoices-configure` | GDT configuration/authentication |
| `admin.invoices.hoadon` | `/admin/invoices/hoadon` | `invoices-create` | GDT synchronization, import and backup workspace |
| `admin.invoices.hoadon-list` | `/admin/invoices/hoadon-list` | `invoices-list` | Filtered invoice/PDF workspace |
| `admin.invoices.reports.partners` | `/admin/invoices/reports/partners` | `invoices-list` | Partner aggregation report |
| `admin.invoices.download-invoice` | `/admin/invoices/download-invoice/{invoice}` | `invoices-download` | Structured PDF download |
| `admin.invoices.download` | `/admin/invoices/download/{lookup_code}` | `invoices-download` | Legacy lookup-code PDF download |

All Admin routes use `web` and `auth:admin`. Legacy `/invoices/*` aliases are retained for bookmark compatibility.

The four linked workspaces expose a shared permission-aware `Quay về Dashboard` link.

## API

The module exposes Sanctum-protected API endpoints under `/api/invoices`:

- `GET /api/invoices/status`
- `POST /api/invoices/filter`

The filter endpoint validates range, sorting and pagination input and caps `per_page` at 200. The Admin Dashboard does not change the API contract.

## Permissions

```text
invoices-list
invoices-create
invoices-export
invoices-download
invoices-configure
```

The Dashboard reuses `invoices-list` for route access and gates optional navigation/status sections with the existing capability permissions. It adds no new permission.

## Dashboard Architecture

```text
InvoicesDashboardController
    -> InvoiceDashboardService
        -> InvoiceDashboardData
            -> Invoices::pages.invoices.dashboard
```

The service returns an immutable bounded DTO. It guards missing tables, uses explicit selection, limits recent lists to five, makes no external HTTP calls and never sends models or raw exceptions to Blade.

Dashboard output is restricted to counts, direction/status allowlists, timestamps and boolean integration state. Financial amounts, invoice/partner identity, contact data, credentials, tokens, backup recipient, file metadata, raw payloads and error messages are excluded.

`InvoiceService::dashboard()` remains part of the existing invoice-list workflow and is not used by the Admin Dashboard because it includes financial and partner-derived summaries.

## Models and Owned Tables

### `Invoices`

Table `invoices` contains invoice identity, partner/contact data, financial fields and `invoice_type` (`sold` or `purchase`). It has one optional `InvoiceFile`.

Indexes:

```text
(invoice_type, issued_date)
tax_code
invoice_number
```

No database unique constraint currently enforces the application-level import identity.

### `InvoiceFile`

Table `invoice_files` has one row per invoice and stores provider, PDF status, private relative path, size, last error and download timestamp.

### `InvoiceBackupRun`

Table `invoice_backup_runs` stores mode/status, recipient, aggregate counts, file fingerprints, raw run message and timestamps. Recipient, file list and message are sensitive operational fields and are never loaded into the Dashboard DTO.

## Livewire Workspaces

- `GdtLogin`: GDT configuration, captcha, login and server-side token lifecycle.
- `GdtInvoice`: quick sold/purchase lookup.
- `SearchHoadon`: direct/queued synchronization, intermediate Excel management and import.
- `AutomaticBackupPanel`: automatic backup configuration and run history.
- `HoadonList`: bounded pagination, filters, invoice statistics, PDF management, ZIP and export.
- `PartnerReport`: bounded partner aggregation and export.
- `InvoiceList` and `InvoiceManager`: inactive placeholders retained for later cleanup review.

## Services and Integrations

### Query and reporting

- `InvoiceService`
- `InvoicePartnerReportService`

### Import/export

- `InvoiceImportExportService`
- `InvoiceImportService`
- `InvoiceExportService`
- canonical Shared base: `Modules/Shared/Services/ImportExport/BaseImportExportService`

Invoice import accepts XLSX/CSV mappings, normalizes Vietnamese headers/numbers/dates and uses a cache lock plus skip-duplicate behavior. Selected export sanitizes positive unique IDs. Large exports remain an existing boundedness/private-storage follow-up.

### External systems

- `GdtApiService`, `GdtConfigService`, `GdtInvoiceService`, `GdtPdfService`
- `MeInvoiceService`

GDT token stays in server cache. The Dashboard checks only a boolean cache/config state and never calls GDT or MeInvoice.

### PDF and backup

- `InvoiceFileService`
- `InvoiceFileManagerService`
- `InvoicePdfService`
- `ScanInvoiceService`
- `AutomaticInvoiceBackupService`
- `InvoiceFilesEmailBackupService`
- `InvoiceBackupEnvironmentService`

## Jobs, Commands and Scheduler

Job:

```text
ProcessGdtInvoicesJob
```

Commands include GDT retrieval/import and `invoices:backup-files`.

When automatic backup is enabled, the provider schedules the command monthly on a clamped day 1–28, uses the configured time, `withoutOverlapping(120)` and `onOneServer()`.

Current GDT job progress is stored under a per-run cache key and cannot be enumerated safely as a global queue. The Dashboard therefore directs users to the sync workspace instead of inventing a global processing state.

## Storage and Sensitive Data

Structured PDFs are resolved from private application storage, with a legacy `hoadon_temp` fallback. The Dashboard never returns file paths or names.

Credentials, cached tokens, captcha content, Authorization headers, invoice financial fields, partner identity/contact data, backup recipient, file fingerprint lists and raw errors must not be placed in Dashboard DTO/HTML/log context.

## ClientPortal / PWA Boundary

Invoices is expected to become a ClientPortal PWA application later. The Admin Dashboard does not register or modify ClientPortal.

Future client integration must be manifest/registry-driven, use ClientPortal authentication/permissions/adaptive navigation, provide a client-specific presentation and keep Admin routes/Blade separate. If PDF download/open is introduced to the PWA, it requires a separate authorized external-file handoff design.

## Tests

Dashboard coverage:

```text
tests/Feature/InvoicesDashboardTest.php
```

Existing Invoices regression:

```text
tests/Feature/InvoicesModuleTest.php
tests/Feature/InvoicesFilterSortTest.php
tests/Feature/InvoicesPdfStorageTest.php
tests/Feature/InvoicesFileManagementTest.php
tests/Feature/InvoicesPartnerReportTest.php
tests/Feature/InvoicesAutomaticBackupTest.php
```

Run focused/module tests and the impacted `tests/Feature/Admin` suite. Do not run full-project regression for an Invoices-only change.

## Known Direct Debt

- Runtime GDT `.env` mutation needs a production-grade settings/secrets replacement.
- Some sync workspace actions share a broad route capability and need deeper action-level review.
- Existing export/ZIP/filter option/storage breakdown/backup fingerprint paths can become unbounded.
- Current Shared export output uses public storage for potentially sensitive spreadsheets.
- The list can perform per-row PDF status lookups.
- Public-link Google Drive ingestion is not the canonical private System OAuth flow.
- The module manifest lists only `invoices`, although the module also owns `invoice_files` and `invoice_backup_runs`.
- The import business identity has no database unique constraint.
