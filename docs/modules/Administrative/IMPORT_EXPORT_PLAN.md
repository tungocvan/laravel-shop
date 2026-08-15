# Administrative Import / Export Plan

## Status

**PLANNING COMPLETE — AWAITING USER APPROVAL**

This plan follows `.codex/tasks/create-import-export.md` and reuses the repository's canonical shared foundation under `Modules/Shared/Services/ImportExport` plus `shared.import-export.panel`.

No Administrative import/export application code may be implemented until the user explicitly approves this plan.

## Goal

Add safe, production-compatible Import / Export for the Administrative submission workspace without bypassing domain rules, exposing lookup secrets, or creating a competing import/export framework.

Primary v1 dataset:

```text
Administrative submissions / Hồ sơ hành chính
```

Procedure catalog import/export is intentionally left as a follow-up dataset after the submissions workflow is verified, because submissions are the primary operational screen requested by the user.

## Existing Foundation To Reuse

Shared service:

```text
Modules/Shared/Services/ImportExport/BaseImportExportService.php
```

Shared UI:

```text
Modules/Shared/Livewire/ImportExport/Panel.php
Modules/Shared/Resources/views/livewire/import-export/panel.blade.php
```

Administrative implementation will provide only module-owned business mapping/validation/persistence logic.

## Proposed Architecture

```text
Admin submission page
 -> SubmissionTable / page shell
 -> shared.import-export.panel
 -> Modules\Administrative\Services\ImportExport
 -> Modules\Shared\Services\ImportExport\BaseImportExportService
 -> AdministrativeSubmission / AdministrativeProcedure
 -> administrative_* tables
```

Business invariants that require status-history creation or lookup-token hashing must remain in Administrative-owned service methods rather than being implemented directly in Blade/Livewire.

## Import Scope

### Accepted formats

```text
.xlsx
.csv
```

Maximum upload size for the first version:

```text
10 MB
```

This matches the current shared panel validation.

### Canonical template headers

Proposed headers:

```text
submission_code
procedure_code
lookup_token
applicant_name
phone
email
wants_email_receipt
student_name
student_code
date_of_birth
current_class
academic_year
relationship
relationship_other
status
response
rejection_reason_code
rejection_reason
supplement_reason
submitted_at
processed_at
```

### Required headers

```text
submission_code
procedure_code
lookup_token
applicant_name
phone
student_name
status
submitted_at
```

Rationale:

- `submission_code` is the stable import/upsert key.
- `procedure_code` avoids exposing internal database IDs.
- `lookup_token` is required on import so imported/demo records can still be tested through the public lookup flow; only its hash is stored.
- applicant/student/status/submitted time are core domain fields.

### Fields never imported directly

```text
id
procedure_id
lookup_token_hash
processed_by
version
revision_count
created_at
updated_at
deleted_at
```

Those values are system-owned or derived.

### Header aliases

Vietnamese-friendly aliases should be accepted, for example:

```text
ma_ho_so -> submission_code
ma_thu_tuc -> procedure_code
ma_tra_cuu -> lookup_token
nguoi_nop -> applicant_name
so_dien_thoai -> phone
hoc_sinh -> student_name
ma_hoc_sinh -> student_code
ngay_sinh -> date_of_birth
lop -> current_class
nam_hoc -> academic_year
quan_he -> relationship
trang_thai -> status
ngay_nop -> submitted_at
ly_do_tu_choi -> rejection_reason
yeu_cau_bo_sung -> supplement_reason
```

Normalization must be handled through the existing shared header-normalization/alias infrastructure.

## Normalization Rules

- Trim all text values.
- Normalize phone by removing spaces.
- Normalize email to lowercase.
- Normalize procedure code and submission code to uppercase.
- Normalize booleans from `1/0`, `true/false`, `yes/no`, `co/khong` when shared helpers support it.
- Normalize dates to `Y-m-d`.
- Normalize datetimes to a database-safe datetime representation.
- Normalize statuses to the canonical values only:
  - `pending`
  - `need_supplement`
  - `approved`
  - `rejected`
- Hash `lookup_token` before persistence; plaintext lookup token must never be stored in the database.

## Validation Rules

### Procedure

`procedure_code` must resolve to an existing, non-deleted Administrative procedure.

Unknown procedure codes produce a row-level error.

### Submission code

- Required.
- Maximum 32 characters.
- Used as duplicate/upsert key.

### Lookup token

