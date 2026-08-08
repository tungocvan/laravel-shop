# 🚀 XÂY DỰNG LARAVEL MODULE ĐĂNG BÀI TỰ ĐỘNG LÊN FACEBOOK FANPAGE

Bạn đang làm việc trực tiếp trong thư mục gốc của một dự án Laravel đã tồn tại.

Nhiệm vụ của bạn là phân tích dự án, đọc tài liệu kiến trúc hiện có, sau đó xây dựng hoàn chỉnh một Laravel Module chuyên dùng để kết nối Facebook Fanpage, quản lý nội dung và tự động đăng bài thông qua Meta Graph API.

---

# 1. TÀI LIỆU BẮT BUỘC PHẢI ĐỌC

Trước khi sửa hoặc tạo bất kỳ file nào, hãy đọc toàn bộ file:

```text
huong-dan/1-# 🚀 MASTER PROMPT – LARAVEL MODULE + LI.md
huong-dan/2-MASTER PROMPT — LARAVEL ADMIN UI (v1.0).md
```

Nếu tên file có ký tự đặc biệt hoặc khoảng trắng, hãy tìm đúng file trong thư mục `huong-dan`.

Ví dụ trên Linux:

```bash
find huong-dan -maxdepth 2 -type f -name "*.md" -print
```

Sau khi tìm thấy:

```bash
cat "huong-dan/1-# 🚀 MASTER PROMPT – LARAVEL MODULE + LI.md"
```

Phải tuân thủ toàn bộ:

* Kiến trúc Laravel Module của dự án.
* Quy tắc đặt namespace.
* Quy tắc đặt tên class, service, repository và model.
* Quy tắc viết migration.
* Quy tắc route, controller, request và resource.
* Quy tắc sử dụng repository hoặc service.
* Quy tắc response API.
* Quy tắc phân quyền.
* Quy tắc viết test.
* Quy tắc menu, giao diện và ngôn ngữ nếu tài liệu có quy định.
* Quy tắc không phá vỡ mã nguồn hiện tại.

Nếu yêu cầu trong prompt này xung đột với tài liệu kiến trúc của dự án, ưu tiên tài liệu trong file Markdown, ngoại trừ các yêu cầu bảo mật và tính đúng đắn của Meta Graph API.

---

# 2. NGUYÊN TẮC LÀM VIỆC

Không bắt đầu viết code ngay.

Thực hiện lần lượt:

1. Đọc tài liệu Markdown bắt buộc.
2. Kiểm tra phiên bản Laravel và PHP.
3. Kiểm tra dự án đang dùng package Laravel Modules nào.
4. Kiểm tra cấu trúc các module hiện có.
5. Tìm một module hoàn chỉnh nhất làm mẫu.
6. Kiểm tra cơ chế authentication và authorization hiện tại.
7. Kiểm tra conventions của controller, service, repository, model và route.
8. Kiểm tra hệ thống queue, scheduler, logging và storage.
9. Kiểm tra frontend hiện tại là Blade, API, React, Vue hay kết hợp.
10. Lập kế hoạch triển khai ngắn gọn.
11. Sau đó mới bắt đầu tạo và sửa file.

Không được:

* Tạo một kiến trúc mới trái với dự án.
* Tự ý nâng cấp Laravel hoặc PHP.
* Xóa hoặc đổi tên module hiện có.
* Sửa mã nguồn không liên quan.
* Hardcode Facebook App ID, App Secret hoặc access token.
* Đưa secret hoặc access token vào Git.
* Log toàn bộ token ở dạng rõ.
* Dùng App Access Token để đăng bài Fanpage.
* Dùng automation trình duyệt, cookie Facebook hoặc phương pháp không chính thức.
* Bỏ qua lỗi từ Meta Graph API.
* Giả định một phiên bản Graph API cố định trong toàn bộ source code.

---

# 3. TÊN VÀ MỤC ĐÍCH MODULE

Tạo module:

```text
Facebook
```

Nếu dự án đã có module tên tương tự, hãy mở rộng module hiện có thay vì tạo module trùng.

Mục tiêu module:

* Kiểm tra kết nối Facebook App.
* Thực hiện OAuth với Meta.
* Lấy User Access Token.
* Lấy danh sách Fanpage mà người dùng được phép quản lý.
* Lưu Page Access Token an toàn.
* Quản lý một hoặc nhiều Fanpage.
* Soạn bài đăng.
* Đăng bài ngay.
* Lên lịch đăng bài.
* Đăng bài chỉ có nội dung.
* Đăng bài có ảnh.
* Theo dõi trạng thái đăng.
* Lưu Facebook Post ID.
* Ghi nhận lỗi Graph API.
* Tự thử lại các lỗi tạm thời.
* Quản lý token hết hạn hoặc không còn hợp lệ.
* Hỗ trợ Laravel Queue và Scheduler.
* Có command line phục vụ kiểm tra và vận hành.

---

# 4. KIỂM TRA MÃ NGUỒN ĐÃ CÓ

Dự án có thể đã tồn tại command:

```bash
php artisan facebook:test
```

Hãy tìm toàn bộ mã nguồn liên quan:

```bash
grep -R "facebook:test" -n app Modules routes config 2>/dev/null
grep -R "FACEBOOK_APP_ID" -n app Modules routes config 2>/dev/null
grep -R "graph.facebook.com" -n app Modules routes config 2>/dev/null
```

Nếu command đã tồn tại:

* Không tạo command trùng.
* Di chuyển hoặc tái cấu trúc command vào module nếu phù hợp với kiến trúc.
* Giữ nguyên khả năng chạy:

```bash
php artisan facebook:test
```

Command phải tiếp tục kiểm tra được:

* `FACEBOOK_APP_ID`.
* `FACEBOOK_APP_SECRET`.
* Kết nối tới Meta Graph API.
* Khả năng lấy App Access Token.
* Thông tin ứng dụng Facebook.

App hiện tại đã có thể kết nối thành công. Không làm hỏng chức năng này.

---

# 5. CẤU HÌNH MÔI TRƯỜNG

Bổ sung cấu hình mẫu vào `.env.example`, không ghi dữ liệu thật:

```env
FACEBOOK_GRAPH_VERSION=
FACEBOOK_APP_ID=
FACEBOOK_APP_SECRET=
FACEBOOK_REDIRECT_URI=
FACEBOOK_WEBHOOK_VERIFY_TOKEN=
FACEBOOK_HTTP_TIMEOUT=30
FACEBOOK_CONNECT_TIMEOUT=10
FACEBOOK_MAX_RETRIES=3
FACEBOOK_RETRY_DELAY=1000
FACEBOOK_TOKEN_ENCRYPTION=true
```

Không mặc định cứng một phiên bản Graph API trong nhiều class.

Phiên bản API phải được lấy từ một cấu hình trung tâm, ví dụ:

```php
config('facebook.graph_version')
```

Nếu `FACEBOOK_GRAPH_VERSION` chưa được cấu hình, sử dụng một giá trị mặc định duy nhất trong file config và ghi chú rõ rằng cần đối chiếu với phiên bản Graph API mà Meta đang hỗ trợ cho App.

Tạo file cấu hình module phù hợp kiến trúc dự án, ví dụ:

```text
Modules/Facebook/Config/config.php
```

hoặc cấu trúc config đang được dự án áp dụng.

Cấu hình tối thiểu:

* Graph API base URL.
* Graph API version.
* App ID.
* App Secret.
* Redirect URI.
* Webhook verify token.
* HTTP timeout.
* Connect timeout.
* Retry count.
* Retry delay.
* Token encryption.
* Queue name.
* Disk lưu ảnh.
* Thời gian khóa chống đăng trùng.

---

# 6. QUYỀN META CẦN HỖ TRỢ

Luồng OAuth cần chuẩn bị các quyền tối thiểu phù hợp với việc quản lý và đăng bài Fanpage, bao gồm:

```text
pages_show_list
pages_read_engagement
pages_manage_posts
```

Không tự ý thêm quyền không cần thiết.

Meta có thể thay đổi tên quyền, quy trình xét duyệt và phiên bản Graph API. Vì vậy:

* Đối chiếu tài liệu chính thức Meta hiện hành trước khi hoàn thiện.
* Không dựa vào blog hoặc thư viện không chính thức làm nguồn chính.
* Gom danh sách scopes vào file config.
* Cho phép thay đổi scopes mà không sửa service.
* Hiển thị rõ quyền nào bị từ chối.
* Không coi OAuth thành công khi thiếu quyền bắt buộc.

Phải phân biệt đúng:

* App Access Token: chỉ xác minh ứng dụng, không đăng Fanpage.
* User Access Token: dùng để truy vấn các Page được cấp quyền.
* Page Access Token: dùng để đăng nội dung lên Page.

---

# 7. LUỒNG OAUTH

Xây dựng đầy đủ luồng:

```text
Người quản trị
    ↓
Nhấn “Kết nối Facebook”
    ↓
Laravel tạo OAuth URL
    ↓
Meta yêu cầu đăng nhập và cấp quyền
    ↓
Meta redirect về callback
    ↓
Laravel kiểm tra state
    ↓
Đổi authorization code lấy User Access Token
    ↓
Nếu phù hợp, đổi sang token dài hạn
    ↓
Gọi /me/accounts
    ↓
Lưu danh sách Fanpage và Page Access Token
```

Yêu cầu bảo mật OAuth:

* Sử dụng tham số `state`.
* Lưu `state` vào session hoặc cơ chế phù hợp.
* Kiểm tra chính xác `state` tại callback.
* State phải dùng một lần.
* Không nhận token trực tiếp từ query string do người dùng tự nhập.
* Không ghi access token vào URL nội bộ.
* Không log authorization code, App Secret hoặc access token đầy đủ.
* Xử lý trường hợp người dùng từ chối quyền.
* Xử lý callback thiếu `code`.
* Xử lý lỗi token exchange.
* Xử lý scopes không đầy đủ.
* Redirect về màn hình quản trị kèm thông báo rõ ràng.

Các route dự kiến, điều chỉnh theo conventions của dự án:

```text
GET  /admin/facebook/connect
GET  /admin/facebook/callback
POST /admin/facebook/disconnect
POST /admin/facebook/sync-pages
```

Tất cả route quản trị phải có:

* Authentication middleware.
* Authorization hoặc permission phù hợp.
* CSRF protection đối với route nội bộ.
* Rate limiting nếu conventions dự án có sử dụng.

Callback OAuth phải có thiết kế middleware phù hợp, không vô hiệu hóa bảo mật toàn cục một cách tùy tiện.

---

# 8. LƯU TOKEN AN TOÀN

Không lưu token dạng plain text nếu dự án hỗ trợ encryption.

Ưu tiên:

* Laravel encrypted cast nếu phiên bản hỗ trợ.
* Hoặc accessor/mutator sử dụng `Crypt`.
* Hoặc một `TokenEncryptionService` riêng.

Database không được lưu:

```text
FACEBOOK_APP_SECRET
```

App Secret chỉ tồn tại trong biến môi trường hoặc secret manager.

Page token phải:

* Được mã hóa khi lưu.
* Chỉ giải mã ngay trước lúc gọi API.
* Không xuất ra API Resource.
* Không xuất ra Blade hoặc frontend.
* Không ghi vào log.
* Không xuất toàn bộ trong command line.
* Khi hiển thị chỉ cho phép dạng che:

```text
EAAJ**************xYz1
```

Có thể lưu thêm:

* Token type.
* Ngày cấp.
* Ngày hết hạn nếu API trả về.
* Ngày kiểm tra token gần nhất.
* Trạng thái token.
* Scopes được cấp.
* Lỗi token gần nhất.

---

# 9. THIẾT KẾ CƠ SỞ DỮ LIỆU

Thiết kế migration theo conventions của dự án.

## 9.1. Bảng kết nối Facebook

Tên gợi ý:

```text
facebook_connections
```

Các trường tham khảo:

```text
id
user_id nullable hoặc required theo kiến trúc
facebook_user_id nullable
facebook_user_name nullable
user_access_token encrypted
token_type nullable
token_expires_at nullable
granted_scopes json nullable
declined_scopes json nullable
status
last_verified_at nullable
last_error_code nullable
last_error_message nullable
created_at
updated_at
deleted_at nếu dự án dùng soft delete
```

## 9.2. Bảng Fanpage

Tên gợi ý:

```text
facebook_pages
```

Các trường:

```text
id
facebook_connection_id
page_id unique theo phạm vi phù hợp
page_name
page_category nullable
page_picture_url nullable
page_access_token encrypted
token_expires_at nullable
granted_tasks json nullable
is_active boolean
is_default boolean
last_synced_at nullable
last_verified_at nullable
last_error_code nullable
last_error_message nullable
created_at
updated_at
deleted_at nếu cần
```

Không dùng cột tên chung chung như `token` nếu conventions dự án yêu cầu tên rõ nghĩa.

## 9.3. Bảng bài đăng

Tên gợi ý:

```text
facebook_posts
```

Các trường:

```text
id
facebook_page_id
created_by nullable
title nullable
message longText
post_type
link_url nullable
status
scheduled_at nullable
queued_at nullable
processing_at nullable
published_at nullable
failed_at nullable
facebook_post_id nullable
facebook_permalink nullable
attempts default 0
idempotency_key nullable unique
last_error_code nullable
last_error_subcode nullable
last_error_type nullable
last_error_message nullable
last_error_trace_id nullable
meta_response json nullable
created_at
updated_at
deleted_at nếu phù hợp
```

Trạng thái nên được chuẩn hóa bằng enum, constant hoặc value object phù hợp phiên bản PHP:

```text
draft
scheduled
queued
processing
published
failed
cancelled
```

Loại bài:

```text
text
photo
link
```

Không triển khai video ở giai đoạn đầu nếu chưa có thiết kế upload video lớn và resumable upload đúng chuẩn. Có thể để interface mở rộng cho video sau.

## 9.4. Bảng media

Nếu dự án cần nhiều ảnh cho một bài, tạo:

```text
facebook_post_media
```

Các trường:

```text
id
facebook_post_id
media_type
disk
path
original_name nullable
mime_type nullable
size nullable
sort_order default 0
facebook_media_id nullable
status
last_error_message nullable
created_at
updated_at
```

Giai đoạn đầu ít nhất phải hỗ trợ một ảnh.

---

# 10. MODEL VÀ QUAN HỆ

Tạo các model tương ứng:

```text
FacebookConnection
FacebookPage
FacebookPost
FacebookPostMedia
```

Quan hệ dự kiến:

```text
FacebookConnection hasMany FacebookPage
FacebookPage belongsTo FacebookConnection
FacebookPage hasMany FacebookPost
FacebookPost belongsTo FacebookPage
FacebookPost hasMany FacebookPostMedia
FacebookPostMedia belongsTo FacebookPost
```

Model phải:

* Có `$fillable` hoặc `$guarded` đúng conventions.
* Có casts cho boolean, JSON, datetime và enum nếu hỗ trợ.
* Ẩn trường token trong serialization.
* Có scope phục vụ truy vấn bài đến hạn.
* Không chứa logic gọi HTTP trực tiếp.
* Không giải mã token không cần thiết.

