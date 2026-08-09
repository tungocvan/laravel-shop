# HƯỚNG DẪN SỬ DỤNG `.codex/tasks`

Tài liệu này hướng dẫn cách sử dụng bộ task Codex của project Laravel theo quy trình an toàn:

```text
PHÂN TÍCH / LẬP KẾ HOẠCH
        ↓
NGƯỜI DÙNG DUYỆT
        ↓
IMPLEMENT
        ↓
TEST / VERIFY
        ↓
CẬP NHẬT DOCUMENTATION
```

> Nguyên tắc quan trọng: các task có khả năng thay đổi source code phải tách **Plan/Spec** khỏi **Implementation**. AI không được tự triển khai trước khi người dùng duyệt kế hoạch/specification.

---

## 1. Cấu trúc liên quan

```text
.codex/
├── bootstrap/
│   ├── CODEX_BOOTSTRAP.md
│   ├── PROJECT_BOOTSTRAP.md
│   └── AI_PROJECT_CONTEXT.md
├── standards/
│   ├── MODULE_STANDARD.md
│   └── ADMIN_UI_STANDARD.md
├── tasks/
│   ├── analyze-md.md
│   ├── analyze-module.md
│   ├── analyze-livewire.md
│   ├── create-module.md
│   ├── create-module-from-docs.md
│   ├── create-import-export.md
│   ├── refactor-module.md
│   ├── refactor-livewire.md
│   └── rebuild-module.md
└── TASKS_GUIDE_VI.md
```

### `bootstrap/`
Mô tả project thực tế: Laravel/PHP, module discovery, convention, shared infrastructure, context cho AI.

### `standards/`
- `MODULE_STANDARD.md`: kiến trúc module, Service, Model, DB, transaction, security, compatibility...
- `ADMIN_UI_STANDARD.md`: chuẩn Blade/Livewire/Admin UI, form, table, loading, validation, responsive...

### `tasks/`
Mỗi file định nghĩa một workflow cụ thể cho Codex/AI.

---

# 2. Thứ tự ưu tiên

Khi tài liệu có khác biệt hoặc mâu thuẫn, AI phải ưu tiên:

```text
1. Source code / repository thực tế
2. .codex/bootstrap/* + ROADMAP.md
3. .codex/standards/*
4. docs/modules/<ModuleName>/*
5. Plan/Spec của task đang thực hiện
6. Tài liệu/hướng dẫn cũ
```

Không được ép project dùng kiến trúc từ một prompt cũ nếu repository hiện tại đã có canonical architecture khác.

---

# 3. `/analyze <ModuleName>`

Dùng khi module **đã tồn tại** và cần hiểu chính xác module trước khi sửa/refactor/rebuild.

Ví dụ:

```text
/analyze Administrative
```

AI phân tích theo flow:

```text
Route
→ Controller
→ Page Blade
→ Livewire
→ Shared UI
→ Service
→ Import/Export
→ Model
→ Migration/Database
```

Output:

```text
docs/modules/Administrative/
├── ANALYSIS.md
├── INFORMATION.md
└── README.md
```

`/analyze` không sửa source code, không tự tạo `REFACTOR_PLAN.md` hoặc `REBUILD_SPEC.md`.

---

# 4. `/analyze-livewire <ModuleName> <Component>`

Dùng khi chỉ muốn phân tích **một Livewire component** mà không cần mở scope cả module.

Ví dụ:

```text
/analyze-livewire Administrative Submissions/SubmissionDetail
```

Task kiểm tra:

```text
Route / Page Blade
      ↓
Livewire PHP
      ↔
Livewire Blade
      ↓
Service(s)
      ↓
Model / Database

+ Shared UI
+ Permission
+ Event / Job
+ Upload / Download
+ Related tests
```

Phân tích bao gồm:

- state/public/locked properties
- lifecycle: mount, boot, hydrate...
- validation
- action/mutation
- authorization từng action
- event/dispatch/listener
- search/filter/sort/pagination
- upload/download
- service call
- query trực tiếp Model/DB
- transaction/concurrency
- N+1/repeated render query
- `wire:model`, `wire:key`
- loading/disabled/error/empty state
- shared UI reuse
- test coverage

