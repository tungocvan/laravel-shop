# Partner Rebuild Specification

## 1. Goal

The rebuilt/refactored `Partner` module must provide a secure, maintainable Laravel 12 and Livewire 3 admin workflow for managing Partner records in the `partners` table.

The refactor must achieve these outcomes:

- Secure every Partner entry point and mutating action with server-side authorization. Reference: `ANALYSIS.md` sections 10-12 and `REFACTOR_PLAN.md` P0-01 through P0-05.
- Preserve the required module flow and move business logic out of Livewire into services. Reference: `ANALYSIS.md` sections 5, 8, 13, 15 and `REFACTOR_PLAN.md` P1-01, P1-02, P1-05.
- Replace custom Livewire import/export behavior with the shared import/export foundation. Reference: `ANALYSIS.md` section 10 and `REFACTOR_PLAN.md` P1-03, P1-04, P1-11.
- Prevent unsafe imports caused by nullable `tax_code` upserts. Reference: `ANALYSIS.md` sections 10, 12, 17 and `REFACTOR_PLAN.md` P0-04.
- Make Partner list, selection, pagination, bulk delete, import, and export bounded and testable. Reference: `ANALYSIS.md` sections 13-15 and `REFACTOR_PLAN.md` P1-02, P1-05, P1-06, P1-09.
- Keep Partner as the canonical owner for Partner CRUD and the `partners` table. Reference: `REFACTOR_PLAN.md` executive summary and risk control.

Needs confirmation before coding:

- Whether Partner should expose any API at all. Reference: `REFACTOR_PLAN.md` P0-01, P0-05, P2-03.
- Import unique key, import mode, dry-run behavior, null-overwrite behavior, and partial versus all-or-nothing persistence. Reference: `REFACTOR_PLAN.md` P0-04 and P1-04.
- Whether phone validation should remain loose or follow a confirmed Vietnamese/international format. Reference: `ANALYSIS.md` section 12 and `REFACTOR_PLAN.md` P2-07.

## 2. Target Architecture

Target flow:

```text
Route
→ Controller
→ Page Blade
→ Livewire PHP
→ Livewire Blade
→ Shared Components
→ Service
→ Import
→ Export
→ Model
→ Migration
```

### Route

- `Modules/Partner/routes/web.php` remains the admin web route owner.
- Web routes keep `web` and `auth:admin` middleware.
- Route definitions stay limited to URI, route name, middleware, and controller action.
- The API route in `Modules/Partner/routes/api.php` must be removed/disabled or secured and implemented. Reference: `ANALYSIS.md` sections 2, 3, 16 and `REFACTOR_PLAN.md` P0-01, P0-05, P2-03.

### Controller

- `Modules/Partner/Http/Controllers/PartnerController.php` remains a thin controller returning page views and scalar IDs.
- `Modules/Partner/Http/Controllers/Api/PartnerController.php` must not remain as an empty public scaffold. Reference: `REFACTOR_PLAN.md` P0-05.

### Page Blade

- `Modules/Partner/resources/views/pages/index.blade.php`, `create.blade.php`, and `edit.blade.php` remain page shells.
- Page files must not query models or call services.
- Edit page should pass a scalar `id` after refactor. Reference: `ANALYSIS.md` section 4 and `REFACTOR_PLAN.md` P1-08.

### Livewire PHP

- `Modules/Partner/Livewire/Partner/Index.php` owns list UI state only and calls services for data operations.
- `Modules/Partner/Livewire/Partner/Form.php` owns form UI state, Livewire validation, and service calls.
- Direct `Partner::query()`, `Partner::updateOrCreate()`, import mapping, export mapping, and transactions must be removed from Livewire. Reference: `ANALYSIS.md` sections 5, 10, 13, 15 and `REFACTOR_PLAN.md` P1-01.

### Livewire Blade

- Livewire views render table, filters, form fields, validation errors, loading states, and action controls.
- Livewire Blade must not implement business rules or rely on UI-only authorization. Reference: `ANALYSIS.md` sections 6, 11 and `REFACTOR_PLAN.md` P0-02, P0-03.

### Shared Components

- Import/export UI should use `shared.import-export.panel` with a module `serviceClass`. Reference: `ANALYSIS.md` sections 6, 10 and `REFACTOR_PLAN.md` P1-03.
- Shared form/input components may be introduced later only after behavior is stable. Reference: `REFACTOR_PLAN.md` P2-04.

