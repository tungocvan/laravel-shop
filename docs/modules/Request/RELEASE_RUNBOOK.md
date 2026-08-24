# Request v1 — Release & Operations Runbook

Tài liệu này là runbook vận hành cho Request v1 trong phạm vi MR-10. Nó bổ sung cho `CREATE_PLAN.md`; không thay đổi các locked decisions của Request.

## 1. Nguyên tắc

- Request mặc định tắt (`default_enabled=false`) và chỉ được bật qua runtime module state.
- Không sửa `Modules/Request/config/module.php` để bật module.
- File Request phải ở private/default disk; không dùng public storage cho attachment/export.
- Queue ổn định: `request-outbox`, `request-notifications`, `request-exports`.
- Scheduler phải chạy để dispatch outbox và các scheduled jobs của hệ thống.
- Production dùng Dockerfile và `compose.yaml` ở thư mục gốc. Không chạy nhiều daemon trong container `app`.

## 2. Pre-release application gate

Chạy trước khi xác nhận Request sẵn sàng:

```bash
php artisan module:migration-status Request
php artisan request:release-readiness
php artisan queue:failed
php artisan schedule:list
```

Kỳ vọng `request:release-readiness` PASS toàn bộ:

- module enabled;
- schema và migration ledger READY;
- permissions đã tồn tại;
- Super Admin có đủ permissions Request;
- disk Request là private;
- queue names đúng contract.

Nếu migration status báo `NEEDS_RECOVERY`, chạy dry-run trước:

```bash
php artisan module:migration-recover Request
```

Chỉ khi kết quả là `RECOVERABLE` và schema ownership đã VERIFIED mới được xem xét:

```bash
php artisan module:migration-recover Request --apply
```

Không tự insert bảng `migrations` bằng SQL/Tinker.

## 3. Enablement và permission sync

Sau khi migrations sẵn sàng, bật Request bằng UI module hoặc runtime module-state mechanism hiện hành. Sau đó đồng bộ permissions bằng command hiện hành của hệ thống:

```bash
php artisan module:permissions-sync
php artisan request:release-readiness
```

Nếu command permission sync không tồn tại ở một deployment cũ, dùng deployment flow hiện hành có `RolesAndPermissionsSeeder`; không tạo permission registry riêng cho Request.

## 4. Local development — PM2

Local giữ worker chung hiện hữu và chạy Request riêng để queue nặng không chặn `default`.

Các process mong đợi:

```text
Socketio-laravel-shop
Queue-laravel-shop
Request-Queue-laravel-shop
Scheduler-laravel-shop
```

Worker Request:

```bash
pm2 start /usr/bin/php \
  --name Request-Queue-laravel-shop \
  --cwd /var/www/laravel-shop \
  --max-memory-restart 300M \
  -- \
  artisan queue:work database \
  --queue=request-outbox,request-notifications,request-exports \
  --sleep=3 \
  --tries=5 \
  --timeout=120
```

Scheduler:

```bash
pm2 start /usr/bin/php \
  --name Scheduler-laravel-shop \
  --cwd /var/www/laravel-shop \
  -- \
  artisan schedule:work
```

Sau khi xác nhận online:

```bash
pm2 list
pm2 save
```

## 5. Production build — Docker Compose contract

Production build lại image từ `Dockerfile` gốc và dùng `compose.yaml` gốc. Request không có Dockerfile hoặc compose riêng.

Các role liên quan:

- `app`: PHP-FPM;
- `queue`: worker queue chung;
- `queue-request`: worker riêng của Request;
- `scheduler`: `php artisan schedule:work`;
- `web`, `socket`, `db`, `redis`: giữ contract hiện hành.

`queue-request` phải nghe đúng:

```text
request-outbox,request-notifications,request-exports
```

Các biến tuning production có trong `.env.docker.example`:

```text
REQUEST_QUEUE_SLEEP
REQUEST_QUEUE_TRIES
REQUEST_QUEUE_TIMEOUT
REQUEST_QUEUE_MAX_TIME
REQUEST_QUEUE_MEMORY_LIMIT
REQUEST_QUEUE_CPU_LIMIT
```

Không đưa các queue Request vào `QUEUE_NAMES` để thay worker riêng; worker chung và worker Request có lifecycle/resource riêng.

## 6. Deploy production sau khi dự án hoàn thiện

Dùng `deploy.sh` gốc. Script hiện hành chịu trách nhiệm validate Compose, backup DB trước deploy, build images, maintenance mode, migrations, optimize, permission sync nếu command tồn tại và health check.

Sau deploy, xác nhận:

```bash
docker compose ps
docker compose exec -T app php artisan module:migration-status Request
docker compose exec -T app php artisan request:release-readiness
docker compose exec -T app php artisan queue:failed
docker compose exec -T app php artisan schedule:list
```

`queue-request` và `scheduler` phải healthy trước khi coi Request operationally ready.

## 7. Backup và restore

Không tạo backup engine riêng cho Request. Request data nằm trong database ứng dụng và private `storage` volume nên dùng cơ chế backup hệ thống hiện hành.

Backup DB thủ công:

```bash
./backup-database.sh storage/app/backups/before-request-change.sql
```

Production deploy còn tạo database dump trước deploy khi DB container đang chạy.

Restore DB là thao tác phá hủy dữ liệu hiện tại. Chỉ thực hiện khi đã xác nhận backup và maintenance window:

```bash
./restore-database.sh storage/app/backups/before-request-change.sql
```

Sau restore luôn chạy:

```bash
php artisan module:migration-status Request
php artisan request:release-readiness
```

Private Request files/exports nằm trong storage; production phải backup/restore persistent `app_storage` theo chính sách backup toàn hệ thống. Không chuyển chúng sang public disk để đơn giản hóa restore.

## 8. Rollback

Rollback code và rollback data là hai quyết định riêng.

### Code-only rollback

Nếu migration mới tương thích ngược và dữ liệu không cần quay lại:

1. bật maintenance mode;
2. checkout/redeploy commit đã biết tốt;
3. rebuild/recreate containers theo `deploy.sh`/Compose hiện hành;
4. không tự rollback database migrations;
5. chạy readiness checks trước khi mở lại traffic.

### Data rollback

Nếu thay đổi dữ liệu/schema bắt buộc phải quay lại:

1. maintenance mode;
2. dừng worker tạo mutation mới;
3. xác nhận file backup trước deploy;
4. restore DB bằng công cụ hệ thống;
5. restore persistent storage nếu release đã thay đổi file state cần khôi phục;
6. deploy commit đã biết tốt;
7. chạy migration status/readiness;
8. chỉ mở traffic khi checks PASS.

Không dùng `migrate:rollback` một cách mù quáng cho sự cố Request. Migration ledger recovery của MR-10 chỉ sửa ledger đã VERIFIED; nó không phải công cụ rollback dữ liệu.

## 9. Post-release evidence

Ghi lại tối thiểu:

```text
Git commit/tag
request:release-readiness output
module:migration-status Request output
docker compose ps hoặc pm2 list
queue:failed output
schedule:list output
backup file/time trước deploy
```

Release chưa đạt nếu application readiness PASS nhưng worker Request hoặc scheduler không chạy.