Output:

```text
docs/modules/<ModuleName>/livewire/<component-key>/ANALYSIS.md
```

Ví dụ:

```text
Submissions/SubmissionDetail
→ docs/modules/Administrative/livewire/submissions-submission-detail/ANALYSIS.md
```

Task này chỉ phân tích, không sửa source.

---

# 5. Bổ sung chức năng cho một Livewire component

Nếu muốn thêm nghiệp vụ mới cho component, quy trình khuyến nghị:

```text
/analyze-livewire <Module> <Component>
        ↓
ANALYSIS.md
        ↓
Yêu cầu AI lập CHANGE_PLAN.md
        ↓
STOP
        ↓
USER APPROVAL
        ↓
IMPLEMENT
        ↓
TEST
```

Ví dụ:

```text
/analyze-livewire Administrative Submissions/SubmissionDetail
```

Sau đó:

```text
Hãy lập kế hoạch bổ sung chức năng hoàn tác yêu cầu bổ sung hồ sơ.
Chưa implement.
```

AI phải tạo:

```text
docs/modules/Administrative/livewire/submissions-submission-detail/CHANGE_PLAN.md
```

`CHANGE_PLAN.md` phải xác định mọi dependency bị ảnh hưởng, ví dụ:

```text
Livewire PHP
Livewire Blade
SubmissionService
Permission
Enum/status
Model/DB
Audit history
Event/job
Tests
```

Sau khi duyệt:

```text
Tôi duyệt CHANGE_PLAN.md của SubmissionDetail.
Tiếp tục implement đúng scope.
```

Không được giấu business behavior mới bên trong một refactor.

---

# 6. `/refactor-livewire <ModuleName> <Component>`

Dùng khi muốn refactor riêng một Livewire component.

Ví dụ:

```text
/refactor-livewire Administrative Submissions/SubmissionDetail
```

Nếu chưa có component analysis, AI phải chạy tư duy `/analyze-livewire` trước.

Output planning:

```text
docs/modules/Administrative/livewire/submissions-submission-detail/REFACTOR_PLAN.md
```

Sau đó AI phải STOP và chờ duyệt.

Ví dụ duyệt:

```text
Tôi duyệt REFACTOR_PLAN.md của SubmissionDetail.
Tiếp tục implement đúng plan.
```

Refactor phù hợp khi:

- Livewire quá lớn
- query Model trực tiếp
- business logic nằm trong component
- transaction nằm trong component
- authorization thiếu
- render query lặp lại
- N+1
- UI/state khó bảo trì
- component chưa reuse shared UI
- test coverage yếu

Mục tiêu kiến trúc thường là:

```text
Livewire
├── state
├── validation
├── authorization
└── gọi Service
        ↓
Service
├── business rule
├── transaction
├── persistence
└── workflow
```

---

# 7. `/analyze-md <MarkdownFile>`

Dùng để phân tích một tài liệu Markdown, specification, business analysis, architecture note hoặc prompt trước khi triển khai.

Ví dụ:

```text
/analyze-md docs/specs/administrative-workflow.md
```

Output:

```text
docs/analysis/administrative-workflow_ANALYSIS.md
```

Task này không thay thế `/analyze <ModuleName>`.

---

# 8. `/create-module <ModuleName>`

Dùng khi cần tạo **module hoàn toàn mới**.

Ví dụ:

```text
/create-module StudentHealth
```

Workflow:

```text
Requirement
    ↓
CREATE_PLAN.md
    ↓
STOP
    ↓
Người dùng kiểm tra
    ↓
Người dùng APPROVE
    ↓
IMPLEMENT
    ↓
TEST
```

Plan:

```text
docs/modules/StudentHealth/CREATE_PLAN.md
```

---

# 9. `/create-module-from-docs <SourceModule> <TargetModule>`

Dùng khi muốn tạo module mới dựa trên nghiệp vụ/ý tưởng của module đã tồn tại nhưng module mới phải độc lập.

Ví dụ:

```text
/create-module-from-docs Website WebsiteV2
```

Task không clone source và không implement ngay.

Nó tạo:

```text
docs/modules/WebsiteV2/CREATE_PLAN.md
```

Sau khi duyệt, implementation phải theo canonical `/create-module` workflow.

---

# 10. `/refactor <ModuleName>`

Dùng khi module đã hoạt động và muốn cải tiến nhưng giữ phần lớn behavior/public contract hiện tại.

Khuyến nghị:

```text
/analyze Administrative
        ↓
/refactor Administrative
```

Output:

```text
docs/modules/Administrative/REFACTOR_PLAN.md
```

Sau đó STOP để người dùng duyệt.

---

# 11. `/rebuild <ModuleName>`

Dùng khi module có vấn đề kiến trúc lớn hoặc cần thiết kế lại đáng kể.

Workflow:

```text
/analyze Module
      ↓
ANALYSIS.md
INFORMATION.md
README.md
      ↓
/rebuild Module
      ↓
REBUILD_SPEC.md
      ↓
STOP
      ↓
USER APPROVAL
      ↓
IMPLEMENT
```

Không nên dùng rebuild chỉ để sửa vài lỗi nhỏ.

---

# 12. `/import-export <ModuleName>`

Dùng khi cần tạo mới hoặc cải tiến Import/Export cho module.

Ví dụ:

```text
/import-export Product
```

Task phải tái sử dụng canonical infrastructure:

```text
Modules/Shared/Services/ImportExport
```

nếu phù hợp.

Planning output:

```text
docs/modules/Product/IMPORT_EXPORT_PLAN.md
```

Sau đó STOP để người dùng duyệt.

---

# 13. Chọn task nào?

```text
Tôi có MODULE ĐÃ TỒN TẠI
        │
        ├── Muốn hiểu cả module
        │       → /analyze
        │
        ├── Muốn cải tiến cả module
        │       → /analyze → /refactor
        │
        ├── Muốn thiết kế lại cả module
        │       → /analyze → /rebuild
        │
        ├── Muốn phân tích 1 Livewire
        │       → /analyze-livewire
        │
        ├── Muốn refactor 1 Livewire
        │       → /analyze-livewire → /refactor-livewire
        │
        ├── Muốn thêm chức năng vào 1 Livewire
        │       → /analyze-livewire
        │       → CHANGE_PLAN.md
        │       → approve
        │       → implement
        │
        └── Muốn thêm Import/Export
                → /analyze (khuyến nghị)
                → /import-export

Tôi CHƯA CÓ MODULE
        │
        ├── Có requirement mới
        │       → /create-module
        │
        └── Muốn dựa trên module cũ
                → /create-module-from-docs

Tôi có FILE MARKDOWN cần kiểm tra
        │
        └── /analyze-md
```

---

# 14. Quy trình khuyến nghị cho Production

## Module-level

```text
/analyze
   ↓
đọc ANALYSIS.md
   ↓
quyết định
   │
   ├── không cần sửa
   ├── /refactor
   └── /rebuild

PLAN / SPEC
   ↓
USER APPROVAL
   ↓
IMPLEMENT
   ↓
TEST
```

## Livewire-level

```text
/analyze-livewire
        ↓
component ANALYSIS.md
        ↓
        ├── Feature mới
        │      ↓
        │   CHANGE_PLAN.md
        │
        └── Refactor
               ↓
          /refactor-livewire
               ↓
          REFACTOR_PLAN.md

PLAN
 ↓
USER APPROVAL
 ↓
IMPLEMENT
 ↓
TEST
```

## Module mới

```text
Requirement
   ↓
/create-module
   ↓
CREATE_PLAN.md
   ↓
USER APPROVAL
   ↓
IMPLEMENT
   ↓
TEST
   ↓
/analyze Module
```

---

# 15. Cách duyệt Plan/Spec

Không cần câu lệnh đặc biệt. Chỉ cần nói rõ file nào đã được duyệt.

Ví dụ:

```text
Tôi duyệt REFACTOR_PLAN.md của Administrative.
Tiếp tục implement đúng plan, không mở rộng scope.
```