### Service

- `Modules/Partner/Services/PartnerService.php` owns queries, pagination, filters, create/update/delete, bulk delete, current-page ID lookup, normalization, service-level invariants, and transaction boundaries.
- `Modules/Partner/Services/ImportExport.php` owns module import/export orchestration.
- Services must accept arrays/scalars/models and must not depend on Livewire state. Reference: `ANALYSIS.md` section 8 and `REFACTOR_PLAN.md` P1-01, P1-02, P1-07.

### Import

- Import is handled through `Modules/Partner/Services/ImportExport.php`, with dedicated import helper classes only if the implementation grows beyond a small service.
- Import persistence must not use nullable `tax_code` as an upsert key. Reference: `ANALYSIS.md` section 10 and `REFACTOR_PLAN.md` P0-04.

### Export

- Export is handled through `Modules/Partner/Services/ImportExport.php` and shared export storage under `storage/app/public/exports`.
- Export must use bounded iteration for large datasets. Reference: `ANALYSIS.md` sections 10, 14 and `REFACTOR_PLAN.md` P1-06, P1-11.

### Model

- `Modules/Partner/Models/Partner.php` remains the Eloquent model for `partners`.
- Model remains ORM-focused: fillable, casts, constants, simple accessors, and possible export exclusions.
- Business invariants belong in services. Reference: `ANALYSIS.md` section 9 and `REFACTOR_PLAN.md` P1-07.

### Migration

- Existing migration `Modules/Partner/database/migrations/2026_05_26_095912_partners.php` is the schema source.
- Future schema changes should be additive migrations unless project migration policy allows editing historical migrations. Reference: `REFACTOR_PLAN.md` P1-10 and Risk Control.

## 3. Database Design

### Tables

Primary table: `partners`.

Reference: `ANALYSIS.md` section 17.

### Columns

Existing columns:

| Column | Current Design | Target Decision |
|---|---|---|
| `id` | Primary key | Keep. |
| `tax_code` | Nullable string, unique | Keep nullable until business rules confirm otherwise; do not use null as import upsert key. Reference: `REFACTOR_PLAN.md` P0-04. |
| `name` | Required string | Keep required. |
| `legal_type` | String default `company` | Keep enum-like string; validate at Livewire and service boundaries. Reference: `REFACTOR_PLAN.md` P1-07. |
| `partner_types` | Nullable JSON | Keep JSON for now. Do not normalize without evidence. Reference: `REFACTOR_PLAN.md` P2-06. |
| `phone` | Nullable string | Keep until phone rules are confirmed. Reference: `REFACTOR_PLAN.md` P2-07. |
| `email` | Nullable string | Keep; validate email format in form/import. |
| `contact_person` | Nullable string | Keep. |
| `address` | Nullable text | Keep. |
| `source` | String default `manual` | Keep enum-like string; validate at service/import boundaries. |
| `status` | String default `active` | Keep enum-like string; validate at service/import boundaries. |
| `note` | Nullable text | Keep; exclude from public API/export if business says internal notes are sensitive. Needs confirmation before coding. |
| `created_at`, `updated_at` | Timestamps | Keep. |

### Indexes

Existing indexes:

- Unique index on `tax_code`.
- Index on `name`.
- Index on `legal_type`.
- Index on `status`.
- Index on `source`.

Target decisions:

- Do not add speculative indexes before measuring data volume and query plans. Reference: `ANALYSIS.md` section 14 and `REFACTOR_PLAN.md` P2-05.
- Review potential indexes for `email`, `phone`, and `contact_person` only after query plan evidence.
- JSON filtering on `partner_types` remains acceptable until scale proves otherwise. Reference: `REFACTOR_PLAN.md` P2-06.

### Foreign Keys

- No foreign keys are evident in the current Partner module. Reference: `ANALYSIS.md` section 9.
- Do not add relationships or foreign keys without confirmed business ownership.

### Constraints

- Keep database unique constraint on `tax_code`.
- Add service/import validation for enum-like values because database does not constrain `legal_type`, `partner_types`, `source`, or `status`. Reference: `ANALYSIS.md` sections 9, 12 and `REFACTOR_PLAN.md` P1-07.
- Database-level enum/check constraints need confirmation before coding due to MySQL compatibility and migration policy. Reference: `ANALYSIS.md` section 9 and `REFACTOR_PLAN.md` P1-10.

