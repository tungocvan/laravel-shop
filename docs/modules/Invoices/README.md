# Invoices Module

## Module Overview

`Invoices` is the electronic-invoice domain module for GDT integration, sold/purchase invoice synchronization, Excel import/export, local PDF management and partner reporting.

Refactor status: **R1–R6.5 implemented on `agent/invoices-refactor`; R7 final regression/merge verification pending.**

## Registration

Canonical registration:

```text
Modules/ModuleServiceProvider.php
Modules/Invoices/config/module.php
```

Module type: `domain`.

Owned tables:

```text
invoices
invoice_files
```

Do not use legacy `module.json` as the architectural source of truth.

## Main Routes

Admin prefix:

```text
/admin/invoices
```

Important routes:

```text
admin.invoices.index              -> invoices-list
admin.invoices.create-token       -> invoices-configure
admin.invoices.hoadon             -> invoices-create
admin.invoices.hoadon-list        -> invoices-list
admin.invoices.reports.partners   -> invoices-list
admin.invoices.download-invoice   -> invoices-download
admin.invoices.download           -> invoices-download
```

Legacy `/invoices/*` aliases are retained for bookmark compatibility.

## Permissions

Declared capabilities:

```text
invoices-list
invoices-create
invoices-export
invoices-download
invoices-configure
```

Sensitive Livewire actions enforce server-side permission checks. UI actions are also hidden/disabled when the admin lacks the corresponding capability.

## Refactor Results

### R1 — Authorization / GDT hardening

- Route capabilities aligned with module permissions.
- GDT configuration/login/token actions require `invoices-configure`.
- Export requires `invoices-export`.
- PDF actions require `invoices-download`.
- GDT password is not hydrated back into public Livewire state.
- GDT config validation rejects invalid cache keys/newline injection.

Runtime `.env` mutation remains deferred for a future production secrets/settings store.

### R2 — List / pagination / admin UX

- Removed unbounded `All` pagination.
- Allowed page sizes: `10/25/50/100`.
- Searchable partner and tax-code filters use the shared select-search component.
- Year/month/date filters and safe sort whitelist added.
- Sort supports date, amount, invoice number and partner.
- Header checkbox selects the current page.
- After selecting the page, the UI can explicitly select the entire filtered result set.
- Selection resets when filter/page-size state changes.

### R3 — Import / Export

Canonical service:

```text
Modules/Invoices/Services/InvoiceImportExportService.php
```

It extends:

```text
Modules/Shared/Services/ImportExport/BaseImportExportService.php
```

Behavior:

- XLSX/CSV import.
- Vietnamese header aliases.
- `sold` / `purchase` support.
- Default `skip_duplicate` mode.
- Legacy `gdt:import-excel` compatibility through `InvoiceImportService` adapter.
- Export current filter scope or selected IDs.
- Decimal values are normalized without lossy float conversion.
- Tax-rate normalization accepts GDT Excel variations.

### R4 — Data integrity / idempotency

Current application identity remains:

```text
lookup_code + invoice_number + issued_date + tax_code
```

Import duplicate detection is date-aware and does not overwrite an existing invoice in `skip_duplicate` mode.

A database unique constraint remains deferred until identity is validated against production data.

### R5 — Query / statistics / dashboard

- Filtered statistics consolidated into aggregate queries.
- Dashboard totals/customer counts optimized.
- Deterministic ordering maintained.
- Selected IDs sanitized before query use.
- Purchase invoices without `lookup_code` remain visible/filterable.

### R6.1 — Professional filter/sort UX

- Searchable partner/MST filters.
- Year/month quick period selection.
- Amount/date/invoice/partner sorting.
- Filtered KPI row.
- Indigo pagination component consistent with Admin UI.

### R6.2 — PDF Storage Manager

PDFs created after R6.2 are stored under:

```text
storage/app/invoices/pdf/{YYYY}/{MM}/{sold|purchase}/
```

Filename pattern is human-readable and contains invoice date/number/tax code/partner slug.

Legacy files under:

```text
storage/app/hoadon_temp
```

remain readable for backward compatibility.

### R6.3 — Invoice File Management

Metadata table:

```text
invoice_files
```

Tracks:

```text
invoice_id
provider
status
path
size
last_error
downloaded_at
```

Supported management actions:

- Reconcile/scan PDF metadata.
- Download missing PDFs in bounded batches.
- Retry failed PDF downloads.
- Create ZIP archives from currently filtered PDFs.
- Storage summary by year/month/type.
- Delete PDF files only for explicitly checkbox-selected invoices.

Deleting a PDF never deletes the invoice database record.

### R6.4 — File Manager safety / UX

- PDF status filter: all / available / missing / error.
- Recent PDF error details.
- Busy modal/overlay for long-running actions.
- Selected-only destructive PDF deletion.
- Error/retry state is stored in metadata.

