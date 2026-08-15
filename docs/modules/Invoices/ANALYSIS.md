# Invoices Module Analysis

## Executive Summary

`Modules/Invoices` is a domain module responsible for electronic invoice workflows, including GDT authentication, invoice synchronization, invoice listing, filtering/statistics, API access, Excel import/export, PDF download, and queued synchronization work.

The module already follows the repository's first-party modular architecture and has a meaningful service layer. It should not be rebuilt from scratch. The recommended direction is **Major Refactor** focused on authorization boundaries, safe configuration handling, bounded list/export behavior, canonical import/export integration, data integrity, and targeted test coverage.

No application source changes are part of this analysis.

## Module Purpose and Overview

Primary responsibilities observed:

- Authenticate against the GDT electronic invoice API using captcha and server-side cached token.
- Query sold and purchase invoices from GDT.
- Persist invoice data into the local `invoices` table.
- Present admin invoice list, filters, statistics, and dashboard summaries.
- Export selected invoices to Excel.
- Import exported Excel data back into the invoice table.
- Download/store invoice PDFs.
- Expose a Sanctum-protected invoice API.
- Support queue-based GDT invoice processing.

## Bootstrap / Standards Context

Repository context establishes:

- Laravel 12 / PHP 8.3.
- Livewire 3.
- First-party modular monolith under `Modules/`.
- `Modules\ModuleServiceProvider` is the canonical module registration mechanism.
- Domain workflows should live in services.
- Livewire should own UI state/validation and enforce authorization for sensitive mutations.
- Imports/exports should converge on `Modules/Shared/Services/ImportExport`.
- Large datasets must remain bounded; unbounded `get()` / `All` pagination is not production-safe.

Invoices already has `config/module.php` with `type=domain`, `enabled=true`, table ownership and permission declarations.

A legacy `Modules/Invoices/module.json` also exists. It is not used by the repository's canonical module provider and should be treated as technical debt/documentation drift rather than an architectural source of truth.

## Dependency Graph

```text
Admin Routes
-> Modules\Invoices\Http\Controllers\InvoicesController
-> Page Blade
-> Livewire components
-> Invoice / GDT / MeInvoice services
-> Modules\Invoices\Models\Invoices
-> invoices table

API Routes
-> Modules\Invoices\Http\Controllers\Api\InvoicesController
-> InvoiceService
-> Invoices model
-> invoices table

GDT sync
-> Livewire GdtInvoice / Console command / ProcessGdtInvoicesJob
-> GdtInvoiceService + GdtApiService
-> remote GDT API
-> Invoice export/import workflow
-> invoices table

Selected Excel export
-> HoadonList
-> InvoiceService::selected()
-> InvoicesSelectedExport
-> Maatwebsite Excel

PDF download
-> InvoicesController::download()
-> InvoiceFileService
-> storage/app/<pdf_directory>/<lookup_code>.pdf
```

## Route / Controller / Blade / Livewire Analysis

### Routes

`Modules/Invoices/routes/web.php` defines admin routes under `admin/invoices` protected by `web`, `auth:admin`, and named permissions.

Observed route capabilities:

- `invoices-list`: list and PDF download.
- `invoices-create`: token/config/authentication page and GDT sync page.

Backward-compatible aliases under `/invoices` are retained.

### Route findings

**P1 - permission granularity mismatch**

Priority: P1  
File: `Modules/Invoices/routes/web.php`, `Modules/Invoices/config/module.php`  
Evidence: module manifest declares `invoices-export`, `invoices-download`, and `invoices-configure`, while routes currently rely mainly on `invoices-list` and `invoices-create`.  
Problem: declared capability model is more granular than actual route/action enforcement.  
Impact: users granted broad list/create permissions may receive configuration, download, export, or synchronization capabilities beyond the intended least-privilege model.  
Recommendation: during refactor, align every route and sensitive Livewire action to the dedicated capability (`invoices-configure`, `invoices-download`, `invoices-export`, etc.) while keeping existing route names and URLs compatible.

### Controller

`InvoicesController` is appropriately thin:

- redirects index;
- returns page views;
- delegates PDF path resolution to `InvoiceFileService`.

This generally complies with `MODULE_STANDARD.md`.

### Page Blade

The three page blades are lightweight shells:

- `pages/invoices/authenticate.blade.php`
- `pages/invoices/index.blade.php`
- `pages/invoices/sync.blade.php`

This is consistent with the expected page-shell architecture.

### Livewire

