# Pharma Module Analysis

Last verified: 2026-08-30

Scope: `Modules/Pharma/**`, `docs/modules/Pharma/**`, plus direct dependencies used by the module (`Modules/Shared` import/export infrastructure and Admin shell conventions). This is a documentation-only analysis; application source was not changed.

## Executive Summary

Pharma is a domain module for medicine master data, drug bid awards, supplier/commercial tracking, and XLSX price-list generation. The current architecture already contains useful boundaries: thin web controllers, Livewire UI components, domain services, Eloquent models, migrations, shared import/export integration, and a dedicated spreadsheet pipeline for price lists.

The module is **not structurally broken enough to justify a full rebuild**, but it has multiple security, correctness, performance, and operational gaps that require coordinated work. The correct recommendation remains **Major Refactor**.

Highest-risk findings:

- admin routes use only `web` + `auth:admin`; capability permissions are not enforced at route/action boundaries;
- Livewire mutations (`save`, `delete`, bulk delete, price-list generation) do not authorize the specific capability;
- `PriceList\Create::$analysis` is public Livewire state and contains the workbook product payload, including column values not necessarily rendered by the UI;
- shared Import/Export still stores generated files on the public disk;
- shared Import/Export keeps `serviceClass` in mutable public Livewire state and dynamically resolves it during actions; `mount()` checks subclassing but the action path does not lock/re-resolve from a server-owned registry;
- Medicine and DrugBidAward expose an unbounded-like `All` option by converting it to `999999`; SupplierTracking select-all loads every filtered ID;
- exports build full collections using `get()` and imports materialize the full spreadsheet collection;
- `GET /api/pharma` calls an `index()` method that does not exist;
- SupplierTracking business-key semantics are not enforced by a unique database constraint;
- some Livewire paths return raw exception messages to the operator.

Current source also shows one important state drift from older documentation: `Modules/Pharma/config/module.php` currently has `enabled => false`.

## Module Purpose and Overview

Current functional areas:

1. **Medicine** — medicine profile CRUD, search/filter, bulk selection/delete, import/export.
2. **DrugBidAward** — drug bid award CRUD, filtering, bulk selection/delete, import/export.
3. **SupplierTracking** — supplier/commercial tracking, financial calculations, CRUD/list, bulk delete, import/export.
4. **PriceList** — analyze a source workbook and generate a filtered XLSX quotation/price list.

Current manifest:

```text
name: Pharma
type: domain
enabled: false
depends: Shared
permissions:
  view_pharma
  create_pharma
  edit_pharma
  delete_pharma
tables:
  pharma_medicines
  pharma_drug_bid_awards
  pharma_supplier_trackings
```

No Pharma-specific service provider is required; repository-level `Modules\ModuleServiceProvider` owns module discovery/registration.

## Bootstrap / Standards Context

Verified against:

- `composer.json`
- `.codex/bootstrap/CODEX_BOOTSTRAP.md`
- `.codex/bootstrap/PROJECT_BOOTSTRAP.md`
- `.codex/standards/MODULE_STANDARD.md`
- `.codex/standards/ADMIN_UI_STANDARD.md`
- `.codex/tasks/analyze-module.md`

Relevant project conventions:

- Laravel 12 / PHP 8.3 / Livewire 3;
- first-party modular monolith under `Modules/`;
- controllers should remain thin;
- Livewire owns UI state/validation but not domain workflows;
- sensitive Livewire mutations must authorize server-side;
- services own domain workflows, transactions, import/export orchestration, and reusable queries;
- production-capable lists must use bounded pagination;
- sensitive generated files should use private storage;
- shared import/export infrastructure should be reused instead of creating a competing engine.

## Dependency Graph