### Migration Notes

- Do not edit historical migration `Modules/Partner/database/migrations/2026_05_26_095912_partners.php` unless project policy explicitly permits it. Reference: `REFACTOR_PLAN.md` Risk Control.
- Add future migration for comments on status/source/type fields if approved. Reference: `REFACTOR_PLAN.md` P1-10.
- Add future migration for measured indexes only after query review. Reference: `REFACTOR_PLAN.md` P2-05.

## 4. Model Design

### Model Classes

- `Modules/Partner/Models/Partner.php`

Reference: `ANALYSIS.md` section 9.

### Fillable Fields

Keep:

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

Decision reference: current model in `ANALYSIS.md` section 9.

### Casts

Keep:

- `partner_types` => array

Decision reference: JSON partner type design in `ANALYSIS.md` section 17 and `REFACTOR_PLAN.md` P2-06.

### Relationships

- No relationships are required now.
- Needs confirmation before coding if Partner must connect to orders, products, invoices, admissions, or users.

Reference: `ANALYSIS.md` section 9.

### Scopes

Target service-owned filtering is preferred.

Optional scopes may be considered only if they simplify repeated query logic without moving business rules into the model:

- active status scope
- search scope
- partner type scope

Needs confirmation before coding because `REFACTOR_PLAN.md` P1-02 recommends centralizing query construction in `PartnerService`, not model expansion.

### Accessors / Mutators

Keep simple display accessors:

- `legal_type_label`
- `source_label`
- `status_label`
- `partner_type_labels`

Reference: `ANALYSIS.md` section 9.

Do not add mutators for business normalization unless there is a clear model-level ORM need. Normalization belongs in services/import logic. Reference: `REFACTOR_PLAN.md` P1-01 and P1-07.

Optional:

- Add export exclusion metadata for sensitive fields if shared export foundation uses `$exceptExport`. Needs confirmation before coding. Reference: `AI_PROJECT_CONTEXT.md` export rules and `REFACTOR_PLAN.md` P1-03.

## 5. Service Design

### Service Classes

Required:

- `Modules/Partner/Services/PartnerService.php`
- `Modules/Partner/Services/ImportExport.php`

Optional, only if implementation grows:

- `Modules/Partner/Import/RowMapper.php`
- `Modules/Partner/Import/RowNormalizer.php`
- `Modules/Partner/Import/RowValidator.php`
- `Modules/Partner/Export/ExportQuery.php`
- `Modules/Partner/Export/ExportMapper.php`
- `Modules/Partner/Export/TemplateBuilder.php`

Reference: `ANALYSIS.md` section 10 and `REFACTOR_PLAN.md` P1-03.

### Public Methods

`PartnerService` target public methods:

- `paginate(array $filters = [], int|string $perPage = 10)`
- `create(array $data): Partner`
- `update(Partner $partner, array $data): Partner`
- `delete(Partner $partner): bool`
- `bulkDelete(array $ids): int`
- `find(int $id): ?Partner`
- `findOrFail(int $id): Partner`
- `options(): array`
- `currentPageIds(array $filters, int|string $perPage, int $page): array`
- `filteredQuery(array $filters)` or equivalent internal query builder method

References:

- Existing methods from `ANALYSIS.md` section 8.
- Bulk delete/current page IDs from `REFACTOR_PLAN.md` P1-01, P1-02.
- Bounded `All` from `REFACTOR_PLAN.md` P1-06.

`ImportExport` target public surface should follow shared base service contracts. The exact method names must follow `Modules/Shared/Services/ImportExport/BaseImportExportService.php`. Needs confirmation by reading shared base before coding.

### Responsibilities

`PartnerService` owns:

- Query construction.
- Search/filter/sort/pagination.
- Current-page ID lookup.
- Create/update/delete.
- Bulk delete.
- Data normalization.
- Service-level enum validation.
- Transaction boundaries for multi-write operations.

References: `ANALYSIS.md` sections 8, 13, 15 and `REFACTOR_PLAN.md` P1-01, P1-02, P1-05, P1-07.

`ImportExport` owns:

- Import/export orchestration.
- Header aliases.
- Row normalization.
- Row validation.
- Duplicate handling.
- Export mapping.
- Template generation if needed.
- Shared storage integration.

References: `ANALYSIS.md` section 10 and `REFACTOR_PLAN.md` P1-03, P1-04, P1-11.

### Transaction Boundaries

Transactions required:

- Bulk delete if deleting multiple records or adding audit/related cleanup. Reference: `REFACTOR_PLAN.md` P1-05.
- Import persistence when all-or-nothing behavior is confirmed. Reference: `ANALYSIS.md` section 13 and `REFACTOR_PLAN.md` P1-05.

Transactions not required:

- Simple reads.
- Single create/update/delete unless future behavior adds related writes or audit requirements.

Needs confirmation before coding:

- Import should be all-or-nothing or partial row success. Reference: `REFACTOR_PLAN.md` P1-04, P1-05.
- Audit logging requirements for destructive actions. Reference: roadmap P0 acceptance criteria and `REFACTOR_PLAN.md` P1-05.

### Business Rules

Confirmed:

- `name` is required.
- `legal_type`, `partner_types`, `source`, and `status` must match `Partner` constants.
- Empty strings should normalize to null for nullable string fields.
- `partner_types` remains an array/JSON value.

Needs confirmation before coding:

- Whether `tax_code` is required for imported rows.
- Whether duplicate `tax_code` should update, skip, or error.
- Whether `note` is exportable.
- Phone number format.

References: `ANALYSIS.md` sections 9, 12 and `REFACTOR_PLAN.md` P0-04, P1-04, P1-07, P2-07.

## 6. Livewire Design

### Component List

- `Modules/Partner/Livewire/Partner/Index.php`
- `Modules/Partner/Livewire/Partner/Form.php`
- Shared import/export panel: `shared.import-export.panel`

References: `ANALYSIS.md` sections 5, 6 and `REFACTOR_PLAN.md` P1-03.

### State Properties

`Index.php` target state:

- `search`
- `legalType`
- `partnerType`
- `source`
- `status`
- `perPage`
- `selected`
- `selectAll`

Remove from `Index.php`:

- Direct import file processing state if shared import/export panel owns upload. Reference: `ANALYSIS.md` section 5 and `REFACTOR_PLAN.md` P1-03.

`Form.php` target state:

- `id` or compatible `partnerId` during transition
- `partner`
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

Reference: `ANALYSIS.md` section 5 and `REFACTOR_PLAN.md` P1-08.

### Validation Rules

Form validation:

- `tax_code`: nullable string max 50 unique ignoring current record.
- `name`: required string max 255.
- `legal_type`: required in `Partner::LEGAL_TYPES`.
- `partner_types`: required array min 1.
- `partner_types.*`: required in `Partner::PARTNER_TYPES`.
- `phone`: nullable string max 50 until phone rules confirmed.
- `email`: nullable email max 255.
- `contact_person`: nullable string max 255.
- `address`: nullable string max 1000.
- `source`: required in `Partner::SOURCES`.
- `status`: required in `Partner::STATUSES`.
- `note`: nullable string max 2000.

Reference: existing rules in `ANALYSIS.md` section 5.

Service/import must repeat business invariants for non-Livewire callers. Reference: `REFACTOR_PLAN.md` P1-07.

### Events

Current module does not require cross-component events.

Optional events:

- Refresh list after shared import completes.
- Reset selection after bulk delete.

Needs confirmation before coding by checking shared import/export panel event API. Reference: `REFACTOR_PLAN.md` P1-03.

### Pagination

Target:

- Default `perPage` = 10.
- Options: 10, 25, 50, 100, `All`.
- Reset page when filters or per-page change.
- `All` must be capped, disabled, or warned when unsafe.
- Pagination rendering must use Laravel/Livewire bounded pagination links or a bounded custom window.

References: `ANALYSIS.md` section 14 and `REFACTOR_PLAN.md` P1-06, P1-09.

### Search/Filter/Sort Behavior

Search fields:

- `name`
- `tax_code`
- `phone`
- `email`
- `contact_person`

Filters:

- `legal_type`
- `partner_type` via JSON contains
- `source`
- `status`

Sort:

- Default latest `id`.

All query logic belongs in `PartnerService`. Reference: `ANALYSIS.md` sections 8, 14, 15 and `REFACTOR_PLAN.md` P1-01, P1-02.

