# Production Docker Workflow Guardrails

## Mục tiêu

Tài liệu này là guardrail bắt buộc cho diagnose, deploy, runtime mutation và acceptance trên Production Docker.

Khi một task liên quan Production Docker, phải áp dụng tài liệu này cùng `docs/GITHUB_COLLABORATION_WORKFLOW.md` trước khi kết luận production đã PASS.

## 1. Xác định đúng Docker Compose project và runtime

Trước mọi lệnh production phải xác định đúng Compose project/container thực tế.

```bash
docker compose ls
docker ps
```

Nếu cùng một `compose.yaml` có thể chạy dưới nhiều project name, không dùng `docker compose ...` trần theo suy đoán. Phải chỉ rõ project, ví dụ:

```bash
docker compose -p tnv ...
```

Một container khác cùng source path không phải bằng chứng cho runtime đang phục vụ domain production.

## 2. Host source, image và container là ba trạng thái khác nhau

Không kết luận production đã nhận code chỉ vì host `git pull` PASS.

Phải phân biệt:

```text
Git source trên host
Docker image
Container đang chạy
Bind mounts / persistent volumes
```

Nếu file được bake vào image, pull source trên host không đủ; phải rebuild/recreate service applicable.

Nếu path được bind mount, source host có thể che hoàn toàn nội dung đã `COPY` trong image.

## 3. ENV contract và cached configuration

### 3.1 `.env.docker.example`

Biến môi trường mới được dùng trên production phải được phản ánh trong `.env.docker.example` khi applicable.

- Không commit secret thật.
- Optional key nên có default an toàn khi phù hợp.
- Required key phải fail rõ ràng nếu thiếu.

### 3.2 Runtime code không đọc `env()` trực tiếp

Biến `.env` dùng bởi application runtime phải được ánh xạ qua file config.

Ví dụ đúng:

```php
// config/settings.php
'demo_seeders_enabled' => env('REQUEST_ENV', false),
```

Runtime application/seeder/service/command đọc:

```php
config('request.settings.demo_seeders_enabled', false)
```

Không dùng:

```php
env('REQUEST_ENV')
```

trực tiếp trong runtime code.

Lý do: trên production sử dụng `config:cache`/`optimize`, Laravel có thể không load `.env` cho lời gọi `env()` ngoài config files. Khi đó:

```text
env('REQUEST_ENV') = null
config('request.settings.demo_seeders_enabled') = true
```

vẫn là trạng thái hợp lệ nếu cached config đã được build từ `REQUEST_ENV=true`.

**Effective `config()` là nguồn sự thật cho application runtime, không phải `env()` trực tiếp.**

### 3.3 Khi `.env` thay đổi

Không suy luận rằng sửa host `.env` đồng nghĩa process/container đã nhận effective config mới.

Phải xác định deployment mode và thực hiện cache refresh/recreate applicable, sau đó verify bằng `config()` trong đúng application container.

Không đưa `optimize:clear` thành workaround thường trực cho code đọc `env()` sai. Phải sửa code đọc `config()`.

## 4. Module enable/disable

Không kết luận Module hoạt động chỉ vì runtime state là `true`.

Acceptance applicable:

```text
runtime state
    ↓
effective modules.registry
    ↓
dependency graph
    ↓
database readiness
    ↓
permission infrastructure + sync
    ↓
provider/bootstrap/routes
    ↓
HTTP/UI smoke
    ↓
queue/scheduler nếu applicable
```

Runtime state file chỉ là một bằng chứng, không phải acceptance cuối cùng.

## 5. Database/migration readiness

Trước enable hoặc test feature phụ thuộc database:

- xác minh DB connection từ đúng app container;
- xác minh expected tables;
- xác minh migration records;
- phân biệt `fresh`, `ready`, `needs_recovery`;
- không dùng global migration theo phỏng đoán nếu Module có lifecycle/path riêng.

Nếu migration fail giữa chừng, diagnose partial schema trước khi retry hoặc cleanup.

## 6. Permission readiness

`enabled=true` của Role/Permission Module không chứng minh permission tables đã tồn tại.

Trước permission sync phải xác minh infrastructure tables và migration state.

