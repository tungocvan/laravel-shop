# Request Demo Seeder Runbook

## Mục tiêu

Tài liệu này cung cấp các lệnh chuẩn để tạo dữ liệu demo/starter template cho Module Request trên local hoặc Production Docker mà không cần đổi `APP_ENV`.

Nguồn sự thật liên quan:

```text
Modules/Request/Database/Seeders/RequestDemoSeeder.php
Modules/Request/Database/Seeders/RequestStarterTemplateSeeder.php
Modules/Request/config/settings.php
.env.docker.example
```

## 1. Cơ chế hiện tại

`RequestDemoSeeder` chỉ chạy khi:

```text
REQUEST_ENV=true
```

Seeder này gọi:

```text
RequestStarterTemplateSeeder
```

Starter template cần 2 user hợp lệ và khác nhau:

```text
REQUEST_STARTER_TEMPLATE_ACTOR_ID
REQUEST_STARTER_TEMPLATE_APPROVER_ID
```

`RequestStarterTemplateSeeder` cũng có thể được bật riêng bằng:

```text
REQUEST_STARTER_TEMPLATES_ENABLED=true
```

`REQUEST_ENV=true` đã tự bật starter templates, vì vậy khi chạy demo flow thông thường không cần bật thêm `REQUEST_STARTER_TEMPLATES_ENABLED`.

## 2. ENV cần thiết

Trong `.env.docker.example` hiện có:

```dotenv
REQUEST_ENV=false
REQUEST_STARTER_TEMPLATES_ENABLED=false
REQUEST_STARTER_TEMPLATE_ACTOR_ID=0
REQUEST_STARTER_TEMPLATE_APPROVER_ID=0
```

Trên production bình thường nên giữ:

```dotenv
REQUEST_ENV=false
REQUEST_STARTER_TEMPLATES_ENABLED=false
```

Chỉ bật `REQUEST_ENV=true` có chủ đích trong thời gian chạy demo seeder.

Không đổi:

```dotenv
APP_ENV=production
```

chỉ để seed demo data.

## 3. Điều kiện trước khi seed

Trước khi chạy phải bảo đảm:

- Module Request đã enabled và database Request đã ready
- các bảng Request đã migrate đầy đủ
- Role/Permission infrastructure đã ready nếu flow Request phụ thuộc permission
- actor ID tồn tại và là user active
- approver ID tồn tại và là user active
- actor ID và approver ID khác nhau
- đang kết nối đúng database cần seed

Seeder tự bỏ qua nếu actor/approver không hợp lệ, vì vậy command có thể kết thúc mà không tạo dữ liệu nếu ENV ID sai.

## 4. Kiểm tra effective config trước khi seed

### Production Docker

```bash
docker compose exec -T app php artisan tinker --execute='
dump([
    "request_enabled" => config("modules.registry.Request.enabled"),
    "demo_seeders_enabled" => config("request.settings.demo_seeders_enabled"),
    "starter_templates_enabled" => config("request.settings.starter_templates_enabled"),
    "actor_id" => config("request.settings.starter_template_actor_id"),
    "approver_id" => config("request.settings.starter_template_approver_id"),
]);
'
```

Kỳ vọng khi chuẩn bị chạy demo:

```text
request_enabled = true
demo_seeders_enabled = true
starter_templates_enabled = true
actor_id > 0
approver_id > 0
actor_id != approver_id
```

### Local

```bash
php artisan tinker --execute='
dump([
    "request_enabled" => config("modules.registry.Request.enabled"),
    "demo_seeders_enabled" => config("request.settings.demo_seeders_enabled"),
    "starter_templates_enabled" => config("request.settings.starter_templates_enabled"),
    "actor_id" => config("request.settings.starter_template_actor_id"),
    "approver_id" => config("request.settings.starter_template_approver_id"),
]);
'
```

## 5. Chạy RequestDemoSeeder

### Production Docker

```bash
docker compose exec -T app php artisan db:seed \
  --class='Modules\\Request\\Database\\Seeders\\RequestDemoSeeder' \
  --force
```

### Local

```bash
php artisan db:seed \
  --class='Modules\\Request\\Database\\Seeders\\RequestDemoSeeder'
```

`--force` chỉ cần thiết khi Laravel đang chạy trong production environment.