```text
Web routes
  -> Pharma controllers
    -> Admin page Blade
      -> Pharma Livewire

Medicine
  -> MedicineService
    -> MedicineImportExport
      -> Shared BaseImportExportService
    -> Medicine model
      -> pharma_medicines

DrugBidAward
  -> DrugBidAwardService
    -> DrugBidAwardImportExport
      -> Shared BaseImportExportService
    -> DrugBidAward model
      -> pharma_drug_bid_awards

SupplierTracking
  -> SupplierTrackingService / Pharma ImportExport service
    -> Shared BaseImportExportService
    -> SupplierTracking model
      -> pharma_supplier_trackings

PriceList
  -> PriceListService
    -> WorkbookAnalyzer
    -> PriceListWorkbookBuilder
    -> private source workbook / generated XLSX

API route
  -> Api\PharmaController@index (method currently absent)
```

Direct module dependencies are appropriate: Pharma -> Shared import/export; Pharma views -> Admin shell. No circular module dependency was observed.

## Route / Controller / Blade / Livewire Analysis

### Routes

`Modules/Pharma/routes/web.php` groups all admin pages under `/admin/pharma` with `web` and `auth:admin` only. The declared `view/create/edit/delete_pharma` capabilities are not applied there.

`Modules/Pharma/routes/api.php` exposes `GET /api/pharma` without Sanctum or another explicit guard and targets `Api\PharmaController@index`; the controller is currently empty. This route is therefore a broken public contract and should either be removed or implemented with an intentional authentication/authorization contract.

The supplier import/export page route should be re-verified during refactor against `SupplierTrackingController`; older analysis identified a route/action mismatch and no source change in this analysis authorizes assuming it is fixed.

### Controllers

The web controllers are generally thin and consistent with repository architecture: they return page views and pass IDs. `PharmaController` still contains commented legacy permission middleware, which confirms authorization was considered but is not currently active.

### Page Blade / Admin UI

The module uses the Admin shell and delegates interactive behavior to Livewire, which is the correct ownership model. During implementation work, rendered UI should be checked against current Admin UI standards for:

- visible input borders/focus states;
- bounded pagination choices;
- responsive table overflow;
- empty/loading states;
- clear permission-aware actions;
- destructive bulk confirmation;
- shared import/export presentation rather than module-specific duplicate UI.

No broad UI rewrite is justified by this analysis alone.

### Livewire

#### Medicine

`Medicine\Index` maintains custom page state and converts `perPage === 'All'` to `999999`. Select-all also requests the same oversized page. This conflicts with the bounded pagination standard.

`deleteMedicine()` and `deleteSelected()` do not authorize deletion. `Medicine\Form::save()` validates fields but does not authorize create/update and flashes raw exception text on failure.

#### DrugBidAward

`DrugBidAward\Index` uses Livewire pagination but still maps `All` to `999999`; select-all explicitly requests `999999` rows. Delete actions do not authorize.

`DrugBidAward\Form::save()` validates but does not authorize and returns `$e->getMessage()` in the flash message. Its `render()` loads the complete Medicine collection directly with `get()`, which will not scale well for a large medicine catalog and bypasses a service/query abstraction.

#### SupplierTracking

`SupplierTrackings\Form` correctly recalculates derived commercial values server-side through `SupplierTrackingService`, and validation constrains status to known values. However, create/update actions do not authorize.

`SupplierTrackings\Index` reports caught delete errors safely, but delete and bulk delete do not authorize. Select-all uses `getFilteredIds()` which plucks the entire matching ID set, so selection is not bounded to the visible page.

#### PriceList

`PriceList\Create` validates sheet name, column expression, selected rows, recipient, and signature fields. `PriceListService` independently validates selected columns and product rows against a fresh workbook analysis; this is a good defense against row/column tampering.

Risks:

- `public array $analysis` contains `WorkbookAnalysis::toArray()` and therefore serializes workbook analysis data into Livewire state;
- `mount()` analyzes the workbook before capability authorization;
- `loadWorkbook()` and `generate()` expose raw exception messages to the UI;
- `filteredProducts()` analyzes the workbook again during render, causing repeated workbook loads;
- generation is synchronous and can become expensive for larger workbooks;
- no capability-specific authorization is performed before analyze/generate/download.

## Service Analysis

### Strengths

