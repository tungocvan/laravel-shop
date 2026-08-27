# Production Docker Workflow Guardrails

## Mục tiêu

Tài liệu này là canonical guardrail cho mọi công việc triển khai, diagnose, acceptance hoặc thay đổi runtime trên môi trường Production chạy Docker.

Khi một task có liên quan tới Production Docker, tài liệu này phải được đọc cùng `docs/GITHUB_COLLABORATION_WORKFLOW.md` trước khi đề xuất lệnh vận hành hoặc kết luận production đã PASS.

Mục tiêu là ngăn các lỗi phổ biến như:

- source trên host đã cập nhật nhưng container vẫn chạy image/code cũ
- thiếu biến môi trường hoặc `.env.docker.example` không phản ánh contract thực tế
- Module có runtime state `true` nhưng chưa thực sự boot/route/serve được
- migration hoặc Shell Module foundation chưa ready
- permission sync chạy khi permission infrastructure chưa tồn tại
- runtime file được tạo bởi `root` nhưng PHP-FPM/queue chạy bằng `www-data` không ghi được
- DB/Redis/container health PASS nhưng application runtime vẫn dùng cấu hình sai
- worker/scheduler vẫn chạy lifecycle cũ sau deploy
- deploy source thành công nhưng HTTP/UI vẫn 404/500

## 1. Nguyên tắc nguồn sự thật trên Production

Không kết luận production đã nhận thay đổi chỉ từ Git HEAD trên host.

Phải phân biệt tối thiểu:

```text
Git source trên host
Docker image
Container đang chạy
Bind mounts / persistent volumes
Effective ENV/config bên trong container
Database/runtime state
HTTP request thực tế
```

Một kết quả như:

```text
git pull → PASS
```

không chứng minh container đang chạy code mới.

Khi kết luận production đã nhận một thay đổi, phải xác minh source/config/runtime state bên trong đúng container/service đang phục vụ request hoặc xử lý job.

## 2. Production ENV contract

### 2.1 Biến môi trường mới

Nếu source, Module, queue, scheduler, Dockerfile, Compose hoặc entrypoint sử dụng biến môi trường mới:

- phải rà soát `.env.docker.example`
- biến cần cấu hình trên production phải được bổ sung vào `.env.docker.example`
- mô tả/default phải đủ để người vận hành hiểu giá trị cần thiết
- không commit secret thật

Nếu biến là optional, application phải có default an toàn khi phù hợp. Không để production fail chỉ vì một optional key không tồn tại.

Nếu biến là required, phải fail rõ ràng thay vì âm thầm dùng một default nguy hiểm.

### 2.2 ENV chỉnh qua Production UI

Nếu hệ thống cho phép chỉnh `.env` hoặc environment configuration qua UI:

- phải xác minh UI ghi đúng nguồn cấu hình mà container/application thực sự sử dụng
- phải xác minh thay đổi có cần recreate/restart service hay clear/rebuild config cache không
- không kết luận UI save thành công đồng nghĩa application đã nhận effective value mới

### 2.3 Diagnose ENV an toàn

Không in secret/password/token nguyên văn vào chat hoặc log diagnose.

Khi cần kiểm tra, ưu tiên:

```text
SET / NOT_SET
length
boolean
hash/fingerprint không đảo ngược
```

Phải phân biệt `.env` trên host với effective ENV bên trong container.

## 3. Docker image, bind mount và container source

Trước khi quyết định chỉ `git pull`, recreate hay rebuild, phải đọc Docker/Compose hiện tại và xác định:

- source nào được `COPY` vào image
- source nào bind mount từ host
- volume nào persistent
- service nào dùng chung image
- service nào có lifecycle riêng

Các thay đổi trong source được bake vào image không được coi là đã deploy chỉ vì host đã pull commit mới.

Sau deploy quan trọng, phải kiểm tra marker/file/behavior bên trong container khi cần để chứng minh container dùng source mong đợi.

## 4. Quyết định pull / recreate / rebuild

