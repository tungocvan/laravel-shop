# Admission Module Analysis

## 1. Executive Summary

The Admission module handles student admission registration, administrative review, class assignment, document generation, admission search, catalog data, and location data. It is a domain module, but its manifest currently disables it.

Current condition: functionally broad but inconsistent. The module contains public registration/search pages, admin dashboards, Livewire CRUD/listing actions, Excel import/export, Word/PDF generation, queued PDF generation, and admission lookup by identity number plus birth date password. The main risks are disabled module registration, missing API action, incomplete authorization, schema mismatch for approval metadata, weak validation, unbounded import/export queries, and sensitive student/family data exposure.

Recommendation: Major Refactor. A full rebuild is not required yet because the module has a coherent domain boundary and usable classes, but safety, schema, validation, and import/export foundations need repair before feature expansion.

## 2. Bootstrap Context

Detected from `composer.json`:

- Laravel: `^12.0`
- PHP: `^8.3`
- Livewire: `^3.6`
- Excel: `maatwebsite/excel ^3.1.64`
- Permissions: `spatie/laravel-permission ^6.23`
- Admin UI: `almasaeed2010/adminlte 4.0.0-rc.3`

Detected from `Modules/ModuleServiceProvider.php`:

- Modules are discovered from `Modules/*`.
- Module namespace convention is `Modules\<ModuleName>`.
- Module config is loaded from `config` or `Config`.
- Web routes are loaded from `routes/web.php`; API routes are loaded under global `api` prefix from `routes/api.php`.
- Views are loaded using both `Admission::` and `admission::` namespaces.
- Livewire aliases are generated as `admission.<kebab.path>`.
- Migrations are loaded from `database/migrations` or `Database/Migrations`.
- Super Admin bypass is registered through `Gate::before`.

Missing or empty bootstrap files:

- `docs/bootstrap/CODEX_BOOTSTRAP.md` was not found or had no readable content.
- `docs/bootstrap/PROJECT_BOOTSTRAP.md` was not found or had no readable content.
- `docs/bootstrap/AI_PROJECT_CONTEXT.md` was not found or had no readable content.

Additional context from `ROADMAP.md`:

- Project roadmap identifies Laravel 12 and Livewire 3.
- Project has limited automated test coverage.
- Project-wide risks include authorization gaps, import/export consolidation, query performance, and migration hygiene.

## 3. Module Purpose

Admission manages the admission lifecycle:

- Parent/admin registration form.
- Admin application list, approval, rejection, deletion, import, export, class assignment.
- Admission document generation in DOCX/PDF.
- Public search of approved applications.
- Administrative location data maintenance.
- Catalog data for ethnicity, religion, and similar form options.

## 4. Module Overview

Main files:

- Routes: `Modules/Admission/routes/web.php`, `Modules/Admission/routes/api.php`
- Controllers: `Modules/Admission/Http/Controllers/AdmissionController.php`, `Modules/Admission/Http/Controllers/Api/AdmissionController.php`
- Livewire: `Modules/Admission/Livewire/Public/RegistrationForm.php`, `Modules/Admission/Livewire/Admin/Applications/Index.php`, `Modules/Admission/Livewire/Admin/Dashboard/StatsOverview.php`, `Modules/Admission/Livewire/Search.php`, `Modules/Admission/Livewire/Dvhc.php`
- Services: `Modules/Admission/Services/AdmissionService.php`
- Jobs: `Modules/Admission/Jobs/GenerateAdmissionPdfJob.php`
- Imports: `Modules/Admission/Imports/ApplicationsImport.php`, controller usage of `App\Services\Data\Import\GenericImport`
- Exports: `Modules/Admission/Exports/ApplicationsExport.php`
- Models: `Modules/Admission/Models/AdmissionApplication.php`, `Modules/Admission/Models/AdmissionCatalog.php`, `Modules/Admission/Models/AdmissionLocation.php`, `Modules/Admission/Models/Admission.php`
- Tables: `admission_applications`, `admission_catalogs`, `admission_locations`
- Shared dependencies: `App\Services\Data\DataTransformer`, `App\Services\Data\Import\GenericImport`, `App\Services\DocumentConverterService`, `PhpOffice\PhpWord`, `Symfony\Component\Process\Process`, `Maatwebsite\Excel`