## 7. Blade/UI Design

### Page Blade Files

- `Modules/Partner/resources/views/pages/index.blade.php`
- `Modules/Partner/resources/views/pages/create.blade.php`
- `Modules/Partner/resources/views/pages/edit.blade.php`

Target:

- Continue extending `Admin::layouts.master`.
- Keep pages as shells that mount Livewire.
- Fix stale comments. Reference: `ANALYSIS.md` section 4 and `REFACTOR_PLAN.md` P2-01.

### Livewire Blade Files

- `Modules/Partner/resources/views/livewire/partner/index.blade.php`
- `Modules/Partner/resources/views/livewire/partner/form.blade.php`

Target:

- Keep responsive Tailwind admin layout.
- Remove custom import/export controls after shared panel integration.
- Keep delete confirmation.
- Render permission-aware action states only as a UI enhancement; authorization must remain server-side.

References: `ANALYSIS.md` section 6 and `REFACTOR_PLAN.md` P0-02, P0-03, P1-03.

### Shared Components

Required:

- `shared.import-export.panel` for import/export.

Optional:

- Shared input/button components after behavior is stable.

Reference: `REFACTOR_PLAN.md` P1-03 and P2-04.

### AdminLTE/Bootstrap Layout Rules

Active project context says new admin UI uses Tailwind CSS 4 and `Admin::layouts.master`, and says do not use Bootstrap for new work.

Decision:

- Do not introduce Bootstrap or jQuery.
- Keep compatibility with existing `Admin::layouts.master`.
- Use Tailwind classes consistent with current Partner views.

Reference: `AI_PROJECT_CONTEXT.md` Admin UI standard and `REFACTOR_PLAN.md` Risk Control.

### Table Design

Target table:

- Responsive horizontal overflow.
- Columns: selection, partner identity, classification, contact, status, actions.
- Empty state.
- Loading state.
- Bounded pagination controls.
- Bulk delete button with confirmation and server-side authorization.

References: `ANALYSIS.md` section 6 and `REFACTOR_PLAN.md` P0-02, P1-06, P1-09.

### Form Design

Target form:

- Sections: main info, contact info, internal note.
- Field-level validation errors.
- Loading and disabled save state.
- Back/cancel controls.
- Server-side authorization in Livewire action.

Reference: `ANALYSIS.md` section 6 and `REFACTOR_PLAN.md` P0-03.

## 8. Import Design

### Import Classes

Required:

- `Modules/Partner/Services/ImportExport.php`

Optional if complexity grows:

- `Modules/Partner/Import/RowMapper.php`
- `Modules/Partner/Import/RowNormalizer.php`
- `Modules/Partner/Import/RowValidator.php`

Reference: `REFACTOR_PLAN.md` P1-03.

### Header Mapping

Known current headers:

- `name`
- `Tên đối tác`
- `tax_code`
- `Mã số thuế`
- `partner_types`
- `legal_type`
- `address`
- `email`
- `phone`
- `contact_person`
- `source`
- `status`
- `note`

Needs confirmation before coding:

- Final canonical headers.
- Vietnamese aliases for every field.
- Whether headers are required or positional A/B/C mapping is needed.

Reference: `ANALYSIS.md` section 10 and `REFACTOR_PLAN.md` P1-04.

### Column Mapping

No positional column mapping is confirmed.

Decision:

- Use header-based mapping unless a sample file proves headers are unstable.
- Needs confirmation before coding with a sample or real Excel file.

Reference: `AI_PROJECT_CONTEXT.md` import rules and `REFACTOR_PLAN.md` P1-04.

### Row Normalization

Target normalization:

- Trim strings.
- Convert empty strings to null for nullable fields.
- Normalize `partner_types` from comma-separated string or array to array of allowed keys.
- Normalize `legal_type`, `source`, and `status` to allowed keys only.
- Normalize email by trimming and lowercasing if business approves.
- Phone normalization needs confirmation before coding.

References: `ANALYSIS.md` sections 10, 12, 15 and `REFACTOR_PLAN.md` P1-04, P1-07, P2-07.

### Row Validation

Target validation:

- `name`: required.
- Import unique key: Needs confirmation before coding.
- `tax_code`: required or nullable depending on confirmed unique key behavior.
- `legal_type`: allowed value.
- `partner_types`: non-empty allowed values.
- `source`: allowed value.
- `status`: allowed value.
- `email`: valid email.
- string length checks aligned with form rules.

Reference: `ANALYSIS.md` section 12 and `REFACTOR_PLAN.md` P0-04, P1-04, P1-07.

### Duplicate Handling

Needs confirmation before coding:

- `create_only`
- `update_or_create`
- `skip_duplicate`
- `replace` is not allowed without explicit confirmation.

Important decision:

- Do not match or upsert by nullable `tax_code`.

Reference: `ANALYSIS.md` sections 10, 12, 17 and `REFACTOR_PLAN.md` P0-04.

### Error Reporting

Import result must include:

- total rows
- success rows
- error rows
- skipped rows
- row-level errors with sheet, row, column, value, reason
- debug metadata: mode, dry-run, headers, sheet counts

Reference: `AI_PROJECT_CONTEXT.md` import standard and `REFACTOR_PLAN.md` P1-03, P1-04.

## 9. Export Design

### Export Classes

Required:

- `Modules/Partner/Services/ImportExport.php`

Optional if complexity grows:

- `Modules/Partner/Export/ExportQuery.php`
- `Modules/Partner/Export/ExportMapper.php`
- `Modules/Partner/Export/TemplateBuilder.php`

Reference: `REFACTOR_PLAN.md` P1-03, P1-11.

### Query Design

Target:

- Export current records with optional filters.
- Support selected IDs if shared panel and UI flow require it.
- Use service-owned query construction.
- Use chunk/lazy iteration for large exports.

References: `ANALYSIS.md` sections 10, 14 and `REFACTOR_PLAN.md` P1-02, P1-06, P1-11.

### Export Mapping

Default export fields should align with fillable fields unless sensitive fields are excluded:

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

Needs confirmation before coding:

- Whether `note` is exportable.
- Whether labels or raw keys should be exported for enum fields.
- Whether `partner_types` should export as comma-separated keys or labels.

Reference: `ANALYSIS.md` section 10 and `REFACTOR_PLAN.md` P1-04, P1-11.

### Template Generation

Target:

- Generate an import template with canonical headers.
- Include sample row.
- Include allowed values for `legal_type`, `partner_types`, `source`, and `status`.
- Mark required fields.

Needs confirmation before coding:

- Final canonical headers and Vietnamese aliases.

Reference: `AI_PROJECT_CONTEXT.md` export template rules and `REFACTOR_PLAN.md` P1-04.

### Large Export Strategy

Target:

- Do not use `get()` for all records.
- Use shared export storage under `storage/app/public/exports`.
- Use bounded iteration and queue if data size can exceed request limits.

Needs confirmation before coding:

- Dataset size threshold for queued export.
- Export retention policy.

Reference: `ANALYSIS.md` section 14 and `REFACTOR_PLAN.md` P1-06, P1-11.

## 10. Permissions and Authorization

### Required Permissions

Existing declared permissions in `Modules/Partner/config/module.php`:

- `view_partner`
- `create_partner`
- `edit_partner`
- `delete_partner`

Additional permissions need confirmation before coding:

- `import_partner`
- `export_partner`
- `bulk_delete_partner`

Reference: `ANALYSIS.md` section 11 and `REFACTOR_PLAN.md` P0-02, P0-03.

### Policy/Gate Checks

Target:

- Use the project's established permission/gate convention.
- Route/page access should require view permission where convention supports it.
- Livewire create/update/delete/import/export actions must enforce named permissions server-side.

Needs confirmation before coding:

- Exact permission helper/API used in this repository.

Reference: `REFACTOR_PLAN.md` P0-02, P0-03.

### Livewire Action Protection

Protect:

- `Index::delete`
- `Index::deleteSelected`
- import action or shared panel service action
- export action or shared panel service action
- `Form::save`

Reference: `ANALYSIS.md` section 11 and `REFACTOR_PLAN.md` P0-02, P0-03.

### Route Middleware

Web:

- Keep `web`, `auth:admin`.

API:

- Remove API route or protect with API auth and explicit authorization.
- Needs confirmation before coding whether API exists.

Reference: `ANALYSIS.md` sections 2, 3 and `REFACTOR_PLAN.md` P0-01, P0-05, P2-03.

