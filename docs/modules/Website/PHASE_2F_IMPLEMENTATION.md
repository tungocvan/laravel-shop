# Website Phase 2F — User/Profile Write Ownership

## Trạng thái

- Lát cắt: `2F — User/Profile write ownership`
- Triển khai: `HOÀN TẤT`
- Kiểm thử tự động: `PASS`
- Kiểm thử thủ công: `PASS — NGƯỜI DÙNG XÁC NHẬN`
- Quyết định: `ĐÃ ĐÓNG`

## Nội dung đã triển khai

- Đưa nghiệp vụ cập nhật hồ sơ và đổi mật khẩu sang `Modules/User/Services/UserProfileService`.
- Đưa nghiệp vụ tạo, cập nhật, bật/tắt và xóa khách hàng sang `Modules/User/Services/CustomerService`.
- Website tiếp tục sở hữu route, Livewire component và view vì đây là lớp trình bày storefront/CMS.
- Website không còn sở hữu service ghi dữ liệu hồ sơ khách hàng.
- Giữ nguyên permission hiện có trong component trước khi gọi User service.
- Xóa `Modules/Website/Services/Account/ProfileService.php` sau khi caller về zero.

## Ranh giới ownership

`App/Models/User` là model identity dùng lúc chạy; `Modules/User` sở hữu nghiệp vụ
ghi hồ sơ, mật khẩu, khách hàng và địa chỉ. `Modules/Website` chỉ điều phối giao diện
và authorization dành cho Website admin. Vì vậy không cần di chuyển vật lý các view
hoặc Livewire component để đạt canonical business ownership.

## Ngoài phạm vi

- Không đổi route hoặc view.
- Không đổi permission.
- Không thay đổi schema/migration.
- Không di chuyển checkout workflow trong cùng lát cắt.

## Bằng chứng kiểm thử tự động

```text
Website + User profile/customer gate: 29 PASS / 10.461 assertions
UserProfileCustomerServiceTest: 4 PASS
git diff --check: PASS
```

Các test chạy bằng PHP 8.3 với database SQLite in-memory cô lập. `pdo_sqlite` đã
được cài cho PHP 8.3; hai test settings từng bị bỏ qua nay đều PASS.

## Yêu cầu kiểm thử thủ công

Các nghiệp vụ từ mục 1–7 đã được kiểm tra tự động bằng CLI trên database in-memory.
Người dùng chỉ cần quan sát bằng trình duyệt:

1. Avatar mới hiển thị đúng sau khi tải lên.
2. Modal và thông báo thành công/lỗi hoạt động bình thường.
3. Bố cục trang hồ sơ và quản trị khách hàng không bị vỡ.
