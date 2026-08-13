# Admission Import Error Tracking — CHANGE PLAN

## Scope

Feature change for the Admission application import workflow.

Current entry point:

- `POST admin/admission/import`
- `AdmissionController::import()`
- `App\Services\Data\Import\GenericImport`
- Admin application index import form

This change is intentionally separate from the `Admission/Search` refactor.

## Goal

Make Admission Excel import resilient and operator-friendly:

1. valid rows continue importing even when other rows fail;
2. invalid rows are skipped, not allowed to abort the whole import;
3. each import run records summary counts;
4. row errors are stored in a dedicated Admission audit trail;
5. after import, admin sees total/success/failed counts;
6. admin can click **Xem lỗi Import** to review exactly which Excel rows need correction;
7. sensitive student/family data is not dumped wholesale into `laravel.log`.

## Current Behavior

`AdmissionController::import()` currently calls:

```php
Excel::import(
    new GenericImport(
        AdmissionApplication::class,
        ['ma_dinh_danh', 'mhs']
    ),
    $request->file('file')
);
```

`GenericImport` already catches exceptions per row and `continue`s, and it skips rows without either configured unique key. However:

- errors only go to Laravel log;
- full row data is logged;
- no import run summary exists;
- no admin-visible error history exists;
- controller always returns `Import thành công` regardless of partial failures;
- Admission-specific validation is not explicit;
- the generic importer is shared infrastructure and should not be overloaded with Admission-specific UI/audit requirements.

## Product Contract

### Import identity rule

For an Admission row:

- at least one of `ma_dinh_danh` or `mhs` must be present to resolve/create/update a record;
- if both are missing: skip row and log error;
- `ma_dinh_danh` is not made globally unique by this change because current schema/business rules do not define that contract;
- when both identifiers are present and they resolve to conflicting existing records, skip the row and log a conflict instead of guessing.

### Date of birth

`ngay_sinh` is not made universally required by this feature unless source/runtime business rules already require it for a specific row operation.

When present:

- it must be parseable as a valid date;
- unsupported/invalid values cause that row to be skipped and logged;
- accepted Excel date formats should be normalized before persistence.

### Partial import

Expected behavior:

```text
Row valid   -> import/update
Row invalid -> skip + record error
Next row    -> continue
```

A row failure must not rollback already-successful rows from the same file.

A fatal file-level error (unreadable file, invalid spreadsheet, missing temporary storage permission, etc.) may fail the run itself and must be shown separately from row-level validation errors.

## Data Model

Add two Admission-owned tables via new migrations; do not rewrite existing migrations.

### `admission_import_runs`

Suggested columns:

```text
id
original_filename
stored_filename nullable
status            // processing | completed | failed
total_rows
success_rows
failed_rows
created_rows
updated_rows
imported_by nullable
started_at
completed_at nullable
fatal_error nullable
created_at
updated_at
```

Indexes:

- `status`
- `imported_by`
- `created_at`

`imported_by` records the `admin` user ID. Foreign key is optional if the repository's admin user ownership is not stable enough for a cross-module FK; preserve loose coupling if needed.

### `admission_import_errors`

Suggested columns:

```text
id
import_run_id
row_number
error_code
field nullable
error_message
ma_dinh_danh nullable
mhs nullable
student_name nullable
row_snapshot json nullable
created_at
updated_at
```

Indexes:

- `import_run_id`
- `error_code`
- `row_number`

FK:

- `import_run_id -> admission_import_runs.id` with cascade delete.

## Sensitive Data Policy

Admission import rows may contain identity numbers, phone numbers, addresses, CCCDs and health-related information.

Therefore:

- do not write full raw rows into Laravel log;
- database error snapshots must be allowlisted/sanitized;
- preferred snapshot fields are only values useful for correcting the Excel row, e.g. identifiers, student name, field/value causing validation failure;
- never store parent/guardian CCCD, phone, address or health content in the error snapshot unless specifically required for the reported field;
- fatal exception logs may include import run ID, exception class and safe message, but not the entire spreadsheet row.

## Architecture

Preferred flow:

```text
Route
  -> AdmissionController (thin)
      -> AdmissionImportService
          -> AdmissionApplicationsImport (Excel adapter)
              -> row validation/normalization
              -> AdmissionApplication persistence
              -> AdmissionImportRun / AdmissionImportError
```

The Admission-specific importer/service lives under `Modules/Admission`.

Do not modify `GenericImport` globally unless a separate shared-infrastructure task proves necessary.

## Service Design

### `AdmissionImportService`

Responsibilities:

- validate uploaded file presence/type/size;
- create import run;
- invoke Laravel Excel import adapter;
- finalize run counts/status;
- expose latest/history/error queries for admin UI;
- handle fatal file-level exceptions;
- keep controller thin.