## 5. Dependency Graph

```text
Modules/Admission/routes/web.php
-> Modules/Admission/Http/Controllers/AdmissionController.php
-> Modules/Admission/resources/views/pages/*
-> Modules/Admission/Livewire/*
-> Modules/Admission/Services/AdmissionService.php
-> Modules/Admission/Models/AdmissionApplication.php
-> admission_applications

Admin application list
-> Modules/Admission/Livewire/Admin/Applications/Index.php
-> Modules/Admission/Exports/ApplicationsExport.php
-> Modules/Admission/Models/AdmissionApplication.php
-> admission_applications

Import
-> Modules/Admission/Http/Controllers/AdmissionController.php
-> App/Services/Data/Import/GenericImport.php
-> App/Services/Data/DataTransformer.php
-> Modules/Admission/Models/AdmissionApplication.php
-> admission_applications

Approved application
-> Modules/Admission/Models/AdmissionApplication.php updated event
-> Modules/Admission/Jobs/GenerateAdmissionPdfJob.php
-> Modules/Admission/Services/AdmissionService.php
-> App/Services/DocumentConverterService.php
-> storage/app/admission/*

Location admin
-> Modules/Admission/Livewire/Dvhc.php
-> Modules/Admission/Models/AdmissionLocation.php
-> admission_locations
```

No circular dependency was found inside the module. Cross-module/shared app dependencies exist through `App\Services\Data/*`, `App\Services\DocumentConverterService`, global Blade components such as `x-select-search`, Excel, QR code, and PhpWord.

## 6. Route Analysis

| Method | URI | Name | Middleware | Controller/Action | Permission | Notes |
|--------|-----|------|------------|-------------------|------------|-------|
| GET | `/admission/search/{ma_dinh_danh?}/{password?}` | `admission.search` | `web` | `AdmissionController@search` | None | Public search accepts credentials in URL path. |
| GET | `/admin` | `admin.dashboard` | `web`, `auth:admin` | `AdmissionController@dashboard` | None | Generic admin route owned by Admission module. |
| GET | `/admin/admission` | `admin.admission.index` | `web`, `auth:admin` | `AdmissionController@adminIndex` | None | Admin list page. |
| GET | `/admin/admission/create` | `admin.admission.create` | `web`, `auth:admin` | `AdmissionController@adminCreate` | None | Admin create page. |
| GET | `/admin/admission/edit/{id}` | `admin.admission.edit` | `web`, `auth:admin` | `AdmissionController@adminEdit` | None | No authorization for record access. |
| GET | `/admin/admission/export-pdf/{id}` | `admin.admission.export-pdf` | `web`, `auth:admin` | `AdmissionController@downloadPdf` | None | Generates/downloads PDF synchronously. |
| GET | `/admin/admission/export` | `admin.admission.export` | `web`, `auth:admin` | `AdmissionController@export` | None | Controller method is missing. |
| POST | `/admin/admission/import` | `admin.admission.import` | `web`, `auth:admin` | `AdmissionController@import` | None | No file validation in controller. |
| GET | `/admin/admission/dvhc` | `admin.admission.dvhc` | `web`, `auth:admin` | `AdmissionController@dvhc` | None | Location maintenance page. |
| GET | `/admin/admission/list-class` | `admin.admission.list-class` | `web`, `auth:admin` | `AdmissionController@listClass` | None | Uses public list-class view under admin route. |
| GET | `/admission/register` | `admission.register` | `web`, `auth:admin` | `AdmissionController@index` | None | Parent registration is protected by admin auth; confirm intended behavior. |
| GET | `/admission/download-pdf/{id}` | `admission.download-pdf` | `web`, `auth:admin` | `AdmissionController@downloadPdf` | None | Admin-only despite admission prefix. |
| GET | `/admission/download-word/{id}` | `admission.download-word` | `web`, `auth:admin` | `AdmissionController@downloadDocx` | None | No record-level permission. |
| GET | `/admission/{id}/download/{type}` | `admission.download` | `web`, `auth:admin` | `AdmissionController@download` | None | Downloads stored PDF/Word. |
| GET | `/admission/{id}/receipt` | `admission.receipt` | `web`, `auth:admin` | `AdmissionController@receipt` | None | Only status check, no explicit permission. |
| GET | `/api/admission` | unnamed | `api` | `Api\AdmissionController@index` | None | `index` method does not exist. |

