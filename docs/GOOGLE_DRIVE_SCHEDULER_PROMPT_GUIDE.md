# Hướng dẫn sử dụng Prompt — Google Drive & Scheduler

Tài liệu này dành cho người phát triển khi yêu cầu ChatGPT/Codex/AI bổ sung Google Drive, backup cloud hoặc lập lịch cho một Module trong project.

Đọc cùng:

- `docs/GOOGLE_DRIVE_AND_SCHEDULER_REUSE_GUIDE.md` — kiến trúc kỹ thuật/canonical reuse guide.
- `.codex/bootstrap/AI_PROJECT_CONTEXT.md` — context bắt buộc của AI.

---

## 1. Mục tiêu

Bạn **không cần mô tả lại toàn bộ cách OAuth Google Drive hoạt động** mỗi lần giao việc.

Project đã có hạ tầng trong `Modules/System`. Prompt chỉ cần:

1. nêu Module cần làm;
2. nêu nghiệp vụ mong muốn;
3. yêu cầu AI đọc reuse guide;
4. yêu cầu AI phân tích trước;
5. chỉ implement sau khi kiến trúc tái sử dụng đã rõ.

Nguyên tắc mặc định:

```text
Module nghiệp vụ
      ↓ reuse
Modules/System
      ↓
GoogleDriveConnectionService
Laravel Scheduler / Queue / Settings
```

---

## 2. Cách dùng prompt theo quy trình an toàn

### Bước 1 — Chỉ yêu cầu phân tích

Ví dụ:

```text
/analyze-module Invoices

Tôi muốn Modules/Invoices có chức năng tự động backup file hóa đơn lên Google Drive.

Trước khi đề xuất:
- đọc docs/GOOGLE_DRIVE_AND_SCHEDULER_REUSE_GUIDE.md;
- đọc .codex/bootstrap/AI_PROJECT_CONTEXT.md;
- kiểm tra Modules/System đã có Google Drive connection và scheduler gì;
- kiểm tra trạng thái kết nối phải tái sử dụng bằng GoogleDriveConnectionService;
- không implement OAuth mới;
- chưa code ở bước này.

Hãy báo:
1. thành phần System có thể tái sử dụng;
2. kiến trúc đề xuất;
3. folder Drive đề xuất;
4. queue/job/scheduler cần dùng;
5. UI trạng thái cần có;
6. file dự kiến thay đổi;
7. test cần bổ sung;
8. rủi ro regression.
```

Mục đích: buộc AI nhìn vào infrastructure hiện có trước khi code.

### Bước 2 — Duyệt kiến trúc

Sau khi AI phân tích, bạn kiểm tra tối thiểu:

```text
Có reuse GoogleDriveConnectionService không?
Có tạo OAuth/token riêng không?
Có namespace folder riêng cho Module không?
Tác vụ nặng có Queue không?
Scheduler có dùng hạ tầng Laravel hiện tại không?
Có trạng thái last run / next run / failed không?
Có test System + Module không?
```

Nếu đúng, trả lời:

```text
Tôi đồng ý kiến trúc đề xuất.
Hãy implement theo đúng analysis vừa thống nhất.
Không mở rộng scope sang Module không liên quan.
```

### Bước 3 — Test

Yêu cầu AI đưa ra test nhỏ trước, sau đó regression Module/System.

Ví dụ:

```text
Cho tôi danh sách command test theo thứ tự:
1. test feature mới;
2. test Module Invoices;
3. test System liên quan Google Drive/Scheduler;
4. full regression chỉ sau khi các test trên PASS.
```

### Bước 4 — Chỉ merge khi PASS

Trước merge:

```text
Kiểm tra git diff/branch so với main.
Xác nhận không có thay đổi ngoài scope.
Tóm tắt architecture, migrations/config mới nếu có, production requirements và test result.
Chưa merge main cho tới khi tôi xác nhận.
```

---

## 3. Prompt: Module chỉ cần upload file lên Google Drive

Dùng khi Module không cần lịch tự động.

