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

Module được auto-discover bởi `Modules\ModuleServiceProvider` từ `Modules/Administrative/config/module.php` với type `domain`.

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
administrative.submission.import_export
administrative.file.download
administrative.history.view
```

## Features

- Quản lý thủ tục và biểu mẫu.
- Public nộp nhiều file.
- Sinh mã hồ sơ và mã tra cứu bí mật.
- Tra cứu session-bound 15 phút.
- PDF/email receipt.
- Admin search/filter/detail/download.
- Approve/reject/request supplement.
- Supplement resubmission.
- Status history + optimistic version + row locking.
- Soft-delete/archive hồ sơ có audit history.
- Delete selected và Delete all dùng modal xác nhận.
- Bounded pagination `10/25/50/100` với active state indigo.
- Import/Export hồ sơ bằng shared framework.
- Không chọn checkbox -> export toàn bộ approved scope.
- Có chọn checkbox -> export đúng hồ sơ được chọn.
- File export có thể import ngược cho update/upsert khi an toàn.
- Import/Export success modal có nút refresh.
- Deterministic demo seeders, không dùng Faker.
- Public branding dùng `Modules\System\Models\Setting`.

## Import / Export

Service:

```text
Modules\Administrative\Services\ImportExport
```

Shared UI:

```text
shared.import-export.panel
```

Canonical selected export contract:

```text
selected_ids empty     -> export all approved-scope records
selected_ids not empty -> export selected records only
```

Security rules:

- không export `lookup_token` hoặc `lookup_token_hash`;
- existing system-owned lookup/version fields không bị overwrite khi update;
- unsafe raw `replace` bị chặn;
- import errors redact lookup secret.

## Demo Seeder

```bash
php artisan db:seed --class="Modules\\Administrative\\database\\seeders\\DatabaseSeeder" --force
```

Seeder dùng dữ liệu deterministic để test nhiều thủ tục/trạng thái, phù hợp demo/Docker/VPS.

## UI Standard

Administrative tuân thủ `.codex/standards/ADMIN_UI_STANDARD.md`:

- bounded pagination;
- filter/reset;
- destructive modal;
- visible bordered form controls;
- ưu tiên shared form components `x-admin::form.input`, `textarea`, `select`, `error` khi phù hợp.

## Operational Notes

- Không chuyển hồ sơ sang public disk.
- Không expose storage URL trực tiếp.
- Chỉ `pending` được approve/reject/request supplement.
- `need_supplement` có thể resubmit về `pending`.
- State transitions phải giữ transaction + locking/version semantics.
- Import/Export phải tiếp tục dùng shared infrastructure.
- Backup cả database và private administrative storage.

## Verification

Latest supplied full regression checkpoint:

```text
356 passed
12,858 assertions
0 failed
Duration: 19.00s
```

User manual UI verification đã PASS các luồng chính: delete all, selected/all export, import-back và Administrative list workflow.

Round 2 cần một lượt Pint + targeted tests + frontend build/full regression cuối sau khi pull documentation/shared UI changes mới nhất.

## Refactor Status

`/refactor-module Administrative`:

```text
Round 1: COMPLETED / MERGED
Round 2: IMPLEMENTED / FINAL VERIFICATION PENDING
```

Chi tiết nằm trong `ANALYSIS.md`, `INFORMATION.md`, `REFACTOR_PLAN.md`, `IMPORT_EXPORT_PLAN.md`.
