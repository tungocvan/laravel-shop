# Administrative Module Information

## Purpose

`Administrative` là domain module quản lý thủ tục hành chính, tiếp nhận hồ sơ công khai từ phụ huynh/người dân và hỗ trợ quản trị viên xử lý hồ sơ.

Người nộp không cần tài khoản.

## Features

### Public

- Xem danh sách thủ tục đang hoạt động.
- Xem chi tiết và hướng dẫn thủ tục.
- Tải biểu mẫu từ private storage thông qua controlled route.
- Nộp hồ sơ với nhiều file đính kèm.
- Nhận mã hồ sơ và mã tra cứu bí mật.
- Nhận biên nhận PDF và tùy chọn email.
- Tra cứu trạng thái bằng mã hồ sơ + mã bí mật.
- Được cấp quyền tra cứu trong session 15 phút sau khi xác minh.
- Tải file kết quả được phép công khai cho hồ sơ.
- Bổ sung hồ sơ khi trạng thái là `need_supplement`.

### Admin

- Danh sách hồ sơ, search/filter/date/procedure/status.
- Xem chi tiết hồ sơ.
- Tải file hồ sơ qua route có kiểm tra quyền.
- Phê duyệt.
- Từ chối kèm nhóm lý do và nội dung chi tiết.
- Yêu cầu bổ sung.
- Xem lịch sử trạng thái.
- Soft-delete/archive hồ sơ theo permission.
- Quản lý thủ tục: create/edit/active-inactive/archive.
- Upload/download biểu mẫu thủ tục.

## Registration

Module được auto-discover bởi `Modules\ModuleServiceProvider` từ:

```text
Modules/Administrative/
```

Manifest:

```text
Modules/Administrative/config/module.php
```

Type: `domain`.

Views được đăng ký dưới namespace `Administrative::` và `administrative::`; Livewire aliases được auto-register theo path.

## Routes

### Public

```text
GET /thu-tuc-hanh-chinh
GET /thu-tuc-hanh-chinh/{procedure:slug}
GET /thu-tuc-hanh-chinh/{procedure:slug}/bieu-mau
GET /thu-tuc-hanh-chinh/{procedure:slug}/nop-ho-so
GET /thu-tuc-hanh-chinh/nop-thanh-cong/{receipt}
GET /thu-tuc-hanh-chinh/nop-thanh-cong/{receipt}/bien-nhan.pdf

GET /tra-cuu-ho-so
GET /tra-cuu-ho-so/{accessToken}
GET /tra-cuu-ho-so/{accessToken}/files/{file}
```

### Admin

```text
GET /admin/administrative
GET /admin/administrative/submissions
GET /admin/administrative/submissions/{id}
GET /admin/administrative/submissions/{submission}/files/{file}

GET /admin/administrative/procedures
GET /admin/administrative/procedures/create
GET /admin/administrative/procedures/{id}/template
GET /admin/administrative/procedures/{id}/edit
```

## Permissions

