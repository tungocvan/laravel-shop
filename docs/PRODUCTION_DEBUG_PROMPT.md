# Production Docker Debug Master Prompt

Dùng prompt này khi mở chat mới để chẩn đoán Production Docker của repository `tungocvan/laravel-shop`.

> **Cách dùng:** copy toàn bộ khối prompt bên dưới sang chat mới, sau đó điền phần **INCIDENT HIỆN TẠI**. Các tài liệu trong repository là nguồn chuẩn; nếu tài liệu thay đổi thì nội dung tài liệu mới được ưu tiên hơn prompt này.

## Prompt

~~~text
Tôi cần tiếp tục DEBUG PRODUCTION cho repository:

Repository: tungocvan/laravel-shop
Module chính: Modules\Pharma

Production:
- Host project: /opt/projects/tnv
- Docker Compose project: tnv
- App working directory trong container: /var/www/html
- Production đang chạy bằng Docker.
- Không được giả định PHP/Composer/Artisan trên host giống container.
- Production có các overlay quan trọng, phải bảo toàn:
  - compose.queue.yaml
  - compose.scheduler.yaml
  - compose.socket.yaml

Trước khi thực hiện debug, hãy đọc và tuân thủ:
- docs/PRODUCTION_OPERATIONS_GUIDE.md
- docs/PRODUCTION_DOCKER_WORKFLOW_GUARDRAILS.md
- docs/PRODUCTION_DOCKER_RUNTIME.md
- docs/GITHUB_COLLABORATION_WORKFLOW.md
- docs/chuyen_chat.md
- docs/modules/Pharma/COLLABORATION_HANDOFF.md
- .codex/standards/ADMIN_UI_STANDARD.md

Nếu các file trên có thay đổi so với thông tin trong prompt này thì nội dung trong repository được ưu tiên.

==============================
NGUYÊN TẮC DEBUG BẮT BUỘC
==============================

1. Debug theo CHECKPOINT, từng bước một.

2. Mỗi checkpoint:
   - Nói rõ mục tiêu kiểm tra.
   - Ghi rõ chính xác số lượng shell command.
   - Ưu tiên đúng 1 command/checkpoint.
   - Tôi sẽ chạy command trên production và gửi nguyên output lại.
   - Chỉ sau khi đọc output mới được đưa checkpoint tiếp theo.

3. Nếu command FAIL:
   - DỪNG.
   - Phân tích output.
   - Không đưa thêm hàng loạt command.
   - Không đoán nguyên nhân khi chưa có bằng chứng.

4. Luôn READ-ONLY / DIAGNOSE-FIRST.
   Không sửa production trước khi xác định root cause.

5. Tuyệt đối không tự ý:
   - git reset --hard
   - git clean -fd
   - docker compose down -v
   - docker system prune
   - docker volume prune
   - xóa volume/database
   - chmod 777
   - sửa SQL mode
   - sửa trực tiếp source production
   - xóa production overlay
   - chạy optimize:clear hàng loạt khi chưa chứng minh cần thiết
   - cài Composer/dev dependencies vào production image chỉ để chạy test

6. Luôn phân biệt rõ 4 lớp:
   - Git/source trên HOST
   - Docker image
   - Container đang chạy
   - Bind mount / named volume

Không được kết luận source trong container đã đổi chỉ vì host vừa git pull.

7. Trước mọi thay đổi production phải biết:
   - command chạy trên HOST hay CONTAINER
   - container/service nào
   - thay đổi có persistent hay không
   - có ảnh hưởng database/volume hay không
   - rollback bằng cách nào

8. Ưu tiên các helper script của repository nếu docs quy định, đặc biệt:
   - ./production-debug.sh
   - ./run-docker-artisan.sh "..."

Không tự thay bằng command Docker khác nếu chưa cần thiết.

9. Không hiển thị secrets:
   - APP_KEY
   - DB_PASSWORD
   - API keys
   - tokens
   - credentials

Nếu cần kiểm tra ENV chỉ kiểm tra tên biến/trạng thái cần thiết.

==============================
QUY TẮC SOURCE CODE / GITHUB
==============================

Nếu xác định lỗi nằm trong source code:

- KHÔNG sửa source trực tiếp trên production.
- Sửa repository/GitHub trên branch riêng.
- Tạo commit rõ ràng.
- Tạo PR.
- Operator merge sau khi review/test.
- Sau đó production chỉ git fetch / git pull --ff-only và deploy/reload theo đúng production docs.

Nếu có thể fix bằng source thì không dùng workaround production để che lỗi.

Không thay đổi database schema hoặc migration nếu root cause không yêu cầu.

==============================
PRODUCTION RUNTIME ĐÃ BIẾT
==============================

Production database là MariaDB/MySQL-compatible.

SQL mode từng xác nhận:
ONLY_FULL_GROUP_BY,
STRICT_TRANS_TABLES,
NO_ZERO_IN_DATE,
NO_ZERO_DATE,
ERROR_FOR_DIVISION_BY_ZERO,
NO_ENGINE_SUBSTITUTION

Không được tắt ONLY_FULL_GROUP_BY để workaround query lỗi.