## 7. Controller Analysis

### `Modules/Admission/Http/Controllers/AdmissionController.php`

Responsibilities:

- Returns page Blade views for public/admin Admission UI.
- Handles document generation/download.
- Handles Excel import through shared `GenericImport`.
- Handles receipt generation.

Issues:

- Missing `export()` method although `Modules/Admission/routes/web.php` registers `admin.admission.export`.
- Import uses `$request->file('file')` with no validation.
- Download and document actions rely only on route middleware and record lookup.
- Public search route places identity number and birth-date password in URL.
- `downloadPdf()` exposes raw exception message to the user and creates cache files with file names derived from student names.
- Uses direct filesystem functions in controller.

Recommendations:

- Move document generation/download orchestration to service/job boundary.
- Add form request or controller validation for imports.
- Add permission checks for view, create, edit, delete, import, export, approve, reject, document download.
- Avoid credentials in URL path for search.
- Return generic user errors and log detailed exceptions server-side.

### `Modules/Admission/Http/Controllers/Api/AdmissionController.php`

Responsibilities:

- Intended API endpoint for Admission.

Issues:

- Empty controller while `Modules/Admission/routes/api.php` maps GET `/api/admission` to `index`.

Recommendations:

- Implement intentionally or remove/disable the API route. Needs confirmation before coding.

## 8. Page Blade Analysis

Main page views:

- `Modules/Admission/resources/views/pages/dashboard.blade.php`
- `Modules/Admission/resources/views/pages/admin/index.blade.php`
- `Modules/Admission/resources/views/pages/admin/create.blade.php`
- `Modules/Admission/resources/views/pages/admin/edit.blade.php`
- `Modules/Admission/resources/views/pages/admin/dvhc.blade.php`
- `Modules/Admission/resources/views/pages/public/register.blade.php`
- `Modules/Admission/resources/views/pages/public/search.blade.php`
- `Modules/Admission/resources/views/pages/public/list-class.blade.php`
- `Modules/Admission/resources/views/layouts/auth.blade.php`

Responsibilities:

- Host Livewire components.
- Provide import form and admin list UI.
- Provide public registration/search shells.

Issues:

- `pages/admin/edit.blade.php` exists but `adminEdit()` returns `pages.admin.create`.
- `pages/public/register.blade.php` is served behind `auth:admin`.
- Admin import form posts directly to controller rather than using the module's dedicated `ApplicationsImport`.
- Shared component `x-select-search` is used by form partials, but its source is outside module and must remain compatible.

Recommendations:

- Clarify public vs admin registration routes.
- Remove dead edit view or route edit page consistently.
- Add authorization-aware UI states and server-side checks.

## 9. Livewire PHP Analysis

### `Modules/Admission/Livewire/Public/RegistrationForm.php`

Responsibilities:

- Multi-step application form.
- Loads province/catalog options.
- Creates or updates `AdmissionApplication` through `AdmissionService`.
- In admin edit mode, assigns `approved` status when class and teacher are filled.

State Properties:

- `currentStep`, `totalSteps`, ward/province/catalog arrays, copy flags, edit flags.
- Large `form` array using PascalCase keys.

Validation:

- Only `form.HoVaTenHocSinh` and `form.MaDinhDanh` are validated.

Events:

- Dispatches `show-success-modal`.

Pagination:

- None.

Search/Filter/Sort:

- Location dropdown queries by selected province.

Performance Concerns:

- Loads all distinct province rows and catalogs at mount.
- Re-queries locations for each location field change.

Issues:

- Incomplete validation for dates, phones, class type, guardian fields, checkboxes, and enum-like values.
- Public component supports edit mode by ID without authorization inside component.
- Service maps `NoiSinhChiTiet` inconsistently; `prepareData()` stores `NoiSinh` into `noi_sinh_chi_tiet`.
- Status transition is driven by presence of class/teacher fields rather than explicit approval workflow.

Recommendations:

- Add step-aware validation or full `rules()` coverage.
- Require authorization for edit mode and admin-only fields.
- Move status transition to dedicated service method.
- Cache stable catalogs/locations.

