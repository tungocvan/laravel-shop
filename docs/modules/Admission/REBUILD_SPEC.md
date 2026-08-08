# Admission Rebuild Specification

## 1. Goal

Rebuild the Admission module into a secure, validated, testable Laravel 12 and Livewire 3 domain module while preserving current business workflows: registration, review, class assignment, import/export, document generation, receipt generation, public lookup, catalogs, and location maintenance.

## 2. Target Architecture

```text
Route
-> Controller
-> Page Blade
-> Livewire PHP
-> Livewire Blade
-> Shared Components
-> Service
-> Import
-> Export
-> Model
-> Migration
-> Database
```

Target ownership:

- Routes define middleware and permission boundaries.
- Controllers return pages or delegate file/import/document actions.
- Livewire owns UI state only.
- Services own business rules and transactions.
- Models stay thin and avoid heavy side effects where possible.
- Jobs own long-running document/export/import work.

## 3. Database Design

Tables:

- `admission_applications`
- `admission_catalogs`
- `admission_locations`

Recommended `admission_applications` improvements:

- Keep `id`, `mhs`, `status`, student, address, parent/guardian, commitment, class assignment, `pdf_path`, `word_path`, timestamps.
- Add approval metadata if workflow needs it:
  - `approved_at`, `approved_by`
  - `rejected_at`, `rejected_by`
  - Optional `rejection_reason`
- Add indexes:
  - unique `mhs`
  - index `ma_dinh_danh`
  - index `status`
  - index `loai_lop_dang_ky`
  - optional composite `(status, loai_lop_dang_ky)`
- Store `ngay_sinh` as date in `Y-m-d`.
- Convert `kha_nang_hoc_sinh` and `suc_khoe_can_luu_y` to JSON columns or intentionally store strings. Needs confirmation before coding.

Recommended `admission_catalogs` improvements:

- Add unique constraint for `(type, code)` if codes are stable.
- Keep `(type, is_active)` index.

Recommended `admission_locations` improvements:

- Keep `ward_code` unique and `province_code` indexed.
- Prefer code-based updates over province-name string updates.

Compatibility risks:

- Existing text array/date data may require backfill.
- Approval metadata needs data migration only if historical approval audit is required.

## 4. Model Design

### `AdmissionApplication`

Fillable:

- Keep current business fields.
- Add approval/rejection metadata only if migrated.

Casts:

- `ngay_sinh` => `date:Y-m-d`
- Boolean commitment fields => `boolean`
- JSON fields => `array` only if DB columns are JSON.
- Approval/rejection timestamps => `datetime`

Relationships:

- Optional `approvedBy()` and `rejectedBy()` to admin/user model. Needs confirmation before coding.

Scopes:

- `scopeSearch($query, string $term)`
- `scopeStatus($query, ?string $status)`
- `scopeClassType($query, ?string $classType)`

Soft delete strategy:

- Needs confirmation before coding. Current code hard-deletes records and files.

### `AdmissionCatalog`

- Fillable: `type`, `code`, `value`, `sort_order`, `is_active`
- Scope: `activeType($type)`

### `AdmissionLocation`

- Fillable: `province_code`, `province_name`, `ward_code`, `ward_name`
- Scopes: `provinceList()`, `forProvinceCode($code)`

## 5. Service Design

### `AdmissionService`

Public methods:

- `createRegistration(array $validatedData): AdmissionApplication`
- `updateRegistration(int $id, array $validatedData): AdmissionApplication`
- `approve(int $id, int $adminId): AdmissionApplication`
- `reject(int $id, int $adminId, ?string $reason = null): AdmissionApplication`
- `delete(int $id): void`
- `templateData(int $id): array`
- `generateReceipt(AdmissionApplication $application): BinaryFileResponse`

Transaction boundaries:

- Create registration with code generation.
- Update registration and status reset.
- Approve/reject and document job dispatch.
- Delete record and files, preferably with after-commit cleanup.

Business rules:

- `mhs` must be unique and concurrency safe.
- Only pending applications can be approved/rejected unless an admin override exists.
- Approved documents are generated asynchronously.
- Status values should be constrained to known values.

Validation boundaries:

- Controllers/Livewire validate input.
- Service rechecks invariants and permissions where appropriate for non-HTTP callers.

## 6. Livewire Design

Components:

- `Public\RegistrationForm`
- `Admin\Applications\Index`
- `Admin\Dashboard\StatsOverview`
- `Search`
- `Dvhc`

Registration state:

- `currentStep`
- `form`
- location/catalog option arrays
- edit mode fields only when authorized

Validation rules:

- Step 1: name, gender, birth date, identity number, nationality, ethnicity, religion, phone.
- Step 2: permanent/current address fields.
- Step 3: household and health/skill fields.
- Step 4: parent/guardian names, birth years, phones, identity numbers.
- Step 5: class type, commitments, signer, admin-only class assignment.