## 6. Chạy riêng starter templates

Chỉ dùng khi muốn seed starter template mà không bật toàn bộ demo flow.

ENV:

```dotenv
REQUEST_ENV=false
REQUEST_STARTER_TEMPLATES_ENABLED=true
REQUEST_STARTER_TEMPLATE_ACTOR_ID=<ACTIVE_USER_ID>
REQUEST_STARTER_TEMPLATE_APPROVER_ID=<OTHER_ACTIVE_USER_ID>
```

### Production Docker

```bash
docker compose exec -T app php artisan db:seed \
  --class='Modules\\Request\\Database\\Seeders\\RequestStarterTemplateSeeder' \
  --force
```

### Local

```bash
php artisan db:seed \
  --class='Modules\\Request\\Database\\Seeders\\RequestStarterTemplateSeeder'
```

## 7. Dữ liệu starter được tạo

Seeder hiện tạo group/template theo code cố định và bỏ qua record đã tồn tại cùng code.

Một số template hiện có gồm:

```text
STARTER
GENERAL_APPROVAL
EQUIPMENT_PURCHASE
LEAVE_REQUEST
EXPENSE_REIMBURSEMENT
```

Do seeder kiểm tra code đã tồn tại trước khi tạo mới, việc chạy lại không nhằm tạo duplicate cho các template đã tồn tại.

## 8. Kiểm tra dữ liệu sau seed

### Production Docker

```bash
docker compose exec -T app php artisan tinker --execute='
dump([
    "groups" => \Modules\Request\Models\RequestGroup::query()
        ->whereIn("code", ["STARTER", "SALES"])
        ->pluck("name", "code")
        ->all(),
    "types" => \Modules\Request\Models\RequestType::query()
        ->whereIn("code", [
            "GENERAL_APPROVAL",
            "EQUIPMENT_PURCHASE",
            "LEAVE_REQUEST",
            "EXPENSE_REIMBURSEMENT",
        ])
        ->pluck("name", "code")
        ->all(),
]);
'
```

### Local

Bỏ prefix `docker compose exec -T app` và chạy cùng đoạn `php artisan tinker --execute=...`.

## 9. Tắt demo flag sau khi hoàn tất

Sau khi seed production xong, đưa ENV về trạng thái an toàn:

```dotenv
REQUEST_ENV=false
REQUEST_STARTER_TEMPLATES_ENABLED=false
```

Giữ actor/approver ID hay đưa về `0` tùy chính sách vận hành, nhưng không để demo flag bật ngoài thời gian seed có chủ đích.

Sau khi chỉnh ENV, áp dụng đúng quy trình Docker hiện tại để application nhận effective ENV mới; không giả định chỉnh file trên host là đủ.

## 10. Không có demo reset command tự động

Hiện Module Request không có seeder/command chuẩn để xóa toàn bộ demo data đã seed.

Không tự dùng `truncate`, `migrate:fresh`, xóa trực tiếp các bảng Request hoặc xóa template theo phỏng đoán trên production.

Nếu cần cleanup demo data:

1. xác định chính xác record được tạo bởi seeder
2. kiểm tra record đã được dùng bởi request thật hay chưa
3. chuẩn bị cleanup plan riêng
4. chỉ thực hiện destructive cleanup sau khi được phê duyệt rõ ràng

## 11. Production checklist ngắn

Trước seed:

- [ ] Request effective state = enabled
- [ ] Request database ready
- [ ] đúng database target
- [ ] `REQUEST_ENV=true` hoặc starter flag đúng mục đích
- [ ] actor ID active
- [ ] approver ID active và khác actor
- [ ] effective config trong container đã nhận ENV

Sau seed:

- [ ] command PASS
- [ ] starter group/types tồn tại
- [ ] UI Request load được
- [ ] actor/approver flow representative hoạt động
- [ ] `REQUEST_ENV=false` trở lại trên production
- [ ] không có log lỗi mới đáng kể

## 12. Khi source seeder thay đổi

Mỗi lần thêm/xóa/đổi demo seeder, command, ENV flag hoặc dữ liệu mẫu của Request phải cập nhật file này trong cùng MR/branch.

Không để runbook chứa command cũ khác với source thực tế.