---

# 11. SERVICE LAYER

Thiết kế service rõ trách nhiệm. Điều chỉnh namespace theo tài liệu của dự án.

Các class gợi ý:

```text
FacebookGraphClient
FacebookOAuthService
FacebookTokenService
FacebookPageService
FacebookPostService
FacebookMediaService
FacebookPublishingService
FacebookErrorMapper
FacebookTokenMasker
```

## 11.1. FacebookGraphClient

Đây là HTTP client trung tâm.

Trách nhiệm:

* Tạo URL Graph API.
* Gửi GET và POST.
* Gắn access token an toàn.
* Timeout.
* Connect timeout.
* Retry có kiểm soát.
* Parse JSON.
* Chuẩn hóa lỗi.
* Ghi log đã che thông tin nhạy cảm.
* Không để mỗi service tự viết HTTP request riêng.

Sử dụng Laravel HTTP Client nếu phù hợp với phiên bản dự án.

Không retry mù tất cả lỗi.

Chỉ retry các lỗi tạm thời như:

* Timeout.
* Connection failure.
* Một số HTTP 5xx.
* Rate limit hoặc lỗi tạm thời nếu Meta cho phép retry.

Không retry các lỗi như:

* Token không hợp lệ.
* Thiếu quyền.
* Nội dung vi phạm.
* Page không tồn tại.
* Validation sai.

## 11.2. FacebookOAuthService

Các method tham khảo:

```php
public function buildAuthorizationUrl(): string;
public function exchangeCodeForUserToken(string $code): FacebookTokenData;
public function exchangeForLongLivedToken(string $shortLivedToken): FacebookTokenData;
public function getGrantedPermissions(string $userToken): array;
```

## 11.3. FacebookPageService

Các method:

```php
public function syncPages(FacebookConnection $connection): Collection;
public function verifyPage(FacebookPage $page): FacebookPageVerificationResult;
public function deactivateInvalidPage(FacebookPage $page, string $reason): void;
```

Gọi endpoint phù hợp để lấy danh sách Page và Page Access Token.

Không lưu dữ liệu khi response chưa được kiểm tra đầy đủ.

## 11.4. FacebookPublishingService

Các method tham khảo:

```php
public function publish(FacebookPost $post): FacebookPublishResult;
public function publishText(FacebookPost $post): FacebookPublishResult;
public function publishPhoto(FacebookPost $post): FacebookPublishResult;
public function publishLink(FacebookPost $post): FacebookPublishResult;
```

Phải chọn endpoint theo loại bài.

Khi thành công, lưu:

* Facebook Post ID.
* Published time.
* Response cần thiết.
* Trạng thái `published`.

Khi thất bại, lưu:

* Error code.
* Error subcode.
* Error type.
* Error message.
* Trace ID.
* Thời điểm lỗi.
* Trạng thái phù hợp.

Không lưu response chứa token.

---

# 12. DTO VÀ RESULT OBJECT

Không truyền array tùy ý xuyên suốt module nếu kiến trúc dự án cho phép DTO.

Tạo các DTO/result object phù hợp, ví dụ:

```text
FacebookTokenData
FacebookPageData
FacebookPublishResult
FacebookApiErrorData
FacebookPageVerificationResult
```

DTO cần tương thích phiên bản PHP của dự án.

Không sử dụng syntax PHP mới hơn phiên bản dự án đang chạy.

---

# 13. FORM REQUEST VÀ VALIDATION

Tạo Form Request theo conventions dự án.

## Tạo hoặc cập nhật bài

Validation tham khảo:

```text
facebook_page_id: required, exists và Page đang active
message: required_without media/link, string, giới hạn hợp lý
post_type: required, in text/photo/link
scheduled_at: nullable, date và phải ở tương lai khi đăng theo lịch
link_url: required_if post_type=link, valid URL
image: required_if post_type=photo, image, mime và dung lượng hợp lý
```

Yêu cầu:

* Không tin tưởng `facebook_page_id` từ client.
* Kiểm tra người dùng có quyền sử dụng Page đó.
* Không cho sửa bài đang `processing`.
* Không cho lên lịch thời gian trong quá khứ.
* Không cho đăng lại bài đã `published` trừ khi có chức năng duplicate riêng.
* Chuẩn hóa timezone theo timezone ứng dụng.
* Hiển thị thời gian ở timezone người dùng nếu hệ thống có hỗ trợ.

---

# 14. CONTROLLER VÀ API/WEB

Tạo controller mỏng, không chứa logic Graph API.

Các controller gợi ý:

```text
FacebookConnectionController
FacebookPageController
FacebookPostController
FacebookPublishController
FacebookWebhookController
```

Chức năng quản trị:

## Facebook connection

* Hiển thị trạng thái App.
* Nút kết nối Facebook.
* OAuth callback.
* Ngắt kết nối.
* Đồng bộ lại danh sách Page.
* Kiểm tra token.

## Fanpage

* Danh sách Page đã kết nối.
* Bật/tắt Page.
* Chọn Page mặc định.
* Kiểm tra Page.
* Không bao giờ hiển thị token đầy đủ.