- Medicine, DrugBidAward, and SupplierTracking services own Eloquent queries and CRUD workflows.
- CRUD writes use database transactions.
- DrugBidAward list eager-loads Medicine.
- SupplierTracking derived values are recalculated server-side instead of trusting client-computed values.
- PriceList uses a dedicated service, analyzer, builder, DTOs, server-side row validation, and a private default output path.

### Risks

- list page-size inputs are not normalized/capped at the service boundary;
- `SupplierTrackingService::medicinesForSelect()` loads the complete Medicine catalog;
- `SupplierTrackingService::getFilteredIds()` loads every matching ID;
- bulk delete accepts an array of IDs and deletes them in one transaction but authorization/ownership checks are absent at the caller boundary;
- PriceList repeatedly analyzes/loads the workbook within a single interaction;
- `PriceListService::generate()` accepts an optional caller-provided `output_path`; this is acceptable only for trusted callers and needs an explicit boundary if retained.

## Import / Export Analysis

### Shared foundation

Pharma correctly reuses `Modules/Shared/Services/ImportExport/BaseImportExportService` and `Modules/Shared/Livewire/ImportExport/Panel`.

The shared panel now performs an improvement not accurately reflected in the older Pharma analysis: `mount()` verifies that the supplied class extends `BaseImportExportService`, and the panel supports an optional permission check. However:

- `serviceClass` remains a public mutable Livewire property;
- action methods resolve `app($this->serviceClass)` without locking/revalidating the class against a server-owned module registry;
- permission is optional, so callers that omit it get no capability guard;
- import mode is client-selectable across `create_only`, `update_or_create`, `skip_duplicate`, and `replace`, which may be too broad for domain invariants.

Therefore the older finding should be narrowed from "arbitrary container class" to **cross-service substitution within the shared import/export service family unless the property is locked/server-owned**.

### Import

`BaseImportExportService` uses FastExcel `import()` and receives a collection of rows before iterating, so large input is not streamed end-to-end. A transaction is opened for non-dry-run imports; non-`replace` modes continue processing row errors and can commit successful rows, while `replace` rolls back when row errors exist. This is a valid partial-import design only if the business contract explicitly permits it.

Import validation includes extension checking and row-level validation. Column-letter mapping is positional and `shouldUseColumnMapping()` currently always returns true in the base service, so header identity is not the primary integrity check for mapped Pharma imports.

### Export

`BaseImportExportService::export()` maps an in-memory collection and writes to:

```text
storage/app/public/<generated-path>
```

The Livewire panel downloads from `Storage::disk('public')`. For Pharma commercial data this is not an appropriate default production boundary. There is also no explicit expiry/retention policy in the observed path.

Module export implementations such as Medicine use query `get()`, so export memory grows with result count.

PriceList is safer: default output is under `storage/app/private/exports/price-lists` and the HTTP response uses `deleteFileAfterSend(true)`. A fallback cleanup policy is still desirable for failed/aborted responses.

## Shared Dependencies

- `Modules/Shared/Services/ImportExport/*`
- `Modules/Shared/Livewire/ImportExport/Panel.php`
- Admin layout/shell
- Laravel Storage / DB / Livewire
- FastExcel / Maatwebsite Excel / PhpSpreadsheet ecosystem

PhpSpreadsheet classes are imported directly by Pharma. Dependency ownership should be verified so a transitive package removal cannot silently break PriceList.

## Model / Migration / Database Analysis

### Medicine

`Medicine` defines fillable fields and casts dates, special-control boolean, and `declared_price` as decimal. Migration uses `decimal(15,2)` and a unique composite key on registration number + packaging specification. These are good integrity controls.

### DrugBidAward

The model/migration pair owns bid-award persistence and links to Medicine. Existing structure is suitable for refactor rather than rebuild; authorization, bounded queries, and compatibility are higher priorities than model replacement.

### SupplierTracking

Migration uses decimal types for money/percent values and indexes `(medicine_id, supplier_name)` plus `status`. `medicine_id` cascades on delete.

