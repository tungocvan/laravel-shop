# Facebook Fanpage Module

Module `Facebook` dùng để kết nối Meta App, OAuth quản trị viên, đồng bộ Fanpage, lưu Page Access Token đã mã hóa và đăng bài lên Fanpage qua Meta Graph API.

## Cấu hình

Thêm vào `.env`:

```env
FACEBOOK_GRAPH_VERSION=v25.0
FACEBOOK_OAUTH_BASE_URL=https://www.facebook.com
FACEBOOK_APP_ID=
FACEBOOK_APP_SECRET=
FACEBOOK_REDIRECT_URI=https://your-domain.test/admin/facebook/callback
FACEBOOK_SCOPES=pages_show_list,pages_read_engagement,pages_manage_posts
FACEBOOK_WEBHOOK_VERIFY_TOKEN=
FACEBOOK_HTTP_TIMEOUT=30
FACEBOOK_CONNECT_TIMEOUT=10
FACEBOOK_MAX_RETRIES=3
FACEBOOK_RETRY_DELAY=1000
FACEBOOK_TOKEN_ENCRYPTION=true
FACEBOOK_QUEUE=facebook
FACEBOOK_MEDIA_DISK=local
```

`FACEBOOK_GRAPH_VERSION` được đọc từ `config('facebook.graph_version')`. Giá trị mặc định duy nhất nằm trong `Modules/Facebook/Config/config.php`; hãy đối chiếu phiên bản Meta Graph API đang được app hỗ trợ trước khi chạy production.

## Quyền Meta

Scopes mặc định nằm trong `.env` hoặc config:

- `pages_show_list`
- `pages_read_engagement`
- `pages_manage_posts`

OAuth callback kiểm tra quyền bị từ chối và không coi kết nối thành công nếu thiếu quyền bắt buộc.

## Chạy module

```bash
php artisan config:clear
php artisan cache:clear
php artisan migrate
php artisan facebook:test
php artisan facebook:pages --sync
php artisan facebook:dispatch-scheduled
php artisan queue:work --queue=facebook
php artisan schedule:run
```

Cron scheduler:

```cron
* * * * * cd /duong-dan-du-an && php artisan schedule:run >> /dev/null 2>&1
```

## Artisan commands

- `php artisan facebook:test`: kiểm tra App ID, App Secret, App Access Token và thông tin app.
- `php artisan facebook:pages --sync`: đồng bộ Fanpage từ kết nối mới nhất.
- `php artisan facebook:post --page=1 --message="Bài đăng kiểm tra" --dry-run`: tạo nháp kiểm tra.
- `php artisan facebook:post --page=1 --message="Bài đăng kiểm tra" --publish`: tạo nháp và đưa vào queue đăng thật.
- `php artisan facebook:dispatch-scheduled`: dispatch bài đến hạn.
- `php artisan facebook:token-check --page=1` hoặc `--all`: kiểm tra token Page.

## Giao diện quản trị

- `/admin/facebook`: dashboard cấu hình, OAuth và số liệu.
- `/admin/facebook/pages`: danh sách Fanpage, bật/tắt, chọn mặc định, kiểm tra token.
- `/admin/facebook/posts`: quản lý bản nháp, lịch đăng, retry và trạng thái.
- `/admin/facebook/posts/create`: tạo bài text, photo hoặc link.

## Bảo mật

- Không lưu `FACEBOOK_APP_SECRET` trong database.
- User/Page access token dùng encrypted cast của Laravel.
- Token bị hidden khi serialize và chỉ hiển thị dạng che.
- Log channel `facebook` loại bỏ `access_token`, `client_secret`, `code`, `authorization`.
- Không dùng App Access Token để đăng bài Fanpage.

## Lưu ý vận hành

Nếu Meta đã tạo bài nhưng request timeout trước khi Laravel nhận response, rủi ro duplicate vẫn tồn tại ở phía nền tảng. Module giảm thiểu bằng `idempotency_key`, unique job, atomic status transition, kiểm tra `facebook_post_id` và scheduler `withoutOverlapping`.

## Troubleshooting

- Redirect URI mismatch: kiểm tra `FACEBOOK_REDIRECT_URI` đúng tuyệt đối với cấu hình Meta App.
- Thiếu quyền `pages_manage_posts`: cấp lại quyền qua OAuth và đảm bảo app đã được review nếu cần.
- Token Page không hợp lệ: chạy `php artisan facebook:token-check --all`, sau đó kết nối lại.
- Không thấy bài được đăng: kiểm tra `QUEUE_CONNECTION`, worker `--queue=facebook` và `php artisan schedule:run`.