## Bài đăng

* Danh sách bài.
* Bộ lọc trạng thái.
* Tạo bài.
* Sửa bản nháp.
* Xóa bản nháp.
* Đăng ngay.
* Lên lịch.
* Hủy bài chưa đăng.
* Thử đăng lại bài thất bại.
* Xem lỗi.
* Nhân bản bài đã đăng thành bản nháp mới.

Controller phải gọi service/action/use case đúng conventions dự án.

---

# 15. QUEUE VÀ JOB

Tạo job:

```text
PublishFacebookPostJob
```

Yêu cầu:

* Job nhận ID bài đăng, không serialize toàn bộ model có token.
* Khi chạy phải đọc lại bài từ database.
* Dùng database transaction hoặc atomic update để khóa trạng thái.
* Chống hai worker đăng cùng một bài.
* Sử dụng lock hoặc cơ chế unique job phù hợp phiên bản Laravel.
* Kiểm tra lại trạng thái trước khi đăng.
* Kiểm tra `scheduled_at`.
* Kiểm tra Page còn active.
* Kiểm tra token.
* Chuyển trạng thái theo luồng an toàn:

```text
scheduled/queued
    ↓
processing
    ↓
published hoặc failed
```

* Tăng `attempts`.
* Thiết lập `tries`, `timeout`, `backoff`.
* Không retry vô hạn.
* Không đăng trùng khi job chạy lại.
* Nếu Meta đã tạo bài nhưng ứng dụng timeout trước khi nhận response, phải ghi chú rủi ro duplicate và áp dụng chiến lược giảm thiểu phù hợp.

Tên queue có thể là:

```text
facebook
```

nhưng phải đặt trong config.

---

# 16. SCHEDULER

Tạo command hoặc scheduled task để tìm bài đến hạn.

Command gợi ý:

```bash
php artisan facebook:dispatch-scheduled
```

Nhiệm vụ:

1. Lấy các bài có trạng thái `scheduled`.
2. `scheduled_at <= now()`.
3. Dùng chunk để tránh tải toàn bộ database.
4. Atomic update bài sang `queued`.
5. Dispatch `PublishFacebookPostJob`.
6. Không dispatch trùng.
7. Hiển thị số bài đã đưa vào queue.

Đăng ký scheduler theo conventions và phiên bản Laravel của dự án.

Ví dụ logic:

```php
$schedule->command('facebook:dispatch-scheduled')
    ->everyMinute()
    ->withoutOverlapping();
```

Nếu dự án chạy nhiều server, cân nhắc `onOneServer()` khi hạ tầng hỗ trợ.

Không tự ý sửa cron hệ điều hành. Chỉ bổ sung hướng dẫn:

```cron
* * * * * cd /duong-dan-du-an && php artisan schedule:run >> /dev/null 2>&1
```

---

# 17. ARTISAN COMMANDS

Xây dựng hoặc hoàn thiện các command:

## 17.1. Kiểm tra Facebook App

```bash
php artisan facebook:test
```

Giữ chức năng hiện tại:

* Kiểm tra cấu hình.
* Lấy App Access Token.
* Xác minh App.
* Che token.
* Không dùng App Access Token để đăng bài.

Có thể hỗ trợ:

```bash
php artisan facebook:test --show-token
```

Nhưng:

* Chỉ hoạt động ngoài production.
* Có cảnh báo bảo mật rõ ràng.

## 17.2. Liệt kê Fanpage

```bash
php artisan facebook:pages
```

Tùy chọn tham khảo:

```bash
php artisan facebook:pages --connection=1
php artisan facebook:pages --sync
php artisan facebook:pages --verify
```

Kết quả chỉ hiển thị:

* ID nội bộ.
* Page ID.
* Page name.
* Active.
* Token status.
* Last sync.
* Token đã che.

Không hiển thị token đầy đủ.

## 17.3. Đăng thử

```bash
php artisan facebook:post
```

Thiết kế an toàn:

```bash
php artisan facebook:post \
  --page=1 \
  --message="Bài đăng kiểm tra từ Laravel" \
  --dry-run
```

Mặc định nên yêu cầu `--dry-run` hoặc xác nhận trước khi đăng thật.

Đăng thật:

```bash
php artisan facebook:post \
  --page=1 \
  --message="Bài đăng kiểm tra từ Laravel" \
  --publish
```

Trong production:

* Không dùng interactive confirmation làm yêu cầu bắt buộc cho automation.
* Nhưng command thủ công phải buộc có `--publish`.
* Nếu thiếu `--publish`, chỉ validation và preview.
* Không cho truyền Page Access Token trực tiếp trên command line.

## 17.4. Dispatch bài đến hạn

```bash
php artisan facebook:dispatch-scheduled
```

## 17.5. Kiểm tra token

```bash
php artisan facebook:token-check
```

Cho phép:

```bash
php artisan facebook:token-check --page=1
php artisan facebook:token-check --all
```

Không hiển thị token rõ.

---

# 18. GIAO DIỆN QUẢN TRỊ

Nếu dự án đang dùng Blade và module có giao diện, tạo giao diện theo layout hiện tại.

Các màn hình:

```text
Facebook Dashboard
Facebook Connections
Facebook Pages
Facebook Posts
Create/Edit Post
Post Detail/Error
```

Dashboard hiển thị:

* Facebook App đã cấu hình hay chưa.
* Trạng thái kết nối OAuth.
* Số Fanpage active.
* Bài nháp.
* Bài chờ đăng.
* Bài đã đăng.
* Bài thất bại.
* Worker/queue hướng dẫn kiểm tra.
* Lần đồng bộ Page gần nhất.

Form bài đăng:

* Chọn Fanpage.
* Loại bài.
* Nội dung.
* Link nếu có.
* Upload ảnh nếu có.
* Chọn đăng ngay hoặc lên lịch.
* Thời gian đăng.
* Preview đơn giản.
* Nút lưu nháp.
* Nút đăng ngay.
* Nút lên lịch.

Không nhúng Page Access Token vào HTML.

Nếu dự án là API-only:

* Không tạo Blade không cần thiết.
* Tạo API routes, Resources và tài liệu endpoint.
* Tuân thủ response wrapper hiện tại.

---

# 19. PHÂN QUYỀN

Kiểm tra hệ thống permission hiện tại.

Tạo permission theo conventions, ví dụ:

```text
facebook.view
facebook.connect
facebook.pages.manage
facebook.posts.view
facebook.posts.create
facebook.posts.update
facebook.posts.delete
facebook.posts.publish
facebook.posts.retry
```

Không hardcode role `admin` nếu dự án dùng permission system.

Áp dụng authorization ở:

* Route/controller.
* Policy hoặc gate.
* Query scope.
* Service cho các hành động quan trọng.

Người dùng không được thao tác Fanpage không thuộc phạm vi của mình.

---

# 20. WEBHOOK

Chuẩn bị webhook cơ bản nhưng không triển khai quá mức cần thiết.

Route tham khảo:

```text
GET  /facebook/webhook
POST /facebook/webhook
```

GET dùng để xác minh webhook:

* Kiểm tra verify token.
* Trả về challenge đúng chuẩn.

POST:

* Xác minh chữ ký request nếu Meta cung cấp cơ chế phù hợp.
* Không log payload nhạy cảm nguyên bản.
* Trả HTTP nhanh.
* Dispatch job xử lý nếu cần.
* Có thể chỉ lưu event tối thiểu hoặc để extension point.

Không coi webhook là điều kiện bắt buộc để đăng bài theo lịch.

---

# 21. XỬ LÝ ẢNH

Ảnh phải được upload qua Laravel Storage.

Yêu cầu:

* Validate MIME bằng server.
* Validate dung lượng.
* Tạo tên file ngẫu nhiên.
* Không dùng trực tiếp tên file người dùng.
* Không cho path traversal.
* Có thể dùng private storage trước khi upload lên Meta.
* Xóa ảnh khi bài nháp bị xóa nếu không còn tham chiếu.
* Không xóa ảnh ngay sau khi đăng nếu cần audit hoặc đăng lại.
* Đường dẫn filesystem không được trả thẳng ra ngoài khi không cần.

Khi đăng ảnh:

* Dùng endpoint Graph API phù hợp.
* Kèm caption/message.
* Lưu ID media hoặc post Meta trả về.
* Xử lý upload thất bại riêng với publish thất bại.

---

# 22. CHỐNG ĐĂNG TRÙNG

Đây là yêu cầu bắt buộc.

Áp dụng nhiều lớp:

1. `idempotency_key` ở database.
2. Atomic status transition.
3. Unique queue job hoặc distributed lock khi hỗ trợ.
4. Kiểm tra `facebook_post_id` trước khi đăng.
5. `withoutOverlapping` cho scheduler.
6. Không cho nút “Đăng ngay” gửi lặp nhiều lần.
7. Controller phải phản hồi hợp lý nếu bài đã queued hoặc processing.

Không được chỉ dựa vào nút frontend bị disable.

---

# 23. LOGGING VÀ AUDIT

Tạo logging channel riêng nếu conventions dự án cho phép:

```text
facebook
```

Log được phép chứa:

* Internal post ID.
* Internal Page record ID.
* Facebook Page ID.
* Endpoint đã gọi nhưng không kèm token.
* HTTP status.
* Error code.
* Error subcode.
* Error type.
* Trace ID.
* Thời gian xử lý.

Không được log:

* App Secret.
* User Access Token.
* Page Access Token.
* Authorization code.
* URL chứa access token.
* Toàn bộ request headers nhạy cảm.

Tạo helper/service để redact các key:

```text
access_token
client_secret
appsecret_proof
code
authorization
```

Nếu lưu `meta_response`, phải loại bỏ dữ liệu nhạy cảm trước.

---

# 24. XỬ LÝ LỖI

Chuẩn hóa exception:

```text
FacebookApiException
FacebookAuthenticationException
FacebookPermissionException
FacebookRateLimitException
FacebookValidationException
FacebookTemporaryException
FacebookPublishingException
```

Mỗi exception cần chứa dữ liệu an toàn:

* HTTP status.
* Error code.
* Error subcode.
* Error type.
* Message.
* Trace ID.
* Retryable boolean.