There is no unique constraint for the practical importer/business-key candidate of Medicine + supplier + working date. `working_date` is nullable in CRUD/database semantics, so the canonical duplicate rule needs to be decided before adding a forward migration.

Cascade deletion from Medicine can remove commercial tracking history. Whether that is acceptable is a business/data-retention question, not something to change during analysis.

### PriceList

PriceList has no database entity. Generated quotations therefore have no persisted lifecycle, actor, recipient, source/version hash, selected-product snapshot, or audit history. This may be acceptable if price lists are deliberately disposable files; otherwise a later feature decision is required.

## Security

### PH-P0-01 — Missing capability authorization

**Priority:** P0  
**Files:** `Modules/Pharma/routes/web.php`, `Modules/Pharma/Livewire/**`  
**Evidence:** routes use only `auth:admin`; mutations do not call Gate/policy/`authorize()`; manifest declares Pharma permissions.  
**Problem:** any authenticated admin who can reach the route may execute Pharma operations.  
**Impact:** unauthorized viewing, mutation, deletion, import/export, or quotation generation.  
**Recommendation:** define capability mapping and enforce it at route/page and every sensitive Livewire action; add denied-path tests.

### PH-P0-02 — PriceList workbook data in public Livewire state

**Priority:** P0  
**Files:** `Modules/Pharma/Livewire/PriceList/Create.php`, workbook DTOs  
**Evidence:** `$analysis` is public and assigned `WorkbookAnalysis::toArray()`.  
**Problem:** more workbook data can be serialized to the browser than the visible UI requires.  
**Impact:** commercial/pricing data exposure to any user able to load the component.  
**Recommendation:** authorize before analysis; expose a minimal projection only; keep full workbook values server-side.

### PH-P0-03 — Public-disk business exports

**Priority:** P0  
**Files:** `Modules/Shared/Services/ImportExport/BaseImportExportService.php`, `Modules/Shared/Livewire/ImportExport/Panel.php`  
**Evidence:** export/template writes to `storage/app/public` and downloads via the public disk.  
**Problem:** sensitive Pharma exports are stored in a publicly oriented storage area with no observed retention contract.  
**Impact:** persistent sensitive files and avoidable download-surface risk.  
**Recommendation:** private disk + authorized download + retention/cleanup policy.

### PH-P1-SEC-04 — Mutable import/export service selector

**Priority:** P1  
**Files:** `Modules/Shared/Livewire/ImportExport/Panel.php`  
**Evidence:** `serviceClass` is public; subclass validation occurs in `mount()`, actions later dynamically resolve the property.  
**Problem:** the browser-owned state still determines which shared import/export service is called.  
**Impact:** cross-dataset operation risk if the property can be tampered to another allowed import/export service.  
**Recommendation:** use `#[Locked]` or a server-owned registry key and revalidate capability/service pairing at action time.

### PH-P1-SEC-05 — Raw exception exposure

**Priority:** P1  
**Files:** `Medicine/Form.php`, `DrugBidAward/Form.php`, `PriceList/Create.php`  
**Evidence:** user-facing flash/error text concatenates exception messages.  
**Problem:** internal paths/workbook/parser/database details can leak.  
**Impact:** information disclosure and poor UX.  
**Recommendation:** report/log exceptions server-side and return stable user-safe messages.

## Performance

### PH-P1-PERF-01 — Unbounded page/select behavior

**Priority:** P1  
**Files:** Medicine/DrugBidAward/SupplierTracking indexes and services  
**Evidence:** `All -> 999999`, DrugBidAward select-all `999999`, SupplierTracking plucks all filtered IDs.  
**Problem:** list and bulk-selection memory/database cost scale with full dataset size.  
**Impact:** slow requests, high memory, Livewire payload growth.  
**Recommendation:** bounded page sizes (`10/25/50/100` or domain-approved set) and page-scoped selection unless explicit server-side "all matching" semantics are designed.

### PH-P1-PERF-02 — Collection-based import/export