- Required for import.
- Minimum sensible length should be enforced.
- Never exported after import.
- Never written to logs or error reports in plaintext.

### Applicant / student

- Applicant name: required string.
- Phone: required string, max 30.
- Email: nullable valid email.
- Student name: required string.
- Student code: nullable string.
- Date of birth: nullable valid date.

### Status-specific validation

`rejected`:

- `rejection_reason` required.
- `rejection_reason_code` recommended/validated against existing vocabulary when present.

`need_supplement`:

- `supplement_reason` required.

`approved` / `pending`:

- rejection fields should be ignored/cleared unless the approved import semantics require preserving historical notes.

## Duplicate Strategy

Canonical unique key:

```text
submission_code
```

Supported modes through the shared panel:

```text
create_only
update_or_create
skip_duplicate
replace
```

Recommended default:

```text
update_or_create
```

### Replace mode safety

The shared foundation supports `replace`, but for Administrative submissions it is high risk because deletion/archive must preserve audit semantics.

Therefore the Administrative service should **disable or override raw shared `replace` behavior** unless it can perform Administrative soft-delete + audit rather than direct model deletion.

For v1, proposed safe behavior:

```text
replace = rejected by Administrative service with a clear report message
```

The existing dedicated "Xóa tất cả" admin action remains the safe audited cleanup workflow.

## Persistence / Domain Integrity

Import must not directly mass-fill system-owned fields.

For every imported row:

1. Resolve `procedure_code` -> `procedure_id`.
2. Hash `lookup_token` -> `lookup_token_hash`.
3. Create/update the submission through Administrative-owned import persistence logic.
4. Set optimistic version/revision to safe defaults for created rows.
5. Create status history records consistent with imported status.

History strategy for newly imported records:

```text
pending:
  null -> pending / submitted

approved:
  null -> pending / submitted
  pending -> approved / approved

rejected:
  null -> pending / submitted
  pending -> rejected / rejected

need_supplement:
  null -> pending / submitted
  pending -> need_supplement / supplement_requested
```

Imported history rows should include metadata such as:

```json
{"source":"administrative_import"}
```

The importing admin should be recorded as actor on processed/import-derived transition rows where applicable.

## Import Transaction Boundaries

Small/medium imports should use the transaction behavior of the shared base service.

No production-sized file should be fully accumulated by new Administrative code.

If runtime verification shows FastExcel/shared base currently loads too many rows for the intended dataset size, the implementation must stop and extend the shared foundation deliberately rather than creating a private workaround.

## Error Reporting

Use the shared import report format.

Every invalid row should report:

- sheet/data source
- row number
- column when available
- safe validation error

Never include plaintext lookup token in error values or debug output.

## Dry Run

Dry-run is required and should remain enabled through `shared.import-export.panel`.

Dry-run must validate:

- headers
- procedure references
- statuses
- status-specific reason requirements
- duplicate behavior
- row mapping

without writing submissions/history.

## Authorization

Proposed module permission:

```text
administrative.submission.import_export
```

Reason for one combined permission in v1:

The existing shared panel accepts a single `permission` value for import, export and template actions. Using one module capability avoids modifying shared infrastructure solely for Administrative.

The panel should only render for users that hold this permission (Super Admin remains covered by repository-wide Gate behavior).

## Import UI

Embed the canonical shared panel in the Administrative submissions workspace, preferably below/inside a collapsible secondary tools area so the primary list/processing task remains visually dominant.

Proposed usage:

```blade
@livewire('shared.import-export.panel', [
    'serviceClass' => \Modules\Administrative\Services\ImportExport::class,
    'title' => 'Import / Export hồ sơ hành chính',
    'description' => 'Kiểm tra dry-run, import Excel/CSV hoặc export hồ sơ hiện tại.',
    'filters' => [...current submission filters...],
    'permission' => 'administrative.submission.import_export',
])
```

No Administrative-specific upload widget should be created unless the shared panel proves insufficient.

## Export Scope

### Authorization

Requires:

```text
administrative.submission.import_export
```

### Filter scope

Export should honor the current admin submission filters:

```text
search
status
procedure_id
date_from
date_to
```

Exporting all records is allowed only when no filters are active.

### Export columns

Proposed safe export columns:

```text
submission_code
procedure_code
procedure_name
applicant_name
phone
email
wants_email_receipt
student_name
student_code
date_of_birth
current_class
academic_year
relationship
relationship_other
status
response
rejection_reason_code
rejection_reason
supplement_reason
submitted_at
processed_by_name
processed_at
revision_count
```

