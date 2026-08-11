# Website Phase 7 — Production Optimization Analysis

## Trạng thái

- Previous phase: `Phase 6 — CLOSED / TESTED / APPROVED`
- Analysis: `COMPLETE`
- Implementation: `COMPLETE`
- CLI gate: `PASS`
- UI cache-invalidation gate: `PASS — USER APPROVED`

## Baseline cục bộ

Đo qua HTTPS và đúng virtual host `inafo-pharma.laravel.tk`, sau khi warm cache:

| Endpoint | HTTP | Thời gian quan sát |
|---|---:|---:|
| Homepage | 200 | 0,33–0,37 giây |
| Product list | 200 | 0,31 giây |
| Product detail | 200 | 0,30 giây |
| Sitemap | 200 | 0,18 giây |

Đây là baseline môi trường local WSL, không được dùng thay cho production APM.

## Phát hiện

- Homepage composition bị đọc lặp lại qua nhiều method trong cùng render.
- Header reorder dùng `Cache::flush()`, ảnh hưởng cache ngoài phạm vi Website.
- Settings group chưa có cache contract và invalidation tương ứng.
- Homepage post cards truy cập categories/author nhưng query chưa eager-load.
- Các truy vấn active/latest/best-seller/review thiếu composite index phù hợp.
- Chưa có sitemap XML công khai và cache header rõ ràng.
- Hero chưa ưu tiên ảnh LCP; ảnh category/product chưa khai báo async decode đầy đủ.
- Asset build chạy được nhưng Node 18.19.1 thấp hơn version Vite khuyến nghị.

## Phạm vi triển khai

1. Cache homepage composition, homepage SEO, navigation, footer và settings.
2. Invalidation theo cache key; tuyệt đối không flush toàn bộ cache.
3. Eager loading/aggregate cho cards và giới hạn query rõ ràng.
4. Composite indexes chỉ phục vụ các access pattern đã quan sát.
5. Sitemap XML tối đa dưới 50.000 URL, cache một giờ và tự invalidation.
6. Image loading hints, asset build review, canonical/structured-data/404 gate.