**Priority:** P1  
**Files:** shared base + Pharma import/export services  
**Evidence:** FastExcel import result is materialized; exportRows implementations use `get()`; export maps complete collections.  
**Problem:** memory scales with file/query size.  
**Impact:** large imports/exports can exhaust request time or memory.  
**Recommendation:** chunk/lazy/generator/queue strategy and explicit thresholds.

### PH-P1-PERF-03 — Full Medicine option lists

**Priority:** P1  
**Files:** `DrugBidAward/Form.php`, `SupplierTrackingService.php`  
**Evidence:** complete Medicine list is loaded for form selects.  
**Problem:** option-list cost grows with the catalog.  
**Recommendation:** shared searchable select / bounded server-side lookup.

### PH-P2-PERF-04 — Repeated workbook analysis

`PriceList\Create` can analyze the same workbook during mount, render filtering, pre-generate validation, service generation, and builder processing. Cache/request-scoped analysis or a server-side snapshot should be evaluated after security boundaries are fixed.

## Validation and Authorization

Field validation is generally present for CRUD forms and PriceList inputs. Important gaps are authorization and normalization of client-controlled list parameters (`perPage`, filter values, bulk IDs).

Server-side PriceList row/column validation is a strength and should be preserved.

## Transactions, Concurrency and Data Integrity

CRUD services use transactions. Shared imports also use transactions, but partial-success semantics differ from strict atomic import and must be documented per aggregate.

Supplier duplicate prevention is not database-enforced. Concurrent import/CRUD can therefore create ambiguous duplicate rows depending on the intended business key.

Bulk delete operations should validate/canonicalize IDs and rely on authorization before transaction execution.

## Admin UI / UX Standard Review

Current list behavior conflicts with the canonical pagination standard because Medicine/DrugBidAward expose an `All` path. Bulk selection semantics are inconsistent: Medicine/DrugBidAward/SupplierTracking can represent more than the visible page.

During refactor, use the shared admin patterns for:

- bounded pagination;
- search/filter reset;
- page-scoped selection or explicitly labelled all-matching selection;
- server-authorized bulk actions;
- confirmation modal with affected count;
- loading/disabled state;
- shared Import/Export panel;
- searchable Medicine picker instead of a huge native select.

## Cross-Module Dependencies

Pharma owns its domain models/migrations/services. Shared import/export is an appropriate cross-module foundation. Admin owns the shell/presentation layout. No evidence supports moving Pharma business logic into Admin or another module.

Any security fix in Shared Import/Export must be treated as a shared-foundation change with impacted-module regression, not as Pharma-local behavior only.

## Technical Debt

### PH-P1-COR-01 — Broken API route contract

`GET /api/pharma` calls a missing `Api\PharmaController@index` and is not guarded by Sanctum in the active route definition. Remove the scaffold route if unused or implement it intentionally with authentication/authorization and tests.

### PH-P1-COR-02 — Supplier duplicate invariant not enforced

Decide whether `(medicine_id, supplier_name, working_date)` or another key is canonical; align CRUD/import nullability and then add a forward unique constraint after duplicate cleanup if appropriate.

### PH-P1-COR-03 — PriceList output path boundary

`PriceListService::generate()` accepts optional `output_path`. Keep this only as a trusted CLI/internal contract or enforce an allowlisted root.

### PH-P2-MAINT-01 — Legacy/scaffold artifacts

`Models/Pharma.php`, old scaffold README content, commented permission middleware, and any unused placeholder controller/routes should be removed only after caller/route tests prove they are unused.

## Test Coverage

Observed module-local test inventory under `Modules/Pharma/Tests` currently contains only:

```text
Unit/PriceListServiceTest.php
```

Repository search did not find the previously documented `PharmaImportExportTest`, so older documentation overstated the current observable test inventory.

Coverage gaps to address before a major refactor is accepted:

- route boot and broken API contract;
- view/create/update/delete permission denial and success paths;
- Livewire mutation authorization;
- bulk delete and tampered IDs;
- import/export permission/service selection;
- private export/download/cleanup;
- Medicine and DrugBidAward bounded pagination;
- Supplier duplicate/invariant behavior;
- raw exception non-disclosure;
- workbook missing/corrupt/large cases;
- PriceList browser-state projection and row/column tampering;
- import partial/atomic semantics and large-file behavior.