Candidate APIs:

```php
public function import(UploadedFile $file, ?int $adminId): AdmissionImportRun;
public function latestRuns(int $limit = 10): Collection;
public function errorsForRun(int $runId): LengthAwarePaginator;
```

### Admission-specific Excel importer

Create or replace current unused/legacy `Modules/Admission/Imports/ApplicationsImport.php` with an explicit Admission importer.

Responsibilities per row:

1. normalize heading names;
2. normalize values/types;
3. validate row;
4. resolve identity (`ma_dinh_danh` / `mhs`);
5. detect conflicting identifiers;
6. create or update application;
7. increment run counters;
8. catch row-level exceptions and create `AdmissionImportError`;
9. continue to next row.

Avoid one transaction around the entire file. Use a row-level transaction only where a single row write requires atomicity.

## Validation Rules

Initial safe Admission import rules:

### Required resolution key

```text
ma_dinh_danh OR mhs -> at least one required
```

If `ma_dinh_danh` is present:

- string/numeric representation normalized without scientific notation where possible;
- exactly 12 digits if this matches current Admission UI contract;
- invalid -> skip row.

If `mhs` is present:

- bounded string;
- normalize whitespace;
- invalid/empty with no identifier -> skip row.

### `ngay_sinh`

When present:

- accept valid Excel serial/date objects and supported string formats;
- normalize to `Y-m-d`;
- invalid impossible date -> skip row with field `ngay_sinh`.

### Other fields

Apply conservative data-type/length validation based on current `AdmissionApplication` schema/casts and RegistrationForm rules.

Do not make optional fields required merely because they are present in the Excel template.

### Unknown headings

Unknown Excel columns should be ignored or explicitly reported according to the existing template contract, but must not be blindly mass-assigned.

Use an allowlist of importable AdmissionApplication columns.

## Create / Update Semantics

Resolution rules:

1. if `ma_dinh_danh` matches one record -> update that record;
2. else if `mhs` matches one record -> update that record;
3. if neither resolves -> create a new record if row passes minimum validation;
4. if both supplied and resolve to different records -> skip with `identity_conflict`;
5. if lookup is ambiguous because duplicate identifiers already exist -> skip with `ambiguous_identity`.

Do not silently choose the first matching duplicate.

Import must not grant approval/rejection state transitions implicitly. Status handling should preserve existing record status on update unless the approved import template contract explicitly includes lifecycle status. For new imports, use a safe canonical state such as `pending` or the existing import-state contract only after confirming current business behavior in implementation source/tests.

## Import Run Counters

Track at least:

```text
total_rows
success_rows
failed_rows
created_rows
updated_rows
```

Invariant:

```text
success_rows + failed_rows = total_rows
```

Empty rows ignored by Excel should not count as total rows.

## Controller Changes

Refactor `AdmissionController::import()` to:

1. validate uploaded file;
2. resolve current admin ID from `auth:admin`;
3. call `AdmissionImportService`;
4. redirect back with structured flash summary.

Example summary:

```text
Import hoàn tất: 350 dòng — 337 thành công, 13 lỗi.
```

When failures exist, include the latest `import_run_id` in flash/session so the UI can immediately show **Xem 13 lỗi Import**.

Do not return the current unconditional `Import thành công` message for partial failures.

## Routes / Authorization

Current import route remains:

`admin.admission.import` with `import_admission`.

Add admin routes for import history/errors, e.g.:

```text
GET /admin/admission/imports
GET /admin/admission/imports/{run}/errors
```

Both use:

- `web`
- `auth:admin`
- `permission:import_admission,admin`

No new permission is planned. Anyone allowed to import Admission files may inspect the errors from those imports.

Route names should follow existing convention, e.g.:

```text
admin.admission.imports.index
admin.admission.imports.errors
```

## Admin UI

### Application index

Keep existing import form.

After import display a summary card/banner:

```text
Import hoàn tất
Tổng: 350
Thành công: 337
Lỗi: 13
[Xem 13 lỗi Import]
```

Within the existing `@can('import_admission')` area add:

- `Lịch sử Import` button;
- context button `Xem lỗi Import` when latest run has failures.

### Import history page / Livewire component

Recommended new admin Livewire component:

`Modules/Admission/Livewire/Admin/Imports/History.php`

Functions:

- paginated import runs;
- filename;
- import time;
- importing admin (when resolvable);
- total/success/error counts;
- run status;
- button to view errors.

### Error detail page / modal

Preferred as a dedicated paginated page or a focused Livewire child rather than putting all error rows into a large modal.

Columns:

```text
Excel row
Mã định danh
Mã hồ sơ
Học sinh
Field
Error
```