Mỗi thay đổi production phải phân loại rõ một trong các hướng:

```text
A. git pull là đủ
B. git pull + recreate/restart service
C. rebuild image + recreate service
D. migration/runtime operation bổ sung
```

Không mặc định một phương án cho mọi thay đổi.

Phải xác định service affected, ví dụ:

```text
app
queue
queue-request
scheduler
web/nginx
```

Nếu nhiều service dùng cùng application image nhưng đang chạy container riêng, phải đánh giá từng service applicable.

## 5. Database readiness

Trước production enable hoặc acceptance của feature phụ thuộc database:

- xác minh DB connection từ application container
- xác minh schema/table cần thiết
- xác minh migration ledger
- phân biệt database mới hoàn toàn, database đã migrate và partial migration

Không chỉ dựa vào trạng thái `healthy` của DB container để kết luận application kết nối đúng.

## 6. Module production enablement gate

Không kết luận Module đã hoạt động chỉ vì runtime state là `true`.

Acceptance của Module phải đánh giá theo chuỗi applicable:

```text
runtime state
    ↓
effective modules.registry
    ↓
dependency graph
    ↓
database readiness
    ↓
permission infrastructure + permission sync
    ↓
provider/bootstrap/autoload
    ↓
route registration
    ↓
HTTP/UI smoke
    ↓
queue/scheduler behavior nếu applicable
```

`storage/app/system/module-state.json` chỉ là một bằng chứng về runtime override, không phải bằng chứng cuối cùng rằng Module đang phục vụ request thành công.

Không sửa `module-state.json` thủ công. Phải dùng cơ chế quản trị hoặc `ModuleStateRepository` theo workflow chung.

## 7. Migration và recovery

Trước khi chạy migration Module, phải diagnose và phân loại tối thiểu:

```text
fresh
ready
needs_recovery
```

Không thấy thiếu table rồi tự động chạy global migration theo phỏng đoán nếu Module có migration lifecycle/path riêng.

Nếu migration fail giữa chừng:

- kiểm tra bảng đã tồn tại
- kiểm tra migration records
- xác định partial schema
- dùng recovery path của hệ thống khi có
- không tự insert migration record chỉ để làm trạng thái trông như PASS
- không drop dữ liệu production nếu chưa có authorization rõ ràng

Migration PASS phải được xác minh lại bằng database status/diagnosis applicable.

## 8. Shell Module và infrastructure dependencies

`required=true` hoặc `enabled=true` không chứng minh database/infrastructure của Shell Module đã ready.

Trước khi Domain Module dùng một foundation như Role/Permission/User/Shared:

- xác minh Module dependency được resolve
- xác minh bảng/schema foundation tồn tại
- xác minh migration ledger
- xác minh service/provider cần thiết đã boot

Không tạo thủ công infrastructure table chỉ để vượt qua lỗi của Module phụ thuộc nếu repository đã có owner Module/migration chính thức.

## 9. Permission readiness

Với Module khai báo hoặc sync permissions, phải kiểm tra applicable:

- permission/role tables
- pivot tables
- migration state
- guard/config
- permission sync
- permission cache
- authorization smoke cho role/user representative

Permission sync PASS không thay thế UI/API authorization smoke.

Không bypass policy/middleware chỉ để làm route truy cập được.

## 10. Storage, ownership và runtime users

Nếu feature tạo hoặc cập nhật runtime file/directory, phải kiểm tra:

- `Dockerfile`
- `docker/entrypoint.sh`
- persistent volume
- owner/group
- directory mode
- file mode
- lock/temp/atomic rename behavior

Phải phân biệt process user, đặc biệt:

```text
CLI có thể chạy bằng root
PHP-FPM thường chạy bằng www-data
queue có thể chạy bằng www-data
scheduler có thể chạy bằng www-data
```

Tinker/CLI chạy bằng `root` PASS không chứng minh Web/Livewire/PHP-FPM chạy bằng `www-data` PASS.

Khi nghi permission, phải test bằng đúng runtime user applicable.

