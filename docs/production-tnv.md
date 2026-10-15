# TNV Production — Verified Runtime Profile

> Repository: `tungocvan/laravel-shop`. Hồ sơ **đã xác minh**, dành cho AI debug và operator. Cập nhật từ output Production ngày **15/10/2026** (ICT). Đây là snapshot có ngày, **không phải** lời khẳng định trạng thái hiện tại vĩnh viễn.
>
> **Quy tắc ưu tiên:** đọc file này trước khi yêu cầu bất kỳ lệnh Docker discovery nào. Chỉ kiểm tra lại một thuộc tính khi incident có bằng chứng thuộc tính đó đã thay đổi hoặc việc xác minh lại thực sự quyết định bước xử lý. Khi source/code có thể phân tích từ GitHub, bắt đầu ở source/route/test; không yêu cầu operator chạy lại baseline Docker theo thói quen.

## 1. Danh tính đã xác minh

| Thuộc tính | Giá trị |
| --- | --- |
| Repository | `tungocvan/laravel-shop` |
| Domain | `https://tungocvan.com` |
| Production HOST path | `/opt/projects/tnv` |
| Branch deploy | `main` |
| Compose project | `tnv` |
| Application container working dir | `/var/www/html` |
| PHP runtime | PHP-FPM; production image `tnv-app` |
| Database | MariaDB (container `tnv-db-1`, image `mariadb:11.8` trong snapshot) |
| Runtime .env | HOST `/opt/projects/tnv/.env` -> container `/var/www/html/.env` (bind mount) |

Không giả định PHP/Composer/Artisan trên HOST giống trong container. Không in secrets.

## 2. Docker topology (snapshot 15/10/2026)

Compose project `tnv` có các service: `app`, `db`, `queue`, `queue-admission-documents`, `queue-request`, `redis`, `scheduler`, `socket`, `web`. Tại thời điểm kiểm tra cả 9 container đều Up; các service có healthcheck hiển thị healthy.

Tên container quan sát: `tnv-app-1`, `tnv-db-1`, `tnv-queue-1`, `tnv-queue-admission-documents-1`, `tnv-queue-request-1`, `tnv-redis-1`, `tnv-scheduler-1`, `tnv-socket-1`, `tnv-web-1`. Dùng service qua `docker compose -p tnv` hoặc helper canonical, không dựa vào tên container bất biến.

### Mounts đã kiểm tra trực tiếp trên `tnv-app-1`

| HOST/volume | Container | Kiểu | Ý nghĩa |
| --- | --- | --- | --- |
| `/opt/projects/tnv/Modules` | `/var/www/html/Modules` | bind | Source module PHP đọc trực tiếp từ HOST |
| `/opt/projects/tnv/.env` | `/var/www/html/.env` | bind | Runtime ENV |
| `tnv_app_storage` (named volume) | `/var/www/html/storage` | volume | Storage persistent |

**Quan trọng:** Chỉ `Modules` được xác nhận bind source ở snapshot này. **Không suy rộng** sang `app/`, `routes/`, `config/`, `resources/`, `vendor/` hoặc các service queue/scheduler; những path đó có thể được đóng gói trong image hoặc mount khác. Khi sửa ngoài `Modules`, kiểm tra đúng source delivery path *nếu ảnh hưởng quyết định deploy*.

## 3. PHP OPcache — đã xác minh

Trong app container:
- `opcache.enable=On`
- `opcache.enable_cli=Off`
- `opcache.validate_timestamps=Off`
- `opcache.revalidate_freq=2` (không có tác dụng tự kiểm tra timestamp khi validate OFF)

**Hệ quả:** Sau `git pull` với code PHP trong bind mount `Modules`, file mới có thể đã thấy trong container nhưng PHP-FPM vẫn chạy bytecode cũ. Phải đánh giá reload/restart **chỉ service app** theo impact và approval; không rebuild toàn stack nếu chỉ sửa file module bind-mounted. Trong deploy PR #289, operator đã restart `app` và xác nhận Test/UI PASS. Không mặc định restart queue/scheduler nếu code của chúng không bị ảnh hưởng. Không cần chạy `ps` để khám phá PHP-FPM: image snapshot không cài `ps`; container entrypoint là `php-fpm`.

## 4. Git/deploy checkpoint (15/10/2026)

- PR #289 `fix/category-import-row-context` merged vào `main`.
- Production HOST đã `git pull --ff-only origin main` thành công, HEAD `26e789fb5`.
- Tracked working tree sạch; 3 production-only untracked overlays **phải bảo toàn**:
  - `compose.queue.yaml`
  - `compose.scheduler.yaml`
  - `compose.socket.yaml`
- Admin WSL sau merge: Category regression **32 passed / 194 assertions**.
- Production operator báo **Test + UI PASS** sau cập nhật/restart.
- Snapshot HTTP: `/` và `/product` trả 200 trước deploy; HTTP 200 không tự chứng minh có sản phẩm hay chi tiết sản phẩm hiển thị.
- Commit này là **checkpoint lịch sử**. Với incident mới, chỉ fetch/check Git khi có deploy drift, source mismatch hoặc cần xác minh commit cụ thể.

