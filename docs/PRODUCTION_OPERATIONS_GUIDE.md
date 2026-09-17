# Production Operations Guide

Tài liệu này là **entry point vận hành và chẩn đoán production** cho các project Laravel chạy Docker trong repository này.

Mục tiêu là khi có lỗi production, operator có thể mở một file duy nhất để biết:

- đang đứng đúng project hay chưa;
- lệnh nào nên chạy đầu tiên;
- output nào cần gửi để phân tích;
- khi nào chỉ cần chẩn đoán, khi nào mới nên restart/reconcile;
- cách chạy Artisan đúng container;
- cách áp dụng thay đổi `.env`;
- cách kiểm tra dung lượng/log/build cache;
- các lệnh nguy hiểm không được dùng làm bước chẩn đoán mặc định.

Tài liệu kiến trúc/runtime contract liên quan: [`docs/PRODUCTION_DOCKER_RUNTIME.md`](./PRODUCTION_DOCKER_RUNTIME.md).

---

## 1. Quick Reference

Mỗi production project được đặt tại:

```text
/opt/projects/<project-name>
```

Tên Compose project được lấy từ **basename của thư mục project**. Ví dụ:

```text
/opt/projects/tnv            -> Compose project: tnv
/opt/projects/ntd            -> Compose project: ntd
/opt/projects/ntd-tungocvan -> Compose project: ntd-tungocvan
```

Không hard-code tên project vào helper script.

### Chẩn đoán production

```bash
./production-debug.sh --docker
```

Đây là lệnh mặc định nên chạy đầu tiên khi production có vấn đề.

### Chạy Artisan đúng container của project

```bash
./run-docker-artisan.sh "php artisan ..."
```

Ví dụ:

```bash
./run-docker-artisan.sh "php artisan about --only=environment"
./run-docker-artisan.sh "php artisan route:list"
./run-docker-artisan.sh "php artisan config:show app"
```

### Sau khi sửa `.env`

```bash
./run-updated-env.sh
```

### Chỉ xem dung lượng/rác

```bash
./production-cleanup.sh --report
```

### Rotate Laravel log khi vượt ngưỡng

```bash
./production-cleanup.sh --logs
```

### Dọn dangling image + build cache, không xóa named volume

```bash
./production-cleanup.sh --docker
```

### Sau deploy/update production

```bash
docker compose -p "$(basename "$PWD")" ps --all
./production-debug.sh --docker
git status -sb
```

---

## 2. Nguyên tắc chẩn đoán production

Luôn theo thứ tự:

1. **Diagnose first** — chẩn đoán trước khi sửa.
2. Không rebuild image nếu chưa xác định lỗi liên quan image/build artifact.
3. Không migrate nếu chưa xác định migration thực sự cần chạy.
4. Không restart toàn stack nếu chỉ một service có vấn đề.
5. Không xóa cache/log/volume để “thử xem có hết lỗi không”.
6. Không dùng destructive database command trong quá trình chẩn đoán.
7. Không tự ý xóa production-only Compose overlays.
8. Không thay đổi permission bừa bãi; đặc biệt không dùng `chmod 777`.
9. Khi stack đang degraded, coi đó là **tín hiệu chẩn đoán**, không tự động recreate/reconcile trước khi hiểu nguyên nhân.

Mặc định, production diagnosis phải là **read-only**.

---

## 3. Lệnh đầu tiên khi production có lỗi

Đứng trong thư mục project, ví dụ:

```bash
cd /opt/projects/tnv
```

Sau đó chạy:

```bash
./production-debug.sh --docker
```

Script này được thiết kế để cung cấp baseline với ít lệnh nhất có thể và không in secrets.

Thông thường output sẽ cho biết:

- Docker version;
- Docker Compose version;
- Compose project đang được target;
- trạng thái từng service/container;
- container `app` thực tế;
- container có running/healthy hay không;
- user/uid runtime;
- working directory;
- PHP version;
- Laravel version;
- environment;
- debug mode;
- application URL;
- maintenance mode;
- timezone;
- locale.

Khi gửi yêu cầu chẩn đoán, **ưu tiên gửi nguyên output của lệnh này trước**. Chỉ chạy thêm lệnh khi baseline chưa đủ.

### Các chế độ chẩn đoán bổ sung

Runtime contract hiện hỗ trợ các nhóm kiểm tra như:

```bash
./production-debug.sh --permissions
./production-debug.sh --database
./production-debug.sh --logs
```

Chỉ dùng khi vấn đề tương ứng cần phân tích sâu hơn.

