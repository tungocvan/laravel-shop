# Skill — Google Drive Upload / Local ↔ Drive

> Canonical implementation skill cho mọi task upload, backup, download, restore hoặc đồng bộ file ứng dụng với Google Drive trong repository này.

## Trigger

Áp dụng skill này khi yêu cầu có một trong các ý: **Google Drive**, upload cloud, backup file lên Drive, tải Drive về Local, Local ↔ Drive, nơi lưu file, restore file cloud.

Trước khi đề xuất hoặc code, bắt buộc đọc theo thứ tự:

1. `.codex/bootstrap/AI_PROJECT_CONTEXT.md`
2. `docs/GOOGLE_DRIVE_AND_SCHEDULER_REUSE_GUIDE.md`
3. `docs/GOOGLE_DRIVE_SCHEDULER_PROMPT_GUIDE.md`
4. `Modules/System/Services/Cloud/GoogleDriveConnectionService.php`
5. source/model/migration/tests của feature đang sửa.

Không cần đối chiếu lại route System/HSSP chỉ để khám phá cách kết nối Drive nếu các contract dưới đây vẫn tồn tại. Chỉ audit sâu lại khi source đã drift khỏi skill/reuse guide hoặc task yêu cầu thay đổi infrastructure.

## Architecture contract

```text
Business Module
   ↓ reuse
Modules/System/Services/Cloud/GoogleDriveConnectionService
   ↓
shared OAuth + encrypted token lifecycle + Drive API
```

- `Modules/System` là infrastructure owner.
- Không tạo OAuth, access token, refresh token, Google client config hoặc refresh flow riêng trong business module.
- Kiểm tra connection bằng `status()`; operation thực tế dùng service chung và để `accessToken()` xử lý refresh.
- Nếu API generic còn thiếu, mở rộng `GoogleDriveConnectionService` backward-compatible thay vì copy Google HTTP client vào Module.
- Không đưa token, Drive ID, absolute local path hoặc raw external payload ra browser/log/message không cần thiết.
- Giữ authorization/permission của Module cho mọi upload/download/delete/sync action.

## Canonical System API

Ưu tiên các API dùng chung hiện có:

```php
$drive->status();
$drive->testConnection();
$drive->accessToken();
$drive->uploadApplicationFile($localPath, $folderSegments, $fileName, $mimeType);
$drive->downloadApplicationFile($remoteId, $destinationPath);
$drive->deleteApplicationFile($remoteId, $remotePath);
```

Nếu signature source thay đổi, source thực tế thắng ví dụ trên và skill phải được cập nhật trong cùng PR nếu contract kiến trúc thay đổi.

## Folder contract

Root Drive do System quản lý, mặc định:

```text
Laravel-Backup/
```

Business module phải namespace riêng:

```text
Laravel-Backup/<Module>/<Domain>/<BusinessKey>/...
```

Ví dụ Pharma:

```text
Laravel-Backup/Pharma/DrugBidAwards/<TBMT>/Contracts/<Allocation>/<Contract>/file.pdf
```

Không hard-code OAuth root ID. Folder segment phải sanitize. DB lưu metadata/remote reference cần thiết, không lưu binary.

## Storage model

Khi feature cho phép Local và Drive, model tối thiểu nên phân biệt:

- original/display file name;
- mime type;
- size;
- local disk/path;
- remote ID/path/reference;
- sync status khi có async workflow.

**Trạng thái phải kiểm tra thực tế**:
- Local: dùng `Storage::disk(...)->exists(...)`, không chỉ tin path DB.
- Drive: với action quan trọng, remote metadata/API là nguồn xác minh; remote ID trong DB chỉ là reference.
- DB metadata stale phải được xử lý rõ, không hiển thị giả `✓ Có file`.

## Canonical UI/UX — create

Create mode tập trung vào **file + nơi lưu**, không hiển thị các card “Chưa có file” lớn khi record chưa tồn tại.

```text
File hợp đồng / tài liệu
[ Chọn hoặc kéo thả file ]

Lưu tại:  ☐ Local   ☑ Google Drive
Google Drive: ✓ Đã kết nối

[Hủy] [Lưu]
```

- Có thể chọn Local, Google Drive hoặc cả hai nếu nghiệp vụ cho phép.
- Nếu Drive chưa kết nối: disable lựa chọn Drive, hiển thị trạng thái và dẫn tới cấu hình System khi phù hợp.
- Không tạo màn OAuth thứ hai.
- Validation phải yêu cầu ít nhất một target khi có file.

## Canonical UI/UX — edit

Sau Save record, giữ người dùng ở **edit mode của record vừa lưu**, không reset thành create nếu workflow còn thao tác file.

```text
file.pdf · PDF · 1,21 MB                         [Tải xuống] [•••]

Local                         Google Drive
✓ Có file                     — Chưa có
                               [Local → Drive]

[ Thay file ]                 Lưu tại: ☑ Local ☑ Google Drive

[+ Thêm bản ghi khác]                    [Đóng] [Lưu thay đổi]
```