## 5. Mặc định debug: application-first, không lặp Docker baseline

1. Người dùng cung cấp URL/route, symptom, thời điểm và thay đổi gần nhất nếu có.
2. Đọc hồ sơ này, `docs/PRODUCTION_DEBUG_PROMPT.md`, tài liệu module và source/tests hiện hành trên GitHub. Xác định controller/service/query/permission liên quan.
3. Nếu đã có exception/SQL/stack trace trong output người dùng: phân tích trực tiếp. Nếu chưa đủ, xin **một** lệnh read-only có mục tiêu: Laravel log theo thời điểm/route hoặc helper `./production-debug.sh --logs` (nếu phù hợp), tránh dump log vô hạn.
4. Nếu lỗi là dữ liệu/logic, kiểm tra query/schema/record tối thiểu bằng helper `./run-docker-artisan.sh "php artisan ..."` khi cần; không mutate.
5. **Chỉ** chạy lại `./production-debug.sh --docker` / `docker compose -p tnv ps` / mount inspection khi: container unhealthy, HTTP toàn site lỗi, deploy mới thay image/mount/Compose, code HOST và runtime khác nhau, OPcache stale nghi ngờ có chứng cứ, hoặc incident liên quan Docker/network/queue/storage.
6. Source defect: branch riêng -> tests -> UI gate -> PR -> user merge -> Production fast-forward -> reload đúng service nếu cần -> verify. Không sửa trực tiếp production.
7. Đóng incident: ghi kết luận, thời điểm, các thay đổi runtime/topology mới **đã xác minh** vào file này qua PR; không chép secrets.

**Không biến hồ sơ thành lý do bỏ qua kiểm tra an toàn trước mutation.** Trước deploy/restart/migration phải xác định đúng target, ảnh hưởng, backup/rollback và trạng thái mới nếu có thay đổi kể từ snapshot.

## 6. Quy tắc bắt buộc: PHP Artisan thông qua helper

**Mọi lệnh `php artisan ...` trên Production TNV phải đi qua `./run-docker-artisan.sh` từ HOST `/opt/projects/tnv`.** Helper chọn đúng Compose project và app container. Không chạy `php artisan` trực tiếp trên HOST, không dùng `docker exec ... php artisan` hoặc `docker compose exec app php artisan` như lối tắt mặc định.

Ví dụ (HOST, chỉ khi có mục đích kiểm tra cụ thể):

```bash
cd /opt/projects/tnv
./run-docker-artisan.sh "php artisan route:list"
```

```bash
./run-docker-artisan.sh "php artisan migrate:status"
```

```bash
./run-docker-artisan.sh "php artisan queue:failed"
```

`route:list`, `migrate:status` và `queue:failed` là ví dụ read-only, **không phải** checklist chạy mỗi lần. Các Artisan command thay đổi dữ liệu/trạng thái (`migrate`, `db:seed`, `cache:clear`, `queue:restart`, v.v.) vẫn phải có lý do, impact/rollback và operator approval; helper không biến command mutation thành an toàn.

Không tự ý chạy test suite trên Production chỉ vì code đã deploy; ưu tiên tests trên Admin WSL/CI. Khi cần truy vấn dữ liệu, không in credentials hoặc PII.

## 7. Lệnh tham chiếu — chỉ sử dụng theo điều kiện

- Khi **có bằng chứng** lỗi Docker/runtime: `./production-debug.sh --docker`
- Khi lỗi log/exception: `./production-debug.sh --logs` hoặc log đúng thời điểm
- Khi cần Artisan: `./run-docker-artisan.sh "php artisan <command>"`
- Khi nghi permission: `./production-debug.sh --permissions`
- Khi nghi DB connection: `./production-debug.sh --database`
- Khi operator chủ động đổi .env: `./run-updated-env.sh` theo runbook
- Khi cần kiểm tra service cụ thể: `docker compose -p tnv ps app`

Không dùng `git reset --hard`, `git clean -fd`, `docker compose down -v`, prune volume, `migrate:fresh`, `db:wipe`, `chmod 777`, `optimize:clear` hàng loạt để thử lỗi. Không xóa overlays.

## 8. Nguồn quy định bổ sung

- `docs/PRODUCTION_OPERATIONS_GUIDE.md`: runbook và helper.
- `docs/PRODUCTION_DOCKER_WORKFLOW_GUARDRAILS.md`: gate trước mutation và acceptance.
- `docs/PRODUCTION_DOCKER_RUNTIME.md`: image/mount/config/runtime contract chung.
- `docs/GITHUB_COLLABORATION_WORKFLOW.md`: GitHub branch, test/UI, PR/merge.

Nếu hướng dẫn chung khuyến nghị Docker baseline như **bước đầu tiên mặc định**, với **TNV đã có hồ sơ này** thì ưu tiên application-first khi symptom chỉ thuộc một route và runtime không có dấu hiệu drift. Các safety gate trước mutation của tài liệu chung vẫn bắt buộc.