Không đưa raw response nhạy cảm ra frontend.

Thông báo người dùng bằng tiếng Việt, ví dụ:

* Token Fanpage không còn hợp lệ.
* Tài khoản chưa cấp quyền `pages_manage_posts`.
* Fanpage đã bị ngắt khỏi ứng dụng.
* Meta đang giới hạn tần suất yêu cầu.
* Nội dung không được Meta chấp nhận.
* Không thể tải ảnh lên Facebook.
* Bài đăng đã được đưa vào hàng đợi.
* Bài đã được đăng trước đó.

---

# 25. TEST

Viết test theo framework và conventions hiện có.

Ít nhất cần:

## Unit tests

* Token masking.
* Error mapping.
* URL builder.
* DTO mapping.
* Status transitions.
* Kiểm tra retryable error.
* Validation scheduling time.
* Redaction dữ liệu nhạy cảm.

## Feature tests

* OAuth connect redirect.
* OAuth callback state hợp lệ.
* OAuth callback state sai.
* Callback người dùng từ chối.
* Sync Page thành công.
* Tạo bản nháp.
* Lên lịch bài.
* Đăng ngay dispatch job.
* Không được đăng Page không có quyền.
* Không đăng trùng.
* Retry bài thất bại.
* Không trả token trong API response.

## Job tests

Dùng `Http::fake()` hoặc cơ chế mock tương ứng:

* Publish text thành công.
* Publish photo thành công.
* Token hết hạn.
* Thiếu quyền.
* Timeout.
* Lỗi tạm thời được retry.
* Lỗi không retry.
* Job chạy hai lần không tạo hai bài.

Không gọi thật Meta Graph API trong automated tests.

---

# 26. TÀI LIỆU SỬ DỤNG

Tạo tài liệu:

```text
Modules/Facebook/README.md
```

Hoặc vị trí đúng theo conventions dự án.

Nội dung:

1. Mục đích module.
2. Yêu cầu hệ thống.
3. Cách tạo Meta App.
4. Cách cấu hình redirect URI.
5. Các quyền cần thiết.
6. Cấu hình `.env`.
7. Chạy migration.
8. Kết nối Fanpage.
9. Đồng bộ Page.
10. Tạo bài.
11. Đăng ngay.
12. Lên lịch.
13. Chạy queue worker.
14. Cấu hình cron scheduler.
15. Các Artisan command.
16. Kiểm tra lỗi token.
17. Quy trình App Review khi kết nối Page của người khác.
18. Lưu ý App Development Mode và Live Mode.
19. Bảo mật token.
20. Troubleshooting.

Thêm hướng dẫn Linux:

```bash
php artisan config:clear
php artisan cache:clear
php artisan migrate
php artisan facebook:test
php artisan facebook:pages
php artisan facebook:dispatch-scheduled
php artisan queue:work --queue=facebook
php artisan schedule:run
```

Không ghi secret hoặc token thật vào tài liệu.

---

# 27. FILE HƯỚNG DẪN META APP

Tạo thêm:

```text
Modules/Facebook/docs/meta-app-setup.md
```

Nội dung hướng dẫn từng bước:

* Tạo Meta App.
* Chọn loại App phù hợp theo giao diện Meta hiện hành.
* Lấy App ID.
* Lấy App Secret.
* Thêm website URL.
* Cấu hình domain.
* Cấu hình Valid OAuth Redirect URI.
* Thêm Facebook Login hoặc sản phẩm tương ứng.
* Yêu cầu quyền Page.
* Thêm tài khoản Developer/Tester khi App còn Development Mode.
* Chuyển sang Live khi cần.
* App Review cho các quyền nâng cao.
* Cách kiểm tra bằng `facebook:test`.
* Cách kết nối OAuth từ Laravel.
* Cách xử lý lỗi redirect URI mismatch.

Chỉ dựa vào tài liệu Meta chính thức hiện hành khi xác minh các bước có thể thay đổi.

---

# 28. API ENDPOINT GỢI Ý

Điều chỉnh đường dẫn theo route conventions của dự án.

```text
GET    /admin/facebook
GET    /admin/facebook/connect
GET    /admin/facebook/callback
POST   /admin/facebook/disconnect

GET    /admin/facebook/pages
POST   /admin/facebook/pages/sync
POST   /admin/facebook/pages/{page}/verify
PATCH  /admin/facebook/pages/{page}/status
PATCH  /admin/facebook/pages/{page}/default

GET    /admin/facebook/posts
POST   /admin/facebook/posts
GET    /admin/facebook/posts/{post}
PUT    /admin/facebook/posts/{post}
DELETE /admin/facebook/posts/{post}

POST   /admin/facebook/posts/{post}/publish
POST   /admin/facebook/posts/{post}/schedule
POST   /admin/facebook/posts/{post}/cancel
POST   /admin/facebook/posts/{post}/retry
POST   /admin/facebook/posts/{post}/duplicate
```

Không nhất thiết phải tạo đúng toàn bộ endpoint nếu conventions dự án có thiết kế khác tốt hơn, nhưng phải đáp ứng đủ use case.

---

