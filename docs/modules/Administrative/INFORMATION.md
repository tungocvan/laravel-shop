# Administrative Module Information

## Purpose

`Administrative` là domain module quản lý thủ tục hành chính, tiếp nhận hồ sơ công khai từ phụ huynh/người dân và hỗ trợ quản trị viên xử lý hồ sơ. Người nộp không cần tài khoản.

## Features

### Public

- Xem danh sách/chi tiết thủ tục đang hoạt động.
- Tải biểu mẫu qua controlled route từ private storage.
- Nộp hồ sơ với nhiều file đính kèm.
- Nhận mã hồ sơ + mã tra cứu bí mật.
- Nhận biên nhận PDF và tùy chọn email.
- Tra cứu trạng thái bằng mã hồ sơ + mã bí mật.
- Session-bound lookup grant 15 phút.
- Tải file kết quả được phép công khai.
- Bổ sung hồ sơ khi trạng thái `need_supplement`.
- Public branding dùng `Modules\System\Models\Setting`.

### Admin

- Danh sách hồ sơ với search/filter/date/procedure/status.
- Bounded pagination: `10/25/50/100` với pagination accent/indigo.
- Checkbox selection và selected count.
- Xóa hồ sơ đã chọn bằng modal xác nhận.
- Xóa tất cả bằng modal xác nhận.
- Soft-delete/archive và ghi audit history.
- Xem chi tiết/tải file/phê duyệt/từ chối/yêu cầu bổ sung.
- Quản lý thủ tục: create/edit/active-inactive/archive.
- Import/Export hồ sơ qua shared Import/Export foundation.
- Export all khi không chọn checkbox; export selected khi có chọn checkbox.
- Import file export trở lại cho update/upsert khi an toàn.
- Success modal + refresh sau Import/Export.

## Demo Data

Module có deterministic demo seeders, không phụ thuộc Faker, tạo bộ thủ tục/hồ sơ đa trạng thái để test UI/workflow trên demo/VPS/Docker.

Seeder entrypoint:

```text
Modules\Administrative\database\seeders\DatabaseSeeder
```

## Registration

Module được auto-discover bởi `Modules\ModuleServiceProvider` từ `Modules/Administrative/`.
Manifest: `Modules/Administrative/config/module.php`; type: `domain`.

## Permissions

```text
administrative.dashboard.view
administrative.procedure.view
administrative.procedure.create
administrative.procedure.update
administrative.procedure.archive
administrative.submission.view
administrative.submission.process
administrative.submission.edit
administrative.submission.delete
administrative.submission.import_export
administrative.file.download
administrative.history.view
```

Compatibility:

- dashboard canonical: `administrative.dashboard.view`, fallback `administrative.submission.view`;
- processing canonical: `administrative.submission.process`, fallback `administrative.submission.edit`;
- history canonical: `administrative.history.view`, legacy submission-view fallback retained where implemented.

## Services

```text
AdministrativeFileService
LookupService
ProcedureService
PublicBrandingService
ReceiptService
SubmissionService
ImportExport
```

Key contracts:

- admin list services always paginate with bounded sizes;
- archive/delete is soft delete + audit;
- ImportExport extends shared `BaseImportExportService`;
- selected export contract:

```text
selected_ids empty     -> export all approved-scope records
selected_ids not empty -> export selected records only
```

- lookup secrets are never exported;
- existing lookup/version system fields are preserved during import update/upsert;
- raw `replace` import mode is blocked for Administrative.

## Models / Tables

Models:

```text
AdministrativeProcedure
AdministrativeSubmission
AdministrativeFile
AdministrativeStatusHistory
```

Tables:

```text
administrative_procedures
administrative_submissions
administrative_files
administrative_status_histories
```

No Round 2 migration is required.

## Status Workflow

```text
pending
├── approved
├── rejected
└── need_supplement
      └── resubmit -> pending
```

State transitions retain transaction + row lock + optimistic version checking.

## File Policy

Default allowed extensions:

```text
pdf, doc, docx, jpg, jpeg, png
```

Default max size: 10 MB/file. Default max count: 5 files. Private storage remains mandatory.

## Tests / Verification

Administrative regression contracts cover route/schema plus Round 1/2 contracts including bounded pagination, delete-all audit, selected-delete modal and Import/Export integration.

Latest supplied full regression checkpoint:

```text
356 passed
12,858 assertions
0 failed
Duration: 19.00s
```

Final post-Round-2 closure verification is required before merge.

## Maintenance Notes

- Preserve route names, table names, storage paths and status values.
- Preserve private file access through controlled routes.
- Keep destructive actions soft-delete + audit.
- Do not restore unbounded `All` list behavior.
- Keep transaction/locking/version semantics.
- Keep Import/Export on shared infrastructure.
- Never expose lookup-token plaintext in export/log/error output.
- Admin form controls should follow `.codex/standards/ADMIN_UI_STANDARD.md` and prefer `x-admin::form.*` primitives when applicable.
