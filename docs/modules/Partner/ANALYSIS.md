# Partner Module Analysis

## Reading Context

- Read `ROADMAP.md`.
- Read `docs/AI_PROJECT_CONTEXT.md`.
- Read `docs/CODEX_BOOTSTRAP.md`.
- Scope limited to `Modules/Partner`.
- No code refactor was performed.

## 1. Module Purpose

`Modules/Partner` manages business partners in the admin area, including suppliers, customers, hospitals, business households, individuals, contact information, status, manual CRUD, bulk delete, and basic import/export.

Primary data table: `partners`.

## 2. Route List

### Web Routes

File: `Modules/Partner/routes/web.php`

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | `admin/partner/partners` | `admin.partner.partners.index` | `Modules\Partner\Http\Controllers\PartnerController@index` |
| GET | `admin/partner/partners/create` | `admin.partner.partners.create` | `Modules\Partner\Http\Controllers\PartnerController@create` |
| GET | `admin/partner/partners/{id}/edit` | `admin.partner.partners.edit` | `Modules\Partner\Http\Controllers\PartnerController@edit` |

Middleware: `web`, `auth:admin`.

### API Routes

File: `Modules/Partner/routes/api.php`

| Method | URI | Controller |
|---|---|---|
| GET | `partner` | `Modules\Partner\Http\Controllers\Api\PartnerController@index` |

Issue: `Modules/Partner/routes/api.php` registers a public API route without `auth:sanctum` or another guard, and the target controller has no `index()` method.

Recommendation: **P0** - Either remove/disable the API route until implemented or protect it with explicit API authentication and authorization.

## 3. Controllers

File: `Modules/Partner/Http/Controllers/PartnerController.php`

- `index(): View` returns `Partner::pages.index`.
- `create(): View` returns `Partner::pages.create`.
- `edit(int $id): View` returns `Partner::pages.edit` with scalar `id`.

This controller is thin and aligned with the required Route → Controller → Page Blade flow.

File: `Modules/Partner/Http/Controllers/Api/PartnerController.php`

- Empty controller.
- No `index()` method despite `Modules/Partner/routes/api.php` pointing to `index`.

Recommendation: **P0** - Fix or remove the broken public API route/controller pair.

## 4. Page Blade Files

File: `Modules/Partner/resources/views/pages/index.blade.php`

- Extends `Admin::layouts.master`.
- Mounts `@livewire('partner.partner.index')`.
- Comment says "danh sách thuốc", which does not match Partner.

Recommendation: **P2** - Correct stale comments during cleanup.

File: `Modules/Partner/resources/views/pages/create.blade.php`

- Extends `Admin::layouts.master`.
- Mounts `@livewire('partner.partner.form')`.
- Comment says "danh sách thuốc", which does not match Partner.

Recommendation: **P2** - Correct stale comments during cleanup.

File: `Modules/Partner/resources/views/pages/edit.blade.php`

- Extends `Admin::layouts.master`.
- Mounts `@livewire('partner.partner.form', ['partnerId' => $id])`.
- Uses `partnerId` rather than the standard `id` parameter described in project docs.
- Comment says "danh sách thuốc", which does not match Partner.

Recommendation: **P1** - Standardize edit parameter naming to `id` only when refactoring the Livewire form contract.

File: `Modules/Partner/resources/views/partner.blade.php`

- Generic scaffold page.
- Includes `Partner::components.placeholder`.
- No route reference found in the Partner module.

Recommendation: **P2** - Remove after confirming no external route/provider references it.

## 5. Livewire PHP Classes

File: `Modules/Partner/Livewire/Partner/Index.php`

Responsibilities found:

- Filter state: `search`, `legalType`, `partnerType`, `source`, `status`.
- Pagination state: `perPage`.
- Selection state: `selected`, `selectAll`.
- Import upload state: `importFile`.
- Actions: `resetFilters`, `delete`, `deleteSelected`, `import`, `export`.
- Render calls `PartnerService::paginate()`.

Problems:

- Direct model query in `deleteSelected()` via `Partner::query()->whereIn(...)->delete()`.
- Direct model query in `currentPagePartnerIds()`.
- Direct model query and unbounded `get()` in `export()`.
- Direct model writes inside `import()` through `Partner::updateOrCreate()`.
- Import/export mapping, normalization, persistence, and file handling live in Livewire instead of a module `ImportExport` service/shared panel.
- `WithFileUploads` is used directly in the feature component rather than shared import/export UI.
- `perPage = 'All'` can flow into `currentPagePartnerIds()` and `PartnerService::paginate()` without a dataset size guard.
- `currentPagePartnerIds()` duplicates the filtering logic from `PartnerService::paginate()`.

Recommendations:

- **P0** - Move import, export, delete, and bulk delete authorization checks to server-side actions using named permissions such as `delete_partner`, `create_partner`, and export/import permissions if introduced.
- **P1** - Move bulk delete and current-page ID lookup into `PartnerService`.
- **P1** - Replace custom Livewire import/export with `Modules/Partner/Services/ImportExport.php` and the shared `shared.import-export.panel`.
- **P1** - Guard or cap `All` pagination/export behavior for large datasets.
- **P1** - Add transactions for bulk delete and import persistence.

File: `Modules/Partner/Livewire/Partner/Form.php`

Responsibilities found:

- Form state for partner fields.
- Loads existing partner via `PartnerService::findOrFail()`.
- Validates form input in `rules()`.
- Calls `PartnerService::create()` or `PartnerService::update()`.

Problems:

- Uses model constants directly in validation and render arrays.
- No visible authorization checks for create/update.
- No explicit service-level validation/invariant enforcement for enum values beyond Livewire.
- Edit parameter is `partnerId`, while project standard examples use `id`.

Recommendations:

- **P0** - Add server-side authorization for create/update before persistence.
- **P1** - Add service-level invariants for enum/status fields so non-Livewire callers cannot bypass validation.
- **P2** - Align edit parameter naming with module standards during refactor.

## 6. Livewire Blade Views

File: `Modules/Partner/resources/views/livewire/partner/index.blade.php`

Contains:

- Header and create button.
- Import/export/bulk delete tools.
- Filters.
- Table.
- Manual pagination UI.
- Delete confirmation via `wire:confirm`.

Problems:

- Import/export UI is custom and bypasses shared import/export architecture.
- Bulk delete button relies on UI disabled state plus Livewire action; server-side authorization/validation still required.
- Pagination renders every page with `$partners->getUrlRange(1, $partners->lastPage())`, which can create huge pagination output on large datasets.
- Delete action has confirmation, but no visible permission gate around row actions.

Recommendations:

- **P0** - Add permission checks around delete/bulk delete/import/export actions server-side.
- **P1** - Replace custom import/export controls with shared import/export panel.
- **P1** - Replace full-page-range pagination rendering with Laravel pagination links or a bounded window.

File: `Modules/Partner/resources/views/livewire/partner/form.blade.php`

Contains:

- Create/edit form with Partner identity, classification, contact, status, and note fields.
- Field-level validation messages.
- Loading/disabled save state.

Problems:

- No visible permission-aware UI state for create/update.
- UI has repeated input class strings; acceptable now, but reusable components could reduce duplication later.

Recommendations:

- **P0** - Enforce create/update permissions in Livewire action, not only UI.
- **P2** - Consider shared form input components after behavior is stabilized.

## 7. Shared Components

File: `Modules/Partner/resources/views/components/placeholder.blade.php`

- Scaffold placeholder only.
- Used by `Modules/Partner/resources/views/partner.blade.php`.

File: `Modules/Partner/resources/views/livewire/placeholder.blade.php`

- Scaffold placeholder only.
- No module reference found.

Recommendations:

- **P2** - Remove placeholders after confirming no provider or external reference depends on them.

## 8. Services and Public Methods

File: `Modules/Partner/Services/PartnerService.php`

Public methods:

- `paginate(array $filters = [], int|string $perPage = 10)`
- `create(array $data): Partner`
- `update(Partner $partner, array $data): Partner`
- `delete(Partner $partner): bool`
- `find(int $id): ?Partner`
- `findOrFail(int $id): Partner`
- `options(): array`

Private helpers:

- `normalizeData(array $data): array`
- `nullableString(?string $value): ?string`

Problems:

- `paginate()` returns unbounded `get()` when `$perPage === 'All'`.
- Service does not validate enum/status values or required business invariants.
- Service does not own import/export or bulk delete.
- No transaction use around writes. Single create/update/delete may be acceptable, but import and bulk delete are currently outside the service.
- Search/filter query duplicated in `Modules/Partner/Livewire/Partner/Index.php`.

Recommendations:

- **P1** - Add service methods for bulk delete and current-page/filtered ID selection.
- **P1** - Add bounded handling for `All`.
- **P1** - Move import/export orchestration into `Modules/Partner/Services/ImportExport.php`.
- **P1** - Add service-level validation or explicit invariants for enum-like fields.

## 9. Models and Database Tables

File: `Modules/Partner/Models/Partner.php`

Table: `partners`.

Fillable:

- `tax_code`
- `name`
- `legal_type`
- `partner_types`
- `phone`
- `email`
- `contact_person`
- `address`
- `source`
- `status`
- `note`

Casts:

- `partner_types` as `array`.

Constants:

- `LEGAL_TYPES`: `company`, `business_household`, `hospital`, `individual`, `other`
- `PARTNER_TYPES`: `supplier`, `customer`
- `SOURCES`: `manual`, `import`, `system`
- `STATUSES`: `active`, `inactive`, `pending`

Accessors:

- `legal_type_label`
- `source_label`
- `status_label`
- `partner_type_labels`

Problems:

- Enum-like constants are useful for display, but database does not constrain them.
- Model accessors contain display mapping; acceptable for small labels, but business invariants still need service validation.
- No relationships are defined. None are evident in this module alone.

Recommendations:

- **P1** - Keep enum values validated at Livewire and service boundaries.
- **P2** - Consider database-level constraints only after confirming supported MySQL version and migration policy.

## 10. Import/Export Classes

No dedicated import/export classes found under `Modules/Partner/Import`, `Modules/Partner/Export`, or `Modules/Partner/Services/ImportExport.php`.

Current import/export is implemented directly in:

- `Modules/Partner/Livewire/Partner/Index.php`

Problems:

- Import file validation is minimal.
- No header mapping report.
- No row-level validation report.
- No dry-run mode.
- No confirmed unique key strategy beyond `tax_code`.
- `updateOrCreate()` with nullable `tax_code` risks merging or overwriting records with `tax_code = null`.
- No transaction strategy.
- Export loads all records into memory and writes to `storage/app/public/{filename}` instead of the shared export directory convention.

Recommendations:

- **P0** - Stop using nullable `tax_code` as an unsafe upsert key during import until behavior is confirmed.
- **P1** - Implement `Modules/Partner/Services/ImportExport.php` using the shared import/export foundation.
- **P1** - Define confirmed headers, aliases, unique key, import mode, dry-run behavior, null overwrite behavior, transaction strategy, and export columns.
- **P1** - Store exports through the shared export storage foundation.

## 11. Authorization/Security Risks

- File: `Modules/Partner/routes/api.php` - Public API route has no authentication/authorization and points to a missing method. Recommendation: **P0** - remove, disable, or protect it.
- File: `Modules/Partner/Livewire/Partner/Index.php` - `delete()`, `deleteSelected()`, `import()`, and `export()` do not show named permission checks. Recommendation: **P0** - enforce named permissions server-side.
- File: `Modules/Partner/Livewire/Partner/Form.php` - `save()` does not show create/update permission checks. Recommendation: **P0** - enforce named permissions server-side.
- File: `Modules/Partner/Livewire/Partner/Index.php` - Import accepts browser-supplied file data and persists rows without full row-level validation/reporting. Recommendation: **P1** - migrate to shared import/export service with safe validation/reporting.
- File: `Modules/Partner/Livewire/Partner/Index.php` - Export writes a public storage file and immediately downloads it. Recommendation: **P1** - use shared export storage and review access/retention policy.

## 12. Validation Problems

