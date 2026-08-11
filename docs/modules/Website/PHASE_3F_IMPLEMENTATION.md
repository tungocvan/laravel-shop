# Website Phase 3F — Canonical Settings Runtime Cleanup

## Trạng thái

- Triển khai: `HOÀN TẤT`
- Kiểm thử tự động: `PASS`
- Kiểm thử giao diện: `CHỜ NGƯỜI DÙNG`
- Quyết định: `SẴN SÀNG KIỂM THỬ GIAO DIỆN`

## Đã triển khai

- Admin global settings callers dùng `Modules/System/Models/Setting`.
- System Setting có compatibility helpers gọi canonical transactional service.
- Xóa duplicate Admin Setting model và SettingsService.
- Xóa duplicate Admin homepage Livewire/service/views.
- Admin homepage page dùng cùng Website homepage editor có structured dual-write và
  kéo-thả.
- Giữ `wp_settings`, Website legacy model, migration tools và rollback fallback;
  chưa xóa legacy data trong lát cắt này.

## Kiểm thử

```text
Focused ownership/routes/homepage gate: 18 PASS / 186 assertions
Full affected gate: 50 PASS / 10.686 assertions
Composer optimized autoload: PASS (existing unrelated PSR-4 warnings retained)
Production homepage HTTP smoke: 200
git diff --check: PASS
```

## Yêu cầu kiểm thử giao diện

1. Mở trang Settings/System và xác nhận form general/logo/SEO tải bình thường.
2. Mở homepage editor từ menu Admin và xác nhận đó là editor mới có kéo-thả.
3. Không bắt buộc lưu dữ liệu; chỉ cần xác nhận không có lỗi Livewire/class missing.