Không dùng `chmod 777` như giải pháp production.

Runtime file được tạo bằng atomic replace phải giữ ownership/mode phù hợp sau lần ghi tiếp theo, không chỉ PASS ở file ban đầu.

## 11. Redis, cache, session và queue connection

Nếu feature phụ thuộc Redis/cache/session/queue:

- xác minh connection từ application container
- xác minh effective host/port/database/credential
- xác minh service/container health
- không coi container `healthy` là bằng chứng application config đúng

Khi thay ENV/config liên quan, xác định cache/config restart/recreate requirements trước acceptance.

## 12. Queue worker và scheduler lifecycle

Nếu thay đổi ảnh hưởng job, queue, scheduler hoặc Module registration:

- xác định worker/service nào phải restart/recreate
- xác minh worker dùng image/source/config mới
- xác minh queue connection/name
- xác minh scheduler không chạy command/job của Module disabled hoặc database chưa ready
- xác minh Module enabled đăng ký đúng scheduled work applicable

Không chỉ restart `app` nếu queue/scheduler là container/process riêng và thay đổi có ảnh hưởng tới chúng.

## 13. Route và HTTP runtime verification

Sau enable/deploy Module hoặc thay đổi bootstrap/routes:

- xác minh route registration bên trong application container
- xác minh route name/path representative
- thực hiện HTTP/UI smoke applicable

Phải phân biệt các lớp lỗi:

```text
Module disabled
Provider/route chưa bootstrap
Middleware/auth/permission
Application 404
Reverse proxy/web-server 404
Container/image cũ
```

Route xuất hiện trong `route:list` là bằng chứng quan trọng nhưng vẫn không thay thế HTTP/UI smoke cuối cùng.

## 14. Seeder và production data safety

Seeder production phải explicit opt-in khi có khả năng tạo demo/sample/test data.

Không dùng `APP_ENV=production` như tín hiệu tự động để seed demo data.

Nếu dùng feature flag/env flag cho seeder:

- phải có default an toàn
- phải cập nhật `.env.docker.example`
- phải xác minh đúng DB target trước chạy
- phải ưu tiên idempotent behavior hoặc guard rõ ràng

### 14.1 Runbook demo seeder bắt buộc theo Module

Nếu một Module có demo/sample/starter seeder hoặc command chuyên dùng để tạo dữ liệu test/demo, phải tạo và duy trì file:

```text
docs/modules/<Module>/DEMO_SEEDER_RUNBOOK.md
```

File này là runbook vận hành để người dùng có thể tự lấy lệnh chuẩn mà không phải hỏi lại ChatGPT mỗi lần.

Runbook phải được viết từ source thực tế của Module và tối thiểu phải có:

- danh sách seeder/command liên quan
- ENV/feature flag cần thiết và default an toàn
- điều kiện database/Module/permission trước khi chạy
- lệnh local nếu applicable
- lệnh Production Docker nếu applicable
- `--force` khi Laravel production yêu cầu
- cách kiểm tra effective config trước khi seed
- cách xác minh dữ liệu sau seed
- idempotency/duplicate behavior nếu có
- cách tắt demo flag sau khi hoàn tất
- cleanup/reset boundary; nếu không có command cleanup an toàn phải ghi rõ là không được tự xóa dữ liệu
- cảnh báo destructive operation và yêu cầu approval khi applicable

Khi seeder, command, ENV flag hoặc cấu trúc demo data thay đổi, `DEMO_SEEDER_RUNBOOK.md` phải được cập nhật trong cùng branch/MR.

Khi bootstrap một Module theo `docs/GITHUB_COLLABORATION_WORKFLOW.md`, nếu source có demo seeder nhưng thiếu runbook này thì phải ghi nhận documentation gap và đề xuất/tạo runbook trong scope docs phù hợp trước khi coi production/demo workflow hoàn chỉnh.

Không chạy destructive/reset seeder trên production nếu chưa có authorization rõ ràng.

## 15. Before/After evidence

