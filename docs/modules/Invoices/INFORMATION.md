# Invoices Module Information

## Purpose

`Modules/Invoices` manages electronic invoice integration and local invoice data for both sold and purchase invoices.

## Features

- GDT captcha and authentication.
- Server-side GDT token cache.
- Query sold/purchase invoices from GDT.
- Queue-based invoice processing.
- Import invoice Excel files.
- List/filter invoice records.
- Invoice statistics and dashboard summaries.
- Export selected invoices.
- Download invoice PDFs.
- Sanctum-protected invoice API.
- MeInvoice integration for PDF-related workflow.

## Registration

Canonical registration uses the repository first-party module system:

```text
Modules/ModuleServiceProvider.php
Modules/Invoices/config/module.php
```

Module manifest:

- Name: `Invoices`
- Type: `domain`
- Enabled: `true`
- Owned table: `invoices`
- Declared module dependencies: none

A legacy `Modules/Invoices/module.json` also exists but is not the canonical registration mechanism.

## Routes

### Admin web routes

Base prefix:

```text
/admin/invoices
```

Route names:

```text
admin.invoices.index
admin.invoices.create-token
admin.invoices.hoadon
admin.invoices.hoadon-list
admin.invoices.download
```

Backward-compatible `/invoices/*` aliases also exist.

### API routes

Base prefix after global API registration:

```text
/api/invoices
```

Methods:

- `GET /api/invoices`
- `POST /api/invoices`

Default middleware:

```text
api
auth:sanctum
```

## Permissions

Declared in `Modules/Invoices/config/module.php`:

```text
invoices-list
invoices-create
invoices-export
invoices-download
invoices-configure
```

Current route enforcement primarily uses `invoices-list` and `invoices-create`; more granular action-level enforcement is recommended during refactor.

## Controllers

### `Modules\Invoices\Http\Controllers\InvoicesController`

Responsibilities:

- Redirect module index to invoice list.
- Return authentication page.
- Return sync page.
- Return invoice list page.
- Download invoice PDF through `InvoiceFileService`.

### `Modules\Invoices\Http\Controllers\Api\InvoicesController`

Responsibilities:

- API health/status response.
- Validate filter request.
- Build invoice filter query through `InvoiceService`.
- Apply amount/tax ranges.
- Sort and paginate results.
- Return summary metadata.

## Livewire Components

### `GdtLogin`

- Ensures default GDT environment keys exist.
- Loads current GDT config without exposing stored password to public state.
- Loads captcha.
- Authenticates against GDT.
- Saves GDT config.
- Clears server-side token.

### `GdtInvoice`

- Accepts date range.
- Supports `sold` / `purchase` type.
- Calls `GdtInvoiceService` to query GDT.
- Displays result count/items.

### `HoadonList`

- Invoice filters.
- Date range filter.
- Tax-rate filter.
- Pagination.
- Dynamic name/tax-code options.
- Statistics.
- Dashboard data.
- Row selection.
- Selected Excel export.
- Selected PDF download.

### Other classes

- `InvoiceList`
- `InvoiceManager`
- `SearchHoadon`

`InvoiceList` and `InvoiceManager` should be verified for active runtime usage before future cleanup.

## Blade Views

Page shells:

```text
Modules/Invoices/resources/views/pages/invoices/authenticate.blade.php
Modules/Invoices/resources/views/pages/invoices/index.blade.php
Modules/Invoices/resources/views/pages/invoices/sync.blade.php
```

Livewire views live under:

```text
Modules/Invoices/resources/views/livewire/
```

## Services

### `GdtApiService`

- GDT HTTP client.
- Captcha retrieval.
- Authentication.
- Server-side token caching.
- Token invalidation.

### `GdtConfigService`

- Ensures GDT environment keys.
- Writes allowed GDT settings to root `.env`.

### `GdtInvoiceService`

- GDT invoice-query workflow.
- Supports sold/purchase synchronization behavior.

### `InvoiceService`

- Filtered invoice query.
- Pagination.
- Filter option lists.
- Statistics.
- Dashboard summaries.
- Selected-record retrieval.

### `InvoiceImportService`

- Imports Excel rows through FastExcel.
- Maps exported Vietnamese headers.
- Detects application-level duplicates.
- Persists invoice rows.

### `InvoiceExportService`

- Creates XLSX workbooks with PhpSpreadsheet.

### `InvoiceFileService`

- Resolves invoice PDF path.
- Rejects path-like lookup codes.
- Verifies PDF existence.