Optional expandable safe snapshot for debugging the source row.

Add empty state when a run has zero errors.

## Error Codes

Use stable machine-readable codes plus Vietnamese messages. Suggested initial codes:

```text
missing_identity
invalid_ma_dinh_danh
invalid_date
identity_conflict
ambiguous_identity
validation_failed
transform_failed
persistence_failed
file_failed
```

This makes filtering/tests more reliable than parsing exception strings.

## Laravel Log Policy

Retain operational logging but reduce sensitivity.

Recommended log entry:

```text
Admission import row failed
import_run_id: 25
row: 17
error_code: invalid_date
field: ngay_sinh
```

Do not log the entire `$row`.

## File Validation / Temporary Storage

Controller/service should validate:

- file exists;
- supported spreadsheet MIME/extension;
- bounded file size.

Document runtime requirement that Laravel Excel temporary directory under `storage/framework/cache/laravel-excel` must be writable by the PHP-FPM user. This is operational configuration, not an application migration.

## Tests

Add focused tests under e.g.:

`tests/Feature/Admission/AdmissionImportTrackingTest.php`

Required coverage:

### Row behavior

- valid row imports successfully;
- bad row is skipped and following valid row still imports;
- missing both `ma_dinh_danh` and `mhs` creates error;
- invalid 12-digit identity creates error;
- invalid `ngay_sinh` creates error;
- Excel date is normalized correctly;
- identity conflict skips instead of updating the wrong record;
- existing row updates;
- new valid row creates;
- unknown columns are not mass assigned.

### Run summary

- total/success/failed counts are correct;
- created/updated counts are correct;
- completed run records completion timestamp;
- fatal file failure marks run failed.

### Security/privacy

- error record stores only allowlisted/safe snapshot fields;
- full sensitive row is not written through the Admission import logger path.

### Authorization/routes

- import history/errors require `auth:admin`;
- `import_admission` is required;
- unauthorized admin cannot inspect import errors.

### UI

- index shows summary after partial import;
- `Xem lỗi Import` appears when failed rows > 0;
- history is paginated;
- error list shows row number and correction message.

Run at minimum:

```bash
php artisan test tests/Feature/Admission/AdmissionImportTrackingTest.php
php artisan test tests/Feature/Admission/AdmissionRouteConfigurationTest.php
php artisan test tests/Feature/Admission
```

## Expected Files

Likely new files:

```text
Modules/Admission/Models/AdmissionImportRun.php
Modules/Admission/Models/AdmissionImportError.php
Modules/Admission/Services/AdmissionImportService.php
Modules/Admission/Imports/ApplicationsImport.php
Modules/Admission/Livewire/Admin/Imports/History.php
Modules/Admission/Livewire/Admin/Imports/Errors.php (optional if separate component)
Modules/Admission/resources/views/livewire/admin/imports/history.blade.php
Modules/Admission/resources/views/livewire/admin/imports/errors.blade.php
Modules/Admission/resources/views/pages/admin/imports/index.blade.php (if page shell used)
Modules/Admission/resources/views/pages/admin/imports/errors.blade.php (if page shell used)
Modules/Admission/database/migrations/<timestamp>_create_admission_import_runs_table.php
Modules/Admission/database/migrations/<timestamp>_create_admission_import_errors_table.php
tests/Feature/Admission/AdmissionImportTrackingTest.php
```

Likely modified files:

```text
Modules/Admission/Http/Controllers/AdmissionController.php
Modules/Admission/routes/web.php
Modules/Admission/resources/views/livewire/admin/applications/index.blade.php
```

Module config/docs only if table inventory must be updated after implementation.

## Migration / Rollback

Two additive tables only. No changes to existing `admission_applications` columns are planned.

Rollback:

1. remove UI/routes/service/import adapter/models;
2. revert controller to previous import path if necessary;
3. rollback the two new import audit tables;
4. existing application data remains intact.

## Acceptance Criteria

Implementation is complete only when:

- one bad Excel row cannot abort subsequent valid rows;
- every skipped row has a durable import error record;
- import run counts are accurate;
- admin receives partial-success summary instead of unconditional success;
- `Xem lỗi Import` is available after failed rows;
- import history/error pages are protected by `import_admission`;
- error display identifies Excel row and useful correction information;
- sensitive full rows are not dumped to Laravel logs;
- identifiers are resolved deterministically and conflicts are skipped;
- valid date values are normalized and invalid dates are skipped;
- no unrelated module behavior is changed;
- targeted Admission tests pass.

## Approval Gate

This is a feature-change planning artifact only.

Do not implement source code, migrations, routes or UI until the user explicitly approves this `CHANGE_PLAN.md`.