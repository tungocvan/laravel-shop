# Pharma Module Information

Last verified: 2026-08-30

## Purpose

Pharma owns medicine master data, drug bid awards, supplier/commercial tracking, and XLSX price-list generation.

## Module State

```text
Type: domain
Enabled in config: false
Direct module dependency: Shared
```

Declared permissions:

```text
view_pharma
create_pharma
edit_pharma
delete_pharma
```

These permissions are currently declared but not consistently enforced by Pharma routes/Livewire actions.

## Features

- Medicine CRUD, search/filter, bulk selection/delete, import/export.
- DrugBidAward CRUD, filters, bulk selection/delete, import/export.
- SupplierTracking CRUD/list/filter, commercial calculations, bulk delete, import/export.
- PriceList workbook analysis, product/column selection, recipient/signature metadata, XLSX generation/download.
- CLI support for selected Pharma workflows.

## Routes

Admin prefix: `/admin/pharma`, active middleware currently `web, auth:admin`.

Main routes:

- `/admin/pharma/hssp`
- `/admin/pharma/hssp/create`
- `/admin/pharma/hssp/{id}/edit`
- `/admin/pharma/drug-bid-awards`
- `/admin/pharma/drug-bid-awards/create`
- `/admin/pharma/drug-bid-awards/{id}/edit`
- `/admin/pharma/supplier-trackings`
- `/admin/pharma/supplier-trackings/create`
- `/admin/pharma/supplier-trackings/{id}/edit`
- `/admin/pharma/supplier-trackings/import-export`
- `/admin/pharma/price-lists/create`

API:

- `GET /api/pharma` currently targets `Api\PharmaController@index`, but the controller has no `index()` method. The active API route is not guarded by Sanctum.

## Controllers

- `Http/Controllers/PharmaController.php`
- `Http/Controllers/DrugBidAwardController.php`
- `Http/Controllers/SupplierTrackingController.php`
- `Http/Controllers/PriceListController.php`
- `Http/Controllers/Api/PharmaController.php` — empty scaffold at current checkpoint.

Web controllers are primarily thin page controllers.

## Livewire Components

- `Medicine/Index.php`
- `Medicine/Form.php`
- `DrugBidAward/Index.php`
- `DrugBidAward/Form.php`
- `SupplierTrackings/Index.php`
- `SupplierTrackings/Form.php`
- `PriceList/Create.php`

Important current behavior:

- Medicine and DrugBidAward support an `All` path implemented as `999999` rows.
- SupplierTracking select-all loads every filtered ID.
- CRUD/delete/generate Livewire actions do not currently enforce capability authorization.
- Medicine/DrugBidAward/PriceList contain user-facing raw exception paths.
- PriceList keeps workbook analysis in a public Livewire property.

## Blade Views

Module views live under `Modules/Pharma/resources/views` and use the Admin shell. Interactive feature behavior is delegated to Livewire.

## Services

Core services:

- `MedicineService`
- `DrugBidAwardService`
- `SupplierTrackingService`
- `PriceListService`

Import/export:

- `MedicineImportExport`
- `DrugBidAwardImportExport`
- `ImportExport` (SupplierTracking)
- compatibility helper `MedicineImportService`

Spreadsheet:

- `Spreadsheet/WorkbookAnalyzer`
- `Spreadsheet/PriceListWorkbookBuilder`

## Imports / Exports

Pharma reuses the canonical Shared import/export foundation.

Shared panel behavior currently includes:

- upload validation for xlsx/csv and max size;
- dry-run support;
- modes `create_only`, `update_or_create`, `skip_duplicate`, `replace`;
- optional permission check;
- subclass check for the configured service during `mount()`.

Known concerns:

- `serviceClass` remains public mutable Livewire state and is dynamically resolved during actions;
- permission is optional;
- imports are collection-based;
- exports are collection-based and written to `storage/app/public`;
- no observed explicit retention/cleanup contract for shared exports.

PriceList differs from shared exports: its default generated file is under private storage and the HTTP response deletes the file after send.

## Models

- `Medicine`
- `DrugBidAward`
- `SupplierTracking`
- `Pharma` — scaffold/unused status should be confirmed before removal.

## Database Tables

- `pharma_medicines`
- `pharma_drug_bid_awards`
- `pharma_supplier_trackings`

Medicine has a composite unique constraint for registration number + packaging specification.

SupplierTracking has indexes on `(medicine_id, supplier_name)` and `status`, but no unique constraint for a supplier business key involving working date.

PriceList has no table/model; generated quotations are not persisted as audit records.

## Relationships

- DrugBidAward -> Medicine (`belongsTo`).
- SupplierTracking -> Medicine (`belongsTo`).
- Medicine inverse relationships are not a major requirement for current behavior and should only be added if callers need them.

## Shared / Cross-Module Dependencies

- `Modules/Shared/Services/ImportExport/*`
- `Modules/Shared/Livewire/ImportExport/Panel.php`
- Admin layout/shell

No circular module dependency was observed.

## Events / Jobs

No Pharma domain event/listener or queue job was observed in the current module structure. Long-running import/export/PriceList work currently runs synchronously.

## Configuration / Environment Variables

`Modules/Pharma/config/module.php` defines module metadata, dependency, permissions, table catalog, and currently `enabled => false`.

PriceList default source:

```text
storage/app/excel/BANG_GIA_TONG_HOP.xlsx
```

Default generated directory:

```text
storage/app/private/exports/price-lists
```

No Pharma-specific environment variable was observed.

## Test Inventory

Observed module-local tests:

```text
Modules/Pharma/Tests/Unit/PriceListServiceTest.php
```

The previously documented `PharmaImportExportTest` was not found in the current repository search.

## Known Limitations

- capability authorization is incomplete;
- PriceList analysis data is stored in public Livewire state;
- shared exports use public storage;
- shared import/export service selection remains browser-influenced;
- API route is currently broken/public;
- large lists/import/export are not bounded/streamed sufficiently;
- full Medicine collections are loaded for some form selectors;
- SupplierTracking duplicate/business-key semantics are not database-enforced;
- PriceList has no persisted lifecycle/audit history;
- some raw exception text can reach UI.

## Maintenance Notes

- Treat Pharma as a **Major Refactor** candidate, not a full rebuild.
- Fix P0 authorization/data/file boundaries before performance or visual cleanup.
- Preserve existing route names/tables/Livewire aliases unless compatibility impact is explicitly planned.
- Shared Import/Export fixes require impacted-module regression beyond Pharma.
- Do not enable Pharma in production merely because source/documentation analysis is complete; runtime enablement is a separate operational decision.