### `Modules/Admission/Livewire/Admin/Applications/Index.php`

Responsibilities:

- Admin listing, search/filter, pagination, select all, approve/reject/delete/export.

State Properties:

- `search`, `filterStatus`, `filterClass`, `perPage`, `selected`, `selectAll`.

Validation:

- None for action inputs.

Events:

- None.

Pagination:

- Uses `WithPagination`; supports `perPage = all`.

Search/Filter/Sort:

- Searches student name, identity number, phone; filters status and class; sorts latest.

Performance Concerns:

- `perPage = all` loads every application.
- Export loads all matching rows into memory.
- Search fields lack supporting indexes.

Issues:

- `approve()` and `reject()` write `approved_at`, `approved_by`, `rejected_at`, `rejected_by`, but migration/model do not define/fill these columns.
- No permission checks for approve, reject, delete, bulk delete, export.
- Bulk delete accepts selected IDs from component state without server-side authorization.

Recommendations:

- Add schema fields or remove metadata writes.
- Add permissions/policies to every mutating method.
- Remove unbounded `all` mode or cap it.
- Queue large exports.

### `Modules/Admission/Livewire/Admin/Dashboard/StatsOverview.php`

Responsibilities:

- Counts total, pending, approved, rejected, import/other, and class type totals.

Validation:

- None.

Performance Concerns:

- Runs aggregate queries on every mount.
- No cache for dashboard counts.

Issues:

- Depends on consistent status values; no enum or constraint enforces allowed statuses.

Recommendations:

- Add status enum/value object or config-backed constants.
- Cache counts with invalidation after application write.

### `Modules/Admission/Livewire/Search.php`

Responsibilities:

- Public admission lookup by identity number and birth-date password.

Validation:

- Manual regex validation.

Issues:

- `mount()` checks `$this->ma_dinh_danh` instead of `$this->MaDinhDanh`, so the early guard is wrong.
- Uses `firstOrFail()` before custom "not found" message, causing 404 on missing record.
- Password error says 6 digits while regex requires 8 digits.
- URL route accepts sensitive lookup credentials in path.

Recommendations:

- Fix property name and use `first()` with controlled error.
- Move lookup credentials to POST or non-logged request body. Needs confirmation before coding.
- Rate limit lookup route.

### `Modules/Admission/Livewire/Dvhc.php`

Responsibilities:

- Admin location/province/ward maintenance.

Validation:

- None.

Performance Concerns:

- Loads up to 200 rows; acceptable but not paginated.

Issues:

- No authorization inside mutating methods.
- Updating province name modifies all rows by string match without transaction or audit log.

Recommendations:

- Add explicit permission checks and validation.
- Wrap bulk province update in transaction and log old/new names.

## 10. Livewire Blade Analysis

Main files:

- `Modules/Admission/resources/views/livewire/admission/registration-form.blade.php`
- `Modules/Admission/resources/views/livewire/admission/partials/*.blade.php`
- `Modules/Admission/resources/views/livewire/admin/applications/index.blade.php`
- `Modules/Admission/resources/views/livewire/admin/dashboard/stats-overview.blade.php`
- `Modules/Admission/resources/views/livewire/search.blade.php`
- `Modules/Admission/resources/views/livewire/dvhc.blade.php`

Responsibilities:

- Render registration steps, admin table, search form, and location editor.

Issues:

- Admin actions are exposed through Livewire buttons without corresponding component authorization.
- Import form lacks visible file type/size enforcement from server-side validation.
- Registration form uses many free-text fields for sensitive personal data with limited validation.

Recommendations:

- Treat Blade controls as presentation only; enforce server-side permissions in Livewire/controller.
- Add loading/disabled states and validation summaries for long import/export/document actions.

## 11. Shared UI Component Analysis

Shared component references:

- `x-select-search` in registration partials.
- Module placeholders under `Modules/Admission/resources/views/components/placeholder.blade.php`.

Issues:

- `x-select-search` is outside the module. Its contract is a shared dependency and should be documented before rebuilding form fields.

Recommendations:

- Keep `x-select-search` compatibility or replace with a documented module-level select component. Needs confirmation before coding.

## 12. Service Analysis

### `Modules/Admission/Services/AdmissionService.php`

Responsibilities:

- Create/update applications.
- Normalize form data.
- Build template data for DOCX/PDF.
- Provide paginated admin list.
- Delete applications.
- Generate receipt DOCX with QR code.

Public Methods:

- `createRegistration(array $formData)`
- `updateRegistration($id, array $formData)`
- `getDataForTemplate($id)`
- `getPaginatedList(array $filters = [], $perPage = 15)`
- `deleteRegistration($id)`
- `generateBienNhan($app)`

Transaction Boundaries:

- None.

Business Rules:

- `mhs` generated as `MHS<year><count+1>`.
- Status stored from input or updated by admin edit flow.
- Receipt only generated for approved records by controller.

Issues:

- `mhs` generation via `AdmissionApplication::count() + 1` is race-prone.
- No transaction around create/update plus side effects.
- Date format mismatch: `ngay_sinh` is stored as `d-m-Y` while DB column is `date` and model casts as date.
- Direct array access can throw undefined index errors for optional guardian fields.
- `prepareData()` stores `noi_sinh_chi_tiet` from `NoiSinh`, not `NoiSinhChiTiet`.

Recommendations:

- Generate `mhs` with DB-backed sequence, UUID, or retry-safe unique generator.
- Store dates in `Y-m-d`.
- Add validated DTO/input boundary before service.
- Use transactions for status/document workflows.

## 13. Import Analysis

Import classes:

- `Modules/Admission/Imports/ApplicationsImport.php`
- `App/Services/Data/Import/GenericImport.php` used by `AdmissionController@import`

Excel handling:

- Maatwebsite Excel collection imports with heading row and skipped empty rows.

Header/column mapping:

- Columns are normalized to lowercase snake case.

Row normalization:

- `DataTransformer` cleans and casts values based on model casts.

Row validation:

- No explicit row validation rules.

Duplicate handling:

- Dedicated module import uses `ma_dinh_danh` or `mhs`.
- Controller `GenericImport` uses `['ma_dinh_danh', 'mhs']`.

Error reporting:

- Errors are logged only; no downloadable error report for the admin.

Memory/chunk strategy:

- Uses `ToCollection`; no chunk reading or queueing.

Issues:

- `Modules/Admission/Imports/ApplicationsImport.php` exists but controller does not use it.
- No controller file validation before Excel import.
- Large files can exhaust memory.
- Whole import transaction wraps all rows but catches row errors internally, causing partial success inside one transaction without explicit summary.

Recommendations:

- Choose one import implementation and document it.
- Add file validation, row rules, chunk reading, queued import option, and user-facing error report.

## 14. Export Analysis

Export class:

- `Modules/Admission/Exports/ApplicationsExport.php`

Export query:

- Filters by search, status, class and calls `get($columns)`.

Export mapping:

- Dynamically uses table columns except excluded fields.
- Formats date-like fields and converts arrays to comma-separated strings.

Template generation:

- None for Excel export.

Large export strategy:

- None. Full result set is loaded into memory.

Issues:

- Export headings depend on live DB schema, which can surprise downstream users.
- No permission check in Livewire `export()`.
- No queued export or chunking.

Recommendations:

- Define explicit export columns and labels.
- Use `FromQuery`/queued exports or chunk strategy for large datasets.

## 15. Shared Service Analysis

Shared services:

- `App/Services/Data/Import/GenericImport.php`
- `App/Services/Data/DataTransformer.php`
- `App/Services/DocumentConverterService.php`

Issues:

- `GenericExportService` file path is `app/Services/Data/Export/GenericExportService.php` but namespace is `App\Services\Export`, which appears inconsistent.
- `GenericImport` logs raw row data, potentially including personal data.
- `DocumentConverterService` depends on system `libreoffice` availability.

Recommendations:

- Redact sensitive import log data.
- Verify namespace/autoload for shared export service before using it.
- Add operational checks for LibreOffice availability and queue worker permissions.

## 16. Model Analysis

### `Modules/Admission/Models/AdmissionApplication.php`

Table: `admission_applications`

Fillable:

- Student identity, birth, address, parent/guardian, commitment, class assignment, and file path fields.

Casts:

- `ngay_sinh` as date.
- `skills` as array, but no matching migration/fillable field was found.
- `kha_nang_hoc_sinh` and `suc_khoe_can_luu_y` as array.
- `ck_goc_hoc_tap` as boolean.

