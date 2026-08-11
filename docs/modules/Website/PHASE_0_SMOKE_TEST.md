# Website Phase 0 — Manual Smoke Test

## Mục tiêu

Checklist này dùng để xác nhận hành vi thực tế của `Modules/Website` trước khi bắt đầu Phase 1.

Chỉ đánh dấu một trong bốn trạng thái:

- `PASS` — hoạt động bình thường.
- `BROKEN` — lỗi rõ ràng, không sử dụng được.
- `PARTIAL` — hoạt động một phần hoặc có lỗi phụ.
- `NOT USED` — chức năng hiện không sử dụng / chưa cấu hình.

Không sửa code trong lúc chạy checklist. Nếu gặp lỗi, ghi lại URL, hành động vừa thực hiện và thông báo lỗi.

---

## A. Frontend — Public

### A1. Homepage

URL: `/`

- [ ] Trang load không lỗi 500.
- [ ] Header hiển thị.
- [ ] Footer hiển thị.
- [ ] Hero/banner nếu có cấu hình hiển thị.
- [ ] Các section sản phẩm/danh mục/blog hiển thị dữ liệu nếu có.
- [ ] Link chính có thể click.

Kết quả: `PASS / BROKEN / PARTIAL / NOT USED`

Ghi chú:

---

### A2. Help

URL: `/help`

- [ ] Trang load không lỗi 500.
- [ ] Nội dung hiển thị.

Kết quả: `PASS / BROKEN / PARTIAL / NOT USED`

Ghi chú:

---

### A3. Product Listing

URL: `/product`

- [ ] Trang load.
- [ ] Danh sách sản phẩm hiển thị.
- [ ] Pagination/filter/search nếu có hoạt động.
- [ ] Click product mở đúng detail.

Kết quả: `PASS / BROKEN / PARTIAL / NOT USED`

Ghi chú:

---

### A4. Product Detail

URL: `/product/{slug}`

- [ ] Trang load đúng sản phẩm.
- [ ] Tên/ảnh/giá hiển thị.
- [ ] Tồn kho/quantity hiển thị hợp lý nếu có.
- [ ] Add to Cart hoạt động.

Kết quả: `PASS / BROKEN / PARTIAL / NOT USED`

Ghi chú:

---

### A5. Blog

URLs: `/blog`, `/blog/{slug}`

- [ ] Blog list load.
- [ ] Blog detail load.
- [ ] Link từ list sang detail đúng.

Kết quả: `PASS / BROKEN / PARTIAL / NOT USED`

Ghi chú:

---

### A6. Authentication

URLs: `/login`, `/register`, `POST /website/logout`

- [ ] Login page load.
- [ ] Login thành công với tài khoản test hợp lệ.
- [ ] Login sai hiển thị lỗi hợp lý.
- [ ] Register page load.
- [ ] Register nếu đang sử dụng hoạt động.
- [ ] Logout hoạt động.

Kết quả: `PASS / BROKEN / PARTIAL / NOT USED`

Ghi chú:

---

## B. Frontend — Cart

URL: `/cart`

Thực hiện với một sản phẩm đang active và còn stock.

- [ ] Add sản phẩm vào cart.
- [ ] Cart icon/count cập nhật.
- [ ] Increment quantity.
- [ ] Decrement quantity.
- [ ] Không vượt stock.
- [ ] Remove item.
- [ ] Refresh trang vẫn giữ đúng cart theo session/user.
- [ ] Coupon hợp lệ được apply nếu có dữ liệu test.
- [ ] Coupon sai/hết hạn bị reject.
- [ ] Tổng tiền/subtotal/discount đúng bằng quan sát thông thường.

Kết quả: `PASS / BROKEN / PARTIAL / NOT USED`

Ghi chú:

---

## C. Frontend — Checkout

URLs: `/checkout`, `/checkout/success`, `/checkout/momo-callback`

### C1. Checkout Page

- [ ] Cart có sản phẩm thì mở được checkout.
- [ ] Cart trống không cho checkout sai flow.
- [ ] Form hiển thị đầy đủ customer information.
- [ ] Validation bắt các field bắt buộc.

### C2. COD

Nếu COD đang enabled:

- [ ] Submit một đơn COD test.
- [ ] Chỉ tạo một order.
- [ ] Order items đúng.
- [ ] Tổng tiền đúng.
- [ ] Cart được clear sau thành công.
- [ ] Redirect success đúng.

### C3. MoMo

- [ ] Ghi nhận trạng thái thực tế của MoMo callback.

Baseline source hiện biết route `checkout.momo.callback` tồn tại nhưng controller thiếu `momoCallback()`. Nếu runtime lỗi, ghi `BROKEN BEFORE REFACTOR`, không xem là regression của Phase 1.

Kết quả: `PASS / BROKEN / PARTIAL / NOT USED`

Ghi chú:

---

## D. Frontend — Account

Đăng nhập user test trước khi chạy.

### D1. Dashboard

URL: `/account`

- [ ] Load được.
- [ ] Thông tin dashboard hiển thị.

### D2. Profile

URL: `/account/profile`

- [ ] Load được.
- [ ] Update thông tin cơ bản hoạt động.
- [ ] Address UI nếu có hoạt động.

### D3. Orders

URLs: `/account/orders`, `/account/orders/{code}`

- [ ] Danh sách đơn load.
- [ ] Order detail của chính user load.
- [ ] Không phát sinh lỗi 500 khi mở detail.

Không thử truy cập dữ liệu người khác trên production. Record-ownership sẽ được test tự động trong Phase 1/2.

### D4. Wishlist