Admin listing:

- Paginated search/filter/sort.
- Remove unbounded `all` mode or cap it.
- Bulk actions require permission checks.
- Export should create queued export for large datasets.

Modal behavior:

- Success modal after create/update.
- Confirm modal for delete/bulk delete.
- Clear denied-permission state.

Upload behavior:

- Validate file MIME and size before import.
- Show progress/status for queued imports.

## 7. Blade/UI Design

Page Blade files:

- Keep page shells under `Modules/Admission/resources/views/pages`.
- Keep Livewire views under `Modules/Admission/resources/views/livewire`.

Shared components:

- Continue using `x-select-search` if its API is stable. Needs confirmation before coding.

AdminLTE/Bootstrap rules:

- Align with project stack: Bootstrap 5.3 and AdminLTE 4 RC per roadmap.
- Avoid mixing Tailwind-style utility assumptions unless the project compiles them. Needs confirmation before coding.

Table design:

- Server-side pagination.
- Stable columns.
- Per-row action permissions.

Form design:

- Step-aware validation.
- Inline errors.
- No admin-only fields in public flow.

Import/export panel:

- File validation.
- Upload progress.
- Downloadable import error report.
- Queued export status for large data.

Empty/loading states:

- Empty list state for no applications.
- Loading indicators for search, filters, import, export, and document generation.

## 8. Import Design

Import classes:

- Prefer one canonical `ApplicationsImport`.
- Reuse shared `DataTransformer` only after sensitive logging and validation are addressed.

Header mapping:

- Normalize headings to snake case.
- Maintain explicit accepted aliases for Vietnamese/business labels.

Column mapping:

- Explicit map from import columns to `AdmissionApplication` fields.

Row normalization:

- Trim strings.
- Normalize dates to `Y-m-d`.
- Normalize booleans.
- Normalize arrays or delimited strings consistently.

Row validation:

- Required unique key: `ma_dinh_danh` or `mhs`.
- Validate identity number, dates, status, class type, phone fields.

Duplicate handling:

- Upsert by `ma_dinh_danh` or `mhs`.
- Report conflicts when both keys match different records.

Error reporting:

- Collect row errors.
- Return downloadable report.
- Redact sensitive values in logs.

Dry run:

- Recommended for admin preview. Needs confirmation before coding.

Chunk processing:

- Use chunk reading.
- Queue large imports.

Transaction strategy:

- Per-chunk transactions or per-row save with error collection. Avoid one giant transaction for large files.

## 9. Export Design

Export classes:

- `ApplicationsExport` should use explicit columns and labels.

Export query:

- Use model scopes for search/status/class.

Export mapping:

- Format dates for Excel display.
- Convert arrays consistently.
- Exclude sensitive/internal fields by default.

Template generation:

- Optional template export for import sample. Needs confirmation before coding.

Large export strategy:

- Use query-based export and queue when row count exceeds threshold.

Memory strategy:

- Avoid `get()` for large exports.

Queued export strategy:

- Store generated file in private storage with retention cleanup.

## 10. Permissions and Authorization

Required permissions:

- `view_admission`
- `create_admission`
- `edit_admission`
- `delete_admission`
- `import_admission`
- `export_admission`
- `approve_admission`
- `reject_admission`
- `download_admission_documents`
- `manage_admission_locations`

Route middleware:

- Apply permission middleware to admin routes.

Policy checks:

- Add `AdmissionApplicationPolicy`.
- Check view/update/delete/approve/reject/download.

Livewire action protection:

- Call `$this->authorize(...)` or permission checks inside every mutating method.

Bulk action protection:

- Authorize every selected record or restrict query to authorized records.

## 11. Transactions and Data Integrity

Actions requiring transactions:

- Create with `mhs` generation.
- Update with status reset.
- Approve/reject with metadata.
- Import row/chunk upserts.
- Delete with file cleanup scheduling.

Rollback conditions:

- Validation/invariant failures.
- Duplicate key conflicts.
- Document job dispatch failures should not roll back approval if job is after-commit and retryable.

Idempotency:

- Document generation should safely skip existing files.
- Import should be deterministic by unique keys.

Duplicate prevention:

- Unique `mhs`.
- Add unique/index strategy for `ma_dinh_danh` if business rules allow. Needs confirmation before coding.

Audit log needs:

- Approval/rejection.
- Delete.
- Import/export.
- Document download.
- Location updates.

## 12. Performance Strategy

- Add indexes for search/filter columns.
- Remove unbounded admin `all` loading.
- Use query scopes and pagination.
- Cache stable catalogs and location lists.
- Cache dashboard counts with invalidation.
- Chunk/queue imports.
- Query/queue exports.
- Keep document conversion in queue.