```text
Modules/<MODULE> cần upload <LOẠI FILE> lên Google Drive.

Bắt buộc đọc trước:
- docs/GOOGLE_DRIVE_AND_SCHEDULER_REUSE_GUIDE.md
- .codex/bootstrap/AI_PROJECT_CONTEXT.md

Yêu cầu:
- kiểm tra và tái sử dụng GoogleDriveConnectionService của Modules/System;
- dùng status() để xác định kết nối;
- dùng accessToken() nếu cần gọi Drive API;
- không tạo OAuth/token/config Google riêng trong Module;
- dùng folder riêng: Laravel-Backup/<module>/YYYY/MM;
- không lưu token vào Livewire state/log/browser;
- upload lớn phải dùng Queue Job;
- UI phải hiển thị connected/disconnected, processing, success, failed;
- nếu chưa kết nối, hướng người quản trị tới cấu hình Google Drive của System;
- giữ permission/auth của Module;
- bổ sung test và không regression System.

Trước khi code, phân tích implementation hiện tại và báo các file dự kiến thay đổi.
```

Ví dụ thay `<MODULE>` bằng `Invoices` và `<LOẠI FILE>` bằng `Excel/PDF hóa đơn`.

---

## 4. Prompt: Module cần backup tự động lên Drive

```text
Modules/<MODULE> cần tự động backup <DỮ LIỆU> lên Google Drive.

Đọc docs/GOOGLE_DRIVE_AND_SCHEDULER_REUSE_GUIDE.md trước.

Tái sử dụng:
- GoogleDriveConnectionService;
- Laravel Scheduler hiện tại;
- Queue;
- SettingsService/pattern runtime configuration của System.

Không được:
- tạo OAuth Google riêng;
- tạo cron Linux/VPS riêng cho Module;
- chạy tác vụ nặng trực tiếp trong Livewire;
- hard-code token hoặc giờ chạy.

UI cần:
- bật/tắt lịch;
- chọn giờ chạy;
- trạng thái Google Drive;
- lần chạy gần nhất;
- lần chạy kế tiếp;
- kết quả gần nhất;
- lỗi gần nhất;
- nút Chạy ngay;
- modal processing/success/failed.

Folder Drive:
Laravel-Backup/<module>/YYYY/MM

Trước khi implement hãy đề xuất Command → due check → Queue Job → Service flow và test plan.
```

---

## 5. Prompt: Chỉ cần lập lịch, không dùng Google Drive

```text
Modules/<MODULE> cần lập lịch tự động thực hiện <NGHIỆP VỤ>.

Đọc phần Scheduler trong docs/GOOGLE_DRIVE_AND_SCHEDULER_REUSE_GUIDE.md.

Yêu cầu:
- tái sử dụng Laravel Scheduler của project;
- không tạo cron VPS riêng cho Module;
- nếu người dùng thay đổi giờ từ UI, lưu runtime config bằng service/settings;
- scheduler command chỉ kiểm tra due/config/lock và dispatch job;
- operation nặng chạy Queue;
- tránh chạy trùng;
- dùng withoutOverlapping() khi phù hợp;
- UI hiển thị enabled, giờ chạy, last run, next run, status/error;
- có disable/cancel schedule;
- có manual run nếu an toàn;
- bổ sung test.

Phân tích trước, chưa code cho tới khi kiến trúc được duyệt.
```

---

## 6. Prompt: Module cần kiểm tra xem Drive đã kết nối chưa

Dùng khi chỉ muốn bổ sung UI/status.

```text
Trong Modules/<MODULE>, tôi muốn hiển thị trạng thái Google Drive dùng chung của hệ thống.

Không tạo kết nối mới.
Đọc docs/GOOGLE_DRIVE_AND_SCHEDULER_REUSE_GUIDE.md.

Sử dụng GoogleDriveConnectionService::status().

UI mong muốn:
- Đã kết nối / Chưa kết nối;
- email tài khoản nếu đã kết nối;
- nếu chưa kết nối có link/nút dẫn tới cấu hình Storage của Modules/System;
- không hiển thị access token/refresh token;
- không copy OAuth logic vào Module.

Phân tích vị trí UI phù hợp và implement tối thiểu.
```

---

## 7. Prompt: Tạo folder riêng cho Module trên Drive

```text
Modules/<MODULE> cần lưu file trên Google Drive dùng chung.

Tôi muốn cấu trúc:
Laravel-Backup/<module>/YYYY/MM

Hãy đọc docs/GOOGLE_DRIVE_AND_SCHEDULER_REUSE_GUIDE.md và kiểm tra GoogleDriveConnectionService hiện tại.

Nếu service hiện tại chưa expose API folder/upload generic phù hợp, hãy đề xuất mở rộng service Cloud dùng chung trong Modules/System thay vì viết lại Google Drive HTTP client trong Modules/<MODULE>.

Yêu cầu backward compatible với database backup hiện tại và có regression test System.
```

Đây là prompt quan trọng khi nhiều Module bắt đầu dùng Drive: ưu tiên nâng cấp infrastructure chung thay vì nhân bản code.