Relationships:

- None.

Soft delete:

- No `SoftDeletes`, but import excludes `deleted_at`.

Issues:

- Model event dispatches PDF job on approved status, coupling persistence to document generation.
- Deleting event unlinks files directly.
- Casts array fields while migrations define them as `text`; Laravel array cast expects JSON.
- Approval metadata fields are written by Livewire but not fillable or migrated.

Recommendations:

- Use JSON columns for array fields or store normalized strings consistently.
- Add explicit document-generation service/events with idempotency.
- Decide whether soft deletes are required.

### `Modules/Admission/Models/AdmissionCatalog.php`

Table: `admission_catalogs`

Fillable: `type`, `code`, `value`, `sort_order`, `is_active`

Relationships: None.

Issues:

- No unique constraint for `(type, code)` or `(type, value)`.

Recommendations:

- Add uniqueness rule after data cleanup. Needs confirmation before coding.

### `Modules/Admission/Models/AdmissionLocation.php`

Table: `admission_locations`

Fillable: `province_code`, `province_name`, `ward_code`, `ward_name`

Relationships: None.

Issues:

- Province updates are string-based through Livewire.

Recommendations:

- Prefer code-based updates and immutable location codes.

### `Modules/Admission/Models/Admission.php`

Placeholder model with no table/fillable definition. It appears unused.

## 17. Migration and Database Analysis

Tables:

- `admission_applications`
- `admission_catalogs`
- `admission_locations`

Indexes:

- `admission_applications.mhs` unique.
- `admission_catalogs(type, is_active)` index.
- `admission_locations.province_code` index.
- `admission_locations.ward_code` unique.

Missing indexes:

- `admission_applications.ma_dinh_danh`
- `admission_applications.status`
- `admission_applications.loai_lop_dang_ky`
- Composite lookup/index for common filters.

Foreign keys:

- None.

Constraints:

- No enum/check constraints for `status`, gender, class type, or boolean commitments beyond DB booleans.

Migration risks:

- `ngay_sinh` is `date`, but service stores `d-m-Y`.
- Array-cast fields are `text`, not JSON.
- Livewire writes approval metadata columns that migration does not create.

## 18. Security Analysis

Main risks:

- Admin routes use only `auth:admin`; no capability-level permissions despite module config listing permissions.
- Livewire mutating methods do not authorize actions.
- Public search route accepts identity number and birth-date password in URL.
- Import logs can contain personal data.
- Download routes allow any authenticated admin to download documents by ID.
- Raw exception messages can be flashed to users in `downloadPdf()`.

## 19. Performance Analysis

Main risks:

- Admin list can load all records through `perPage = all`.
- Excel export uses `get()` and full collection mapping.
- Import uses `ToCollection` without chunking.
- Search/filter fields are not indexed.
- Dashboard counts are recalculated on every mount.
- Location dropdowns can repeatedly query without caching.

## 20. Validation Analysis

Validation is incomplete:

- Registration validates only name and identity number.
- Import file is not validated.
- Admin approve/reject/delete IDs are not validated/authorized.
- Location update inputs are not validated.
- Status/class values are not constrained.
- Date and phone formats are inconsistent.

## 21. Authorization and Permission Analysis

Permissions in `Modules/Admission/config/module.php`:

- `view_admission`
- `create_admission`
- `edit_admission`
- `delete_admission`

Observed enforcement:

- No explicit `can`, policy, gate, or permission middleware was found in Admission routes/controllers/Livewire.
- Only `auth:admin` is applied to admin routes.

## 22. Transaction and Data Integrity Analysis

Concerns:

- `mhs` generation is not concurrency-safe.
- Approval/rejection writes fields missing from schema.
- Status auto-reset in model events can surprise admin updates.
- Document generation is triggered by model event and depends on queue after commit.
- File deletion occurs in model deleting event without DB transaction coordination.
- Import catches row errors inside a transaction and continues without user-facing summary.

## 23. Cross Module Dependency Analysis

Direct shared dependencies:

- `App\Services\Data\Import\GenericImport`
- `App\Services\Data\DataTransformer`
- `App\Services\DocumentConverterService`
- Shared Blade component `x-select-search`
- Laravel auth admin guard and permission package