URL: `/account/wishlist`

- [ ] Trang load.
- [ ] Add/remove wishlist nếu UI hỗ trợ.

### D5. Affiliate

URL: `/account/affiliate`

- [ ] Trang load nếu tính năng đang dùng.
- [ ] Không lỗi 500.

Kết quả nhóm Account: `PASS / BROKEN / PARTIAL / NOT USED`

Ghi chú:

---

## E. Website Admin

Đăng nhập bằng admin test.

### E1. Homepage Settings

URL: `/admin/homepage-settings`

- [ ] Trang load.
- [ ] Dữ liệu hiện tại load đúng.
- [ ] Thay đổi một setting an toàn và lưu được.
- [ ] Refresh admin vẫn thấy giá trị mới.
- [ ] Frontend phản ánh thay đổi hoặc ghi nhận nếu cache bị stale.

Kết quả: `PASS / BROKEN / PARTIAL / NOT USED`

Ghi chú:

---

### E2. Header Settings

URL: `/admin/header-settings`

- [ ] Trang load.
- [ ] Menu/header data load.
- [ ] Update an toàn hoạt động.

Kết quả: `PASS / BROKEN / PARTIAL / NOT USED`

Ghi chú:

---

### E3. Footer Settings

URL: `/admin/footer-settings`

- [ ] Trang load.
- [ ] Footer columns/links/social load.
- [ ] Update an toàn hoạt động.

Kết quả: `PASS / BROKEN / PARTIAL / NOT USED`

Ghi chú:

---

### E4. Banner

URL: `/admin/banners`

- [ ] Trang load.
- [ ] Danh sách banner load.
- [ ] Create/update nếu có hoạt động.
- [ ] Upload image nếu test được.

Kết quả: `PASS / BROKEN / PARTIAL / NOT USED`

Ghi chú:

---

### E5. Flash Sale

URL: `/admin/flash-sales`

- [ ] Trang load.
- [ ] Danh sách/config load.
- [ ] Update an toàn hoạt động.

Kết quả: `PASS / BROKEN / PARTIAL / NOT USED`

Ghi chú:

---

### E6. Coupon

URL: `/admin/coupons`

- [ ] List load.
- [ ] Create page load.
- [ ] Edit page load.
- [ ] Create/update một coupon test nếu môi trường cho phép.

Kết quả: `PASS / BROKEN / PARTIAL / NOT USED`

Ghi chú:

---

### E7. Customers

URL: `/admin/customers`

- [ ] List load.
- [ ] Search/filter hoạt động nếu có.
- [ ] Customer detail load.
- [ ] Create page load nếu đang sử dụng.

Không test delete/bulk delete trên production data.

Kết quả: `PASS / BROKEN / PARTIAL / NOT USED`

Ghi chú:

---

### E8. Affiliate Admin

URL: `/admin/affiliate`

- [ ] Trang load.
- [ ] Commission/affiliate data load nếu có.
- [ ] Không lỗi 500.

Kết quả: `PASS / BROKEN / PARTIAL / NOT USED`

Ghi chú:

---

## F. Những lỗi cần đặc biệt ghi lại

Nếu gặp các trường hợp sau, ghi rõ vào báo cáo:

- HTTP 500.
- Livewire error/modal error.
- SQL exception.
- Missing table/column.
- Route not found.
- Component not found.
- Stale setting sau khi save.
- Cart sai user/session.
- Order duplicate.
- Sai tổng tiền.
- Stock âm hoặc vượt tồn.
- Admin action thực hiện được dù tài khoản không nên có quyền.

---

## G. Mẫu kết quả gửi lại

Có thể copy đoạn dưới và điền nhanh:

```text
PHASE 0 MANUAL SMOKE RESULT

Environment:
Commit:
URL:

Frontend
Homepage: PASS/BROKEN/PARTIAL/NOT USED
Help: PASS/BROKEN/PARTIAL/NOT USED
Product list: PASS/BROKEN/PARTIAL/NOT USED
Product detail: PASS/BROKEN/PARTIAL/NOT USED
Blog: PASS/BROKEN/PARTIAL/NOT USED
Auth: PASS/BROKEN/PARTIAL/NOT USED
Cart: PASS/BROKEN/PARTIAL/NOT USED
Checkout: PASS/BROKEN/PARTIAL/NOT USED
Account: PASS/BROKEN/PARTIAL/NOT USED
Wishlist: PASS/BROKEN/PARTIAL/NOT USED
Affiliate: PASS/BROKEN/PARTIAL/NOT USED

Admin
Homepage settings: PASS/BROKEN/PARTIAL/NOT USED
Header: PASS/BROKEN/PARTIAL/NOT USED
Footer: PASS/BROKEN/PARTIAL/NOT USED
Banner: PASS/BROKEN/PARTIAL/NOT USED
Flash sale: PASS/BROKEN/PARTIAL/NOT USED
Coupon: PASS/BROKEN/PARTIAL/NOT USED
Customers: PASS/BROKEN/PARTIAL/NOT USED
Affiliate: PASS/BROKEN/PARTIAL/NOT USED

Errors / observations:
- 
```

## Phase 0 Gate

Sau khi manual smoke test hoàn tất:

1. Các lỗi runtime mới được thêm vào `PHASE_0_BASELINE.md`.
2. Known defects được đóng dấu `BROKEN BEFORE REFACTOR` nếu đã tồn tại.
3. Phase 0 chỉ được chuyển thành `APPROVED` khi user xác nhận.
4. Chỉ sau đó mới bắt đầu `Phase 1A — Checkout Stabilization`.