Với production operation có rủi ro, phải thu thập evidence tối thiểu trước và sau thay đổi theo phạm vi applicable.

Ví dụ:

### Before

```text
Git checkpoint
running container/image state
service health
Module effective state
DB/migration diagnosis
permission readiness
storage ownership
route/runtime state
```

### After

Đối chiếu lại cùng nhóm evidence để chứng minh thay đổi thực sự có hiệu lực.

Không dùng output cũ làm bằng chứng sau khi image/container/config/runtime state đã thay đổi.

## 16. Cache và compiled state

Khi source/config/routes/views thay đổi, phải xác định các cache applicable:

```text
config cache
route cache
view cache
application cache
permission cache
OPcache/process lifetime
```

Không clear mọi cache theo thói quen nếu chưa cần; nhưng cũng không bỏ qua compiled state khi behavior không khớp source.

Sau cache operation phải xác minh lại behavior thay vì coi command PASS là acceptance.

## 17. Rollback boundary

Trước production mutation quan trọng, phải xác định rollback boundary applicable:

```text
source/image rollback
container rollback
ENV/config rollback
runtime Module state rollback
database migration rollback
data rollback
permission rollback
```

Không coi rollback source là đủ nếu schema/data/runtime state đã thay đổi.

Không chạy destructive rollback database khi chưa đánh giá data compatibility và chưa có authorization.

## 18. Production completion checklist

Một production deployment/enablement chỉ được đánh dấu PASS khi các gate applicable đã được xác minh:

- [ ] đúng repository/branch/checkpoint
- [ ] `.env.docker.example` phản ánh ENV contract mới nếu có
- [ ] effective ENV/config trong container đúng
- [ ] đã phân loại pull/recreate/rebuild chính xác
- [ ] container/service thực sự dùng source/image mong đợi
- [ ] DB/Redis dependencies ready
- [ ] migration state ready và không có recovery blocker
- [ ] Module dependencies/Shell foundation ready
- [ ] permission infrastructure + sync + authorization applicable PASS
- [ ] runtime storage/ownership đúng cho process user thực tế
- [ ] queue/scheduler lifecycle applicable PASS
- [ ] Module effective state đúng
- [ ] routes representative được đăng ký
- [ ] HTTP/UI smoke applicable PASS
- [ ] nếu Module có demo seeder, `docs/modules/<Module>/DEMO_SEEDER_RUNBOOK.md` tồn tại và khớp source
- [ ] không có 404/500 bất thường
- [ ] logs không có lỗi mới quan trọng
- [ ] Git working tree không bị runtime operation làm dirty ngoài thiết kế
- [ ] rollback boundary đã được hiểu nếu deployment có rủi ro

## 19. Quy tắc diagnose khi Production khác Local/Test

Khi automated tests PASS nhưng production fail, không sửa application code ngay theo phỏng đoán.

Ưu tiên kiểm tra theo thứ tự:

```text
1. container/image/source thực tế
2. effective ENV/config
3. service lifecycle/restart requirements
4. storage ownership/runtime user
5. DB/migration state
6. Redis/cache/session/queue
7. Module runtime state + dependency
8. permission infrastructure
9. route/bootstrap
10. HTTP/reverse proxy
11. application code defect
```

Mục tiêu là chứng minh lỗi thuộc source hay environment/runtime trước khi tạo corrective code change.

## 20. Quan hệ với workflow chung

`docs/GITHUB_COLLABORATION_WORKFLOW.md` quản lý collaboration, branch, test, handoff, PR và merge.

Tài liệu này quản lý riêng production Docker readiness và runtime acceptance.

Khi task có production Docker scope:

```text
docs/GITHUB_COLLABORATION_WORKFLOW.md
        +
docs/PRODUCTION_DOCKER_WORKFLOW_GUARDRAILS.md
        ↓
Production plan / diagnose / deploy / acceptance
```

Nếu hai tài liệu có nội dung giao nhau, phải áp dụng cả hai; không dùng production guardrail để bỏ qua approval, Git safety, test, handoff hoặc merge gate của workflow chung.