No direct dependency on another domain module was found.

## 24. Technical Debt Analysis

| Area | Score | Notes |
|------|-------|-------|
| Architecture | 62 | Clear domain files exist, but disabled manifest, controller-heavy document flow, and model side effects need cleanup. |
| Security | 38 | Missing permission enforcement and sensitive URL/search/log risks. |
| Performance | 55 | Pagination exists, but import/export and all-mode are unbounded. |
| Maintainability | 58 | Large form/service mapping and duplicate import paths increase change risk. |
| Testability | 30 | No Admission tests found; logic is mixed across Livewire/model/service/controller. |
| Documentation | 35 | No prior docs in `docs/modules/Admission`. |

## 25. Test Coverage Analysis

No Admission-specific tests were found under `tests`.

Recommended tests:

- Route boot tests for enabled/disabled module state.
- Feature tests for admin pages and public search.
- Livewire tests for registration, admin list filters, approve/reject/delete, and DVHC update.
- Service tests for `mhs` generation, date normalization, data mapping, and receipt generation.
- Import tests with valid rows, duplicate rows, invalid rows, and large fixture strategy.
- Export tests for filters and column contract.
- Authorization tests for every admin action.
- Queue/job tests for approved application document generation.

## 26. Module Health Score

| Area | Score |
|------|-------|
| Architecture | 62 |
| Security | 38 |
| Performance | 55 |
| Maintainability | 58 |
| Testability | 30 |
| Documentation | 35 |

Overall Grade: D

## 27. Issue List

### P0 - Module disabled in manifest

Priority: P0  
File: `Modules/Admission/config/module.php`  
Problem: `enabled` is set to `false`.  
Root Cause: Module manifest disables auto-registration.  
Business Impact: Admission routes, views, Livewire aliases, migrations, and config may not be registered in normal module boot.  
Technical Impact: Module can appear broken even when source files exist.  
Recommendation: Confirm intended deployment state and enable the module or document a separate registration path. Needs confirmation before coding.

### P0 - API route targets missing method

Priority: P0  
File: `Modules/Admission/routes/api.php`, `Modules/Admission/Http/Controllers/Api/AdmissionController.php`  
Problem: GET `/api/admission` maps to `index`, but API controller has no `index()` method.  
Root Cause: Stub controller was routed before implementation.  
Business Impact: API consumers receive server errors.  
Technical Impact: Route boot may succeed but request handling fails.  
Recommendation: Implement `index()` with authorization/rate limiting or remove route. Needs confirmation before coding.

### P0 - Approval metadata schema mismatch

Priority: P0  
File: `Modules/Admission/Livewire/Admin/Applications/Index.php`, `Modules/Admission/database/migrations/2026_04_21_200923_create_applications_table.php`, `Modules/Admission/Models/AdmissionApplication.php`  
Problem: `approve()`/`reject()` write `approved_at`, `approved_by`, `rejected_at`, `rejected_by`, but migration and fillable list do not define these fields.  
Root Cause: Workflow fields were added in Livewire without database/model update.  
Business Impact: Admin approval/rejection can fail or silently miss audit metadata.  
Technical Impact: SQL unknown-column errors or mass-assignment inconsistency.  
Recommendation: Add proper columns/fillable/casts or remove these writes and use an audit table. Needs confirmation before coding.

### P0 - Missing authorization on mutating actions

Priority: P0  
File: `Modules/Admission/routes/web.php`, `Modules/Admission/Livewire/Admin/Applications/Index.php`, `Modules/Admission/Livewire/Dvhc.php`, `Modules/Admission/Http/Controllers/AdmissionController.php`  
Problem: Admin actions rely on `auth:admin` only.  
Root Cause: Module permissions are declared but not enforced.  
Business Impact: Any authenticated admin may import, export, approve, reject, delete, update locations, and download sensitive documents.  
Technical Impact: Permission model is bypassed.  
Recommendation: Enforce named permissions/policies in routes, controllers, and every Livewire mutating method.

### P1 - Registration validation is incomplete

Priority: P1  
File: `Modules/Admission/Livewire/Public/RegistrationForm.php`  
Problem: Only student name and identity number are validated.  
Root Cause: Large form was implemented without full validation boundary.  
Business Impact: Invalid admission data can be stored and printed in official documents.  
Technical Impact: Date parse errors, invalid phones, inconsistent enum values, and bad downstream exports.  
Recommendation: Add complete step-aware validation and service-level invariants.