Observed components:

- `GdtInvoice`
- `GdtLogin`
- `HoadonList`
- `InvoiceList`
- `InvoiceManager`
- `SearchHoadon`

`GdtInvoice` validates date range and invoice type before calling `GdtInvoiceService`.

`HoadonList` owns filtering, pagination, selection, statistics, export and bulk PDF download interactions, and delegates queries/business work to services.

`GdtLogin` handles captcha/login UI plus GDT configuration mutation.

### Livewire findings

**P0 - sensitive configuration mutation lacks explicit action-level authorization**

Priority: P0  
File: `Modules/Invoices/Livewire/GdtLogin.php`  
Evidence: `saveGdtConfig()` writes GDT configuration to `.env`, invalidates token state and executes `Artisan::call('config:clear')`; no explicit permission/policy check is present inside the method.  
Problem: route middleware protects the page, but repository standards require authorization at sensitive mutation boundaries.  
Impact: configuration credentials and runtime behavior can be changed by any user able to reach the component under the broader route permission.  
Recommendation: require a dedicated `invoices-configure` authorization check inside `saveGdtConfig()`, `deleteToken()`, and any other privileged GDT configuration/authentication mutation. Add allowed/denied Livewire tests.

**P1 - export/download mutations lack explicit action-level authorization**

Priority: P1  
File: `Modules/Invoices/Livewire/HoadonList.php`  
Evidence: `exportSelected()` and `downloadSelected()` perform privileged data/file operations without explicit server-side authorization in the methods.  
Problem: hiding UI or route-level checks are not sufficient for Livewire mutations.  
Impact: privilege boundaries can become inconsistent if component access patterns change.  
Recommendation: authorize `invoices-export` and `invoices-download` directly at action boundaries.

**P1 - unbounded page size option**

Priority: P1  
File: `Modules/Invoices/Livewire/HoadonList.php`, `Modules/Invoices/Services/InvoiceService.php`  
Evidence: page-size options include `'All'`; `InvoiceService::paginate()` executes `$query->get()` when `'All'` is selected.  
Problem: production datasets can be loaded fully into memory and rendered by Livewire.  
Impact: slow responses, high memory use, large Livewire payloads, and potential worker instability as invoice volume grows.  
Recommendation: remove `All`, normalize page size to bounded values such as 10/25/50/100, and use streaming/chunked export for full-scope data needs.

**P1 - per-page query-string input is not normalized**

Priority: P1  
File: `Modules/Invoices/Livewire/HoadonList.php`  
Evidence: `perPage` is public, query-string backed, and can be set to arbitrary values; service casts non-`All` values to integer.  
Problem: tampered values can become unexpected limits, including zero/negative-like coercions depending on runtime path.  
Impact: unstable pagination behavior and unnecessary query risk.  
Recommendation: validate/normalize against an allowlist of bounded page sizes.

## Service Analysis

### InvoiceService

Strengths:

- centralizes filter/query logic;
- supports pagination;
- keeps dashboard/statistics out of Livewire;
- contains SQLite-aware yearly aggregation for tests.

Concerns:

- `filter()` returns an unbounded collection by default.
- `statistics()` runs multiple cloned aggregate queries per Livewire render/property access.
- dashboard aggregates are recalculated on each render.

**P1 - aggregate query amplification**

Priority: P1  
File: `Modules/Invoices/Services/InvoiceService.php`, `Modules/Invoices/Livewire/HoadonList.php`  
Evidence: statistics performs count, total sum, VAT sum, and multiple tax-rate sums; `HoadonList` exposes several computed properties that each call `statistics()` independently.  
Problem: a single UI render can trigger repeated aggregate query groups over the same filtered dataset.  
Impact: unnecessary database load on large invoice tables.  
Recommendation: calculate statistics once per render/request state or provide a single cached/batched statistics payload; add query-count coverage.

### GdtApiService

Strengths:

- token is stored server-side in cache;
- credentials come from config/env;
- connection failures are handled without exposing secrets;
- token is not returned to Livewire caller.

Potential improvement:

- logging includes remote URL and exception message. No credential logging was observed, but production logging should continue to redact sensitive remote details as needed.

### GdtConfigService

The service directly edits the root `.env` file.

**P0 - runtime `.env` mutation is production-control sensitive**

