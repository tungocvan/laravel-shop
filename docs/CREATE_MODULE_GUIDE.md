# Hướng dẫn phân tích và tạo Module mới

## Mục tiêu

Tài liệu này hướng dẫn hai workflow liên tiếp:

```text
.codex/tasks/analyze-new-module.md
.codex/tasks/create-module.md
```

để biến một ý tưởng nghiệp vụ thành Module mới đúng kiến trúc hiện tại của dự án.

Điểm quan trọng nhất:

`Modules/ModuleServiceProvider.php` là authoritative discovery/bootstrap contract.

Module mới phải phù hợp với provider này, không tự tạo một module system hoặc registration path khác.

## 1. Chọn đúng workflow

Có hai trường hợp.

### Trường hợp A — Chỉ có ý tưởng hoặc file `.md` thô

Không gọi `/create-module` ngay.

Dùng:

```text
/analyze-new-module <idea-file.md>
```

Luồng chuẩn:

```text
Ý tưởng / file .md thô
        ↓
/analyze-new-module
        ↓
Business + architecture analysis
        ↓
CREATE-MODULE READINESS
        ↓
User review / clarification
        ↓
REQUIREMENTS.md
        ↓
User approval
        ↓
/create-module <ModuleName>
        ↓
CREATE_PLAN.md
        ↓
Approval Gate
        ↓
Implementation
```

### Trường hợp B — Requirement đã rõ và đã được duyệt

Có thể đi thẳng vào:

```text
/create-module <ModuleName>
```

với business specification chính thức, ưu tiên:

```text
docs/modules/<ModuleName>/REQUIREMENTS.md
```

Không dùng `/analyze-module` cho Module chưa tồn tại. `/analyze-module` dành cho việc phân tích source của một Module hiện hữu.

## 2. `/analyze-new-module` dùng để làm gì

Workflow này chỉ phân tích và chuẩn hóa ý tưởng trước khi tạo Module.

Nó phải:

- đọc toàn bộ tài liệu ý tưởng được cung cấp
- phân biệt requirement đã xác định và requirement còn mơ hồ
- phân tích actors, roles, business rules và workflow
- phân tích state transitions khi có
- dự kiến domain entities/database ở mức thiết kế
- phân tích permissions, Admin UI, API/Web, files/import/export, jobs/events khi applicable
- kiểm tra cross-module dependencies
- tìm 1–3 Reference Modules phù hợp
- kiểm tra compatibility với `Modules/ModuleServiceProvider.php`
- đề xuất Bootstrap Contract
- phân loại MUST HAVE / SHOULD HAVE / FUTURE
- thực hiện Gap Analysis
- đánh giá `CREATE-MODULE READINESS`

Workflow này KHÔNG được:

- tạo Module
- tạo migration
- tạo application code
- tự chạy `/create-module`
- tự tạo `CREATE_PLAN.md`
- tự sửa file ý tưởng gốc

## 3. CREATE-MODULE READINESS

Cuối `/analyze-new-module`, AI phải đánh giá tối thiểu:

```text
Business requirements : READY / NOT READY
Module boundaries      : READY / NOT READY
Dependencies           : READY / NOT READY
Database               : READY / NOT READY
Permissions            : READY / NOT READY
Workflow               : READY / NOT READY
Bootstrap Contract     : READY / NOT READY

Overall:
READY FOR /create-module
hoặc
NOT READY FOR /create-module
```

Nếu `NOT READY`, phải nêu chính xác các quyết định còn thiếu. Không được tự điền business rule để ép trạng thái thành READY.

## 4. Chuẩn hóa thành `REQUIREMENTS.md`

Khi phân tích đã được người dùng duyệt, bước tiếp theo là chuẩn hóa requirement thành:

```text
docs/modules/<ModuleName>/REQUIREMENTS.md
```

Ví dụ prompt:

```text
Tôi đồng ý với phân tích.

Hãy chuẩn hóa kết quả đã được duyệt thành:
docs/modules/News/REQUIREMENTS.md

REQUIREMENTS.md phải là tài liệu nghiệp vụ chính thức,
không chứa những giả định chưa được tôi duyệt.

Chưa chạy /create-module.
```

Người dùng review `REQUIREMENTS.md` lần cuối trước khi chuyển sang `/create-module`.

## 5. Prompt phân tích ý tưởng mẫu

