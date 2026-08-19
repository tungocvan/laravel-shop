# Google Drive & Scheduler Reuse Guide

> Tài liệu kiến trúc dùng chung cho AI/Codex và các Module trong project.
>
> Mục tiêu: **không tạo lại OAuth Google Drive hoặc scheduler riêng trong từng Module**. Trước khi Module mới cần Google Drive / lịch chạy, phải kiểm tra và tái sử dụng hạ tầng `Modules/System` đã có.
>
> **Hướng dẫn prompt thực hành:** xem `docs/GOOGLE_DRIVE_SCHEDULER_PROMPT_GUIDE.md` để biết cách giao việc cho ChatGPT/Codex theo từng tình huống: phân tích, upload Drive, backup tự động, scheduler, debug, VPS/Docker, refactor và pre-merge.

## 1. Trạng thái hạ tầng hiện tại

Project đã có hạ tầng Google Drive dùng chung trong `Modules/System`.

### Google Drive OAuth

Service trung tâm:

```php
Modules\System\Services\Cloud\GoogleDriveConnectionService
```

Service này chịu trách nhiệm:

- tạo Google OAuth authorization URL;
- đổi authorization code thành access/refresh token;
- mã hóa token bằng `Crypt` trước khi lưu;
- tự refresh access token;
- kiểm tra trạng thái kết nối;
- test connection;
- quản lý thư mục root Google Drive;
- upload backup;
- disconnect.

**Module khác không được tự lưu access token / refresh token riêng nếu chỉ cần dùng cùng tài khoản Google Drive của hệ thống.**

### Kiểm tra trạng thái kết nối

Dependency inject service và gọi:

```php
use Modules\System\Services\Cloud\GoogleDriveConnectionService;

public function example(GoogleDriveConnectionService $drive): void
{
    $status = $drive->status();

    if (! ($status['connected'] ?? false)) {
        // Google Drive chưa kết nối.
        return;
    }

    $email = $status['email'] ?? '';
    $folderId = $status['folder_id'] ?? '';
    $folderName = $status['folder_name'] ?? 'Laravel-Backup';
}
```

`status()` hiện cung cấp:

```php
[
    'connected' => bool,
    'email' => string,
    'folder_id' => string,
    'folder_name' => string,
    'connected_at' => string,
    'last_checked_at' => string,
]
```

> `connected=true` được xác định từ refresh token đã lưu. Nếu thao tác thực tế cần chắc chắn API còn hoạt động, dùng `testConnection()` hoặc để operation gọi `accessToken()` và xử lý exception.

### Lấy access token dùng chung

Nếu Module mới cần gọi Google Drive API trực tiếp:

```php
$token = $drive->accessToken();
```

`accessToken()` tự kiểm tra hạn và refresh token khi cần. **Không đọc token trực tiếp từ database/settings và không tự implement refresh token trong Module khác.**

## 2. Cấu hình Google OAuth

Credentials ứng dụng nằm ở cấu hình hệ thống/.env, còn token runtime được lưu trong settings.

Các giá trị OAuth cần thiết gồm Client ID, Client Secret và Redirect URI. Redirect URI của hệ thống phải trùng URI cấu hình trong Google Cloud Console.

Scope hiện tại được cấu hình tập trung trong config System. Backup sử dụng quyền `drive.file`, vì vậy ứng dụng quản lý các file/folder do chính ứng dụng tạo hoặc được cấp quyền phù hợp.

Không hard-code Client ID, Client Secret, redirect URI hoặc scope trong Module nghiệp vụ.

## 3. Nơi quản trị kết nối

UI quản trị:

```text
/admin/system/settings/env?tab=storage
```

Route OAuth:

```text
/admin/system/settings/cloud/google/connect
/admin/system/settings/cloud/google/callback
```

Tại UI có thể:

- cấu hình OAuth;
- kết nối tài khoản Google;
- xem tài khoản đang kết nối;
- test connection;
- disconnect;
- cấu hình thư mục root backup;
- quản lý lịch backup tự động.

Module khác chỉ nên hiển thị trạng thái kiểu:

```text
Google Drive: Đã kết nối — user@example.com
```

và dẫn người quản trị tới cấu hình System nếu chưa kết nối. Không nên tạo màn hình OAuth thứ hai.

## 4. Quy ước thư mục Google Drive

Root hiện tại mặc định:

```text
Laravel-Backup/
```

Database backup:

```text
Laravel-Backup/
└── database/
    └── YYYY/
        └── MM/
            └── *.sql
```

