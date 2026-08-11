# Website Phase 4 — Service Layer Analysis

## Trạng thái

- Phase: `4 — Service Layer`
- Analysis: `COMPLETE`
- Implementation: `COMPLETE — CLI TESTED`
- Decision: `UI APPROVAL PENDING`
- Previous phase: `Phase 3 — CLOSED / TESTED / APPROVED`

## Kết quả kiểm kê

Website đã có service tốt cho settings, structured homepage writes, checkout adapter,
banner, footer và header. Tuy nhiên query orchestration vẫn phân tán trong controller
và Livewire.

### Controller còn query trực tiếp

- Product detail/related Product.
- Account order count.
- Checkout cart existence và success Order lookup.

### Storefront Livewire còn query trực tiếp

- Product list/detail/review/related product.
- Post list/detail/related post/category.
- Homepage Product/Category/Post/Banner/FlashSale queries.
- Account Order list/detail.
- Register, cart add, newsletter và chat persistence.

### Admin Livewire còn query/mutation trực tiếp

- Coupon import/form/table.
- Flash sale và raw `DB::table('wp_products')`.
- Affiliate matrix.
- Header/footer nested records.
- Customer read presentation.
- Homepage Product/Category picker queries.

## Nguyên tắc

- Website service chỉ orchestration/presentation; không lấy lại domain ownership.
- Product/Category/Post/Order/User query/write contract thuộc canonical module.
- Livewire giữ state, validation, authorization và dispatch UI events; không giữ
  transaction hoặc business query phức tạp.
- Không tạo generic repository chỉ để bọc một dòng Eloquent.
- Mỗi service có typed return contract và focused tests.
- Không thay route/view trong Phase 4 nếu không bắt buộc.

## Trình tự lát cắt đề xuất

### 4A — Thin storefront controllers

- Account summary query qua Order-owned service.
- Checkout page/success lookup qua Cart/Order contracts.
- Product controller qua Product-owned query service.
- Bảo vệ route/status/not-found behavior bằng tests.

### 4B — Homepage query composition

- `HomepageContentService` tiếp tục làm composition layer.
- Product/Category/Post selections gọi canonical domain query services.
- Banner/FlashSale vẫn là Website-owned CMS/marketing services.
- Loại raw/model queries khỏi các homepage Livewire component.

### 4C — Product/Post/Account presentation

- Product list/detail/review/related contracts.
- Post list/detail/category/related contracts.
- Account order list/detail contracts.
- Giữ pagination và Blade data shape hiện tại.

### 4D — Website CMS admin workflows

- Coupon transactional service và import validation.
- FlashSale service thay raw Product table access.
- Header/footer mutations đi hoàn toàn qua service hiện hữu.
- Homepage picker dùng canonical query contracts.
- Permission checks tiếp tục nằm tại boundary Livewire.

### 4E — Interaction workflows and cleanup

- Registration/User, newsletter, chat, wishlist/cart interaction services.
- Affiliate matrix chuyển về canonical owner phù hợp.
- Xóa service/model zero-caller và thêm source guard.

## Cổng kiểm thử

- Route/view/Livewire public contracts không đổi.
- Query result ordering, filters và pagination parity.
- Authorization checks không bị chuyển ra khỏi UI boundary.
- Checkout, settings và structured homepage gates vẫn xanh.
- Không còn direct DB query trong controller hoặc Blade.
- Direct Eloquent trong Livewire chỉ được giữ khi là UI-local lookup có lý do được
  ghi nhận; business mutations phải qua service.

## Ngoài phạm vi

- Không thay schema hoặc xóa `wp_settings`.
- Không redesign admin/frontend.
- Không đổi domain ownership đã khóa ở Phase 2.
- Không tạo abstraction dùng chung khi chưa có ít nhất một caller thực tế.

## Đề xuất

Bắt đầu với **4A — Thin storefront controllers** vì phạm vi nhỏ, dễ kiểm thử và
không thay UI. Chỉ sau khi 4A PASS mới chuyển homepage queries ở 4B.
