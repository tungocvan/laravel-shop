# Invoices Module

## Module Overview

`Invoices` is the electronic-invoice domain module for GDT integration, sold/purchase invoice synchronization, local invoice storage, reporting, Excel import/export and PDF retrieval.

Refactor status: **Major Refactor implemented through R6** on `agent/invoices-refactor`.

## Registration

Canonical registration:

```text
Modules/ModuleServiceProvider.php
Modules/Invoices/config/module.php
```

Module type: `domain`.

Owned table: `invoices`.

Do not use the legacy `module.json` as the architectural source of truth.

## Main Routes

Admin prefix:

```text
/admin/invoices
```

Important routes:

```text
admin.invoices.index          -> invoices-list
admin.invoices.create-token   -> invoices-configure
admin.invoices.hoadon         -> invoices-create
admin.invoices.hoadon-list    -> invoices-list
admin.invoices.download       -> invoices-download
```

Legacy `/invoices/*` aliases are retained for bookmark compatibility.

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

Sensitive Livewire mutations for configuration, export and PDF download enforce capability checks server-side. The invoice list UI also hides actions the current admin cannot perform.

## Refactor Results

### R1 — Authorization / GDT hardening

- Route capabilities aligned with module permissions.
- GDT configuration/login/token actions require `invoices-configure`.
- Export requires `invoices-export`.
- PDF actions require `invoices-download`.
- GDT password is never hydrated back into Livewire public state.
- GDT config validation rejects newline injection and invalid cache keys.

Runtime `.env` mutation remains a production-risk area and should eventually move to a persistent settings/secrets mechanism; this was not rewritten during the compatibility-focused refactor.

### R2 — List / pagination / admin UX

- Removed unbounded `All` pagination.
- Allowed page sizes are `10/25/50/100`.
- Invalid page-size input is normalized server-side.
- Selection resets when filter/page-size state changes.
- Invoice list actions are permission-aware.
- Selected invoice count and clearer export/PDF action labels were added.

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
- Export current filter scope when no row is selected.
- Export selected IDs when selection exists.
- Decimal values are normalized as strings rather than through PHP float conversion.
- Shared export filenames include collision-resistant entropy.

See `IMPORT_EXPORT_PLAN.md` for the detailed contract.

### R4 — Data integrity / idempotency

Current application identity remains:

```text
lookup_code + invoice_number + issued_date + tax_code
```

Import duplicate detection uses a date-aware query plus a short cache lock to reduce concurrent duplicate creation. Existing invoice data is not overwritten by a later `skip_duplicate` import with the same identity.

A database unique constraint is intentionally deferred until this identity is validated against production data.

### R5 — Query / statistics / dashboard

- Filtered statistics were consolidated from multiple repeated aggregate queries into one aggregate query.
- Dashboard totals/customer counts are consolidated into one summary query plus one yearly query.
- Pagination uses deterministic `issued_date DESC, id DESC` ordering.
- Selected IDs are sanitized before query use.

### R6 — Final polish

- Permission-aware invoice-list buttons.
- Bounded pagination UI cleanup.
- Selected count feedback.
- Clear distinction between `Xuất theo bộ lọc` and selected-row export.
- Stale `All` pagination conditions removed from Blade.
- Documentation updated to match the implemented architecture.

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

- GDT token stays server-side in cache.
- Stored GDT password is not exposed through Livewire state.
- PDF paths are server-controlled through `InvoiceFileService`.
- Invoice lists are always bounded/paginated.
- Import duplicate behavior is `skip_duplicate`, not update/replace.
- Runtime root `.env` editing is still considered a production-risk area.
- Shared Import/Export currently materializes collections; very large datasets may still require chunk/queue/stream improvements at the Shared layer.

## Important Classes

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
Modules/Invoices/Services/InvoiceImportExportService.php
Modules/Invoices/Services/InvoiceImportService.php
Modules/Invoices/Services/InvoiceFileService.php
Modules/Invoices/Models/Invoices.php
```

## Verification

Primary regression suite:

```bash
php artisan test tests/Feature/InvoicesModuleTest.php
```

Latest user-verified checkpoint before R6 UI/docs polish:

```text
10 tests passed
53 assertions
```

After pulling the final R6 commits, run the targeted suite again before merge, then run the full project regression.

## Remaining Deferred Work

1. Validate invoice identity against production records before adding any database unique constraint.
2. Replace runtime `.env` mutation with a production-grade settings/secrets store.
3. Add chunk/queue/stream behavior for genuinely large import/export datasets at the Shared layer.
4. Remove legacy placeholders/artifacts only after compatibility usage is verified.

## Related Documentation

```text
docs/modules/Invoices/ANALYSIS.md
docs/modules/Invoices/INFORMATION.md
docs/modules/Invoices/REFACTOR_PLAN.md
docs/modules/Invoices/IMPORT_EXPORT_PLAN.md
.codex/standards/MODULE_STANDARD.md
.codex/standards/ADMIN_UI_STANDARD.md
```