# 29. QUY TRÌNH TRIỂN KHAI THEO GIAI ĐOẠN

Triển khai theo thứ tự để giảm rủi ro.

## Giai đoạn 1 — Foundation

* Đọc kiến trúc dự án.
* Tạo module.
* Config.
* Exception.
* DTO.
* Graph client.
* Giữ và tích hợp `facebook:test`.

## Giai đoạn 2 — OAuth và Page

* OAuth URL.
* Callback.
* Token exchange.
* Token encryption.
* Sync Pages.
* Màn hình kết nối và danh sách Page.

## Giai đoạn 3 — Post cơ bản

* Migration bài đăng.
* CRUD bản nháp.
* Đăng bài text.
* Đăng bài ảnh.
* Lưu kết quả.

## Giai đoạn 4 — Queue và Scheduler

* Publish job.
* Dispatch scheduled command.
* Scheduler.
* Retry.
* Chống đăng trùng.

## Giai đoạn 5 — UI, permission và test

* Dashboard.
* Permission.
* Feature tests.
* Unit tests.
* README.
* Troubleshooting.

Sau mỗi giai đoạn:

* Chạy test.
* Chạy formatter/linter nếu dự án có.
* Kiểm tra route.
* Kiểm tra migration.
* Không tiếp tục nếu giai đoạn hiện tại đang lỗi.

---

# 30. CÁC LỆNH KIỂM TRA BẮT BUỘC

Tùy dự án, chạy các lệnh phù hợp:

```bash
php artisan about
php artisan list | grep facebook
php artisan route:list | grep -i facebook
php artisan migrate:status
php artisan config:clear
php artisan test
```

Nếu dự án có module command:

```bash
php artisan module:list
```

Nếu có formatter:

```bash
./vendor/bin/pint --test
```

hoặc formatter hiện tại của dự án.

Không tự động chạy migration production nguy hiểm.

Có thể tạo migration và kiểm tra syntax, nhưng phải báo rõ trước khi có hành động phá hủy dữ liệu.

---

# 31. TIÊU CHÍ NGHIỆM THU

Module chỉ được coi là hoàn thành khi:

1. `php artisan facebook:test` chạy thành công.
2. Không làm hỏng command Facebook hiện có.
3. OAuth có state protection.
4. Kết nối được tài khoản quản trị Facebook.
5. Đồng bộ được danh sách Fanpage.
6. Page token được mã hóa.
7. Token không xuất hiện trong JSON, HTML hoặc log.
8. Có thể tạo bài nháp.
9. Có thể đăng bài text.
10. Có thể đăng bài có một ảnh.
11. Có thể lên lịch.
12. Scheduler dispatch đúng bài đến hạn.
13. Queue đăng bài thành công.
14. Không đăng trùng khi job chạy nhiều lần.
15. Lưu Facebook Post ID.
16. Lưu lỗi Graph API có cấu trúc.
17. Có chức năng retry bài thất bại.
18. Có permission.
19. Có automated tests.
20. Có README.
21. Có hướng dẫn Meta App.
22. Source code tương thích phiên bản Laravel và PHP hiện tại.
23. Không có hardcoded secret hoặc token.
24. Không có route quản trị thiếu authentication.
25. Không sửa mã nguồn ngoài phạm vi khi không cần thiết.

---

# 32. CÁCH PHẢN HỒI SAU KHI HOÀN THÀNH

Sau khi triển khai, phản hồi bằng tiếng Việt theo mẫu:

```text
Đã xây dựng module Facebook Fanpage.

1. Thông tin dự án
- Laravel: [...]
- PHP: [...]
- Package module: [...]
- Module mẫu đã tham khảo: [...]

2. Chức năng đã hoàn thành
- [...]
- [...]

3. File đã tạo
- [...]
- [...]

4. File đã sửa
- [...]
- [...]

5. Migration
- [...]
- [...]

6. Routes
- [...]
- [...]

7. Artisan commands
- php artisan facebook:test
- php artisan facebook:pages
- php artisan facebook:post
- php artisan facebook:dispatch-scheduled
- php artisan facebook:token-check

8. Cách chạy
- [...]
- [...]

9. Test đã chạy
- [...]
- Kết quả: [...]

10. Việc người quản trị cần thực hiện trên Meta
- [...]
- [...]

11. Cảnh báo hoặc phần chưa thể tự động hoàn thành
- [...]
```

Mỗi file đã tạo hoặc sửa phải được liệt kê rõ.

Không chỉ đưa hướng dẫn hoặc pseudo-code. Hãy trực tiếp tạo đầy đủ source code cần thiết trong dự án, chạy kiểm tra và sửa lỗi phát sinh trong phạm vi có thể.

Nếu thiếu thông tin nhỏ, hãy phân tích conventions hiện tại và chọn phương án hợp lý nhất. Không dừng toàn bộ công việc chỉ để hỏi những câu có thể tự suy luận từ mã nguồn.

Nếu một chức năng phụ thuộc vào thao tác thủ công trên Meta Developers, vẫn phải hoàn thiện code phía Laravel, sau đó ghi rõ chính xác thao tác người quản trị cần thực hiện.