---

## 4. Cách đọc nhanh trạng thái Docker

Kiểm tra toàn bộ service của đúng project:

```bash
docker compose -p "$(basename "$PWD")" ps --all
```

Trạng thái bình thường thường là:

```text
Up ... (healthy)
```

Các trạng thái cần chẩn đoán:

```text
restarting
created
exited
dead
unhealthy
```

Nếu một service đang `restarting` hoặc `unhealthy`, không nên chạy ngay `docker compose up`, rebuild hoặc restart toàn stack chỉ để thử.

Trước tiên xác định service nào lỗi và lấy log đúng service khi cần.

Ví dụ:

```bash
docker compose -p "$(basename "$PWD")" logs --tail=200 app
```

hoặc:

```bash
docker compose -p "$(basename "$PWD")" logs --tail=200 queue
```

Không cần lấy log của mọi service nếu chỉ một service bị lỗi.

---

## 5. Chạy Artisan trong production

Không nên đoán tên container như `tnv-app-1` rồi gọi `docker exec` trực tiếp.

Dùng helper:

```bash
./run-docker-artisan.sh "php artisan <command>"
```

Helper tự xác định canonical Compose project và service `app` tương ứng với thư mục hiện tại.

Ví dụ an toàn để chẩn đoán:

```bash
./run-docker-artisan.sh "php artisan about"
./run-docker-artisan.sh "php artisan about --only=environment"
./run-docker-artisan.sh "php artisan route:list"
./run-docker-artisan.sh "php artisan config:show app"
./run-docker-artisan.sh "php artisan queue:failed"
```

### Không dùng destructive Artisan command để chẩn đoán

Không dùng các lệnh sau như bước thử lỗi:

```bash
php artisan migrate:fresh
php artisan db:wipe
php artisan migrate:rollback
```

Các lệnh trên có thể làm mất hoặc thay đổi dữ liệu production.

Migration production chỉ thực hiện khi đã review migration, xác định rõ cần chạy và có kế hoạch backup/recovery phù hợp.

---

## 6. `.env` trong production

Canonical production `.env` nằm trên host tại:

```text
/opt/projects/<project-name>/.env
```

`.env` là **runtime configuration**, không phải image artifact.

Không:

- commit `.env` vào Git;
- copy `.env` vào image;
- in toàn bộ `.env` trong log/debug output;
- gửi secrets vào chat hoặc issue.

### Sau khi sửa `.env`

Chạy:

```bash
./run-updated-env.sh
```

Helper này được thiết kế để:

- validate Compose config;
- target đúng Compose project hiện tại;
- từ chối automatic apply nếu stack hiện có service non-running/degraded;
- reconcile bằng `docker compose up -d --no-build`;
- refresh Laravel config cache;
- signal queue workers;
- restart scheduler nếu có;
- báo lại trạng thái service cuối cùng.

Helper **không**:

- build image;
- chạy migration;
- mutate database;
- prune Docker;
- xóa volume;
- tự động xóa log.

### Khi nào `.env` có thể cần service recreation

Laravel-only config thường chỉ cần config cache refresh.

Nhưng giá trị được Compose sử dụng qua:

- variable interpolation;
- `environment:`;
- `env_file:`;
- port mapping;

có thể cần Compose reconcile/recreation để service nhận giá trị mới.

Đó là lý do nên dùng `run-updated-env.sh` thay vì chỉ chạy `php artisan config:cache`.

---

## 7. Storage và permissions

Production sử dụng Docker named volume cho `storage` và runtime directories được entrypoint chuẩn bị cho PHP-FPM user `www-data`.

Khi gặp lỗi kiểu:

```text
Permission denied
Unable to write file
Failed to open stream
```

không nên sửa ngay bằng:

```bash
chmod -R 777 storage
```

Thay vào đó chạy kiểm tra permission:

```bash
./production-debug.sh --permissions
```

Điểm cần phân biệt:

- root có thể ghi được nhưng `www-data` không ghi được;
- CLI container có thể chạy bằng root nhưng PHP-FPM xử lý request bằng `www-data`;
- owner/group/mode cần được kiểm tra theo runtime user thực tế.

Không mutate permission trong bước chẩn đoán đầu tiên.

---

## 8. Queue và scheduler

Các project có thể có nhiều queue service độc lập.

Ví dụ project hiện tại có thể gồm:

```text
queue
queue-request
queue-admission-documents
scheduler
```

Khi queue có vấn đề, trước tiên kiểm tra:

```bash
./production-debug.sh --docker
```

Sau đó, nếu cần, lấy log đúng queue:

```bash
docker compose -p "$(basename "$PWD")" logs --tail=200 queue
```

hoặc queue chuyên biệt:

```bash
docker compose -p "$(basename "$PWD")" logs --tail=200 queue-request
```

Không restart tất cả queue nếu chỉ một queue chuyên biệt đang lỗi mà chưa biết nguyên nhân.

Sau thay đổi `.env`, `run-updated-env.sh` đã có logic signal queue workers và xử lý scheduler theo runtime contract.

---

## 9. Database diagnosis

Khi nghi ngờ DB connection/config:

```bash
./production-debug.sh --database
```

Ưu tiên chẩn đoán:

- container DB có running/healthy không;
- Laravel có đang target đúng DB host không;
- config cache có stale không;
- DB service/container có đúng Compose project không;
- named DB volume có đúng project không.

Không dùng `migrate:fresh`, `db:wipe`, rollback hoặc xóa named volume để kiểm tra kết nối DB.

### MariaDB initialization variables

Các biến khởi tạo MariaDB trong `.env` không nên được hiểu là cách tự động đổi credentials/schema của một database đã tồn tại trong named volume.

Nếu DB volume đã tồn tại, thay đổi initialization variables cần kế hoạch riêng và không nên xử lý như một env refresh thông thường.

---

## 10. Laravel logs

Kiểm tra tổng quan:

```bash
./production-cleanup.sh --report
```

Hoặc dùng diagnostics:

```bash
./production-debug.sh --logs
```

Nếu cần xem log trực tiếp:

```bash
tail -n 200 storage/logs/laravel.log
```

Không cần xóa `laravel.log` để debug.

### Rotate log khi quá lớn

Dùng:

```bash
./production-cleanup.sh --logs
```

Mặc định helper chỉ rotate `storage/logs/laravel.log` khi vượt:

```text
LARAVEL_LOG_MAX_MB=200
```

và giữ mặc định:

```text
LARAVEL_LOG_KEEP=5
```

Đây là cleanup có chủ đích, không phải bước đầu tiên của chẩn đoán lỗi application.

---

## 11. Kiểm tra dung lượng Docker và filesystem

Chỉ xem report, không xóa gì:

```bash
./production-cleanup.sh --report
```

Report bao gồm:

- Laravel log usage;
- filesystem usage;
- Docker images;
- containers;
- local volumes;
- build cache;
- reclaimable size.

### Cleanup Docker an toàn theo contract

Khi operator đã xem report và chủ động quyết định cleanup:

```bash
./production-cleanup.sh --docker
```

Action này là **host-wide**, vì nhiều project dùng chung Docker host.

Helper chỉ cleanup:

- dangling images;
- unused build cache.

Helper **không prune named volumes**.

Không thay helper bằng:

```bash
docker system prune
docker volume prune
```

đặc biệt trên host có nhiều project production.

---

## 12. Production-only Compose overlays

Một production checkout có thể có các file overlay chỉ tồn tại trên server và intentionally untracked, ví dụ:

```text
compose.queue.yaml
compose.scheduler.yaml
compose.socket.yaml
```

Khi `git status` hiển thị chúng là untracked, không được mặc định coi là rác.

Không chạy:

```bash
git clean -fd
```

trên production checkout.

Trước mọi thao tác xóa file untracked cần xác định rõ ownership và mục đích runtime của file đó.

---

## 13. Git update trên production

Trước khi pull:

```bash
git status -sb
```

Nếu tracked working tree clean, có thể cập nhật theo workflow đã duyệt.

Sau pull/deploy:

```bash
git status -sb
docker compose -p "$(basename "$PWD")" ps --all
./production-debug.sh --docker
```

Untracked production overlays có thể vẫn tồn tại và đó có thể là trạng thái hợp lệ.

Không dùng `git reset --hard` hoặc `git clean -fd` như cách “đưa production về sạch” khi chưa xác định file local nào đang có vai trò runtime.

---

## 14. Phân loại nhanh lỗi production

### A. Web trả 500 nhưng container vẫn healthy

Chạy trước:

```bash
./production-debug.sh --docker
```

Sau đó kiểm tra Laravel log:

```bash
./production-debug.sh --logs
```

hoặc:

```bash
tail -n 200 storage/logs/laravel.log
```

Tập trung vào exception/application config trước khi restart toàn stack.

### B. Một container restarting/unhealthy

Chạy:

```bash
./production-debug.sh --docker
```