- File: `Modules/Partner/Livewire/Partner/Index.php` - Import validates only file presence/type and does not validate row fields such as email, status, legal type, partner types, or string lengths. Recommendation: **P1** - add import row validation in module import/export service.
- File: `Modules/Partner/Livewire/Partner/Index.php` - Import defaults missing `legal_type`, `partner_types`, `source`, and `status` silently. Recommendation: **P1** - define confirmed defaulting rules before import refactor.
- File: `Modules/Partner/Livewire/Partner/Index.php` - `updateOrCreate()` uses `tax_code` even when null. Recommendation: **P0** - define a non-null business unique key or skip/error rows missing the unique key.
- File: `Modules/Partner/Services/PartnerService.php` - Service normalizes but does not validate enum-like values. Recommendation: **P1** - add service-level invariants for non-UI callers.
- File: `Modules/Partner/Livewire/Partner/Form.php` - Phone accepts any string up to 50 characters. Recommendation: **P2** - confirm phone format rules before tightening validation.

## 13. Transaction Risks

- File: `Modules/Partner/Livewire/Partner/Index.php` - Import performs many independent `updateOrCreate()` calls without an explicit all-or-nothing or partial-success strategy. Recommendation: **P1** - move import persistence into a transaction-aware service after confirming desired behavior.
- File: `Modules/Partner/Livewire/Partner/Index.php` - Bulk delete directly deletes selected IDs without service-owned transaction/audit boundary. Recommendation: **P1** - move bulk delete to `PartnerService` and wrap in a transaction if related cleanup/audit is added.
- File: `Modules/Partner/Services/PartnerService.php` - Create/update/delete are single writes today, but service has no transaction pattern for future multi-write Partner operations. Recommendation: **P2** - add transactions only when operations become multi-write or require audit.

## 14. N+1/Query Performance Risks

- File: `Modules/Partner/Services/PartnerService.php` - `paginate(..., 'All')` returns all matching partners. Recommendation: **P1** - cap, warn, disable, or chunk large datasets.
- File: `Modules/Partner/Livewire/Partner/Index.php` - `export()` loads all partners with `get()` before export. Recommendation: **P1** - use chunked/lazy export through shared service.
- File: `Modules/Partner/Livewire/Partner/Index.php` - `currentPagePartnerIds()` duplicates the list query and may run extra queries whenever selection state changes. Recommendation: **P1** - centralize filtered query building in the service.
- File: `Modules/Partner/resources/views/livewire/partner/index.blade.php` - Pagination renders every page number via `getUrlRange(1, lastPage())`. Recommendation: **P1** - use bounded pagination links for large result sets.
- File: `Modules/Partner/database/migrations/2026_05_26_095912_partners.php` - Search covers `tax_code`, `phone`, `email`, and `contact_person`, but only `name`, `legal_type`, `status`, and `source` are indexed. Recommendation: **P2** - review real query volume/selectivity before adding indexes.

## 15. Duplicate Logic

- File: `Modules/Partner/Livewire/Partner/Index.php` duplicates search/filter query logic from `Modules/Partner/Services/PartnerService.php`.
  - Recommendation: **P1** - centralize query construction in `PartnerService`.
- File: `Modules/Partner/Livewire/Partner/Index.php` duplicates normalization helpers that belong in service/import logic.
  - Recommendation: **P1** - move import normalization to module import/export service.
- File: `Modules/Partner/resources/views/livewire/partner/index.blade.php` and `Modules/Partner/resources/views/livewire/partner/form.blade.php` repeat long Tailwind input/button classes.
  - Recommendation: **P2** - consider shared Blade components after behavioral refactor.

## 16. Files That Look Unused

These files appear scaffold-like or unreferenced within `Modules/Partner`; confirm with route/provider discovery before deletion.

- `Modules/Partner/resources/views/partner.blade.php`
  - Evidence: no Partner route points to it; it only includes placeholder content.
  - Recommendation: **P2** - remove after confirming no external reference.
- `Modules/Partner/resources/views/livewire/placeholder.blade.php`
  - Evidence: no module reference found.
  - Recommendation: **P2** - remove after confirming no Livewire alias/provider reference.
- `Modules/Partner/resources/views/components/placeholder.blade.php`
  - Evidence: only used by `Modules/Partner/resources/views/partner.blade.php`.
  - Recommendation: **P2** - remove together with scaffold page if unused.
- `Modules/Partner/Http/Controllers/Api/PartnerController.php`
  - Evidence: empty and broken target for public API route.
  - Recommendation: **P0** - remove/disable or implement securely.
- `Modules/Partner/routes/api.php`
  - Evidence: only registers a public broken route.
  - Recommendation: **P0** - remove/disable or secure and implement.

## 17. Migration

