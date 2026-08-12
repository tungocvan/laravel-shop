# Website Phase 8 — Release Gate Analysis

## Trạng thái

- Previous phase: `Phase 7 — CLOSED / TESTED / APPROVED`
- Analysis: `COMPLETE`
- Implementation: `COMPLETE`
- CLI release gate: `PASS`
- UI release smoke gate: `PENDING USER VERIFICATION`

## Zero-caller audit

Được phép xóa sau static search, route inspection và optimized autoload:

- `Website/Services/ProductService.php`
- `Website/Services/CategoryService.php`
- `Website/Services/ContentService.php`
- `Website/Services/MarketingService.php`
- misplaced duplicate `Admin/Models/AffiliateLevel.php`
- toàn bộ duplicate models/services đã xóa ở Phase 2–4, gồm `Services/Services`.

Không xóa:

- Affiliate services vì vẫn có Livewire callers.
- `wp_settings` và homepage legacy keys vì Homepage Builder vẫn dùng
  compatibility write trước structured backfill. Xóa lúc này là breaking change.
- Bảng/cột legacy khác khi chưa có zero-caller proof và rollback window.

## Release findings

- Website seeders có case namespace không đồng nhất trên Linux, khiến Composer
  optimized autoload bỏ qua từng seeder class.
- Fresh migration cần chạy độc lập trên SQLite in-memory, không tác động MySQL.
- Full repository suite chứa debt ngoài Website; phải báo riêng, không gán cho
  Website release gate.
- Node 18 vẫn thấp hơn khuyến nghị của Vite dù production build thành công.