No test commands were run in this documentation-only analysis; findings are based on repository source and current test inventory.

## Documentation Drift

Verified drift from the previous Pharma docs:

- manifest currently has `enabled => false`, not enabled;
- Shared Import/Export panel now checks subclassing in `mount()` and supports an optional permission, so the old "no interface check / no authorization support" wording is stale; the remaining issue is mutable public service selection plus optional permission enforcement;
- observable module-local tests currently show only `PriceListServiceTest.php`; previously documented `PharmaImportExportTest` was not found;
- previous docs remain correct that API routing, capability authorization, public export storage, workbook Livewire state, bounded pagination, and Supplier invariant require work.

## Issue List

| ID | Priority | Area | Summary |
|---|---|---|---|
| PH-P0-01 | P0 | Authorization | Capability-specific authorization missing |
| PH-P0-02 | P0 | Data exposure | PriceList workbook payload in public Livewire state |
| PH-P0-03 | P0 | File security | Pharma exports written to public disk |
| PH-P1-SEC-04 | P1 | Shared UI security | Mutable import/export service selector |
| PH-P1-SEC-05 | P1 | Error handling | Raw exception messages exposed |
| PH-P1-PERF-01 | P1 | Pagination | `All`/all-ID selection is unbounded |
| PH-P1-PERF-02 | P1 | Import/export | Collection-based large-data processing |
| PH-P1-PERF-03 | P1 | Forms | Full Medicine catalog loaded for selects |
| PH-P1-COR-01 | P1 | Routes | Public API route calls missing method |
| PH-P1-COR-02 | P1 | Database | Supplier business key not database-enforced |
| PH-P1-COR-03 | P1 | File boundary | Caller-supplied PriceList output path |
| PH-P2-PERF-04 | P2 | Spreadsheet | Repeated workbook analysis/load |
| PH-P2-MAINT-01 | P2 | Maintenance | Legacy/scaffold artifacts |

## Module Health Summary

| Dimension | Assessment |
|---|---|
| Architecture | Moderate-good foundation; service boundaries exist |
| Security | Poor until P0 authorization/data/file findings are fixed |
| Correctness | Moderate; broken API and Supplier invariant remain |
| Performance | Moderate-poor for large lists/import/export/workbooks |
| Database | Generally sensible types/indexes; Supplier invariant unresolved |
| Import/Export | Canonical shared foundation reused, but storage/scaling/security need work |
| Admin UI | Functional patterns present; pagination/selection/permissions need alignment |
| Tests | Insufficient for current module risk surface |
| Documentation | Refreshed to current source in this analysis |

## Final Recommendation

- [ ] Minor Refactor
- [x] **Major Refactor**
- [ ] Full Rebuild
- [ ] No Structural Refactor Required

Do **not** rebuild the entire module. Preserve working domain models, migrations, service boundaries, shared import/export adoption, and PriceList spreadsheet pipeline. Refactor in priority order: P0 authorization/data/file security first, then route/data invariants, bounded large-data behavior, shared Import/Export hardening, test coverage, and UI consistency.

## Open Questions / Unknowns

1. Is Pharma intentionally disabled (`enabled => false`) in every environment, or is production/runtime enablement managed elsewhere?
2. Which roles/capabilities may view, create, edit, delete, import, export, and generate PriceList files?
3. Is PriceList a disposable generated file or a business document requiring audit/version/history?
4. Which workbook columns are sensitive and which are allowed to reach the browser/client?
5. Should users be able to choose import mode, especially destructive `replace`, or should mode be fixed per aggregate/capability?
6. Is partial import the intended contract, or must some Pharma imports be atomic?
7. What is the canonical SupplierTracking unique/business key, and is `working_date` mandatory?
8. Is `/api/pharma` required at all?
9. May trusted CLI callers write PriceList files outside the private export root?
10. What dataset/file-size thresholds should trigger queued import/export/PriceList generation?
