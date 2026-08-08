# Meta App Setup

Các bước dưới đây dựa trên luồng chính thức của Meta Developers cho Facebook Login, Graph API và Page publishing. Giao diện Meta có thể thay đổi, vì vậy hãy kiểm tra lại trong dashboard app trước khi production.

1. Vào Meta Developers và tạo app mới phù hợp với nhu cầu quản lý Page.
2. Lấy `App ID` và `App Secret`, đưa vào `.env` dưới dạng `FACEBOOK_APP_ID` và `FACEBOOK_APP_SECRET`.
3. Thêm domain website và URL website của Laravel trong phần app settings.
4. Thêm sản phẩm Facebook Login hoặc cấu hình OAuth tương ứng.
5. Thêm Valid OAuth Redirect URI trùng tuyệt đối với `FACEBOOK_REDIRECT_URI`, ví dụ `https://your-domain.test/admin/facebook/callback`.
6. Cấu hình scopes Page trong module: `pages_show_list`, `pages_read_engagement`, `pages_manage_posts`.
7. Khi app ở Development Mode, thêm tài khoản quản trị vào Developer/Tester để thử OAuth.
8. Chạy `php artisan facebook:test` để kiểm tra App Access Token và thông tin app.
9. Vào `/admin/facebook`, bấm “Kết nối Facebook”, cấp quyền và quay về callback.
10. Đồng bộ Fanpage tại `/admin/facebook/pages`.
11. Nếu quản lý Page của người khác hoặc production public, thực hiện App Review cho các quyền Page mà Meta yêu cầu.
12. Chuyển app sang Live Mode khi sẵn sàng.

## Lỗi thường gặp

- `redirect_uri mismatch`: URI trong `.env` và Valid OAuth Redirect URI trên Meta không giống nhau từng ký tự.
- Thiếu Page trong `/me/accounts`: tài khoản Facebook chưa có quyền quản lý Page hoặc chưa cấp `pages_show_list`.
- Không đăng được bài: thiếu `pages_manage_posts`, Page token hết hạn, Page bị ngắt khỏi app hoặc nội dung không được Meta chấp nhận.