---

## 8. Prompt: Backup database của Module

Nếu Module có table riêng nhưng backup vẫn là database-level, **không vội tạo một hệ thống dump DB thứ hai**.

```text
Modules/<MODULE> cần backup dữ liệu database liên quan đến Module.

Trước khi code:
- phân tích DatabaseService và database backup hiện tại của Modules/System;
- đọc docs/GOOGLE_DRIVE_AND_SCHEDULER_REUSE_GUIDE.md;
- xác định nên dùng full database backup hiện tại hay cần module-scoped export;
- không duplicate mysqldump/restore logic nếu System đã có;
- nếu cần module-scoped export, giải thích rõ format, restore semantics và dependency giữa tables.

Chỉ đề xuất kiến trúc trước, chưa implement.
```

---

## 9. Prompt: Yêu cầu AI kiểm tra production VPS/Docker

```text
Kiểm tra deployment requirements cho chức năng Scheduler + Queue của Modules/<MODULE> trên VPS Docker.

Đọc docs/GOOGLE_DRIVE_AND_SCHEDULER_REUSE_GUIDE.md.

Hãy xác nhận:
- web container/process;
- queue worker;
- Laravel scheduler process;
- php artisan schedule:list;
- queue restart/deploy procedure;
- storage permissions;
- APP_KEY/token encryption requirement;
- Google OAuth redirect URI production;
- không yêu cầu cron riêng cho từng Module.

Chỉ đưa command phù hợp với kiến trúc repository hiện tại, không giả định Docker topology nếu chưa kiểm tra source/deployment config.
```

---

## 10. Prompt: Debug khi lịch không chạy

```text
Modules/<MODULE> đã cấu hình lịch nhưng không chạy.

Không refactor ngay.
Hãy debug theo tầng:
1. runtime schedule config;
2. php artisan schedule:list;
3. scheduler process/cron;
4. command due check;
5. overlap/lock;
6. queue dispatch;
7. queue worker;
8. failed_jobs;
9. application log;
10. Google Drive connection nếu job có upload Drive.

Đọc docs/GOOGLE_DRIVE_AND_SCHEDULER_REUSE_GUIDE.md và source hiện tại trước khi đưa kết luận.
Cho tôi command kiểm tra từng bước, mỗi bước chờ kết quả rồi mới tiếp tục nếu cần.
```

---

## 11. Prompt: Debug Google Drive

```text
Modules/<MODULE> không upload được Google Drive.

Không tạo lại OAuth và không reconnect ngay.
Đọc docs/GOOGLE_DRIVE_AND_SCHEDULER_REUSE_GUIDE.md.

Debug theo thứ tự:
1. GoogleDriveConnectionService::status();
2. testConnection();
3. accessToken()/refresh token flow;
4. Google Drive API enabled;
5. scope hiện tại;
6. folder root/module folder;
7. queue job;
8. failed_jobs exception;
9. Laravel log;
10. permission/file path local.

Không in access token hoặc refresh token ra output/log.
Cho tôi lệnh kiểm tra an toàn từng bước.
```

---

## 12. Prompt: Refactor Module đã tự làm OAuth/cron riêng

```text
Modules/<MODULE> hiện có Google OAuth hoặc scheduler riêng.

Tôi muốn phân tích khả năng refactor để tái sử dụng infrastructure Modules/System.

Đọc:
- docs/GOOGLE_DRIVE_AND_SCHEDULER_REUSE_GUIDE.md
- .codex/bootstrap/AI_PROJECT_CONTEXT.md

Hãy lập bảng:
- implementation hiện tại;
- phần trùng với System;
- phần nên giữ ở Module;
- phần nên chuyển/reuse System;
- migration compatibility;
- token/config migration risk;
- scheduler/queue migration risk;
- regression tests.

Chưa sửa code. Ưu tiên backward compatibility và migration từng phase.
```

---

## 13. Prompt tổng hợp mạnh nhất

Khi không chắc nên dùng prompt nào, dùng prompt này:

```text
/analyze-module <MODULE>

Tôi muốn bổ sung chức năng:
<MÔ TẢ NGHIỆP VỤ>

Chức năng có liên quan đến Google Drive / cloud storage / backup / scheduler / queue.

BẮT BUỘC trước khi đề xuất hoặc code:
1. đọc .codex/bootstrap/CODEX_BOOTSTRAP.md;
2. đọc .codex/bootstrap/PROJECT_BOOTSTRAP.md;
3. đọc .codex/bootstrap/AI_PROJECT_CONTEXT.md;
4. đọc docs/GOOGLE_DRIVE_AND_SCHEDULER_REUSE_GUIDE.md;
5. đọc docs của Modules/<MODULE>;
6. kiểm tra source Modules/System liên quan Google Drive, Settings, Scheduler, Queue và Backup;
7. kiểm tra source hiện tại của Modules/<MODULE>.

Nguyên tắc:
- System là infrastructure owner;
- reuse GoogleDriveConnectionService;
- không tạo OAuth/token riêng nếu không có yêu cầu tài khoản độc lập;
- reuse Laravel Scheduler, không tạo cron riêng cho Module;
- runtime schedule lưu bằng service/settings;
- tác vụ nặng dùng Queue;
- tránh chạy trùng;
- folder Drive namespace riêng;
- giữ auth/permission;
- UI có processing/success/failed;
- không expose secret;
- không refactor ngoài scope;
- backward compatible;
- có tests.

TRƯỚC KHI CODE, trả về:
A. Current State
B. Reusable Infrastructure
C. Gap Analysis
D. Proposed Architecture
E. Data/Settings
F. Drive Folder Strategy
G. Scheduler/Queue Flow
H. UI/UX
I. Security
J. Files To Change
K. Test Plan
L. Risks
M. Implementation Phases

Dừng sau phần phân tích và chờ tôi xác nhận.
```

Đây là prompt khuyến nghị cho thay đổi lớn.

---

## 14. Cách ra lệnh sau khi duyệt analysis

Sau khi đồng ý:

```text
Tôi đồng ý analysis và kiến trúc đề xuất.

Hãy implement Phase 1 בלבד theo plan.
Yêu cầu:
- giữ đúng scope;
- không thay đổi unrelated modules;
- commit nhỏ, rõ nghĩa;
- cập nhật tests;
- sau khi hoàn thành cho tôi command test local;
- chưa chuyển Phase tiếp theo cho tới khi tôi báo PASS.
```

Sau khi PASS:

```text
Phase 1 đã PASS.
Tiếp tục Phase 2 theo analysis đã duyệt.
Không thay đổi kiến trúc đã thống nhất nếu chưa báo lại.
```

Cách này phù hợp workflow ChatGPT ↔ GitHub ↔ Local: AI thay đổi branch, bạn pull/test local, báo PASS rồi mới tiếp tục.

---

## 15. Cách yêu cầu AI kiểm tra trước merge main

```text
Feature Google Drive/Scheduler của Modules/<MODULE> đã PASS local.

Trước khi merge main:
- so sánh branch hiện tại với main;
- kiểm tra changed files;
- kiểm tra có file secret/.env/token bị commit không;
- kiểm tra docs đã cập nhật;
- kiểm tra migrations/config/deployment requirements;
- kiểm tra tests System + Module;
- tóm tắt commit history;
- đưa command final regression;
- đưa quy trình merge main an toàn.

Không merge cho tới khi tôi xác nhận.
```

---

## 16. Những prompt KHÔNG nên dùng

Không nên chỉ viết:

```text
Thêm Google Drive vào Invoices.
```

hoặc:

```text
Tạo cron backup mỗi ngày.
```

Vì AI có thể hiểu đây là chức năng độc lập và tạo OAuth/cron mới.

Nên viết:

```text
Thêm Google Drive vào Invoices, bắt buộc tái sử dụng infrastructure Modules/System theo docs/GOOGLE_DRIVE_AND_SCHEDULER_REUSE_GUIDE.md. Phân tích trước, chưa code.
```

---

## 17. Quy tắc nhớ nhanh

Khi yêu cầu AI, chỉ cần nhớ công thức:

```text
MODULE
+ NGHIỆP VỤ
+ ĐỌC REUSE GUIDE
+ REUSE SYSTEM
+ PHÂN TÍCH TRƯỚC
+ QUEUE NẾU NẶNG
+ TEST
+ CHỜ PASS
```

Ví dụ ngắn:

```text
Modules/Invoices cần tự động upload PDF lên Drive lúc 02:00.
Đọc GOOGLE_DRIVE_AND_SCHEDULER_REUSE_GUIDE.md, reuse System Google Drive + Scheduler + Queue. Phân tích trước, chưa code.
```

Chỉ câu trên đã đủ để AI bắt đầu đúng hướng; dùng **Prompt tổng hợp mạnh nhất** khi feature phức tạp hoặc ảnh hưởng production.