## 11. Transactions and Data Integrity

### Actions Requiring DB Transactions

Required or conditional:

- Import persistence: transaction required if all-or-nothing is confirmed; partial imports still need controlled per-row failure handling.
- Bulk delete: transaction required if deleting multiple records with audit/related cleanup.

Reference: `ANALYSIS.md` section 13 and `REFACTOR_PLAN.md` P1-05.

### Rollback Conditions

Rollback should occur when:

- Import all-or-nothing mode has any validation or persistence failure.
- Bulk delete fails mid-operation.
- Service-level invariant fails after a write transaction has begun.

Needs confirmation before coding:

- Partial import behavior.
- Audit logging behavior.

Reference: `REFACTOR_PLAN.md` P1-04, P1-05.

### Idempotency Concerns

Import:

- Must not create duplicates on retry.
- Must not update by nullable `tax_code`.
- Needs confirmed unique key and duplicate mode.

Bulk delete:

- Should tolerate already-deleted IDs by returning a clear count/result.

Export:

- Should generate deterministic files without mutating Partner records.

Reference: `REFACTOR_PLAN.md` P0-04, P1-05, P1-11.

## 12. Performance Strategy

### Eager Loading

- No relationships are currently displayed.
- Add eager loading only if relationships are introduced later.

Reference: `ANALYSIS.md` section 9.

### Query Optimization

Target:

- Centralize filter query in `PartnerService`.
- Avoid duplicated selection query in Livewire.
- Measure before adding indexes.
- Keep JSON `partner_types` until performance evidence requires a different schema.

References: `ANALYSIS.md` sections 14, 15 and `REFACTOR_PLAN.md` P1-02, P2-05, P2-06.

### Pagination

Target:

- Service-owned server-side pagination.
- Bounded `All`.
- Bounded pagination UI.

Reference: `REFACTOR_PLAN.md` P1-06, P1-09.

### Caching

No caching required initially.

Possible future cache:

- Static option arrays from `Partner::LEGAL_TYPES`, `PARTNER_TYPES`, `SOURCES`, `STATUSES`.

Do not cache query results until invalidation rules are explicit.

Reference: `AI_PROJECT_CONTEXT.md` caching standard and `REFACTOR_PLAN.md` Risk Control.

## 13. Test Strategy

### Route Tests

Cover:

- Admin index/create/edit require `auth:admin`.
- API route absent or protected.
- Named routes resolve.

References: `ANALYSIS.md` sections 2, 3 and `REFACTOR_PLAN.md` P0-01, P0-05.

### Livewire Tests

Cover:

- Form validation success/failure.
- Create permission denied/allowed.
- Update permission denied/allowed.
- Delete and bulk delete permission denied/allowed.
- Filter changes reset page and selection.
- `All` pagination guard behavior.

References: `ANALYSIS.md` sections 5, 6, 11, 14 and `REFACTOR_PLAN.md` P0-02, P0-03, P1-06.

### Service Tests

Cover:

- Search/filter query behavior.
- Current-page ID behavior matches pagination filters.
- Create/update normalization.
- Enum invariant rejection.
- Bulk delete transaction/result behavior.
- Bounded pagination behavior.

References: `ANALYSIS.md` sections 8, 12, 13, 15 and `REFACTOR_PLAN.md` P1-01, P1-02, P1-05, P1-07.

### Import Tests

Cover:

- Header alias mapping.
- Required fields.
- Invalid enum-like values.
- Invalid email.
- Blank `tax_code` behavior once confirmed.
- Duplicate handling mode once confirmed.
- Dry-run no-write behavior.
- Transaction rollback or partial success behavior once confirmed.
- Row-level error report format.

References: `ANALYSIS.md` sections 10, 12, 13 and `REFACTOR_PLAN.md` P0-04, P1-03, P1-04, P1-05.

### Export Tests

Cover:

- Export fields.
- Enum/partner type mapping format once confirmed.
- Selected IDs and active filters if supported.
- Large export uses bounded iteration.
- Shared storage path under exports directory.
- Template generation if implemented.

References: `ANALYSIS.md` sections 10, 14 and `REFACTOR_PLAN.md` P1-06, P1-11.

### Authorization Tests

Cover:

- Denied create/update/delete/bulk delete/import/export.
- Allowed actions for users with the correct permissions.
- API route denied or absent.