Khi Module khác cần lưu file, nên dùng namespace riêng dưới root, ví dụ:

```text
Laravel-Backup/
├── database/YYYY/MM/
├── invoices/YYYY/MM/
├── website/YYYY/MM/
└── admission/YYYY/MM/
```

Không trộn file Module khác vào `database/`.

Nếu nhu cầu dùng Drive tăng, nên mở rộng một service storage dùng chung trong `Modules/System/Services/Cloud` (upload/download/list/delete theo folder path), thay vì copy HTTP Google API code sang từng Module.

## 5. Browser/restore backup hiện có

Service:

```php
Modules\System\Services\Cloud\GoogleDriveBackupBrowserService
```

Dùng cho backup database để:

- list remote backup;
- download bằng OAuth;
- delete remote file;
- prune retention.

UI:

```text
/admin/system/database/backup-restore
```

Có các thao tác:

- Copy URL;
- Mở Drive;
- Tải về Local;
- Xóa Drive;
- Tải & Restore.

File backup do hệ thống tạo là OAuth/private, **không cần share “Anyone with the link”**. Chức năng URL public chỉ là legacy cho file Drive bên ngoài.

## 6. Scheduler dùng chung

Laravel Scheduler của project đã được kích hoạt trong:

```php
routes/console.php
```

Hiện có:

```php
Schedule::command('system:cloud-backup')
    ->everyMinute()
    ->withoutOverlapping();
```

Command chạy mỗi phút chỉ là dispatcher/checker. Giờ thực thi nghiệp vụ được kiểm tra trong service automation, vì vậy không đồng nghĩa database được backup mỗi phút.

### Service lịch backup hiện tại

```php
Modules\System\Services\Cloud\CloudBackupAutomationService
```

Service quản lý:

- enabled/disabled;
- giờ chạy;
- upload Drive;
- local retention;
- Drive retention;
- last run date/time;
- last status/message;
- next run time.

Config runtime được lưu bằng `SettingsService`, không yêu cầu sửa `.env` khi đổi lịch.

## 7. Module khác muốn lập lịch phải làm thế nào?

### Trường hợp A — Laravel Scheduler cố định

Nếu lịch là quy tắc cố định của code, đăng ký command/job trong scheduler trung tâm và luôn dùng:

```php
->withoutOverlapping()
```

khi operation không được chạy song song.

Ví dụ kiến trúc:

```php
Schedule::command('invoices:sync-scheduled')
    ->everyMinute()
    ->withoutOverlapping();
```

Command kiểm tra cấu hình runtime trước khi dispatch job.

### Trường hợp B — người quản trị chọn giờ trong UI

Nên áp dụng pattern hiện tại:

```text
UI Module
   ↓
SettingsService
   ↓
Automation Service
   ↓
Scheduler command mỗi phút
   ↓
dueNow()
   ↓
Job/Service nghiệp vụ
```

Không sửa cron của VPS mỗi lần người dùng thay đổi giờ.

Không tạo một cron Linux riêng cho mỗi Module.

### Trường hợp C — tác vụ nặng

Scheduler/command chỉ nên dispatch queue job:

```text
Laravel Scheduler
→ Command
→ kiểm tra due/config/lock
→ dispatch Job
→ Queue Worker xử lý
```

Không chạy upload file lớn hoặc tác vụ dài trực tiếp trong scheduler nếu có thể đưa vào queue.

## 8. Production / VPS / Docker

Có code scheduler **chưa đủ**. Production phải có process gọi Laravel Scheduler.

Một trong hai mô hình:

### Cron

Host/container phù hợp gọi mỗi phút:

```bash
php artisan schedule:run
```

### Worker scheduler riêng

```bash
php artisan schedule:work
```

Trong Docker nên chạy scheduler như một process/container/service riêng, tương tự queue worker. Không phụ thuộc request web để kích hoạt lịch.

Kiểm tra:

```bash
php artisan schedule:list
```

Queue vẫn cần worker riêng nếu scheduled command dispatch job:

```bash
php artisan queue:work
```

## 9. Checklist bắt buộc trước khi Module mới làm Google Drive

AI/Codex phải kiểm tra theo thứ tự:

1. `GoogleDriveConnectionService::status()` — hệ thống đã có kết nối chưa?
2. Module có thể dùng cùng tài khoản Google không?
3. Có thể dùng `accessToken()` thay vì tạo OAuth mới không?
4. Có thể mở rộng service Cloud trong System thay vì copy Google API code không?
5. Folder của Module đã namespace riêng chưa?
6. Operation nặng đã đưa vào Queue chưa?
7. Có log và trạng thái success/failed chưa?
8. UI đã có progress/result modal chưa?
9. Có permission phù hợp chưa?
10. Có test contract/regression System và Module liên quan chưa?

**Mặc định: tái sử dụng kết nối System. Chỉ tạo OAuth riêng khi nghiệp vụ thực sự yêu cầu một Google account/credential độc lập.**

## 10. Checklist bắt buộc trước khi Module mới lập lịch

1. Kiểm tra `routes/console.php` và scheduler hiện có.
2. Xác định lịch cố định hay runtime-configurable.
3. Runtime-configurable → lưu config bằng service/settings, không `.env`.
4. Command phải idempotent hoặc có cơ chế tránh chạy lặp.
5. Dùng `withoutOverlapping()` khi phù hợp.
6. Tác vụ dài → Queue Job.
7. UI cần hiển thị enabled, schedule, last run, next run, last status/error.
8. Có nút chạy thử/manual run nếu nghiệp vụ phù hợp.
9. Có disable/cancel schedule mà không phá dữ liệu cấu hình cần tái sử dụng.
10. Production phải xác nhận `schedule:run` hoặc `schedule:work` đang hoạt động.

## 11. Prompt ngắn cho AI/Codex khi Module khác cần Drive

```text
Module <NAME> cần sử dụng Google Drive.

Trước khi implement, đọc docs/GOOGLE_DRIVE_AND_SCHEDULER_REUSE_GUIDE.md và kiểm tra hạ tầng Modules/System hiện tại.

Yêu cầu:
- ưu tiên tái sử dụng GoogleDriveConnectionService và kết nối OAuth đã có;
- kiểm tra status() trước khi dùng;
- không tạo/lưu OAuth token riêng nếu không có lý do nghiệp vụ;
- nếu cần API token, dùng accessToken() để hưởng cơ chế refresh chung;
- file của Module phải dùng folder namespace riêng dưới root Drive;
- operation nặng phải dùng Queue;
- có trạng thái/progress/error UI;
- không làm regression Modules/System.

Trước khi code, báo lại kiến trúc tái sử dụng dự kiến và các file cần thay đổi.
```

> Với yêu cầu thực tế hoặc feature lớn, không nên chỉ dùng prompt ngắn này. Dùng các template chi tiết trong `docs/GOOGLE_DRIVE_SCHEDULER_PROMPT_GUIDE.md`.

## 12. Prompt ngắn cho AI/Codex khi Module khác cần Scheduler

```text
Module <NAME> cần chức năng lập lịch.

Đọc docs/GOOGLE_DRIVE_AND_SCHEDULER_REUSE_GUIDE.md trước khi implement.

Yêu cầu:
- tái sử dụng Laravel Scheduler của project;
- không tạo cron VPS riêng cho Module;
- lịch thay đổi từ UI phải lưu runtime config qua service/settings;
- scheduler command kiểm tra due/config và tránh chạy lặp;
- dùng withoutOverlapping khi phù hợp;
- tác vụ dài dispatch Queue Job;
- UI hiển thị trạng thái lịch, lần chạy gần nhất, lần chạy kế tiếp, kết quả/lỗi và cho phép tạm dừng;
- xác nhận production scheduler/queue requirements trong docs.

Trước khi code, phân tích scheduler hiện tại và đề xuất cách tích hợp không trùng lặp.
```

> Hướng dẫn cách dùng prompt theo workflow `Analyze → Approve → Implement từng Phase → Local Test → Pre-merge` nằm trong `docs/GOOGLE_DRIVE_SCHEDULER_PROMPT_GUIDE.md`.

## 13. Nguyên tắc kiến trúc cần giữ

```text
Modules/System = Infrastructure Owner

Google OAuth / token lifecycle
Google Drive shared connection
Scheduler conventions
Settings runtime
Backup infrastructure
        ↑
        │ reuse
        │
Business Modules
Invoices / Website / Admission / ...
```

Không để kiến trúc biến thành:

```text
Invoices → OAuth riêng + refresh riêng + cron riêng
Website  → OAuth riêng + refresh riêng + cron riêng
Admission→ OAuth riêng + refresh riêng + cron riêng
```

Mục tiêu là **một hạ tầng kết nối và lập lịch có thể quan sát, kiểm thử và tái sử dụng toàn project**.
