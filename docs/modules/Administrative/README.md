# Administrative

Domain module tiếp nhận và xử lý hồ sơ hành chính công khai.

## Module Overview

```text
Public procedure
-> Submit hồ sơ
-> pending
   -> approved
   -> rejected
   -> need_supplement -> resubmit -> pending
```

Người nộp không cần đăng nhập. Tra cứu dùng mã hồ sơ + mã bí mật; file hồ sơ được lưu private và tải qua controlled routes.

## Registration

Module được auto-discover bởi `Modules\ModuleServiceProvider`.

```text
Modules/Administrative/config/module.php
```

Type: `domain`.

Không dùng `nwidart/laravel-modules` hay `module.json`.

## Main Routes

Public:

```text
/thu-tuc-hanh-chinh
/tra-cuu-ho-so
```

Admin:

```text
/admin/administrative
/admin/administrative/procedures
```

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
administrative.file.download
administrative.history.view
```

Permission contract cần được reconcile trong refactor: một số permission đang được khai báo nhưng enforcement hiện dùng capability khác.

## Features

- Quản lý thủ tục và biểu mẫu.
- Public nộp nhiều file.
- Sinh mã hồ sơ và mã tra cứu bí mật.
- Tra cứu session-bound 15 phút.
- PDF/email receipt.
- Admin search/filter/detail/download.
- Approve/reject/request supplement.
- Supplement resubmission.
- Status history.
- Optimistic version + row locking chống xử lý đồng thời.
- Soft-delete/archive hồ sơ.

## Dependencies

- Laravel 12 / PHP 8.3.
- Livewire 3.
- Spatie Permission.
- Laravel private Storage / RateLimiter / session / mail/queue.
- DOMPDF dependency for receipt generation.
- `Modules\Account\Models\User` for `processed_by` relationship.

## Configuration

```dotenv
ADMINISTRATIVE_STORAGE_DISK=local
```

Default upload policy:

```text
Extensions: pdf, doc, docx, jpg, jpeg, png
Max size:   10 MB/file
Max files:  5
```

Per-procedure settings may override file limits.

## Operational Notes

- Không chuyển hồ sơ sang public disk.
- Không expose storage URL trực tiếp.
- Chỉ `pending` được approve/reject/request supplement.
- `need_supplement` có thể resubmit về `pending`.
- State transitions phải giữ transaction + locking/version semantics.
- Production nên dùng HTTPS và shared session/cache/queue khi chạy nhiều instance.
- Backup cả database và private administrative storage.

## Tests

Dedicated feature tests exist under:

```text
tests/Feature/Administrative/
```

Coverage hiện có chủ yếu bảo vệ database structure và route contracts. Cần bổ sung service/Livewire/security regression tests cho file download, upload validation, state transitions, concurrency, lookup/session expiry và archive audit.

## Developer Notes

Kết quả `/analyze Administrative`: **Major Refactor**, không Full Rebuild.

Ưu tiên cho task `/refactor-module Administrative` sau này:

```text
1. Fix AdministrativeFileService admin download model import.
2. Reconcile permission matrix.
3. Add critical workflow regression tests.
4. Remove/bound admin `All` queries.
5. Add audit semantics for submission archive.
6. Profile search + verify UI screenshots/responsive behavior.
7. Declare Account dependency in module metadata if confirmed canonical.
```

Chi tiết evidence và P0/P1/P2 nằm trong `ANALYSIS.md`. Factual module inventory nằm trong `INFORMATION.md`.

## Future Improvements

- Bounded/streamed bulk workflows instead of unbounded list loading.
- Better search strategy only after measured production profiling.
- Stronger audit trail for administrative archive actions.
- Expanded regression coverage and UI quality verification.
