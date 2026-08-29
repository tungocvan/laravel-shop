# Pharma Module

Last verified: 2026-08-30

## Module Overview

Pharma is the domain owner for:

- medicine master data;
- drug bid awards;
- supplier/commercial tracking;
- generated XLSX price lists.

Current assessment: **Major Refactor required**. The module has a useful service/model/spreadsheet foundation and does not require a full rebuild.

## Registration

Pharma is automatically discovered by `Modules\ModuleServiceProvider`.

Current manifest:

```text
type: domain
enabled: false
depends: Shared
```

Do not introduce a separate Pharma service provider or another module-registration system unless repository infrastructure changes.

## Main Routes

Admin UI is under `/admin/pharma`:

- `hssp`
- `drug-bid-awards`
- `supplier-trackings`
- `price-lists/create`

Current admin route middleware is only `web, auth:admin`; capability-specific authorization still needs to be enforced.

`GET /api/pharma` currently points to a missing API `index()` action and should be treated as a broken/unfinished contract.

## Permissions

Declared permissions:

```text
view_pharma
create_pharma
edit_pharma
delete_pharma
```

These are not yet consistently enforced in routes and Livewire actions. Import/export/PriceList generation also need an explicit capability contract.

## Features

### Medicine

CRUD, filters/search, bulk operations, import/export.

### DrugBidAward

CRUD, filters/search, bulk operations, import/export.

### SupplierTracking

CRUD/list/filter, supplier/commercial data, server-side financial calculations, import/export.

### PriceList

Analyzes `storage/app/excel/BANG_GIA_TONG_HOP.xlsx`, allows product/column selection, and generates XLSX output. Default generated files are written under private storage and deleted after successful HTTP download.

## Dependencies

- Admin shell/layout
- Shared Import/Export foundation
- Laravel DB / Storage / Livewire
- FastExcel / spreadsheet libraries

Cross-module dependency direction is appropriate: Pharma owns business logic; Shared owns reusable import/export infrastructure; Admin owns shell presentation.

## Configuration

Primary config:

```text
Modules/Pharma/config/module.php
```

Current source sets:

```text
enabled => false
```

PriceList default source:

```text
storage/app/excel/BANG_GIA_TONG_HOP.xlsx
```

## Operational Notes

Do not treat this analysis as authorization to enable Pharma in production.

Before production-capable use, prioritize:

1. capability authorization at route and Livewire mutation boundaries;
2. removal of unnecessary workbook data from public Livewire state;
3. private storage + retention for Pharma import/export output;
4. correction/removal of the broken API route;
5. bounded pagination/selection and scalable import/export;
6. SupplierTracking duplicate/business-key decision;
7. targeted security/authorization/import-export tests.

## Developer Notes

- Controllers should remain thin.
- Business workflows belong in Pharma services.
- Keep PriceList row/column server-side validation; it is a useful defense-in-depth layer.
- Avoid `All -> 999999`; use bounded page sizes.
- Use a searchable/bounded Medicine picker instead of loading the complete catalog for large datasets.
- Do not return raw exception text to operators.
- Shared Import/Export currently accepts a public `serviceClass`; harden it with locked/server-owned service selection before relying on it as a sensitive cross-module boundary.
- Shared exports currently use public storage; Pharma commercial exports should move to an authorized private-download path.

## Testing

Current observable module-local test inventory:

```text
Modules/Pharma/Tests/Unit/PriceListServiceTest.php
```

No test command was executed during this documentation-only analysis.

## Future Improvements

See `ANALYSIS.md` for the evidence-based P0/P1/P2 findings. Recommended direction remains **Major Refactor**, preserving current domain models, migrations, service boundaries, Shared import/export adoption, and the PriceList spreadsheet pipeline while fixing security, correctness, scaling, and test gaps.
