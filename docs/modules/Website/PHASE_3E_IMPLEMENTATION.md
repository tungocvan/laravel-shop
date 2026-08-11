# Website Phase 3E — Transactional Homepage Dual-write

## Trạng thái

- Triển khai: `HOÀN TẤT`
- Kiểm thử tự động: `PASS`
- Kiểm thử giao diện cuối: `PASS — NGƯỜI DÙNG XÁC NHẬN`
- Quyết định: `ĐÃ ĐÓNG`

## Đã triển khai

- `HomepageContentWriteService` điều phối admin writes.
- Legacy `home_*` compatibility write và structured sync nằm trong một outer DB
  transaction; lỗi ở một phía rollback cả hai.
- HomeSettings giữ permission, validation và file cleanup hiện tại.
- Fix hồi quy section ẩn rồi bật lại không cập nhật structured state.
- Bổ sung kéo-thả thứ tự section trong tab Bố cục & Hiển thị.
- Thứ tự được lưu vào `website_sections.position` trong cùng transaction.
- Frontend render section động theo position, không còn hard-code thứ tự trong Blade.

## Kiểm thử

```text
Focused admin/homepage gate: 14 PASS / 136 assertions
Full affected gate: 49 PASS / 10.713 assertions
Hidden -> enabled structured sync: PASS
Legacy + structured write parity: PASS
Full reverse-order persistence/render contract: PASS
Production homepage HTTP smoke: 200
git diff --check: PASS
```

## Yêu cầu kiểm thử giao diện

Kéo một section sang vị trí khác, bấm **Lưu thay đổi**, tải lại trang admin và
frontend. Xác nhận thứ tự mới được giữ ở cả hai nơi và nút bật/tắt vẫn hoạt động.