Priority: P0  
File: `Modules/Invoices/Services/GdtConfigService.php`  
Evidence: `ensureDefaults()` / `update()` read and write `base_path('.env')`.  
Problem: browser-driven mutation of deployment configuration creates operational and security coupling between application users and infrastructure secrets. It can also fail under immutable/container deployments.  
Impact: accidental credential changes, deployment inconsistency, configuration-cache divergence, and production outage risk.  
Recommendation: preserve existing behavior during refactor compatibility work, but introduce an explicit safe configuration strategy. At minimum gate writes by `invoices-configure`, environment policy, audit logging, and production restrictions. Longer term prefer deployment-managed secrets or an encrypted application settings store for user-managed integration credentials.

## Import / Export Analysis

### Import

`InvoiceImportService` uses FastExcel and manually maps Vietnamese headers.

Duplicate detection currently queries existence by:

- `lookup_code`
- `invoice_number`
- `issued_date`
- `tax_code`

Rows are then inserted individually.

**P1 - import bypasses canonical shared import/export infrastructure**

Priority: P1  
File: `Modules/Invoices/Services/InvoiceImportService.php`  
Evidence: import has its own header mapping, numeric normalization, duplicate handling and row reporting instead of the repository shared import/export foundation.  
Problem: duplicate infrastructure increases maintenance cost and inconsistent behavior across modules.  
Impact: inconsistent validation, reporting, storage, cleanup, and future import/export UX.  
Recommendation: refactor onto `Modules/Shared/Services/ImportExport` while keeping invoice-specific validation and persistence rules inside Invoices.

**P1 - row persistence is not transactionally protected**

Priority: P1  
File: `Modules/Invoices/Services/InvoiceImportService.php`  
Evidence: rows are created one by one; failures are caught per row; no transaction or explicit import atomicity policy is present.  
Problem: partial imports occur by design but the policy is implicit, with no structured error report or retry/idempotency contract.  
Impact: operators may not know which rows succeeded; reruns may create race-condition duplicates.  
Recommendation: define explicit partial-vs-atomic import semantics, use shared import reports, and enforce database-level duplicate protection.

**P1 - numeric conversion uses float**

Priority: P1  
File: `Modules/Invoices/Services/InvoiceImportService.php`  
Evidence: `toDecimal()` returns `floatval()` for money fields.  
Problem: binary floating-point conversion is unsuitable for monetary values before decimal persistence.  
Impact: possible precision drift in imported amounts.  
Recommendation: normalize monetary input as decimal strings and persist to DECIMAL without intermediate float arithmetic.

### Export

Two export paths exist:

- `InvoicesSelectedExport` through Maatwebsite Excel.
- `InvoiceExportService` using PhpSpreadsheet directly for item-oriented exports.

**P1 - export implementations are fragmented**

Priority: P1  
File: `Modules/Invoices/Exports/*`, `Modules/Invoices/Services/InvoiceExportService.php`  
Evidence: module contains multiple export strategies not unified through shared import/export contracts.  
Problem: storage, memory, selected/all scope and lifecycle behavior can diverge.  
Impact: higher maintenance and inconsistent UX.  
Recommendation: converge export orchestration on the shared import/export foundation and retain module-specific mapping only where needed.

**P1 - workbook export is memory-bound**

Priority: P1  
File: `Modules/Invoices/Services/InvoiceExportService.php`  
Evidence: builds a full PhpSpreadsheet workbook in memory before saving.  
Problem: large exports scale poorly.  
Impact: memory exhaustion or long-running request failures.  
Recommendation: use chunked/query-based export or queue large exports, with private temporary storage and retention cleanup.

## Shared Dependencies

Direct/shared dependencies observed:

- Laravel HTTP client.
- Laravel Cache.
- Laravel Queue/Jobs.
- Maatwebsite Excel.
- FastExcel.
- PhpSpreadsheet (indirectly available through Excel stack).
- GDT external API.
- MeInvoice external API.

Canonical shared import/export infrastructure exists elsewhere in the repository but is not yet used by Invoices.

## Model / Migration / Database Analysis

### Model

`Modules\Invoices\Models\Invoices` owns the `invoices` table with appropriate decimal casts for financial values and date cast for `issued_date`.

Naming is plural (`Invoices`) rather than conventional singular `Invoice`. This is a maintainability issue but not sufficient reason to rename because it is a public/internal contract referenced throughout the module.

### Migration

The migration defines:

- DECIMAL money columns.
- enum `invoice_type` (`sold`, `purchase`).
- indexes on `(invoice_type, issued_date)`, `tax_code`, and `invoice_number`.

