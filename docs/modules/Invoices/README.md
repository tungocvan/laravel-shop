# Invoices Module

## Module Overview

`Invoices` is the electronic-invoice domain module for GDT integration, sold/purchase invoice synchronization, local invoice storage, reporting, Excel import/export and PDF retrieval.

Current analysis recommendation: **Major Refactor**, not a rebuild.

## Registration

Canonical registration:

```text
Modules/ModuleServiceProvider.php
Modules/Invoices/config/module.php
```

Module type:

```text
domain
```

Owned table:

```text
invoices
```

Do not use the legacy `module.json` as the architectural source of truth.

## Main Routes

Admin prefix:

```text
/admin/invoices
```

Important routes:

```text
admin.invoices.index
admin.invoices.create-token
admin.invoices.hoadon
admin.invoices.hoadon-list
admin.invoices.download
```

Legacy `/invoices/*` aliases are retained for compatibility.

API:

```text
GET  /api/invoices
POST /api/invoices
```

API defaults to `auth:sanctum`.

## Permissions

Declared capabilities:

```text
invoices-list
invoices-create
invoices-export
invoices-download
invoices-configure
```

Future refactor should align route and Livewire actions to these capability-specific permissions.

## Features

- GDT captcha/login.
- Server-side GDT token cache.
- Sold and purchase invoice queries.
- Queue-based synchronization.
- Local invoice persistence.
- Invoice filtering and dashboard statistics.
- Excel import/export.
- Selected invoice export.
- PDF download workflow.
- MeInvoice integration.
- Sanctum API.

## Dependencies

Main packages/services:

- Laravel HTTP Client.
- Laravel Cache.
- Laravel Queue.
- Laravel Sanctum.
- Maatwebsite Excel.
- FastExcel.
- PhpSpreadsheet.
- GDT electronic invoice API.
- MeInvoice API.

The module should migrate toward the repository's canonical `Modules/Shared/Services/ImportExport` infrastructure during refactor.

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

MeInvoice:

```text
MEINVOICE_API_TOKEN
```

Storage defaults:

```text
export directory: gdt
PDF directory:    hoadon_temp
```

## Operational Notes

- GDT token must remain server-side; never expose it to browser/Livewire public state.
- Stored GDT password must not be hydrated into Livewire state.
- PDF paths are server-controlled through `InvoiceFileService`.
- Large invoice lists must remain paginated.
- Imports/exports may become long-running and should support chunk/queue strategies.
- Current runtime `.env` editing is considered high risk and should be restricted/refactored before broader production use.

## Developer Notes

Main flow:

```text
Route
-> Controller
-> Page Blade
-> Livewire
-> Service
-> Invoices model
-> invoices table
```

Important classes:

```text
Modules/Invoices/Http/Controllers/InvoicesController.php
Modules/Invoices/Http/Controllers/Api/InvoicesController.php
Modules/Invoices/Livewire/GdtLogin.php
Modules/Invoices/Livewire/GdtInvoice.php
Modules/Invoices/Livewire/HoadonList.php
Modules/Invoices/Services/GdtApiService.php
Modules/Invoices/Services/GdtConfigService.php
Modules/Invoices/Services/GdtInvoiceService.php
Modules/Invoices/Services/InvoiceService.php
Modules/Invoices/Services/InvoiceImportService.php
Modules/Invoices/Services/InvoiceExportService.php
Modules/Invoices/Services/InvoiceFileService.php
Modules/Invoices/Models/Invoices.php
```

Primary test:

```text
tests/Feature/InvoicesModuleTest.php
```

Before changing this module, read:

```text
docs/modules/Invoices/ANALYSIS.md
docs/modules/Invoices/INFORMATION.md
.codex/standards/MODULE_STANDARD.md
.codex/standards/ADMIN_UI_STANDARD.md
```

## Future Improvements

Recommended order:

1. Enforce action-level permissions for configuration, export and download.
2. Harden/remove browser-driven root `.env` mutation.
3. Remove unbounded `All` pagination and normalize query-string page size.
4. Consolidate import/export on Shared infrastructure.
5. Define invoice identity and database-level idempotency protection.
6. Replace float-based money normalization.
7. Optimize repeated statistics/dashboard queries.
8. Queue/chunk large exports and imports.
9. Expand authorization, security, duplicate and performance regression tests.
10. Remove verified legacy/placeholder artifacts only after compatibility checks.