File: `Modules/Partner/database/migrations/2026_05_26_095912_partners.php`

Creates table `partners` with:

- `id`
- `tax_code` nullable unique string
- `name`
- `legal_type`
- `partner_types` JSON nullable
- `phone`
- `email`
- `contact_person`
- `address`
- `source`
- `status`
- `note`
- timestamps
- indexes: `name`, `legal_type`, `status`, `source`

Problems:

- Nullable unique `tax_code` is allowed by schema, but import uses it as the upsert key, creating ambiguity for rows without tax code.
- No comments on table or important enum-like columns, despite project database standards.
- No index on `email`, `phone`, `contact_person`, or `tax_code` for the search pattern except the unique index on `tax_code`.
- JSON `partner_types` supports flexible multi-role partners but `whereJsonContains()` index behavior depends on MySQL features and may be slow at scale.

Recommendations:

- **P0** - Do not use nullable `tax_code` as an import upsert key without confirmed behavior.
- **P1** - Add migration comments and confirm enum values when a schema refactor is approved.
- **P2** - Review search indexes after measuring real data volume and query plans.
- **P2** - Keep JSON `partner_types` unless reporting/filtering scale proves a pivot table is needed.

## Refactor Plan

### P0 Critical

- **P0** - `Modules/Partner/routes/api.php`: remove/disable the public API route or secure it with authentication/authorization and a real controller method.
- **P0** - `Modules/Partner/Http/Controllers/Api/PartnerController.php`: remove/disable or implement securely with explicit authorization.
- **P0** - `Modules/Partner/Livewire/Partner/Index.php`: add server-side permission checks for delete, bulk delete, import, and export.
- **P0** - `Modules/Partner/Livewire/Partner/Form.php`: add server-side permission checks for create and update.
- **P0** - `Modules/Partner/Livewire/Partner/Index.php`: stop importing with nullable `tax_code` as the `updateOrCreate()` key until the business unique key and missing-tax-code behavior are confirmed.

### P1 Important

- **P1** - `Modules/Partner/Services/PartnerService.php`: centralize all Partner queries, including filtered IDs and bulk delete.
- **P1** - `Modules/Partner/Livewire/Partner/Index.php`: remove direct model queries and direct persistence from Livewire.
- **P1** - `Modules/Partner/Services/ImportExport.php`: create module import/export service using the shared import/export foundation.
- **P1** - `Modules/Partner/resources/views/livewire/partner/index.blade.php`: replace custom import/export UI with `shared.import-export.panel`.
- **P1** - `Modules/Partner/Livewire/Partner/Index.php`: define import headers, aliases, unique key, mode, dry-run, null-overwrite behavior, row validation, and transaction strategy before implementation.
- **P1** - `Modules/Partner/Services/PartnerService.php`: guard `All` pagination and large exports.
- **P1** - `Modules/Partner/resources/views/livewire/partner/index.blade.php`: replace full page range pagination rendering with bounded pagination.
- **P1** - `Modules/Partner/Services/PartnerService.php`: add service-level invariants for enum-like fields.
- **P1** - `Modules/Partner/database/migrations/2026_05_26_095912_partners.php`: add schema comments and review constraints in a future migration.

### P2 Nice To Have

- **P2** - `Modules/Partner/resources/views/pages/index.blade.php`: fix stale "thuốc" comment.
- **P2** - `Modules/Partner/resources/views/pages/create.blade.php`: fix stale "thuốc" comment.
- **P2** - `Modules/Partner/resources/views/pages/edit.blade.php`: fix stale "thuốc" comment and align parameter naming during refactor.
- **P2** - `Modules/Partner/resources/views/partner.blade.php`: remove scaffold page after confirming unused.
- **P2** - `Modules/Partner/resources/views/components/placeholder.blade.php`: remove scaffold placeholder after confirming unused.
- **P2** - `Modules/Partner/resources/views/livewire/placeholder.blade.php`: remove scaffold placeholder after confirming unused.
- **P2** - `Modules/Partner/resources/views/livewire/partner/form.blade.php`: consider shared input components to reduce repeated UI classes.
- **P2** - `Modules/Partner/database/migrations/2026_05_26_095912_partners.php`: review optional indexes for `email`, `phone`, and `contact_person` after measuring query volume.