Permission sync PASS không thay thế authorization smoke cho user/role representative.

Không bypass policy/middleware để làm UI truy cập được.

## 7. Runtime user, ownership và writable files

CLI có thể chạy bằng `root` trong khi PHP-FPM/queue chạy bằng `www-data`.

Tinker chạy bằng root PASS không chứng minh UI/Livewire PASS.

Nếu feature ghi file runtime, phải kiểm tra đúng runtime user:

- directory ownership/mode;
- file ownership/mode;
- lock/temp/atomic-replace behavior;
- persistent volume behavior.

Không dùng `chmod 777` như giải pháp production.

## 8. Redis/cache/session/queue

Container `healthy` không chứng minh application đang dùng đúng host/password/database/queue.

Phải xác minh connection từ application container khi feature phụ thuộc Redis/cache/session/queue.

Khi code/config ảnh hưởng worker hoặc scheduler, đánh giá restart/recreate từng service applicable.

## 9. Demo seeder trên production

Demo seeder production phải explicit opt-in và không được tự chạy trong entrypoint/deploy mặc định.

Nếu dùng feature flag như `REQUEST_ENV`:

```text
.env
  ↓
config file
  ↓
cached/effective config
  ↓
seeder guard đọc config()
```

Không đổi `APP_ENV=production` sang environment khác để lách guard.

Không dùng `env()` trực tiếp trong seeder guard.

Trước chạy seeder phải xác minh:

- đúng Compose project;
- đúng image/container source;
- Module enabled/effective config;
- DB schema ready;
- permission infrastructure ready;
- class seeder tồn tại;
- command namespace được truyền đúng.

Sau test phải đưa demo opt-in về trạng thái an toàn nếu không còn nhu cầu và verify effective config.

Tắt demo flag không tự xóa dữ liệu demo đã tạo.

## 10. Route và HTTP runtime verification

`route:list` PASS là bằng chứng bootstrap/routes nhưng chưa đủ cho acceptance.

Phải phân biệt:

```text
Module disabled
provider/route chưa bootstrap
middleware/auth/permission
application 404
reverse proxy 404
stale image/container
```

Cuối cùng phải có HTTP/UI smoke applicable.

## 11. Cache/compiled state

Khi source/config/routes/views thay đổi, đánh giá:

```text
config cache
route cache
view cache
application cache
permission cache
OPcache/process lifetime
```

Không clear mọi cache theo thói quen, nhưng không bỏ qua compiled state khi behavior không khớp source.

Sau cache operation phải verify behavior lại.

## 12. Production completion checklist

Một production operation chỉ được đánh dấu PASS khi các gate applicable đã được kiểm tra:

- [ ] Đúng repository/branch/checkpoint.
- [ ] Đúng Compose project/container runtime.
- [ ] Host/image/container source phù hợp.
- [ ] `.env.docker.example` phản ánh ENV contract mới nếu có.
- [ ] Runtime code đọc ENV thông qua `config()`, không phụ thuộc trực tiếp `env()` ngoài config files.
- [ ] Effective config trong container đúng.
- [ ] DB/migrations ready.
- [ ] Module dependencies ready.
- [ ] Permission infrastructure + sync + authorization applicable PASS.
- [ ] Storage ownership đúng cho runtime user.
- [ ] Queue/scheduler lifecycle applicable PASS.
- [ ] Routes representative PASS.
- [ ] HTTP/UI smoke applicable PASS.
- [ ] Không có lỗi mới quan trọng trong log.
- [ ] Rollback boundary được hiểu.

## 13. Khi Local/Test PASS nhưng Production fail

Ưu tiên diagnose theo thứ tự:

```text
1. đúng Compose project/container
2. image/source thực tế
3. effective config() và cache state
4. service lifecycle/restart requirements
5. storage ownership/runtime user
6. DB/migration state
7. Redis/cache/session/queue
8. Module runtime state + dependencies
9. permission infrastructure
10. route/bootstrap/reverse proxy
11. application code defect
```

Không sửa application code theo phỏng đoán trước khi chứng minh lỗi thuộc source hay environment/runtime.
