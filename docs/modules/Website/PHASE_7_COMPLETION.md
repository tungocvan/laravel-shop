# Website Phase 7 — Production Optimization Completion

## Trạng thái

- Implementation: `COMPLETE`
- Migration: `APPLIED`
- CLI gate: `PASS`
- HTTP gate: `PASS`
- UI cache-invalidation gate: `PASS — USER APPROVED`
- Final status: `CLOSED`

## Kết quả

- Cache homepage composition và SEO trong 15 phút, có invalidation khi page,
  section, item hoặc banner thay đổi.
- Navigation/footer giữ cache riêng; menu reorder không còn gọi
  `Cache::flush()` làm ảnh hưởng toàn ứng dụng.
- Settings group có cache contract và invalidation sau mọi write path.
- Loại N+1 ở homepage post/product cards và dùng review aggregate eager-load.
- Thêm composite index cho active/latest products, best sellers, published posts,
  banners và approved reviews.
- Sitemap XML public, giới hạn dưới 50.000 URL, cache một giờ, không tạo session
  cookie và tự invalidation khi product/post thay đổi.
- Hero ưu tiên ảnh LCP đầu tiên; ảnh còn lại lazy-load/async decode.
- Frontend 404 trả đúng HTTP 404 thay vì redirect lỗi sang Admin.
- Canonical và Product structured data từ Phase 6 tiếp tục vượt HTTP gate.

## Gate

```text
61 tests passed
10,946 assertions
Blade compile passed
Pint passed
git diff --check passed
Vite production build passed
Homepage/Product/Product detail/Sitemap HTTP 200
Missing product HTTP 404
Sitemap XML + public cache header + cookie-free passed
```

## Migration

- `2026_08_11_180000_add_storefront_performance_indexes.php`
- Đã chạy thành công trên MySQL hiện tại.
- Chỉ thêm index, không thay đổi hoặc xóa dữ liệu.

## Lưu ý môi trường

Vite build hoàn tất, nhưng Node.js `18.19.1` thấp hơn phiên bản Vite khuyến nghị
(`20.19+` hoặc `22.12+`). Nên nâng Node trước release production; đây không phải
lỗi build hiện tại.
