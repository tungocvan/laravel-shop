# Request Demo Seeder Runbook

## Mục tiêu

Tài liệu này là runbook chuẩn để tạo dữ liệu demo/test cho Module Request trên local và production có chủ đích.

`REQUEST_ENV` là single explicit opt-in cho toàn bộ demo seeder của Request. Không đổi `APP_ENV` để chạy demo.

## Nguyên tắc an toàn

- Production bình thường phải giữ `REQUEST_ENV=false`.
- Chỉ đặt `REQUEST_ENV=true` khi chủ động cần tạo/test dữ liệu demo Request.
- Không đổi `APP_ENV=production` sang environment khác để lách guard seeder.
- Trên Docker production phải xác định đúng Compose project trước khi chạy lệnh. Không dùng `docker compose ...` trần nếu host có thể có nhiều Compose project cùng chạy.
- Với site Từ Ngọc Vân hiện tại, project production là `tnv`, vì vậy dùng `docker compose -p tnv ...`.
- Trước khi seed phải xác nhận Module Request đang enabled, config Request đã load và database Request đã ready.
- E2E demo có tạo user test, role/permission, Request scenarios và dữ liệu vận hành; không chạy trên production thật nếu không có chủ đích test.
- Sau khi hoàn tất test production, trả `REQUEST_ENV=false` và recreate service cần thiết để runtime nhận lại cấu hình.

## Flow chuẩn để test production

```text
APP_ENV=production
REQUEST_ENV=true
        ↓
Xác nhận đúng Docker Compose project/runtime
        ↓
Xác nhận Request enabled + request.settings.demo_seeders_enabled=true
        ↓
RequestE2EDemoSeeder
        ↓
Tạo/cập nhật users E2E
        ↓
Tạo/sync roles + permissions E2E
        ↓
RequestDemoSeeder
        ↓
RequestStarterTemplateSeeder
        ↓
Tạo full E2E Request scenarios
        ↓
Kiểm tra UI/routes/dữ liệu
        ↓
REQUEST_ENV=false sau khi kết thúc test
```

## 1. Bật demo mode có chủ đích

Giữ nguyên:

```env
APP_ENV=production
```

Bật:

```env
REQUEST_ENV=true
```

Không cần thêm một E2E flag khác. `Modules/Request/config/settings.php` ánh xạ `REQUEST_ENV` sang `request.settings.demo_seeders_enabled` và tự bật starter templates trong demo flow.

## 2. Xác nhận đúng Docker Compose project

```bash
docker compose ls
```

Với production `tnv`, các lệnh bên dưới phải dùng rõ project:

```bash
docker compose -p tnv ps
```

Nếu tồn tại nhiều project cùng dùng một `compose.yaml`, tuyệt đối không suy luận rằng `docker compose exec app` đang vào đúng production runtime.

## 3. Xác nhận effective Request config

```bash
docker compose -p tnv exec -T app php artisan tinker --execute='
dump([
    "app_env" => app()->environment(),
    "Request_enabled" => config("modules.registry.Request.enabled"),
    "demo_enabled" => config("request.settings.demo_seeders_enabled"),
    "starter_enabled" => config("request.settings.starter_templates_enabled"),
]);
'
```

Kỳ vọng khi chuẩn bị test production:

```text
app_env         = production
Request_enabled = true
demo_enabled    = true
starter_enabled = true
```

Nếu `request.settings` hoặc Request registry trả `null`, dừng seed và kiểm tra đúng container/source/runtime trước.

## 4. Chạy full Request E2E demo pack

Seeder chuẩn để test UI/flow đầy đủ là:

```text
database/seeders/RequestE2EDemoSeeder.php
```

Chạy:

```bash
docker compose -p tnv exec -T app php artisan db:seed \
  --class='Database\\Seeders\\RequestE2EDemoSeeder' \
  --force
```

E2E pack chịu trách nhiệm bootstrap dữ liệu test cần thiết, gồm user E2E, role/permission E2E, Request demo definition/starter templates và ma trận scenario phục vụ test UI/approval/lifecycle.

Không cần tự đặt `REQUEST_STARTER_TEMPLATE_ACTOR_ID`/`REQUEST_STARTER_TEMPLATE_APPROVER_ID` chỉ để chạy full E2E pack; E2E seeder tự cấu hình actor/approver runtime phù hợp cho starter template sau khi tạo user demo.

## 5. Dữ liệu E2E kỳ vọng

Full E2E pack tạo/cập nhật các persona test phục vụ các vai trò Request như requester, approver, finance và auditor; đồng thời tạo các scenario như draft, pending, SLA warning/overdue/suspended, approved, rejected, returned, cancelled và failed activation.

Seeder cũng có fixture phục vụ kiểm tra collaboration/operations như comments, private attachment, failed outbox và failed export.

Không coi password demo hoặc account demo là credential production thật. Đây chỉ là fixture test có chủ đích.

## 6. Kiểm tra sau seed

Kiểm tra nhanh số lượng dữ liệu:

```bash
docker compose -p tnv exec -T app php artisan tinker --execute='
dump([
    "request_types" => \Illuminate\Support\Facades\DB::table("request_types")->count(),
    "request_instances" => \Illuminate\Support\Facades\DB::table("request_instances")->count(),
    "request_tasks" => \Illuminate\Support\Facades\DB::table("request_tasks")->count(),
]);
'
```

Kiểm tra routes Request:

```bash
docker compose -p tnv exec -T app php artisan route:list | grep -E 'admin/requests|apps/request|request\.'
```

Sau đó test UI theo các persona/scenario mà E2E pack đã tạo.

## 7. Tắt demo mode sau khi test

Sau khi hoàn tất test production:

```env
REQUEST_ENV=false
```

Sau đó recreate app/runtime cần đọc lại ENV, ví dụ:

```bash
docker compose -p tnv up -d --force-recreate app queue-request scheduler
```

Xác nhận:

```bash
docker compose -p tnv exec -T app php artisan tinker --execute='
dump([
    "app_env" => app()->environment(),
    "demo_enabled" => config("request.settings.demo_seeders_enabled"),
]);
'
```

Kỳ vọng `app_env=production` và `demo_enabled=false`.

Lưu ý: tắt `REQUEST_ENV` chỉ khóa việc chạy demo seeder tiếp theo; nó không tự xóa dữ liệu demo đã seed. Nếu cần cleanup demo data phải dùng quy trình cleanup riêng, có kiểm tra phạm vi dữ liệu trước khi xóa.

## 8. Seeder cơ bản và E2E seeder

- `RequestDemoSeeder`: dữ liệu/definition demo Request cơ bản.
- `RequestStarterTemplateSeeder`: starter templates; khi gọi trực tiếp cần actor/approver hợp lệ theo config.
- `RequestE2EDemoSeeder`: lựa chọn chuẩn khi cần full E2E test pack; tự bootstrap users, roles/permissions, gọi Request demo và starter template, rồi tạo lifecycle scenarios.

Khi mục tiêu là test toàn bộ UI/approval flow, ưu tiên `RequestE2EDemoSeeder` thay vì chạy từng seeder rời và tự dựng user/role bằng tay.