Strength: money is stored as DECIMAL, complying with repository standards.

**P1 - duplicate identity is not enforced by database constraint**

Priority: P1  
File: `Modules/Invoices/database/migrations/2025_11_21_045614_invoices.php`, `InvoiceImportService.php`  
Evidence: import uses a four-field duplicate check, but migration has no matching unique constraint.  
Problem: application-level `exists()` followed by `create()` is race-prone.  
Impact: duplicate invoices can be inserted under concurrent imports/jobs/retries.  
Recommendation: after validating real production data and intended invoice identity, add a safe unique/index strategy in a new migration and handle duplicate-key conflicts idempotently.

**P1 - query indexes may be incomplete for actual filters**

Priority: P1  
File: migration + `InvoiceService::filteredQuery()`  
Evidence: common filters include `name`, `lookup_code`, date range, tax rate and partial-like searches, but indexes mainly cover invoice type/date, tax code and invoice number.  
Problem: broad LIKE filters and repeated aggregate queries can scan large datasets.  
Impact: admin list/statistics performance degradation.  
Recommendation: profile production query patterns before adding indexes; avoid speculative indexing and consider search-specific strategy only after measurement.

## Security

### Positive controls

- Admin routes use `auth:admin`.
- Named permission middleware is present.
- API routes default to Sanctum.
- GDT token remains in server-side cache.
- PDF file resolution rejects lookup codes where `basename($lookupCode) !== $lookupCode`.
- GDT password is not hydrated back into public Livewire state.

### Main risks

1. Runtime `.env` editing from Livewire flow.
2. Missing action-level authorization on sensitive Livewire methods.
3. Permission names declared in module manifest are not consistently enforced by matching actions.
4. File download access is capability-based only; no record-level ownership requirement was observed. This may be acceptable for an admin accounting module, but intended access policy should be documented explicitly.

## Performance

Main performance risks:

- unbounded `All` pagination;
- repeated aggregate statistics queries;
- dashboard recalculation on every render;
- unbounded `filter()` collection path;
- memory-bound PhpSpreadsheet export;
- per-row duplicate `exists()` query during imports.

Recommended performance direction:

- bounded pagination;
- batch aggregate/statistics evaluation;
- chunked/queued export;
- batch/upsert-aware import strategy;
- query-count tests for invoice list/dashboard.

## Validation and Authorization

Validation is present in:

- `GdtInvoice::searchInvoices()`;
- `GdtLogin::saveGdtConfig()`;
- `GdtLogin::login()`;
- API filter endpoint.

Validation gaps/concerns:

- `HoadonList` query-string filters and `perPage` are not explicitly normalized/validated.
- selected IDs are passed to `whereIn()`; Eloquent parameterization prevents SQL injection, but ID list shape/count should still be validated/bounded.

Authorization gaps are the most important current risk and should be the first refactor slice.

## Transactions, Concurrency and Data Integrity

Current evidence shows:

- import duplicate protection is application-level only;
- no database uniqueness guarantee for the inferred invoice identity;
- per-row import failures are swallowed into callback messages;
- queue support exists, so retries/concurrency must be treated as realistic rather than theoretical.

Recommended controls:

- explicit idempotency rules for sync/import jobs;
- unique database protection after identity validation;
- structured import report;
- transaction boundaries appropriate to import batch semantics;
- retry-safe service design.

## Admin UI / UX Standard Review

Positive observations:

- Page Blade files are shells and Livewire owns interactive behavior.
- Filters, statistics and selection are organized in the list component.
- page-size defaults are sensible except for `All`.

Required improvements during refactor:

- remove unbounded `All` option;
- ensure filter controls use canonical visible-border styling;
- show clear loading/disabled state for export/download/sync;
- align import/export with canonical shared panel where applicable;
- keep configuration/authentication separate from the primary list workspace;
- use dedicated permission-aware action visibility without relying on visibility as authorization.

A rendered screenshot review is still required during implementation because source inspection alone cannot fully verify spacing, responsive layout, loading states or visual consistency.

## Cross-Module Dependencies

No explicit module dependencies are declared in `config/module.php`.

The module currently depends mostly on framework/shared package services rather than business-domain models from other modules. This is healthy module ownership.

Future refactor should add a conceptual dependency on Shared import/export infrastructure only if the module manifest convention requires declaring Shared as a dependency for direct class use.

## Technical Debt

