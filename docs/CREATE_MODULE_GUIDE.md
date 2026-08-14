# Hướng dẫn sử dụng `/create-module`

## Mục tiêu

Tài liệu này hướng dẫn sử dụng workflow:

`.codex/tasks/create-module.md`

để tạo Module mới đúng kiến trúc hiện tại của dự án.

Điểm quan trọng nhất:

`Modules/ModuleServiceProvider.php` là authoritative discovery/bootstrap contract.

Module mới phải phù hợp với provider này, không tự tạo một module system hoặc registration path khác.

## 1. Khi nào dùng

Dùng `/create-module` khi cần tạo một Module hoàn toàn mới, ví dụ:

```text
/create-module News
/create-module CustomerCare
/create-module Reports
```

Không dùng cho refactor một Module đã tồn tại; trường hợp đó dùng workflow analyze/refactor tương ứng.

## 2. Cách gọi cơ bản

Ví dụ:

```text
/create-module News

Yêu cầu nghiệp vụ:
- Quản lý tin tức
- Admin CRUD
- Danh mục
- Public listing/detail
- Có phân quyền
- Có upload ảnh đại diện
```

AI phải đọc `.codex/tasks/create-module.md` và các tài liệu bắt buộc trước khi code.

## 3. AI phải đọc gì trước

Tối thiểu:

- `.codex/bootstrap/CODEX_BOOTSTRAP.md`
- `.codex/bootstrap/PROJECT_BOOTSTRAP.md`
- `.codex/bootstrap/AI_PROJECT_CONTEXT.md`
- `.codex/standards/MODULE_STANDARD.md`
- `.codex/standards/ADMIN_UI_STANDARD.md`
- `Modules/ModuleServiceProvider.php`
- `app/Modules/ModuleStateRepository.php`
- `app/Modules/ModuleStateResolver.php`
- `config/modules.php`

Nếu có import/export thì đọc thêm `.codex/prompts/import-export.md`.

## 4. Quy trình chuẩn

### Phase 1 — Resolve Scope

AI xác định:

- mục tiêu nghiệp vụ
- route
- permission
- models/tables
- services
- Livewire
- imports/exports
- events/jobs/console
- external/cross-module dependencies
- module type: `shell`, `support`, hoặc `domain`

Nếu business rule chưa rõ, AI phải nêu câu hỏi/rủi ro thay vì tự đoán.

### Phase 2 — Reference Modules

AI phải tìm 1–3 Module hiện có gần nhất để học convention.

Ví dụ:

- CRUD domain → tham khảo module CRUD domain đang PASS
- Admin-heavy → tham khảo module có Admin/Livewire tương tự
- infrastructure → tham khảo support module

Không copy mù quáng toàn bộ module cũ.

### Phase 3 — Bootstrap Contract

AI phải kiểm tra Module mới sẽ được `Modules/ModuleServiceProvider.php` discover/register như thế nào.

`CREATE_PLAN.md` phải có Bootstrap Contract dạng:

```text
Manifest          : config/module.php
Type              : domain/support/shell
Dependencies      : [...]
Module Provider   : required / not required
Config            : yes/no
Web routes        : yes/no
API routes        : yes/no
Migrations        : yes/no
Livewire          : yes/no
Blade components  : yes/no
Console commands  : yes/no
Runtime state     : supported
```

Nếu requirement không thể đi qua provider hiện tại, AI phải STOP và đề xuất provider-level change riêng trước.

## 5. Quy tắc `Modules/ModuleServiceProvider.php`

Provider hiện tại là integration contract chính.

Module mới phải đi theo convention mà provider hỗ trợ, bao gồm khi applicable:

- `config/module.php`
- `Providers/<ModuleName>ServiceProvider.php`
- `config/`
- `routes/web.php`
- `routes/api.php`
- `resources/views`
- `resources/lang`
- `Helpers`
- `database/migrations`
- `Livewire`
- Blade components
- `Console`

Không tự thêm:

- `module.json`
- nwidart infrastructure
- registry thứ hai
- manual provider registration ở bootstrap khác
- custom discovery trùng với root provider

## 6. Chống duplicate registration

Nếu Module có:

`Modules/<ModuleName>/Providers/<ModuleName>ServiceProvider.php`

thì provider riêng không được load lại những thứ root `Modules/ModuleServiceProvider.php` đã tự load, trừ khi có special requirement được ghi rõ.

Phải tránh:

- duplicate route names
- duplicate config merge
- duplicate migrations
- duplicate Livewire registration
- duplicate Blade registration

## 7. Manifest và Runtime State

Manifest là source/default state trong Git.

Runtime state là deployment state.

Module mới không được dùng `config/module.php` làm mutable production state.

Runtime toggle phải đi qua:

- `ModuleStateRepository`
- `ModuleStateResolver`

Canonical runtime file:

`storage/app/system/module-state.json`

Không đọc/ghi JSON trực tiếp nếu abstraction đã tồn tại.

Sau bật/tắt module qua UI:

```bash
git status
```

phải vẫn sạch.

## 8. Dependency

Khi khai báo `depends` phải kiểm tra:

- dependency tồn tại
- không self-dependency
- không circular dependency
- module enabled không phụ thuộc module disabled
- disable/archive phải tôn trọng dependent rules

Không hard-code graph dependency ở nhiều nơi.

## 9. `CREATE_PLAN.md` và Approval Gate

Trước khi code application, AI phải tạo:

`docs/modules/<ModuleName>/CREATE_PLAN.md`

Plan phải bao gồm:

- business scope
- module type
- reference modules
- Bootstrap Contract
- structure
- manifest/runtime-state
- dependency
- routes/permissions
- database/model
- service boundaries
- Livewire/UI
- import/export nếu có
- events/jobs/console nếu có
- Docker/runtime storage nếu có
- seeder nếu có
- security/data integrity
- tests/acceptance criteria
- files change
- MR breakdown
- risks/questions

Sau khi tạo plan, AI phải STOP.

Chỉ code sau khi người dùng đồng ý.

## 10. Implementation theo MR

Một Module vừa/lớn có thể chia:

```text
MR-0 — Analysis / CREATE_PLAN
MR-1 — Skeleton + manifest + bootstrap
MR-2 — Database + models
MR-3 — Services / business logic
MR-4 — Routes / Admin / Livewire
MR-5 — Permissions + menu
MR-6 — Import/export/jobs/integration
MR-7 — Tests + documentation
MR-8 — Final regression + manual smoke
```

Đây là template; module nhỏ có thể gộp.

## 11. Service và Livewire

Luồng ưu tiên:

```text
Controller / Livewire
        ↓
Service / Action
        ↓
Model / Repository
```

Không đưa business logic lớn vào Blade hoặc Livewire method.

Sensitive mutation phải authorize server-side.

## 12. Permission và Admin UI

Nếu có Admin UI:

- route middleware phải đúng
- Livewire mutation phải authorize
- menu visibility theo permission
- Super Admin behavior phải theo convention hiện tại
- không chỉ ẩn button mà bỏ backend authorization

## 13. Database

Nếu có database:

- migration path phải phù hợp root provider
- naming/index/FK rõ ràng
- không sửa historical migrations không liên quan
- không dùng `migrate:fresh` để chữa bug
- test schema và enable/migration lifecycle khi relevant

## 14. Seeder

Seeder phải production/Docker-safe.

Không phụ thuộc Faker nếu production dependencies có thể không chứa Faker.

Ưu tiên deterministic, idempotent data với `updateOrCreate()` / `firstOrCreate()` khi đúng nghiệp vụ.

Demo seeder không được ghi đè production data/admin credential.

## 15. Docker / runtime storage

Nếu module tạo runtime file/directory:

- kiểm tra `Dockerfile`
- kiểm tra `docker/entrypoint.sh`
- kiểm tra volume
- kiểm tra `www-data`
- phân biệt `root` CLI và PHP-FPM `www-data`
- không dùng `chmod 777`

## 16. Test strategy

Thực hiện theo tầng:

```text
Syntax / focused tests
        ↓
Module regression
        ↓
System regression nếu shared infrastructure bị chạm
        ↓
Full project regression
        ↓
Manual UI smoke
```

Runtime-state applicable phải test:

- default state
- runtime ON
- runtime OFF
- required/shell protection
- dependency effective state
- manifest không bị sửa
- Git clean

## 17. Manual UI smoke

Nếu có UI, kiểm tra:

- menu
- route
- permission
- CRUD/actions
- validation
- reload/persistence
- Livewire
- 404/500
- browser console
- enable/disable module
- dependency/required rules
- Git clean

## 18. Completion Criteria

Chỉ kết luận Module hoàn thành khi applicable gates PASS:

- architecture approved
- root provider compatibility
- manifest/runtime state
- dependency
- database
- services/business logic
- routes
- permissions
- UI
- seeders
- Docker/runtime storage
- focused tests
- module regression
- System regression nếu cần
- full project regression
- manual smoke
- Git clean
- docs current
- không còn debug/temp file

Sau đó mới đề xuất merge vào `main`.

## 19. Ví dụ prompt hoàn chỉnh

```text
/create-module News

Nghiệp vụ:
- Admin quản lý tin tức
- Danh mục tin
- Public list/detail
- Upload thumbnail
- Permission view/create/update/delete

Hãy tuân thủ .codex/tasks/create-module.md.
Đặc biệt phải tuân theo Modules/ModuleServiceProvider.php.

Trước tiên:
1. đọc repository
2. chọn 1–3 reference modules
3. phân tích architecture/dependency
4. tạo docs/modules/News/CREATE_PLAN.md
5. STOP để tôi review

Chưa code application trước khi tôi duyệt CREATE_PLAN.md.
```

## 20. Source of Truth

Nếu hướng dẫn trong tài liệu này và implementation thực tế có khác biệt, ưu tiên:

1. code hiện tại của `Modules/ModuleServiceProvider.php`
2. `.codex/tasks/create-module.md`
3. standards/bootstrap hiện hành
4. tài liệu hướng dẫn này

Không dựa vào convention cũ nếu repository đã thay đổi.