### P1 - Search credentials are passed in URL

Priority: P1  
File: `Modules/Admission/routes/web.php`, `Modules/Admission/Livewire/Search.php`, `Modules/Admission/Services/AdmissionService.php`  
Problem: Identity number and birth-date password can be embedded in `/admission/search/{ma_dinh_danh}/{password}`.  
Root Cause: Search shortcut is encoded as route parameters.  
Business Impact: Sensitive student lookup credentials can appear in logs, browser history, referrers, and QR links.  
Technical Impact: Privacy and compliance risk.  
Recommendation: Use POST or opaque token lookup, add rate limiting, and avoid logging credentials. Needs confirmation before coding.

### P1 - Import has no file validation or chunking

Priority: P1  
File: `Modules/Admission/Http/Controllers/AdmissionController.php`, `App/Services/Data/Import/GenericImport.php`, `Modules/Admission/Imports/ApplicationsImport.php`  
Problem: Import accepts uploaded file without validation and processes collection in memory.  
Root Cause: Controller directly invokes Excel import with generic importer.  
Business Impact: Bad or oversized files can break admin workflow.  
Technical Impact: Memory pressure and weak error reporting.  
Recommendation: Validate file type/size, use chunk reading, queue large imports, and return error reports.

### P1 - Export loads all matching rows

Priority: P1  
File: `Modules/Admission/Exports/ApplicationsExport.php`, `Modules/Admission/Livewire/Admin/Applications/Index.php`  
Problem: Export calls `get()` and maps every row in memory.  
Root Cause: Export implements `FromCollection` instead of query/chunk strategy.  
Business Impact: Large admission cohorts can time out export.  
Technical Impact: High memory usage and slow requests.  
Recommendation: Use explicit columns plus `FromQuery`/queued export.

### P1 - Date and array storage mismatch

Priority: P1  
File: `Modules/Admission/Services/AdmissionService.php`, `Modules/Admission/Models/AdmissionApplication.php`, `Modules/Admission/database/migrations/2026_04_21_200923_create_applications_table.php`  
Problem: `ngay_sinh` is stored as `d-m-Y` for a DB date column; array casts are used on `text` columns.  
Root Cause: UI/service formatting is mixed with persistence format.  
Business Impact: Search password/document dates can become inconsistent.  
Technical Impact: Cast/serialization errors and import/export inconsistencies.  
Recommendation: Store dates as `Y-m-d` and convert array fields to JSON columns or consistent string storage.

### P1 - Race-prone application code generation

Priority: P1  
File: `Modules/Admission/Services/AdmissionService.php`  
Problem: `mhs` uses `AdmissionApplication::count() + 1`.  
Root Cause: Sequence generation is not atomic.  
Business Impact: Concurrent submissions can collide and fail.  
Technical Impact: Unique index violations.  
Recommendation: Use a DB sequence/counter, ULID-based code, or transaction with retry.

### P2 - Placeholder/unused model

Priority: P2  
File: `Modules/Admission/Models/Admission.php`  
Problem: Placeholder model appears unused.  
Root Cause: Scaffold artifact remains.  
Business Impact: Low.  
Technical Impact: Confuses future maintainers.  
Recommendation: Remove after confirming no references. Needs confirmation before coding.

### P2 - Dashboard and catalog data not cached

Priority: P2  
File: `Modules/Admission/Livewire/Admin/Dashboard/StatsOverview.php`, `Modules/Admission/Livewire/Public/RegistrationForm.php`  
Problem: Stable counts/catalogs/locations are queried repeatedly.  
Root Cause: No cache policy.  
Business Impact: Low now, grows with data volume.  
Technical Impact: Avoidable database load.  
Recommendation: Add cache with invalidation on application/catalog/location changes.

## 28. Final Recommendation

- [ ] Minor Refactor
- [x] Major Refactor
- [ ] Full Rebuild

Reason: The module has a coherent domain boundary and most feature pieces already exist, but it needs a major refactor to fix module registration, authorization, schema correctness, validation, import/export scalability, and privacy risks before further development.
