# Website Phase 4 — Service Layer Completion

## Trạng thái

- Phase 4A–4E: `IMPLEMENTED`
- CLI gate: `PASS`
- UI gate: `PENDING USER APPROVAL`
- Schema/database migration: `NONE`

## Kết quả

- Storefront controller dùng Product, Order và Cart service thay cho truy vấn model trực tiếp.
- Homepage Livewire nhận dữ liệu qua `HomepageContentService`.
- Product, Post và Account presentation dùng service của canonical domain.
- Coupon admin workflow và Flash Sale product picker không còn giữ transaction/raw table query trong Livewire.
- Registration, newsletter, chat session và product stock lookup dùng service.
- Route và Blade data shape hiện tại được giữ nguyên.

## CLI gate

```text
52 tests passed
10,699 assertions
Pint passed
git diff --check passed
```

Full repository suite còn 4 lỗi ngoài phạm vi Phase 4:

- 3 lỗi cấu hình/template của `PromptEngineCoreTest`.
- 1 lỗi middleware kỳ vọng của `AdminRouteConfigurationTest`.

Các lỗi này xuất hiện ngoài nhóm module bị ảnh hưởng và không được sửa lan phạm vi.

## UI gate cần xác nhận

1. Trang chủ và thứ tự/ẩn hiện các section.
2. Danh sách, lọc, sắp xếp và chi tiết sản phẩm.
3. Danh sách, hero và chi tiết bài viết.
4. Danh sách/chi tiết đơn hàng đúng tài khoản.
5. Checkout và trang đặt hàng thành công.
6. Admin Coupon và Flash Sale.
7. Đăng ký, newsletter, chat và thêm vào giỏ.