Quy tắc:
- Local ↔ Drive là action tài liệu độc lập, **không bắt người dùng bấm Save metadata**.
- Local có / Drive thiếu → `Local → Drive`.
- Drive có / Local thiếu → `Drive → Local`.
- Cả hai có → trạng thái đồng bộ rõ.
- Hiển thị tên file, type, size.
- Download là action thường dùng.
- Delete là destructive secondary action, ưu tiên menu `•••` + confirm.
- Cho xóa Local và Drive độc lập khi business cho phép.
- Thay file phải nói rõ target sẽ nhận file mới.
- Nếu “Thêm bản ghi khác” chuyển sang create mode, phải có **Hủy / Quay lại record trước**.
- Input width theo semantics; không kéo mọi field full width chỉ vì còn chỗ trống.

## Upload flow

Với file nhỏ/synchronous:

```text
validate
→ stage Local an toàn
→ upload Drive nếu được chọn
→ persist metadata
→ nếu không giữ Local thì chỉ xóa staging sau khi Drive upload thành công
```

Không xóa staging trước khi remote success. Nếu Drive fail, không được báo Save thành công giả.

Với file lớn/tác vụ dài:
- stage Local;
- persist pending state;
- dispatch Queue;
- worker upload;
- update synced/failed;
- UI có processing/success/failed;
- không upload dài trực tiếp trong Livewire request.

Tham khảo `App\Dossiers\Services\DossierStorageService` cho pattern staging + Queue khi phù hợp.

## Download / restore Drive → Local

- Dùng System service.
- Download vào file tạm `.partial-*`, chỉ rename sang destination khi hoàn tất.
- Không để file dở dang mang tên final.
- Tạo directory an toàn.
- Có timeout/size guard phù hợp nghiệp vụ.
- Sau success cập nhật metadata Local.

## Delete

- Local delete qua Laravel Storage đúng disk.
- Drive delete qua `GoogleDriveConnectionService::deleteApplicationFile()`.
- Permission + confirmation bắt buộc cho UI destructive action.
- Sau delete cập nhật metadata tương ứng.
- Không xóa bản còn lại ngoài ý người dùng.
- Nếu xóa cả hai, phải thể hiện rõ scope.

## Security checklist

- [ ] Không commit Client ID/Secret/token.
- [ ] Không token trong Livewire public state.
- [ ] Không raw Drive ID/path làm trusted browser identifier nếu boundary cần opaque reference.
- [ ] Permission checked server-side.
- [ ] MIME/extension/size validation.
- [ ] Safe filename/folder segments.
- [ ] Không log token/external payload nhạy cảm.
- [ ] Download/delete bounded và có timeout.
- [ ] Failure không làm mất bản Local hợp lệ.
- [ ] Không expose absolute filesystem path.

## Test contract

Focused tests phải guard tối thiểu:

1. reuse `GoogleDriveConnectionService`;
2. không OAuth/token implementation riêng;
3. connected/disconnected state;
4. target Local / Drive / both;
5. upload metadata;
6. Local → Drive;
7. Drive → Local;
8. local physical existence check;
9. download;
10. delete Local;
11. delete Drive;
12. permission;
13. failure path không báo success giả;
14. UI create/edit states;
15. nếu shared System API thay đổi: System regression.

Test theo `docs/GITHUB_COLLABORATION_WORKFLOW.md`: focused trước, rồi Module/System regression thực sự bị tác động.

## Scheduler

Nếu yêu cầu có lịch tự động, retention hoặc recurring backup, skill này **không thay thế** scheduler guide. Bắt buộc áp dụng thêm `docs/GOOGLE_DRIVE_AND_SCHEDULER_REUSE_GUIDE.md` và `docs/GOOGLE_DRIVE_SCHEDULER_PROMPT_GUIDE.md`: Laravel Scheduler + Settings + Queue, không cron riêng cho Module.

## Definition of Done

Chỉ coi feature Drive hoàn tất khi:

- reuse System connection;
- folder namespace đúng;
- Local/Drive state trung thực;
- sync hai chiều hoạt động nếu feature yêu cầu;
- file có download/delete/replace phù hợp;
- create/edit UX không mắc kẹt;
- secrets không lộ;
- focused tests PASS;
- impacted Module/System regression PASS;
- docs/skill được cập nhật nếu canonical contract thay đổi.

## Prompt ngắn sau này

```text
<Module/route> cần upload file lên Google Drive.
Áp dụng .codex/skills/google-drive-upload/SKILL.md và docs/GOOGLE_DRIVE_SCHEDULER_PROMPT_GUIDE.md.
Reuse System Google Drive; hỗ trợ Local ↔ Drive theo canonical UX; implement đến checkpoint git pull/test.
```

Với yêu cầu này, AI phải dùng skill làm baseline và chỉ audit lại phần feature-specific, không khám phá lại OAuth/connection từ đầu.
