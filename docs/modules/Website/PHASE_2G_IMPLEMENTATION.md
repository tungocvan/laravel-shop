# Website Phase 2G — Order-owned Checkout Workflow

## Trạng thái

- Lát cắt: `2G — Order-owned checkout workflow`
- Triển khai: `HOÀN TẤT`
- Kiểm thử tự động: `PASS`
- Kiểm thử thủ công: `PASS — NGƯỜI DÙNG XÁC NHẬN`
- Quyết định: `ĐÃ ĐÓNG`

## Nội dung đã triển khai

- Chuyển `CheckoutService` từ Website sang canonical `Modules/Order/Services`.
- Order workflow không import model hoặc service thuộc Website.
- Thêm `CheckoutContext` cùng DTO bất biến để Website cung cấp snapshot giỏ hàng,
  coupon, user và affiliate cho transaction thuộc Order.
- Thêm `WebsiteCheckoutContext` làm adapter cho cart/session/coupon/affiliate hiện tại.
- Thêm `PaymentResultVerifier`; `MomoService` triển khai contract này.
- Cập nhật Livewire checkout và callback/IPN controller dùng Order service.
- Giữ nguyên khóa cart/product, kiểm tra tồn kho cuối, transaction, retry, trạng thái
  thanh toán và tính idempotent của callback.
- Bổ sung `coupon_code` vào fillable canonical Order, nhưng chỉ ghi trường này khi
  schema hiện tại có cột tương ứng. Database production cũ không có cột vẫn tương
  thích và không cần migration trong Phase 2.
- Xóa Website CheckoutService sau khi caller về zero.
- Sửa ProductController và AffiliateService còn tham chiếu tên `WpProduct` cũ.

## Bằng chứng kiểm thử CLI

```text
OrderCheckoutServiceTest: 4 PASS
Website + User + Order gate: 34 PASS / 10.542 assertions
Composer optimized autoload: PASS
git diff --check: PASS
```

Kiểm thử SQLite xác nhận tạo order/item/history, áp coupon, affiliate attribution,
trừ tồn kho, tăng sold count, consume cart, rollback khi không đủ tồn kho và khả
năng tương thích với schema production chưa có `coupon_code`.

Kiểm thử bổ sung xác nhận đơn chuyển khoản bắt đầu ở trạng thái `pending_payment`.
Người dùng đã xác nhận checkout thực tế trên giao diện thành công sau hotfix schema.

## Yêu cầu kiểm thử giao diện

1. Thêm sản phẩm vào giỏ và đặt một đơn COD; kiểm tra chuyển đến trang thành công.
2. Đặt một đơn chuyển khoản; kiểm tra đơn được tạo và trang thành công hiển thị đúng.
3. Nếu môi trường đã cấu hình MoMo, kiểm tra nút thanh toán chuyển đến cổng MoMo.

Các nhánh dữ liệu/transaction đã được kiểm thử bằng CLI. Ba mục trên chỉ xác nhận
Livewire, session và redirect thực tế trong trình duyệt.