### R6.5 — Partner Revenue Report

Route:

```text
/admin/invoices/reports/partners
```

Main report table focuses on:

```text
Partner
Tax code
Invoice count
Sold total (VAT included)
Purchase total (VAT included)
```

Partner detail modal displays:

```text
Sold total (VAT included)
Purchase total (VAT included)
Output VAT
Input VAT
Total difference = sold total - purchase total
VAT difference   = output VAT - input VAT
```

The total difference is an accounting comparison by partner and is explicitly **not presented as profit**.

Report filters mirror the invoice-list UX, including searchable partner/MST selectors, year/month/date range and sorting. Excel export is permission-protected.

## GDT Synchronization Safety

GDT synchronization validates pagination completeness. A partial page sequence must not silently produce a successful-looking Excel file.

The sync flow compares received rows with the GDT reported total and treats incomplete pagination as a failure instead of silently exporting partial data.

`TransactionID`/lookup values are resolved by field meaning rather than relying on one fixed `cttkhac` array index.

## PDF Provider Flow

Primary flow:

```text
Invoice database row
    -> GDT invoice detail endpoint
    -> render local PDF with DomPDF
    -> store PDF
    -> record invoice_files metadata
```

GDT detail identity uses invoice data such as seller tax code, invoice symbol and invoice number; `lookup_code` is not required for purchase invoices.

MeInvoice remains an optional fallback provider when configured.

The generated GDT PDF is a **local representation rendered from GDT detail data**, not a claim that GDT supplied an original PDF binary.

## Configuration

GDT environment keys:

```text
GDT_API_BASE_URL
GDT_API_USERNAME
GDT_API_PASSWORD
GDT_API_VERIFY_SSL
GDT_API_TIMEOUT
GDT_TOKEN_TTL
GDT_TOKEN_CACHE_KEY
```

Optional MeInvoice fallback:

```text
MEINVOICE_API_TOKEN
```

Writable runtime directories include:

```text
storage/app/gdt
storage/app/invoices/pdf
storage/app/invoices/archives
storage/app/hoadon_temp   # legacy compatibility
```

Ensure the PHP/queue runtime user has write permission to `storage` and `bootstrap/cache`.

## Important Classes

```text
Modules/Invoices/Http/Controllers/InvoicesController.php
Modules/Invoices/Livewire/GdtLogin.php
Modules/Invoices/Livewire/HoadonList.php
Modules/Invoices/Livewire/PartnerReport.php
Modules/Invoices/Services/GdtInvoiceService.php
Modules/Invoices/Services/GdtPdfService.php
Modules/Invoices/Services/InvoiceService.php
Modules/Invoices/Services/InvoiceImportExportService.php
Modules/Invoices/Services/InvoiceFileService.php
Modules/Invoices/Services/InvoiceFileManagerService.php
Modules/Invoices/Services/InvoicePdfService.php
Modules/Invoices/Services/InvoicePartnerReportService.php
Modules/Invoices/Models/Invoices.php
Modules/Invoices/Models/InvoiceFile.php
```

## R7 Final Verification

Before merge, synchronize the latest `main` into `agent/invoices-refactor` because the branches may have diverged.

Targeted suite:

```bash
php artisan test \
  tests/Feature/InvoicesModuleTest.php \
  tests/Feature/InvoicesFilterSortTest.php \
  tests/Feature/InvoicesPdfStorageTest.php \
  tests/Feature/InvoicesFileManagementTest.php \
  tests/Feature/InvoicesPartnerReportTest.php
```

Then run:

```bash
php artisan test
```

Required manual smoke checks:

```text
/admin/invoices/create-token
/admin/invoices/hoadon
/admin/invoices/hoadon-list
/admin/invoices/reports/partners
```

Verify:

- Sold and purchase synchronization.
- Import from synchronized/uploaded Excel files.
- Invoice list filters/sorts/select-all.
- Single and batch PDF creation.
- Metadata scan / retry / ZIP.
- Selected-only PDF deletion.
- Partner report filter, detail modal and Excel export.
- Permissions for list/create/configure/export/download.

## Remaining Deferred Work

1. Validate invoice identity against production records before adding a database unique constraint.
2. Replace runtime `.env` mutation with a production-grade settings/secrets store.
3. Move very large PDF batches/import/export jobs to background queue orchestration with persistent progress if scale requires it.
4. Remove legacy PDF/routes only after compatibility usage is verified.

## Related Documentation

```text
docs/modules/Invoices/ANALYSIS.md
docs/modules/Invoices/INFORMATION.md
docs/modules/Invoices/REFACTOR_PLAN.md
docs/modules/Invoices/IMPORT_EXPORT_PLAN.md
.codex/standards/MODULE_STANDARD.md
.codex/standards/ADMIN_UI_STANDARD.md
```