Sau đó lấy log đúng service:

```bash
docker compose -p "$(basename "$PWD")" logs --tail=200 <service>
```

Không recreate service trước khi đọc nguyên nhân.

### C. Lỗi sau khi sửa `.env`

Nếu stack trước đó healthy:

```bash
./run-updated-env.sh
```

Nếu stack đã degraded trước khi apply, chẩn đoán nguyên nhân trước.

### D. Lỗi ghi file/storage

```bash
./production-debug.sh --permissions
```

Không dùng `chmod 777`.

### E. Queue không xử lý job

```bash
./production-debug.sh --docker
./run-docker-artisan.sh "php artisan queue:failed"
```

Sau đó lấy log queue cụ thể nếu cần.

### F. DB connection/config lỗi

```bash
./production-debug.sh --database
```

Không chạy migration destructive để thử kết nối.

### G. Disk gần đầy

```bash
./production-cleanup.sh --report
```

Xem nguyên nhân trước; chỉ sau đó mới chọn `--logs` hoặc `--docker` nếu phù hợp.

---

## 15. Thông tin tối thiểu cần gửi khi nhờ chẩn đoán

Khi nhờ ChatGPT/đội phát triển xử lý production, ưu tiên gửi:

```bash
./production-debug.sh --docker
```

và mô tả ngắn:

```text
- URL/route bị lỗi:
- Thời điểm bắt đầu lỗi:
- Hành động ngay trước khi lỗi xảy ra:
- Có vừa pull/deploy/sửa .env/restart service không:
- Lỗi xảy ra với tất cả user hay một chức năng cụ thể:
```

Nếu được yêu cầu thêm, mới chạy đúng nhóm chẩn đoán:

```text
permissions -> ./production-debug.sh --permissions
database    -> ./production-debug.sh --database
logs        -> ./production-debug.sh --logs
```

Mục tiêu là **ít command nhưng đủ context**, tránh yêu cầu operator chạy hàng loạt lệnh không liên quan.

---

## 16. Các lệnh không được dùng làm diagnosis default

Không dùng các lệnh sau chỉ để “thử xem có hết lỗi không”:

```bash
php artisan migrate:fresh
php artisan db:wipe
php artisan migrate:rollback

git reset --hard
git clean -fd

docker system prune
docker volume prune
docker compose down -v
```

Cũng không:

- xóa named DB/storage volume;
- sửa `.env` ngẫu nhiên;
- rebuild image không có căn cứ;
- restart toàn bộ Docker host;
- thay permission toàn bộ project bằng `777`;
- xóa production-only overlay.

Các thao tác destructive chỉ được thực hiện khi đã có mục tiêu rõ ràng, review tác động, backup/recovery plan và operator approval.

---

## 17. Checklist sau deploy/update

Sau khi deploy hoặc pull code production, kiểm tra:

```bash
# 1. Git state
git status -sb

# 2. Compose service state
docker compose -p "$(basename "$PWD")" ps --all

# 3. Runtime baseline
./production-debug.sh --docker
```

Nếu tất cả service cần thiết là `healthy`, Laravel environment đúng `production`, debug OFF và không có tracked file bất thường thì runtime baseline đạt yêu cầu.

Nếu `.env` vừa thay đổi, dùng:

```bash
./run-updated-env.sh
```

sau đó chạy lại health check.

---

## 18. Checklist cleanup định kỳ

Xem report trước:

```bash
./production-cleanup.sh --report
```

Nếu Laravel log quá lớn:

```bash
./production-cleanup.sh --logs
```

Nếu Docker build cache/dangling image quá lớn và operator chủ động cleanup:

```bash
./production-cleanup.sh --docker
```

Sau cleanup kiểm tra lại:

```bash
./production-cleanup.sh --report
docker compose -p "$(basename "$PWD")" ps --all
```

Không prune volumes như một phần của cleanup định kỳ mặc định.

---

## 19. Tư duy vận hành chuẩn

Production troubleshooting nên đi theo chuỗi:

```text
Observe
  -> Identify affected layer
  -> Collect minimal evidence
  -> Diagnose root cause
  -> Apply smallest safe change
  -> Verify health
  -> Record/closeout
```

Không đi theo chuỗi:

```text
Error
  -> restart everything
  -> clear everything
  -> rebuild everything
  -> delete cache/volume
  -> hope it works
```

Mục tiêu của các production helper trong repository là tạo một contract ổn định để operator và người hỗ trợ đều nhìn thấy cùng một runtime context với số lệnh tối thiểu.
