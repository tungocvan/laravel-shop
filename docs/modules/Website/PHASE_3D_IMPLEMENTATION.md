# Website Phase 3D — Structured Homepage Dual-read

## Trạng thái

- Triển khai: `HOÀN TẤT`
- Kiểm thử tự động: `PASS`
- Runtime HTTP smoke: `PASS / HTTP 200`
- Kiểm thử giao diện: `PASS — NGƯỜI DÙNG XÁC NHẬN`
- Quyết định: `ĐÃ ĐÓNG`

## Đã triển khai

- `HomepageContentService` ưu tiên published structured homepage.
- Fallback `wp_settings` khi page/section chưa tồn tại.
- HomeList visibility đọc từ structured sections, không còn phụ thuộc Admin service.
- Featured Product IDs đọc ordered section items.
- New arrivals, best sellers và blog limit đọc section config.
- Promo banner đọc section config; trust badges đọc item configs.
- Legacy settings vẫn được giữ để rollback ứng dụng.

## Kiểm thử

```text
Website gate: 34 PASS / 10.651 assertions
Parity before/after backfill: PASS
Orphan filtering, visibility, limits, promo, trust badges: PASS
Production homepage HTTP smoke: 200
git diff --check: PASS
```

## Yêu cầu kiểm thử giao diện

1. Tải lại trang chủ và kiểm tra đủ các section như trước.
2. Kiểm tra product sections, blog, promo và trust badges không bị vỡ bố cục.
3. Kiểm tra desktop/mobile visibility nếu có section được cấu hình riêng thiết bị.

Không cần sửa hoặc lưu nội dung trong bước kiểm thử này.
