# Website Phase 5 — Admin CMS Analysis

## Trạng thái

- Previous phase: `Phase 4 — CLOSED / TESTED / APPROVED`
- Phase 5 analysis: `COMPLETE`
- Implementation: `COMPLETE / TESTED / APPROVED — CLOSED`

## Phạm vi đã khóa

Website Admin chỉ quản trị dữ liệu Website-owned: Dashboard, Homepage, Menu,
Banner, Footer, SEO, Theme và Settings. Product, Order, Customer, Coupon,
Flash Sale và Affiliate tiếp tục thuộc admin area của canonical domain.

## Trình tự triển khai

1. **5A — IA và Website Dashboard:** `IMPLEMENTED / CLI PASS` — route, permission, health counters, quick actions và Admin menu IA.
2. **5B — Homepage Builder:** `IMPLEMENTED / CLI PASS` — section lifecycle, reorder, duplicate, preview và states.
3. **5C — Menu/Banner/Footer:** `IMPLEMENTED / CLI PASS` — CRUD/reorder, responsive admin states và banner scheduling.
4. **5D — SEO/Theme/Settings:** `IMPLEMENTED / CLI PASS` — global/page SEO, OpenGraph, robots, brand assets và scripts.
5. **5E — Release gate:** `CLI + UI PASS` — authorization, validation, loading/empty/error states và regression tests.

## Nguyên tắc

- Không đưa Product/Order/Customer/Marketing trở lại Website ownership.
- Không đổi storefront route hoặc schema nếu chưa thực sự cần.
- Mọi mutation giữ permission tại Livewire boundary và workflow tại service.
- UI admin dùng tiếng Việt và giữ tương thích layout Admin hiện tại.
