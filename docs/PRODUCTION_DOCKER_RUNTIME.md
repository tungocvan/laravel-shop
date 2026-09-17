# Production Docker Runtime Contract

## Mục tiêu

Tài liệu này là canonical contract khi làm việc với production Docker của các project triển khai theo cấu trúc:

```text
/opt/projects/<project-name>
```

Khi người dùng nói `production`, `Docker production`, `debug production`, `test production`, `lỗi chỉ xảy ra production` hoặc đưa đường dẫn `/opt/projects/<project-name>`, phải đọc tài liệu này trước khi yêu cầu thao tác runtime.

Mục tiêu là thu thập đủ context bằng ít lệnh, ưu tiên chẩn đoán read-only và tránh lặp lại các thao tác nguy hiểm.

## 1. Runtime topology hiện tại

Project name được suy ra từ thư mục project. Ví dụ:

```text
/opt/projects/tnv
```

thì helper `run-docker-artisan.sh` hiện xác định application container theo convention:

```text
tnv-app-1
```

Laravel application working directory trong container là:

```text
/var/www/html
```

`compose.yaml` dùng named volumes cho persistence:

```text
app_storage   -> /var/www/html/storage
mariadb_data  -> /var/lib/mysql
redis_data    -> /data
```

Các service application gồm `app`, queue workers, scheduler, socket và web; database/Redis là service riêng.

## 2. Canonical production `.env`

Production `.env` canonical nằm trên host:

```text
/opt/projects/<project-name>/.env
```

Không coi `.env` nằm trong Docker image là source of truth.

Compose hiện dùng `.env` theo hai cơ chế:

1. `app`, queue workers và scheduler bind-mount:

```text
./.env:/var/www/html/.env
```

2. `socket` dùng:

```text
env_file: .env
```

Ngoài ra Compose interpolation đọc nhiều biến trực tiếp từ `.env`, ví dụ HTTP/socket port, DB/Redis credentials, queue settings, resource limits và image/build versions.

Vì vậy thay `.env` có thể ảnh hưởng nhiều tầng:

- Laravel config runtime;
- environment của long-running service;
- Docker Compose interpolation;
- host port binding;
- DB/Redis service environment;
- queue/scheduler/socket command/runtime.

Không giả định mọi thay đổi `.env` chỉ cần `php artisan config:cache`.

## 3. `.env` không thuộc Docker image

`.dockerignore` phải loại `.env` khỏi Docker build context. `Dockerfile` có `COPY . .`, vì vậy rule `.env` trong `.dockerignore` là security gate bắt buộc để secret không trở thành image layer.

Không xóa rule `.env` khỏi `.dockerignore` nếu chưa có architecture thay thế được phê duyệt.

Không in toàn bộ `.env` trong log, issue, PR, debug output hoặc chat. Diagnostic chỉ được hiển thị safe metadata hoặc `SET` / `NOT SET` cho secret.

## 4. Artisan production

Helper chuẩn:

```bash
./run-docker-artisan.sh "php artisan <command>"
```

Helper này thực thi command trực tiếp trong application container của project hiện tại.

Lưu ý: `docker exec` mặc định dùng user của container/process configuration; trong topology hiện tại application container có thể chạy command với quyền khác PHP-FPM `www-data`. Mọi command có khả năng tạo file trong `storage`, `bootstrap/cache` hoặc runtime path phải được xem xét ownership sau khi chạy.

Không dùng helper này để chạy destructive command trong giai đoạn diagnosis.

## 5. Filesystem / permission contract

`docker/entrypoint.sh` là canonical runtime permission bootstrap hiện tại.

Khi entrypoint chạy với UID 0, nó:

- `chown -R www-data:www-data storage bootstrap/cache`;
- đặt directory trong `storage/app`, `storage/framework`, `storage/logs`, `bootstrap/cache` thành `2770`;
- đặt file tương ứng thành `0660`;
- chuẩn hóa ownership/quyền của `Modules` khi directory tồn tại.

`storage/app` được dùng chung bởi HTTP, queue, scheduler và CLI. Diagnostic phải coi ownership/writability drift là lỗi runtime hạng nhất.