## 13. Shared Foundation Integration

Shared services:

- Keep `DataTransformer` only if it supports explicit Admission validation and safe logging.
- Keep `DocumentConverterService` for DOCX/PDF generation.

Shared UI:

- `x-select-search` can remain as a shared component if stable.

Traits/helpers/base classes:

- Needs confirmation before coding. No Admission-specific base class was found.

Reusable patterns:

- Standardize import/export contracts with project-wide shared foundation.

## 14. Event and Listener Design

Recommended events:

- `AdmissionApplicationCreated`
- `AdmissionApplicationUpdated`
- `AdmissionApplicationApproved`
- `AdmissionApplicationRejected`
- `AdmissionApplicationDeleted`

Listeners:

- Dispatch document generation after approval.
- Invalidate dashboard/cache after writes.
- Write audit logs.

Current model events can be replaced gradually with explicit service events to reduce hidden side effects.

## 15. Queue Design

Jobs:

- `GenerateAdmissionPdfJob`
- Optional `ImportAdmissionApplicationsJob`
- Optional `ExportAdmissionApplicationsJob`

Queue requirements:

- Retryable jobs.
- Idempotent document output.
- Private storage for generated files.
- Failed job visibility for admins.

## 16. Cache Design

Cache candidates:

- Admission catalogs by type.
- Province lists.
- Wards by province.
- Dashboard status/class counts.

Invalidation:

- Catalog write invalidates catalog cache.
- Location write invalidates location cache.
- Application write invalidates dashboard counts.

## 17. Logging Strategy

- Log import/export/document job lifecycle.
- Redact identity numbers, phone numbers, and row-level personal data.
- Log authorization denials without sensitive payloads.
- Add correlation IDs for imports/exports/jobs where possible.

## 18. Monitoring Strategy

- Monitor failed document jobs.
- Monitor import/export duration and memory.
- Monitor search rate-limit hits.
- Monitor approval/rejection counts.
- Monitor missing template or LibreOffice failures.

## 19. Rollback Strategy

- Before schema changes, back up `admission_applications`.
- For date/JSON migration, create reversible migration or staged migration with old columns retained until verified.
- Keep old route names during rollout.
- Keep document files in private storage and avoid deleting during migration.
- Feature-flag new import/export queue behavior if production risk is high.

## 20. Test Strategy

- Route tests for module boot and route availability.
- Feature tests for admin pages and public search.
- Livewire tests for registration, edit, approve, reject, delete, bulk delete, export, and DVHC.
- Service tests for create/update/approve/reject/template data/MHS generation.
- Import tests for valid rows, invalid rows, duplicate rows, large chunks.
- Export tests for filters, headings, and memory-safe query behavior.
- Authorization tests for every permission.
- Validation tests for each registration step.
- Database tests for indexes/constraints where practical.
- Job tests for document generation idempotency and failure handling.

## 21. Deployment Checklist

- Confirm `Modules/Admission/config/module.php` enabled state.
- Run migrations and backfills.
- Seed permissions.
- Verify Super Admin bypass still works.
- Verify queue worker is running.
- Verify `storage/app/templates/application.docx` exists.
- Verify `storage/app/templates/bien-nhan.docx` exists.
- Verify LibreOffice is installed where PDF conversion is enabled.
- Run Admission test suite.
- Smoke test registration, admin approval, document generation, import, export, and search.

## 22. Implementation Checklist

### P0

- [ ] Resolve module enabled state from `ANALYSIS.md` P0 issue "Module disabled in manifest".
- [ ] Fix API route/controller mismatch from `ANALYSIS.md` P0 issue "API route targets missing method".
- [ ] Repair approval metadata from `ANALYSIS.md` P0 issue "Approval metadata schema mismatch".
- [ ] Enforce permissions from `ANALYSIS.md` P0 issue "Missing authorization on mutating actions".

### P1

- [ ] Add complete validation from `ANALYSIS.md` P1 issue "Registration validation is incomplete".
- [ ] Replace URL credential search from `ANALYSIS.md` P1 issue "Search credentials are passed in URL".
- [ ] Standardize and harden import from `ANALYSIS.md` P1 issue "Import has no file validation or chunking".
- [ ] Refactor export from `ANALYSIS.md` P1 issue "Export loads all matching rows".
- [ ] Normalize date/array persistence from `ANALYSIS.md` P1 issue "Date and array storage mismatch".
- [ ] Make `mhs` generation concurrency safe from `ANALYSIS.md` P1 issue "Race-prone application code generation".

### P2

- [ ] Remove confirmed dead artifacts from `ANALYSIS.md` P2 issue "Placeholder/unused model".
- [ ] Add cache policy from `ANALYSIS.md` P2 issue "Dashboard and catalog data not cached".