PHP-FPM production từng xác nhận:
opcache.validate_timestamps=Off

Vì vậy sau khi source mới thực sự vào container/image, code PHP cũ có thể vẫn nằm trong OPcache.

Trong incident trước, graceful PHP-FPM reload bằng signal USR2 đã làm PHP-FPM nạp code mới.

NHƯNG:
Không tự động signal/restart PHP-FPM.
Trước tiên phải xác nhận đúng container/process theo production docs.

==============================
BÀI HỌC INCIDENT TRƯỚC
==============================

Route từng lỗi:
admin/pharma/drug-bid-awards

Root cause trước đây liên quan grouped pagination và:
COALESCE(NULLIF(bidding_notice_code, ''), CONCAT('award-', id))

MariaDB ONLY_FULL_GROUP_BY đã phát hiện lỗi ở:
- DrugBidAwardService::getResultGroupsPaginated()
- DrugBidAward/Index.php::dashboardMetrics()

Đã được fix và merge:
- PR #217: grouped pagination SQL-safe
- PR #218: derived alias contract test

Không mặc định cho rằng lỗi mới cũng là lỗi này.
Thông tin trên chỉ là lịch sử để tránh debug lại từ đầu khi symptom thực sự trùng khớp.

==============================
TEST / LOCAL RUNTIME
==============================

Repo PHPUnit dùng SQLite :memory:.

Baseline Pharma regression:
231 passed (1940 assertions)

Đã từng phát hiện môi trường PHP CLI cũ:
PHP 8.4.1
SQLite 3.43.2
=> SQLite không có CONCAT()
=> gây test failure do runtime SQLite cũ.

System PHP đã xác nhận:
PHP 8.4.24
SQLite 3.45.1
=> CONCAT() hoạt động
=> 231 passed (1940 assertions)

Do đó:
- Không kết luận source lỗi chỉ từ một test failure.
- Phải kiểm tra runtime PHP/SQLite/database đang thực sự chạy.
- Production MariaDB và PHPUnit SQLite là hai môi trường khác nhau.

==============================
QUY TRÌNH DEBUG MONG MUỐN
==============================

A. Xác nhận incident
- URL/route bị lỗi
- HTTP status / symptom
- thời điểm xảy ra
- lỗi toàn site hay chỉ một route

B. Xác nhận Git state trên production HOST
- branch
- HEAD
- origin/main
- working tree
- production-only untracked overlays

Không xóa overlay.

C. Xác nhận Docker runtime
- container/service liên quan có running/healthy không
- image/container đang chạy có phải version vừa deploy không
- mount nào đang cung cấp application source

D. Lấy log đúng thời điểm
- Laravel log
- PHP-FPM/container log
- nginx/proxy log nếu cần

Ưu tiên lọc log theo thời điểm và exception.
Không dump hàng nghìn dòng log nếu chưa cần.

E. Xác định exception/root cause
- file
- line
- SQL/query nếu có
- stack trace cần thiết
- database driver
- runtime/container thực tế

F. Chỉ khi có bằng chứng mới kiểm tra:
- migration status
- config/cache
- OPcache
- queue/scheduler
- permissions
- DB schema/data

G. Nếu source defect:
- sửa trên GitHub branch/PR
- test
- merge
- production pull/deploy
- graceful reload nếu cần
- verify route
- kiểm tra log mới

H. Kết thúc incident phải xác nhận:
- HTTP/UI PASS
- log không sinh exception mới
- git status production an toàn
- container/service healthy
- không có workaround tạm thời bị bỏ quên

==============================
CÁCH LÀM VIỆC VỚI TÔI
==============================

Tôi muốn bạn đóng vai trò technical lead/debugger.

Không gửi 10 command một lúc.

Format mong muốn:

Checkpoint N — [mục tiêu]
Số command: 1
HOST hoặc CONTAINER: [ghi rõ]
READ-ONLY hoặc MUTATING: [ghi rõ]

```bash
command
```

Sau đó:
"Chạy đúng lệnh trên và gửi nguyên output. Tôi sẽ phân tích trước khi sang checkpoint tiếp theo."

Nếu output đã đủ chứng minh root cause thì nói rõ:
- FACT
- DIAGNOSIS
- NEXT ACTION

Không đưa giả thuyết thành kết luận.

==============================
INCIDENT HIỆN TẠI
==============================

Production host prompt:
root@github-tungocvan:/opt/projects/tnv#

Lỗi/symptom hiện tại:
[DÁN LỖI Ở ĐÂY]

Route/URL:
[DÁN URL Ở ĐÂY]

Thời điểm xảy ra:
[DÁN THỜI ĐIỂM NẾU BIẾT]

Deploy/commit gần nhất:
[DÁN NẾU BIẾT]

Hãy bắt đầu debug.

Checkpoint đầu tiên phải an toàn và READ-ONLY.
Trước tiên hãy đọc các production docs nêu trên, sau đó mới đưa command đầu tiên.
~~~

## Maintenance

Khi production workflow, helper scripts, Docker topology hoặc test baseline thay đổi, cập nhật tài liệu nguồn trước. Prompt này chỉ là entry template và không được ghi đè contract trong các tài liệu production/collaboration hiện hành.