Không dùng `chmod 777`.

## 6. Read-only diagnosis mặc định

Khi chưa xác định root cause, production diagnosis là READ-ONLY.

Bootstrap ưu tiên:

```bash
./production-debug.sh
```

Các mode hẹp:

```bash
./production-debug.sh --docker
./production-debug.sh --permissions
./production-debug.sh --database
./production-debug.sh --logs
```

Diagnostic không được tự động:

- migrate / rollback / restore database;
- `migrate:fresh` hoặc `db:wipe`;
- sửa `.env`;
- sửa ownership/permission;
- build/recreate/restart container;
- xóa volume;
- `git reset --hard`;
- `git clean -fd`;
- xóa runtime data.

Nếu cần mutation để sửa lỗi, phải xác định root cause và được người dùng phê duyệt rõ ràng trước.

## 7. Apply `.env`

Sau khi người vận hành sửa host `.env`, helper chuẩn là:

```bash
./run-updated-env.sh
```

Contract của helper:

1. xác nhận đang ở project root và `.env` tồn tại;
2. validate Compose bằng `docker compose config --quiet` trước mutation;
3. reconcile Compose bằng `docker compose up -d --no-build` để áp dụng interpolation/env/service configuration mà không rebuild image;
4. xác nhận application container đang chạy;
5. chạy `php artisan config:clear` rồi `php artisan config:cache` trong app container;
6. dùng `php artisan queue:restart` để yêu cầu Laravel queue workers reload application state an toàn;
7. restart scheduler service để `schedule:work` nhận runtime environment mới;
8. kiểm tra trạng thái các service và báo PASS/WARN/FAIL.

Helper không build image, không migrate DB, không xóa volume và không dump secret.

### Vì sao cần Compose reconciliation

Ví dụ:

- `APP_URL` chủ yếu ảnh hưởng Laravel config;
- `HTTP_PORT` thay host port binding của `web`;
- `SOCKET_PORT` thay host port binding của `socket`;
- `QUEUE_*` có thể thay command của worker;
- `DB_*` / `REDIS_*` được Compose/service dùng trực tiếp;
- memory/CPU variables thay container resource configuration.

Do đó chỉ refresh Laravel cache không đủ cho contract chung.

### Build-time variables

Một số `.env` variables được dùng làm Docker build args hoặc image tags, ví dụ PHP/Node/MariaDB/Redis version hoặc build feature flag. `run-updated-env.sh` cố ý dùng `--no-build`; nếu thay build-time variable, helper phải cảnh báo rằng thay đổi chưa được materialize vào image và cần deployment/build flow riêng.

## 8. Diagnostic snapshot tối thiểu

`production-debug.sh` thu thập safe snapshot gồm:

- project path/name, timestamp, hostname;
- Git branch/HEAD/status/ahead-behind;
- Docker/Compose version;
- resolved Compose services và trạng thái container;
- app container user/working directory/PHP/Laravel summary;
- safe Laravel environment metadata;
- migration status read-only;
- storage/bootstrap ownership, mode và writability;
- host/container `.env` existence, không dump values;
- recent Laravel/container errors ở mode logs;
- disk/Docker disk usage;
- diagnosis flags cho dirty Git, unhealthy container, pending migration, permission drift, storage not writable.

Không yêu cầu user paste `.env` để debug.

## 9. Production safety gate

Các command sau không được đề xuất như bước diagnosis mặc định:

```text
php artisan migrate:fresh
php artisan db:wipe
php artisan migrate:rollback
git reset --hard
git clean -fd
chmod 777
docker compose down -v
docker volume rm ...
```

`docker compose build`, recreate/restart service, migration, restore DB hoặc permission mutation chỉ thực hiện khi có lý do cụ thể và approval phù hợp.

## 10. Quick operating contract

```text
Debug production
→ ./production-debug.sh

Sửa .env production
→ sửa /opt/projects/<project>/.env
→ ./run-updated-env.sh

Chạy Artisan
→ ./run-docker-artisan.sh "php artisan ..."

Canonical .env
→ /opt/projects/<project>/.env
→ mount/inject vào runtime
→ không COPY vào Docker image
```