```text
/analyze-new-module docs/ideas/news-module.md

Đây là tài liệu ý tưởng ban đầu cho Module mới.

Hãy thực hiện đúng:
.codex/tasks/analyze-new-module.md

Yêu cầu:
- chỉ phân tích và chuẩn hóa requirement
- đọc repository thực tế
- tuân thủ Modules/ModuleServiceProvider.php
- tìm 1–3 Reference Modules
- phân tích business workflow, dependency, database, permission và Bootstrap Contract
- thực hiện Gap Analysis
- kết luận CREATE-MODULE READINESS

Chưa tạo Module.
Chưa tạo migration/code.
Chưa chạy /create-module.
```

## 6. Cách gọi `/create-module`

Sau khi requirement đã READY và được duyệt:

```text
/create-module News

Business specification:
docs/modules/News/REQUIREMENTS.md

Hãy thực hiện đúng:
.codex/tasks/create-module.md

Đặc biệt phải tuân thủ:
Modules/ModuleServiceProvider.php

Thực hiện Phase phân tích và tạo CREATE_PLAN.md trước.
DỪNG tại Approval Gate.
Chưa implementation cho đến khi tôi duyệt CREATE_PLAN.md.
```

## 7. AI phải đọc gì trước khi create

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

## 8. Quy trình `/create-module`

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

## 9. Quy tắc `Modules/ModuleServiceProvider.php`

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

## 10. Chống duplicate registration

Nếu Module có:

`Modules/<ModuleName>/Providers/<ModuleName>ServiceProvider.php`

thì provider riêng không được load lại những thứ root `Modules/ModuleServiceProvider.php` đã tự load, trừ khi có special requirement được ghi rõ.

Phải tránh:

- duplicate route names
- duplicate config merge
- duplicate migrations
- duplicate Livewire registration
- duplicate Blade registration

## 11. Manifest và Runtime State

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

## 12. Dependency

Khi khai báo `depends` phải kiểm tra:

- dependency tồn tại
- không self-dependency
- không circular dependency
- module enabled không phụ thuộc module disabled
- disable/archive phải tôn trọng dependent rules

Không hard-code graph dependency ở nhiều nơi.

## 13. `CREATE_PLAN.md` và Approval Gate

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

## 14. Implementation theo MR

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

## 15. Service và Livewire

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

## 16. Permission và Admin UI

Nếu có Admin UI:

- route middleware phải đúng
- Livewire mutation phải authorize
- menu visibility theo permission
- Super Admin behavior phải theo convention hiện tại
- không chỉ ẩn button mà bỏ backend authorization

## 17. Database

Nếu có database:

- migration path phải phù hợp root provider
- naming/index/FK rõ ràng
- không sửa historical migrations không liên quan
- không dùng `migrate:fresh` để chữa bug
- test schema và enable/migration lifecycle khi relevant

## 18. Seeder

Seeder phải production/Docker-safe.

Không phụ thuộc Faker nếu production dependencies có thể không chứa Faker.

Ưu tiên deterministic, idempotent data với `updateOrCreate()` / `firstOrCreate()` khi đúng nghiệp vụ.

Demo seeder không được ghi đè production data/admin credential.

## 19. Docker / runtime storage

Nếu module tạo runtime file/directory:

- kiểm tra `Dockerfile`
- kiểm tra `docker/entrypoint.sh`
- kiểm tra volume
- kiểm tra `www-data`
- phân biệt `root` CLI và PHP-FPM `www-data`
- không dùng `chmod 777`

## 20. Test strategy

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

## 21. Manual UI smoke

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

## 22. Completion Criteria

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

## 23. Phân biệt ba command

```text
/analyze-new-module <idea.md>
    → Module CHƯA tồn tại
    → phân tích ý tưởng và readiness

/analyze <ModuleName>
    → Module ĐÃ tồn tại
    → phân tích source hiện tại và tạo documentation

/create-module <ModuleName>
    → requirement đã đủ rõ
    → tạo CREATE_PLAN.md trước, implementation sau approval
```

Không thay thế lẫn nhau.

## 24. Source of Truth

Nếu hướng dẫn trong tài liệu này và implementation thực tế có khác biệt, ưu tiên:

1. code hiện tại của `Modules/ModuleServiceProvider.php`
2. `.codex/tasks/analyze-new-module.md` hoặc `.codex/tasks/create-module.md` tương ứng với phase hiện tại
3. standards/bootstrap hiện hành
4. tài liệu hướng dẫn này

Không dựa vào convention cũ nếu repository đã thay đổi.
