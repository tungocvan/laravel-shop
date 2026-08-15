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

### Admin

- Danh sách hồ sơ với search/filter/date/procedure/status.
- Bounded pagination: 10/25/50/100.
- Xem chi tiết hồ sơ.
- Tải file hồ sơ qua route có kiểm tra quyền.
- Phê duyệt, từ chối, yêu cầu bổ sung.
- Xem lịch sử trạng thái theo permission.
- Soft-delete/archive hồ sơ theo permission và ghi audit history.
- Quản lý thủ tục: create/edit/active-inactive/archive.
- Upload/download biểu mẫu thủ tục.

## Registration

Module được auto-discover bởi `Modules\ModuleServiceProvider` từ `Modules/Administrative/`.

Manifest: `Modules/Administrative/config/module.php`  
Type: `domain`.

Views được đăng ký dưới namespace `Administrative::` và `administrative::`; Livewire aliases được auto-register theo path.

## Routes

Public:

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

Admin:

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

Declared permissions:

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

Current post-refactor behavior:

- Dashboard canonical permission: `administrative.dashboard.view`, with `administrative.submission.view` fallback for backward compatibility.
- Approve/reject/request-supplement canonical permission: `administrative.submission.process`, with `administrative.submission.edit` fallback.
- History UI is gated by `administrative.history.view` or legacy `administrative.submission.view` fallback.
- Sensitive Livewire mutations still authorize at action boundary.

## Controllers

```text
ProcedureController
PublicLookupController
PublicProcedureController
SubmissionController
```

Controllers remain thin HTTP/page/download adapters; core workflows belong to services/Livewire.

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

## Services

```text
AdministrativeFileService
LookupService
ProcedureService
PublicBrandingService
ReceiptService
SubmissionService
```

Key post-refactor facts:

- `AdministrativeFileService` correctly imports `Modules\Administrative\Models\AdministrativeFile`.
- `ProcedureService::listForAdmin()` and `SubmissionService::listForAdmin()` always paginate with normalized bounded page sizes.
- `SubmissionService::softDeleteMany()` writes `SubmissionAction::Archived` history with admin actor metadata before soft delete.

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

## Status Workflow

```text
pending
├── approved
├── rejected
└── need_supplement
      └── resubmit -> pending
```

Only `pending` may be approved/rejected/requested for supplement. State changes retain transaction + row lock + optimistic version checking.

## File Policy

Default allowed extensions:

```text
pdf, doc, docx, jpg, jpeg, png
```

Default max size: 10 MB/file.  
Default max count: 5 files.

Private storage remains mandatory.

## Dependencies

- Laravel 12 / PHP 8.3.
- Livewire 3.
- Spatie Permission.
- Laravel Storage, RateLimiter, session, queue/mail.
- DOMPDF.
- `Modules\Account\Models\User` for processor relationship.

Known metadata note: source depends on `Account`; manifest dependency declaration remains a future repository-level consistency item.

## Tests / Verification

Tests under `tests/Feature/Administrative` now include the original route/schema suite plus `AdministrativeRefactorContractTest.php`.

Verified on Local, 2026-08-15:

```text
Pint: PASS — 47 files
Full regression: PASS — 353 tests / 12,815 assertions
Duration: 22.73s
```

## Maintenance Notes

- Preserve route names, table names, storage paths and status values.
- Preserve private file access through controlled routes.
- Keep archive as soft delete with audit entry.
- Do not restore unbounded `All` list behavior; use export/streaming if bulk access becomes necessary.
- Keep transaction/locking/version semantics when modifying workflow logic.