Declared in `config/module.php`:

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
administrative.file.download
administrative.history.view
```

Maintenance note: current enforcement is not fully one-to-one with declarations. Dashboard uses `administrative.submission.view`; approve/reject/supplement use `administrative.submission.edit`; dedicated `submission.process` and `history.view` require reconciliation during refactor.

## Controllers

```text
Modules/Administrative/Http/Controllers/ProcedureController.php
Modules/Administrative/Http/Controllers/PublicLookupController.php
Modules/Administrative/Http/Controllers/PublicProcedureController.php
Modules/Administrative/Http/Controllers/SubmissionController.php
```

Controllers are thin HTTP/page/download adapters; core workflows belong to services/Livewire.

## Livewire Components

```text
Procedures/ProcedureForm.php
Procedures/ProcedureTable.php
Public/LookupForm.php
Public/PublicHeader.php
Public/SubmissionForm.php
Public/SupplementForm.php
Submissions/SubmissionDetail.php
Submissions/SubmissionTable.php
```

## Blade Views

Main groups:

```text
resources/views/layouts/public.blade.php
resources/views/pages/procedures/*
resources/views/pages/public/*
resources/views/pages/submissions/*
resources/views/livewire/procedures/*
resources/views/livewire/public/*
resources/views/livewire/submissions/*
resources/views/emails/*
resources/views/pdf/submission-receipt.blade.php
```

Admin page Blade files are shells; interactive surfaces are Livewire views.

## Services

```text
AdministrativeFileService
LookupService
ProcedureService
PublicBrandingService
ReceiptService
SubmissionService
```

### Responsibilities

- `ProcedureService`: query/create/update/status/archive/template handling.
- `SubmissionService`: submit/list/detail/status workflow/supplement/archive.
- `AdministrativeFileService`: upload validation/storage/attachment/admin download.
- `LookupService`: secret verification/session access/result-file download.
- `ReceiptService`: receipt/PDF/email/status notification workflow.
- `PublicBrandingService`: public branding data.

## Imports / Exports

**Not present.**

File upload/download and receipt PDF generation are document workflows, not spreadsheet import/export.

## Models

```text
AdministrativeProcedure
AdministrativeSubmission
AdministrativeFile
AdministrativeStatusHistory
```

## Database Tables

```text
administrative_procedures
administrative_submissions
administrative_files
administrative_status_histories
```

Migration history includes initial tables plus email receipt preference, supplement workflow and submission soft deletes.

## Relationships

Core relationships:

```text
AdministrativeProcedure
 -> hasMany AdministrativeSubmission

AdministrativeSubmission
 -> belongsTo AdministrativeProcedure
 -> hasMany AdministrativeFile
 -> hasMany AdministrativeStatusHistory
 -> belongsTo Modules\Account\Models\User as processor
```

`processed_by` references the shared `users` table and becomes null if the referenced user is deleted.

## Status Workflow

```text
pending
├── approved
├── rejected
└── need_supplement
      └── resubmit -> pending
```

Rules:

- Only `pending` can be approved, rejected or requested for supplement.
- `need_supplement` can be resubmitted by the public user with valid lookup/session access.
- `rejected` and `approved` are processed terminal states in the current workflow.
- State changes use version checking and row locks.

## File Types

The module uses file-type enum semantics for submission/supplement/result files.

Allowed upload extensions by default:

```text
pdf, doc, docx, jpg, jpeg, png
```

Default max size: 10 MB/file.  
Default max count: 5 files.

Per-procedure limits can override these values.

## Shared / Cross-Module Dependencies

- `Modules\Account\Models\User` for processor relationship.
- `Modules\ModuleServiceProvider` for module discovery and registration.
- Admin shell/auth guard.
- Spatie Laravel Permission.
- Laravel Storage, RateLimiter, session, queue/mail.
- DOMPDF for receipt generation through repository dependencies.

Known dependency metadata gap: source depends on `Account`, but current module manifest does not declare `depends => ['Account']`.

## Events / Jobs

No dedicated `Events/` or `Jobs/` tree was observed in the module.

Mail/receipt notifications are delegated through service/mail classes. Runtime queue behavior should be verified in Local/CI.

## Configuration / Environment Variables

Config:

```text
Modules/Administrative/config/administrative.php
Modules/Administrative/config/module.php
```

Environment variable:

```dotenv
ADMINISTRATIVE_STORAGE_DISK=local
```

Administrative files must remain on a private disk; do not expose the underlying storage path directly.

## Security Controls

- Public submit and lookup rate limiting.
- Secret lookup token stored as hash.
- Session-bound 15-minute lookup grant.
- Private/no-store cache behavior on sensitive pages.
- Private file storage.
- Strict extension/MIME/size validation.
- Server-generated storage names.
- Controlled download routes.
- Admin guard + named permissions.
- Livewire mutation authorization.
- Transaction + locking/version checks for processing.

## Tests

Present under `tests/Feature/Administrative`:

```text
AdministrativeDatabaseStructureTest.php
AdministrativeLookupRouteTest.php
AdministrativeProcedureRouteTest.php
AdministrativePublicRouteTest.php
AdministrativeSubmissionRouteTest.php
```

These tests were inspected but not executed during the GitHub-only analysis.

## Known Limitations

- Admin file download service currently has a missing `AdministrativeFile` model import and should be fixed before relying on the download path.
- Admin list `All` options can load an unbounded dataset.
- Permission declarations and enforcement boundaries are not fully aligned.
- Submission soft-delete/archive does not currently create a module status-history audit entry.
- Critical service/Livewire workflow regression coverage needs expansion.
- Actual responsive UI quality requires screenshot/runtime verification.

## Maintenance Notes

- Do not physically expose/delete private submission files as part of normal workflow.
- Preserve route names, table names, storage paths and status values during refactor unless migration/compatibility impact is explicitly planned.
- Add tests before changing concurrency/status-transition logic.
- Keep business rules in services and UI state/validation in Livewire.
- Avoid unbounded `get()` for production-sized admin datasets.