Explicitly excluded:

```text
id
procedure_id
lookup_token_hash
lookup_token
version
deleted_at
internal storage paths
file checksums
```

Public lookup secrets can never be reconstructed from the hash and must never be exposed by export.

## Export Query Strategy

Reuse current Administrative filters and eager-load only required procedure/processor relationships.

The module service should return export rows in stable order, preferably newest submission first unless the existing shared export contract requires another stable order.

No file attachments should be embedded in the spreadsheet.

## Large Dataset Strategy

Initial synchronous threshold should be conservative.

Suggested first implementation target:

```text
<= 5,000 rows synchronous
```

If production requirements exceed this during verification, queue/progress should be introduced using repository-standard infrastructure before enabling larger exports/imports.

Do not solve large exports by calling unbounded `get()` in module UI code.

## Storage / Download Lifecycle

Reuse shared export storage handling.

Administrative exports contain personal data, so long-term target should be private/controlled storage.

However the current shared panel downloads from the shared foundation's configured public disk path. Implementation must first inspect the shared storage concern and either:

1. confirm generated files are short-lived/non-indexed and acceptable under repository standards, or
2. extend the shared foundation consistently to private controlled downloads.

Do not introduce an Administrative-only storage framework.

Temporary import uploads are Livewire temporary uploads and must not be trusted via browser-provided paths.

## Template Export

`exportTemplate()` should generate a clean template containing canonical headers and 2-3 example rows/comments where supported by the shared foundation.

The template must use demo-only placeholder lookup tokens, never real secrets.

## Tests To Add

### Service contract

- service extends `BaseImportExportService`
- correct model class
- required headers
- unique key `submission_code`
- `replace` blocked unless audited semantics are implemented

### Import

- valid pending row imports successfully
- procedure code resolves correctly
- lookup token stored only as hash
- plaintext token absent from persisted model
- rejected requires reason
- need_supplement requires supplement reason
- invalid status rejected
- duplicate mode behavior
- dry-run writes nothing
- imported status history created correctly

### Export

- current filters are honored
- sensitive lookup fields are excluded
- procedure code/name mapped correctly
- processor display name mapped safely

### Permission / UI

- panel is only available with import/export permission
- existing submission list actions remain unaffected

## Failure / Recovery Scenarios

- Unknown procedure code -> row-level failure.
- Duplicate submission code -> behavior determined by selected mode.
- Invalid lookup token -> row-level failure.
- Invalid status-specific fields -> row-level failure.
- Any transaction failure -> shared import transaction rolls back according to mode.
- No import workflow hard-deletes current Administrative data.

## Files Expected To Change After Approval

Likely:

```text
Modules/Administrative/Services/ImportExport.php
Modules/Administrative/Services/SubmissionService.php
Modules/Administrative/Livewire/Submissions/SubmissionTable.php
Modules/Administrative/resources/views/livewire/submissions/submission-table.blade.php
Modules/Administrative/config/module.php
tests/Feature/Administrative/*ImportExport*
docs/modules/Administrative/INFORMATION.md
docs/modules/Administrative/README.md
docs/modules/Administrative/IMPORT_EXPORT_PLAN.md
```

Shared files should only be modified if verification proves the canonical foundation cannot safely satisfy sensitive-data storage or dataset-size requirements.

## Database / Migration Impact

Expected:

```text
No migration required.
```

No existing table/column/status/storage path should be renamed.

## Explicit Non-Goals For V1

- Import/export uploaded attachment binaries.
- Export lookup secrets.
- Import deleted/archive state.
- Spreadsheet-driven hard delete.
- Replace the Shared Import/Export framework.
- Add a second module-specific import/export UI.
- Import/export Administrative procedure catalog in the same first implementation.
- Queue infrastructure unless measured dataset size requires it.

## Acceptance Criteria

Implementation is complete only when:

- shared panel/foundation is reused
- XLSX/CSV template/import/export works
- import uses `procedure_code`, not database IDs
- lookup token plaintext is never persisted/exported/logged
- status-specific validation is enforced
- history/audit semantics remain valid
- duplicate modes are deterministic
- unsafe raw replace is blocked
- export respects active filters
- sensitive/internal fields are excluded
- targeted Administrative tests pass
- Pint passes
- full regression passes
- documentation matches implemented behavior

## Approval Gate

**AWAITING USER APPROVAL**

Do not implement Import / Export until the user explicitly approves this plan.
