# Administrative Module

Module tiếp nhận và xử lý hồ sơ hành chính dành cho phụ huynh không cần đăng nhập.

## Phạm vi hiện tại

- Quản lý danh mục thủ tục và biểu mẫu trong private storage.
- Nộp hồ sơ công khai, nhiều file, sinh mã hồ sơ và mã tra cứu bí mật.
- Tra cứu bằng mã hồ sơ + mã bí mật, cấp quyền xem trong session 15 phút.
- Quản trị danh sách, chi tiết, tải file, phê duyệt, từ chối và lịch sử xử lý.
- Trạng thái: `pending`, `need_supplement`, `approved`, `rejected`.

## Triển khai

Các lệnh dưới đây làm thay đổi database và chỉ nên chạy sau khi đã sao lưu và kiểm tra đúng môi trường:

```bash
php artisan migrate --path=Modules/Administrative/database/migrations --force
php artisan db:seed --class='Modules\Administrative\database\seeders\DatabaseSeeder' --force
php artisan optimize:clear
npm run build
```

Module dùng disk `local` theo mặc định. Có thể cấu hình disk riêng:

```dotenv
ADMINISTRATIVE_STORAGE_DISK=local
```

Không chuyển file hồ sơ sang disk `public`. Web server cần quyền ghi vào `storage/app` và `storage/framework`.

## URL

Public:

- `/thu-tuc-hanh-chinh`
- `/tra-cuu-ho-so`

Admin:

- `/admin/administrative`
- `/admin/administrative/procedures`

## Phân quyền

Permissions được khai báo trong `config/module.php`. Sau khi bật module, đồng bộ permission bằng màn hình quản lý Modules hiện có hoặc chạy luồng seed/permission của hệ thống.

## Quy tắc vận hành

- Không xóa vật lý hồ sơ đã nộp.
- Chỉ hồ sơ `pending` được duyệt, từ chối hoặc yêu cầu bổ sung.
- Hồ sơ `need_supplement` được phụ huynh gửi lại cùng mã hồ sơ; `rejected` là trạng thái kết thúc.
- Từ chối bắt buộc có nhóm lý do và nội dung chi tiết.
- Không gửi file bằng URL storage trực tiếp; luôn tải qua route có kiểm tra quyền.
- Mã tra cứu rõ chỉ xuất hiện trong biên nhận của session nộp hồ sơ.
- Trước khi triển khai production, xác nhận HTTPS, queue/session/cache dùng shared store nếu chạy nhiều server, backup database và private storage.

## Checklist smoke test

1. Admin tạo thủ tục, upload và tải lại biểu mẫu.
2. Khách mở thủ tục không cần đăng nhập và tải biểu mẫu.
3. Khách nộp hồ sơ hợp lệ, nhận mã hồ sơ và mã tra cứu.
4. File sai MIME, quá dung lượng hoặc quá số lượng phải bị từ chối.
5. Tra cứu sai mã trả cùng một thông báo chung.
6. Tra cứu đúng chỉ xem được hồ sơ trong session hiện tại.
7. Admin tải được file hồ sơ; khách không truy cập được URL file trực tiếp.
8. Hai admin cùng xử lý: thao tác thứ hai phải bị từ chối vì version đã đổi.
9. Hồ sơ bị từ chối phải hiển thị lý do khi tra cứu.
10. Hồ sơ đã xử lý không thể duyệt hoặc từ chối lần nữa.
