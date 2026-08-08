# Kế hoạch refactor module Pharma

## Executive Summary

Thực hiện Major Refactor theo `ANALYSIS.md`: containment quyền và dữ liệu báo giá trước, sau đó sửa shared import/export/file storage, route/DB correctness, rồi tối ưu workbook và test. Giữ nguyên domain boundary Pharma và các lớp phân tích/build workbook đang có.

## P0 Critical Fixes

1. **Authorization (`PH-P0-01`)**: permission middleware cho page; authorize trong mọi Livewire mutation/import/export/generate; policy/record checks cho ID; denied tests.
2. **Không serialize workbook đầy đủ (`PH-P0-02`)**: public state chỉ giữ projection 5 field; full values/snapshot ở server; authorize trước analyze.
3. **Khóa shared service resolution (`PH-P0-03`)**: registry server-owned/locked key, interface + allowlist + capability; không resolve class tùy ý từ client state.
4. **Private exports (`PH-P0-04`)**: chuyển Pharma/shared exports sang private disk, authorized download/token ngắn hạn, retention và cleanup.

## P1 Important Refactors

- Sửa/gỡ API và supplier import-export route hỏng (`PH-P1-COR-01`).
- Định nghĩa permission `view/create/update/delete/import/export/generate_price_list`; cập nhật manifest/role/menu.
- Chặn formula injection, không trả raw exception; structured logs + correlation (`PH-P1-SEC-05`).
- Cache server-side workbook analysis theo hash/mtime, tránh load lặp; giới hạn file/row/column; queue khi vượt threshold (`PH-P1-PERF-01`).
- Giới hạn output path; tách trusted CLI override khỏi web contract (`PH-P1-COR-03`).
- Chốt Supplier unique/nullability, thêm forward migration và concurrency test (`PH-P1-COR-02`).
- Bỏ `All`, cap pagination; chunk/lazy/queue import-export; preload medicine lookup (`PH-P1-PERF-02`).
- Cố định import mode theo aggregate trừ khi người dùng có capability/policy đặc biệt.
- Chuẩn hóa partial-vs-atomic transaction/report, header verification, ambiguous medicine lookup và cleanup temp.
- Khai báo `phpoffice/phpspreadsheet` là direct dependency nếu module tiếp tục import namespace trực tiếp.
- Cân nhắc persist quotation/audit/version nếu báo giá là chứng từ phát hành.

## P2 Nice To Have Improvements

- Xóa `Models/Pharma.php`, placeholder và route/show artifacts sau test.
- Thêm menu tạo báo giá theo permission.
- Inverse relations, enums/status, typed Livewire/Form objects.
- Metrics: workbook parse/build duration, size, rows, export/import failures, cleanup backlog.
- Cập nhật module README và runbook nguồn workbook.

## Recommended Implementation Order

1. Viết characterization + denied/tampering tests.
2. Đóng quyền route/action và giảm public Livewire state.
3. Khóa Shared Panel registry; chuyển export private + cleanup.
4. Sửa route hỏng/API.
5. Chặn formula/raw errors/output path.
6. Chốt quotation lifecycle/audit và Supplier key.
7. Tối ưu/cached workbook, bounded import/export/list.
8. Thêm migration/invariants, full tests, observability, cleanup.

## Files Change Matrix

| Nhóm | File dự kiến | Mục tiêu |
|---|---|---|
| Quyền | `routes/web.php`, manifest, mọi Pharma Livewire, policies/tests, Admin menu | capability + record authorization |
| Báo giá | `PriceList/Create.php`, `PriceListService.php`, analyzer/builder, DTO/tests | projection, cache, safe text/path, lifecycle |
| Shared I/E | Shared Panel/Base/storage concerns + Pharma I/E/tests | registry, private files, chunking, mode |
| Route/API | `routes/api.php`, controllers, route tests | bỏ/sửa contract hỏng |
| DB | forward migrations, models/services/tests | Supplier unique/audit/quotation records nếu duyệt |
| Ops | commands, logs, cleanup job/scheduler, docs | retention, metrics, recovery |

## Risk Control

- Không xóa legacy trước khi route/service callers và tests được xác nhận.
- Permission rollout phải seed/assign role trước khi bật middleware.
- Không đưa workbook full values vào cache/client không mã hóa; key cache theo hash và quyền.
- Chuyển public export sang private nhưng giữ grace-period migration cho link đang dùng.
- Cleanup chạy report-only/quarantine trước khi xóa.
- Nếu persist quotation, dùng additive migration và version source workbook.

## Test Strategy

- Route/auth: unauthenticated, admin thiếu quyền, đúng quyền, Super Admin.
- Livewire: mọi action, tampered ID/rows/columns/service key/state.
- PriceList: missing/corrupt/duplicate header, formula-like text, projection leak, output confinement, cleanup, large workbook, concurrent generate.
- Import: header mapping, mode, duplicate, null, row error, dry-run, relationship ambiguity, transaction contract.
- Export: filters, private authorized download, expiry, chunk/memory.
- DB: fresh/rollback MySQL + SQLite, unique/concurrency/cascade.
- Performance: query count, workbook load count, memory/time thresholds.

## Rollback Notes

- Có thể rollback UI/cache tối ưu độc lập, nhưng không rollback authorization hoặc private download về trạng thái public.
- Giữ worker/scheduler cleanup tắt cho đến khi retention được xác nhận.
- Forward migration Supplier cần backup/deduplicate report và rollback index rõ.
- Khi thay đổi quotation format, giữ version builder cũ để tái tạo file đã phát hành nếu nghiệp vụ yêu cầu.
