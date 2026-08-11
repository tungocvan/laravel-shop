# Website Phase 3A — Canonical Settings Foundation

## Trạng thái

- Triển khai code: `HOÀN TẤT`
- Audit production read-only: `PASS`
- Dry-run migration: `PASS`
- Ghi dữ liệu production: `PASS — 14 KEY GLOBAL ĐÃ COPY`
- Caller service switch: `HOÀN TẤT`
- Kiểm thử giao diện: `PASS — NGƯỜI DÙNG XÁC NHẬN`
- Quyết định: `ĐÃ ĐÓNG`

## Kết quả audit production

```text
settings: 14 key
wp_settings: 32 key
identical global keys: 14
structured home_* keys: 18
conflicts: 0
```

Không có giá trị settings nào được in trong báo cáo và không có dữ liệu bị thay đổi.

## Đã triển khai

- Canonical `Modules/System/Models/Setting` dùng bảng `settings`.
- Canonical `Modules/System/Services/SettingsService` có transaction, JSON
  normalization, cache namespace riêng và legacy read fallback.
- Lệnh read-only `settings:audit-legacy`.
- Lệnh idempotent `settings:migrate-legacy`; mặc định dry-run, chỉ ghi khi có
  `--apply`.
- Migration service bỏ qua toàn bộ `home_*`, không overwrite key hiện có và trả lỗi
  khi phát hiện conflict.
- Website/Admin callers dùng service đã chuyển sang canonical System service.
- Hai duplicate service Admin/Website đã được xóa sau khi caller về zero.
- `home_*` được khóa đọc/ghi ở `wp_settings` cho đến structured homepage phase.
- Các direct legacy model/seeder caller chưa được xóa trong lát cắt này.

## Kiểm thử tự động

```text
Website + User + Order + System gate:
39 PASS / 10.593 assertions
Production dry-run: 14 candidates / 18 skipped homepage / 0 conflicts
git diff --check: PASS
```

## Kết quả apply production

```bash
php8.3 artisan settings:migrate-legacy --apply
```

Lệnh đã insert 14 key global. Audit sau apply xác nhận 14 key identical, 18 key
`home_*` chỉ tồn tại ở legacy và 0 conflict. Cả 32 dòng `wp_settings` được giữ nguyên.

## Yêu cầu kiểm thử giao diện

1. Mở frontend và kiểm tra tên/logo, header, footer vẫn hiển thị đúng.
2. Trong admin Website, lưu thử một giá trị header hoặc footer rồi tải lại frontend.
3. Mở trang cấu hình homepage và xác nhận dữ liệu section/product hiện tại vẫn còn;
   không bắt buộc lưu thay đổi nếu không muốn tác động nội dung production.