### `MeInvoiceService`

- External MeInvoice integration used by PDF/download workflow.

### `ScanInvoiceService`

- Invoice scanning/extraction-related workflow.

## Imports / Exports

### Imports

Primary import service:

```text
Modules/Invoices/Services/InvoiceImportService.php
```

Supported logical invoice types:

```text
sold
purchase
```

Current duplicate match:

```text
lookup_code
invoice_number
issued_date
tax_code
```

Current implementation does not yet use the repository canonical `Modules/Shared/Services/ImportExport` foundation.

### Exports

Observed paths:

```text
Modules/Invoices/Exports/InvoicesSelectedExport.php
Modules/Invoices/Services/InvoiceExportService.php
```

Libraries:

- Maatwebsite Excel.
- PhpSpreadsheet.

## Models

### `Modules\Invoices\Models\Invoices`

Table:

```text
invoices
```

Fillable fields:

```text
lookup_code
symbol
invoice_number
type
issued_date
tax_code
name
address
email
phone
tax_rate
vat_amount
amount_before_vat
total_amount
invoice_type
```

Casts:

```text
issued_date        date
tax_rate           decimal:0
vat_amount         decimal:2
amount_before_vat  decimal:2
total_amount       decimal:2
```

## Database Tables

### `invoices`

Important columns:

- invoice lookup code.
- symbol.
- invoice number.
- GDT invoice type text.
- issued date.
- partner tax code/name/address/email/phone.
- tax rate.
- VAT amount.
- amount before VAT.
- total amount.
- invoice direction (`sold` / `purchase`).

Indexes:

```text
(invoice_type, issued_date)
tax_code
invoice_number
```

No database unique constraint currently enforces the import duplicate identity.

## Relationships

No Eloquent relationships were observed on the `Invoices` model in the analyzed source.

## Shared / Cross-Module Dependencies

No business-domain module dependency is declared.

Shared/framework dependencies include:

- Laravel Cache.
- Laravel HTTP Client.
- Laravel Queue.
- Sanctum.
- Maatwebsite Excel.
- FastExcel.
- PhpSpreadsheet.

Canonical Shared import/export infrastructure is available in the repository but not currently consumed by this module.

## Events / Jobs

Observed job:

```text
Modules/Invoices/Jobs/ProcessGdtInvoicesJob.php
```

The feature test confirms queue command dispatch for sold/purchase invoice processing.

No cross-module event contract was established in the analyzed subset.

## Console

The module contains a `Console` directory and supports GDT invoice commands. Existing tests exercise:

```text
gdt:invoices
gdt:import-excel
```

## Configuration / Environment Variables

### GDT

```text
GDT_API_BASE_URL
GDT_API_USERNAME
GDT_API_PASSWORD
GDT_API_VERIFY_SSL
GDT_API_TIMEOUT
GDT_TOKEN_TTL
GDT_TOKEN_CACHE_KEY
```

Default API base URL:

```text
https://hoadondientu.gdt.gov.vn/api
```

### MeInvoice

```text
MEINVOICE_API_TOKEN
```

Default service base URL:

```text
https://api.meinvoice.vn/api/integration
```

### Storage config

```text
invoices.storage.export_directory = gdt
invoices.storage.pdf_directory    = hoadon_temp
```

## Known Limitations

- GDT configuration currently edits root `.env` at runtime.
- Sensitive Livewire mutations need explicit action-level authorization.
- `HoadonList` currently offers unbounded `All` page size.
- Import/export is not yet unified with Shared import/export infrastructure.
- Duplicate invoice identity is not enforced by a unique DB constraint.
- Import amount normalization uses float conversion.
- Some exports can be memory-heavy.
- Statistics may execute repeated aggregate queries.

## Tests

Primary module feature test:

```text
tests/Feature/InvoicesModuleTest.php
```

Current coverage includes:

- enabled module configuration;
- GDT token kept server-side;
- GDT cursor behavior;
- queue command dispatch;
- sold/purchase Excel import;
- invoice list rendering after import.

## Maintenance Notes

- Treat `config/module.php` as authoritative over legacy `module.json`.
- Preserve route names and `/invoices` compatibility aliases unless migration impact is explicitly approved.
- Preserve `sold` and `purchase` semantics.
- Do not expose GDT token or stored password to Livewire/browser state.
- Validate production data before introducing a new unique invoice constraint.
- Prefer bounded lists and queued/chunked large import/export operations.
