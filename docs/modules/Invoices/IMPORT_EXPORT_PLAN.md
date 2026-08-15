# Invoices Import / Export Plan

## Status

Approved as part of the user-approved `Invoices` major refactor. This document records the concrete R3 contract before source implementation.

## Goals

- Reuse `Modules/Shared/Services/ImportExport` and `shared.import-export.panel`.
- Preserve current sold/purchase invoice semantics and Vietnamese spreadsheet compatibility.
- Make selected export follow the repository contract: no selected IDs means export the current approved filtered scope; selected IDs means export only those records.
- Keep import idempotent without inventing a new database identity rule.
- Avoid float conversion for monetary values.

## Import Contract

### Accepted formats

- XLSX
- CSV

### Mode

Default mode: `skip_duplicate`.

`replace` is not allowed for invoice data through the admin UI.

### Current identity

Until production data confirms a stronger canonical identity, retain the existing application-level duplicate tuple:

```text
lookup_code + invoice_number + issued_date + tax_code
```

A database unique constraint is explicitly deferred to R4 because the identity must be validated against real production data first.

### Headers / mapping

Support the current exported Vietnamese headers and normalized aliases for:

- lookup code
- symbol
- invoice number
- invoice type text
- issued date
- tax code
- partner name/address/email/phone
- tax rate
- VAT amount
- amount before VAT
- total amount
- invoice direction (`sold` / `purchase`)

`invoice_type` may also be supplied by the calling context for legacy CLI compatibility. Only `sold` and `purchase` are valid.

### Money

Normalize money to decimal strings. Do not convert money through PHP float before persistence.

### Transactions and errors

Use the Shared base import transaction/report lifecycle. Row validation errors remain row-scoped and visible in the report. `replace` rollback behavior from Shared is not exposed for this module.

### Round trip

An invoice spreadsheet exported by the new service should be accepted by the same service for `skip_duplicate` import without creating duplicate rows.

## Export Contract

### Authorization

Admin UI export requires `invoices-export`.

### Scope

```text
selected_ids empty     -> export records matching current invoice filters
selected_ids not empty -> export only sanitized positive unique selected IDs
```

Selected IDs take precedence over normal filters.

### Filters

Supported filters mirror `InvoiceService`:

- invoice_type
- name
- tax_code
- issued_date_from
- issued_date_to
- tax_rate

### Columns

Export stable business columns only. Do not export internal timestamps or database IDs as business identity.

### Storage

Use Shared export storage lifecycle. Large-dataset streaming/queue improvements to Shared are a separate scalability concern and should not be falsely treated as solved merely by adopting the base service.

## UI Contract

Use `shared.import-export.panel` rather than a module-specific duplicate import/export panel. Pass current filters and `selected_ids` reactively from the invoice list where supported by the existing Shared component contract.

The table remains responsible for list filtering and row selection. The Shared panel remains responsible for upload/import/export/template UX.

## Legacy Compatibility

The existing `gdt:import-excel` command must continue to work for sold and purchase imports. It may delegate to the new module `ImportExport` service after compatibility adaptation.

Existing GDT synchronization and PDF download workflows are outside this import/export migration and must not regress.

## Verification

Targeted tests must cover:

1. sold XLSX import;
2. purchase XLSX import;
3. exported rows can be imported back without duplicates;
4. monetary normalization preserves decimal values without float arithmetic;
5. invalid invoice direction is rejected;
6. no selected IDs exports current filter scope;
7. selected IDs export only selected records;
8. selected IDs are sanitized to positive unique integers;
9. unauthorized export action returns 403;
10. existing `gdt:import-excel` compatibility remains green.

## Deferred Decisions

- Database-level unique constraint: R4 after production identity validation.
- Queue threshold / streaming for very large exports: requires a generic Shared improvement and dataset sizing evidence.
- Runtime GDT `.env` configuration is unrelated to spreadsheet import/export and remains tracked under R1 hardening.