- Legacy `module.json` remains although canonical registration uses `config/module.php`.
- Model class is plural (`Invoices`).
- `InvoiceList` and `InvoiceManager` appear very small and should be verified for placeholder/legacy status before refactor cleanup.
- Multiple export implementations coexist.
- Direct `.env` mutation is tightly coupled to application UI.
- Some services expose unbounded collection methods.

Do not delete or rename these items during analysis.

## Test Coverage

Existing `tests/Feature/InvoicesModuleTest.php` covers:

- module enabled state;
- GDT captcha/login and server-side token cache;
- cursor-based sold/purchase query behavior;
- queue command dispatch;
- Excel import for sold and purchase;
- basic HoadonList display after import.

Important missing coverage:

- route permission matrix;
- denied Livewire config/export/download mutations;
- GDT config mutation safety;
- PDF path traversal/invalid lookup tests;
- API authentication and validation boundaries;
- duplicate import/idempotency/concurrency behavior;
- bounded pagination/tampered `perPage`;
- query-count/performance regression;
- export scope and large-data strategy.

## Documentation Drift

`docs/modules/Invoices/` did not exist before this analysis.

`Modules/Invoices/README.md` exists inside the application module and should be treated as module-local legacy/context documentation. Canonical analysis documentation is now established under `docs/modules/Invoices/`.

Legacy `module.json` also conflicts with current repository architecture expectations. `config/module.php` and `Modules\ModuleServiceProvider` are authoritative.

## Issue List (P0/P1/P2)

### P0

1. Action-level authorization missing around GDT configuration mutation.
2. Runtime `.env` mutation is a production-control risk and requires explicit policy/guardrails.

### P1

1. Permission granularity mismatch between manifest and actual routes/actions.
2. `All` pagination causes unbounded invoice loading.
3. `perPage` query-string input is not allowlist-normalized.
4. Repeated aggregate/statistics queries can amplify database load.
5. Import bypasses Shared import/export infrastructure.
6. Import duplicate detection lacks DB uniqueness/idempotency protection.
7. Import monetary normalization converts through float.
8. Export paths are fragmented and some are memory-bound.
9. Sensitive export/download Livewire actions lack explicit authorization.
10. Test coverage lacks security/authorization/performance regression cases.

### P2

1. Legacy `module.json` cleanup after compatibility verification.
2. Review placeholder/legacy Livewire classes (`InvoiceList`, `InvoiceManager`).
3. Normalize naming/documentation where safe without breaking contracts.
4. Improve observability around queued sync/import/export operations.

## Module Health Summary

| Area | Assessment |
|---|---|
| Module ownership | Good |
| Route/controller structure | Good |
| Service separation | Good with targeted debt |
| Authorization | Needs major improvement |
| Validation | Moderate |
| Data integrity | Needs concurrency/idempotency work |
| Import/export architecture | Needs consolidation |
| Performance | Needs bounded-query improvements |
| Security | High-priority config/action boundary issues |
| Tests | Useful baseline, insufficient for privileged flows |
| Documentation | Newly established |

## Final Recommendation

**Major Refactor**

Rationale:

- Existing architecture is viable and mostly aligned with repository conventions.
- Core business responsibilities are already separated into services.
- A full rebuild would create unnecessary compatibility risk for GDT integration, routes, queue commands, storage paths and import behavior.
- Material security, data integrity and performance issues require more than a minor cleanup.

Recommended refactor order:

```text
1. Authorization + permission alignment
2. GDT configuration safety
3. Bounded pagination/query normalization
4. Import/export consolidation
5. Idempotency + unique data protection
6. Aggregate/query performance
7. UI polish + shared import/export UX
8. Expanded regression tests
9. Legacy cleanup
```

## Open Questions / Unknowns

1. Is production deployment expected to permit application writes to the root `.env` file, or should GDT credentials be deployment-managed only?
2. Is the canonical invoice identity truly `(lookup_code, invoice_number, issued_date, tax_code)`, or does GDT expose a stronger immutable identifier?
3. What is the expected maximum production invoice count and typical date range used by operators?
4. Should `invoices-download` be separate from `invoices-list` for PDF access in all cases?
5. Should configuration and GDT token management be restricted to Super Admin only, or to any role with `invoices-configure`?
6. Are `InvoiceList` and `InvoiceManager` referenced dynamically or safe to classify as legacy placeholders?
7. What retention policy is required for downloaded/generated invoice PDFs and exported workbooks?

These unknowns should be resolved during `/refactor-module Invoices` planning before changing public behavior or schema constraints.