References: `ANALYSIS.md` section 11 and `REFACTOR_PLAN.md` P0-01, P0-02, P0-03, P0-05.

## 14. Implementation Checklist

### P0

- [ ] Resolve `Modules/Partner/routes/api.php`: remove/disable or secure and implement. Reference: `REFACTOR_PLAN.md` P0-01.
- [ ] Resolve `Modules/Partner/Http/Controllers/Api/PartnerController.php`: remove or implement with authorization. Reference: `REFACTOR_PLAN.md` P0-05.
- [ ] Add server-side permission checks for delete, bulk delete, import, and export in `Modules/Partner/Livewire/Partner/Index.php` or shared import/export integration. Reference: `REFACTOR_PLAN.md` P0-02.
- [ ] Add server-side create/update permission checks in `Modules/Partner/Livewire/Partner/Form.php`. Reference: `REFACTOR_PLAN.md` P0-03.
- [ ] Stop nullable `tax_code` upsert behavior before enabling import writes. Reference: `REFACTOR_PLAN.md` P0-04.
- [ ] Confirm import unique key and missing-key behavior before coding import. Reference: `REFACTOR_PLAN.md` P0-04, P1-04.

### P1

- [ ] Move direct Partner model queries out of `Modules/Partner/Livewire/Partner/Index.php`. Reference: `REFACTOR_PLAN.md` P1-01.
- [ ] Centralize search/filter query construction in `Modules/Partner/Services/PartnerService.php`. Reference: `REFACTOR_PLAN.md` P1-02.
- [ ] Create `Modules/Partner/Services/ImportExport.php` using shared import/export foundation. Reference: `REFACTOR_PLAN.md` P1-03.
- [ ] Replace custom import/export controls in `Modules/Partner/resources/views/livewire/partner/index.blade.php` with shared panel. Reference: `REFACTOR_PLAN.md` P1-03.
- [ ] Document and implement import headers, aliases, mode, dry-run, null-overwrite, duplicate handling, row validation, and export columns. Reference: `REFACTOR_PLAN.md` P1-04.
- [ ] Add transaction strategy for import and bulk delete. Reference: `REFACTOR_PLAN.md` P1-05.
- [ ] Guard `All` pagination and large exports. Reference: `REFACTOR_PLAN.md` P1-06.
- [ ] Add service-level invariants for enum-like fields. Reference: `REFACTOR_PLAN.md` P1-07.
- [ ] Align edit parameter contract between `Modules/Partner/resources/views/pages/edit.blade.php` and `Modules/Partner/Livewire/Partner/Form.php`. Reference: `REFACTOR_PLAN.md` P1-08.
- [ ] Replace full page range pagination rendering. Reference: `REFACTOR_PLAN.md` P1-09.
- [ ] Add safe future migration for schema comments if approved. Reference: `REFACTOR_PLAN.md` P1-10.
- [ ] Move exports to shared export storage. Reference: `REFACTOR_PLAN.md` P1-11.

### P2

- [ ] Fix stale comments in `Modules/Partner/resources/views/pages/index.blade.php`, `create.blade.php`, and `edit.blade.php`. Reference: `REFACTOR_PLAN.md` P2-01.
- [ ] Remove `Modules/Partner/resources/views/partner.blade.php` after reference checks. Reference: `REFACTOR_PLAN.md` P2-02.
- [ ] Remove `Modules/Partner/resources/views/components/placeholder.blade.php` after reference checks. Reference: `REFACTOR_PLAN.md` P2-02.
- [ ] Remove `Modules/Partner/resources/views/livewire/placeholder.blade.php` after reference checks. Reference: `REFACTOR_PLAN.md` P2-02.
- [ ] Confirm whether Partner API exists long term and document/remove scaffold accordingly. Reference: `REFACTOR_PLAN.md` P2-03.
- [ ] Consider shared form/input components after behavior is stable. Reference: `REFACTOR_PLAN.md` P2-04.
- [ ] Review search indexes after query-plan measurement. Reference: `REFACTOR_PLAN.md` P2-05.
- [ ] Keep JSON `partner_types` unless metrics or business rules require normalization. Reference: `REFACTOR_PLAN.md` P2-06.
- [ ] Confirm phone validation rules before tightening validation. Reference: `REFACTOR_PLAN.md` P2-07.