Hoặc:

```text
CHANGE_PLAN.md chưa ổn.
Hãy sửa phần permission và audit log, chưa được implement.
```

Trường hợp thứ hai nghĩa là **chưa được phép coding**.

---

# 16. Nguyên tắc chống AI tự mở rộng scope

Khi implement:

- Chỉ làm nội dung đã được duyệt.
- Không tự đổi architecture ngoài plan/spec.
- Không tự đổi database nếu plan không cho phép.
- Không tự đổi route/permission/public contract.
- Không refactor module/component khác vì "tiện thể".
- Không thêm package/framework mới nếu chưa được duyệt.
- Không rewrite migration đã áp dụng chỉ để làm code đẹp hơn.
- Nếu phát hiện vấn đề mới ảnh hưởng scope, phải cập nhật plan/spec và xin duyệt lại.

---

# 17. Documentation lifecycle

```text
ANALYSIS.md
    = module/component đang có vấn đề gì?

INFORMATION.md
    = module hiện đang có gì?

README.md
    = developer cần biết gì để sử dụng/bảo trì?

CREATE_PLAN.md
    = module mới sẽ được tạo như thế nào?

REFACTOR_PLAN.md
    = module/component sẽ được cải tiến như thế nào?

CHANGE_PLAN.md
    = chức năng mới trên component sẽ thay đổi gì?

REBUILD_SPEC.md
    = kiến trúc module mới phải trở thành gì?

IMPORT_EXPORT_PLAN.md
    = import/export sẽ hoạt động như thế nào?
```

Không dùng một file thay cho tất cả mục đích.

---

# 18. Ví dụ với `Administrative / SubmissionDetail`

Phân tích riêng component:

```text
/analyze-livewire Administrative Submissions/SubmissionDetail
```

Output:

```text
docs/modules/Administrative/livewire/submissions-submission-detail/ANALYSIS.md
```

Nếu muốn refactor:

```text
/refactor-livewire Administrative Submissions/SubmissionDetail
```

Output:

```text
REFACTOR_PLAN.md
```

Nếu muốn thêm chức năng:

```text
Hãy lập CHANGE_PLAN.md để thêm chức năng hoàn tác yêu cầu bổ sung.
Chưa implement.
```

Sau khi kiểm tra:

```text
Tôi duyệt CHANGE_PLAN.md của SubmissionDetail.
Tiếp tục implement đúng scope và chạy targeted tests.
```

---

# 19. Checklist trước khi cho AI implement

```text
[ ] Scope đúng chưa?
[ ] Business rule đúng chưa?
[ ] Database có thay đổi không?
[ ] Route/permission có thay đổi không?
[ ] Có phá backward compatibility không?
[ ] Có cần migration/rollback không?
[ ] Có ảnh hưởng component/module khác không?
[ ] Test plan đủ chưa?
[ ] Import/export/storage có rủi ro không?
[ ] Có phần nào AI đang Assumption/Unknown không?
```

Nếu còn điểm quan trọng chưa rõ, yêu cầu AI sửa plan/spec trước khi approve.

---

# 20. Tóm tắt nhanh

| Nhu cầu | Task |
|---|---|
| Phân tích cả module | `/analyze <Module>` |
| Phân tích 1 Livewire component | `/analyze-livewire <Module> <Component>` |
| Refactor 1 Livewire component | `/refactor-livewire <Module> <Component>` |
| Thêm chức năng vào 1 Livewire | `/analyze-livewire` → `CHANGE_PLAN.md` → approve |
| Phân tích Markdown/spec | `/analyze-md <file>` |
| Tạo module mới | `/create-module <Module>` |
| Tạo module mới dựa trên module cũ | `/create-module-from-docs <Source> <Target>` |
| Refactor cả module | `/refactor <Module>` |
| Rebuild cả module | `/rebuild <Module>` |
| Tạo/cải tiến Import Export | `/import-export <Module>` |

Quy tắc cần nhớ nhất:

```text
ANALYZE / PLAN / SPEC
        ↓
USER APPROVAL
        ↓
IMPLEMENT
```
