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
│   ├── create-module.md
│   ├── create-module-from-docs.md
│   ├── create-import-export.md
│   ├── refactor-module.md
│   └── rebuild-module.md
└── TASKS_GUIDE_VI.md
```

### `bootstrap/`

Mô tả project thực tế: Laravel/PHP, module discovery, convention, shared infrastructure, context cho AI.

### `standards/`

Chuẩn kỹ thuật mà AI phải tuân thủ:

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

File:

```text
.codex/tasks/analyze-module.md
```

## Khi nào dùng?

Dùng khi module **đã tồn tại** và cần hiểu chính xác module trước khi sửa/refactor/rebuild.

Ví dụ:

```text
/analyze Administrative
```

## Task sẽ làm gì?

AI đọc:

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

Sau đó tạo/cập nhật:

```text
docs/modules/Administrative/
├── ANALYSIS.md
├── INFORMATION.md
└── README.md
```

## Ý nghĩa

- `ANALYSIS.md`: vấn đề kỹ thuật, security, performance, P0/P1/P2, khuyến nghị.
- `INFORMATION.md`: thông tin thực tế của module.
- `README.md`: hướng dẫn/tổng quan ngắn cho developer.

## Không làm gì?

`/analyze` **không sửa application code**, không tự tạo `REFACTOR_PLAN.md` và không tự tạo `REBUILD_SPEC.md`.

## Sau `/analyze`

Nếu module chỉ cần cải tiến:

```text
/refactor <ModuleName>
```

Nếu cần thiết kế lại lớn:

```text
/rebuild <ModuleName>
```

---

# 4. `/analyze-md <MarkdownFile>`

File:

```text
.codex/tasks/analyze-md.md
```

## Khi nào dùng?

Dùng để phân tích một tài liệu Markdown, specification, business analysis, architecture note hoặc prompt trước khi triển khai.

Ví dụ:

```text
/analyze-md docs/specs/administrative-workflow.md
```

## Output

```text
docs/analysis/administrative-workflow_ANALYSIS.md
```

Task tìm:

- yêu cầu thiếu
- mâu thuẫn
- assumption
- security/data integrity risk
- performance risk
- architecture problem
- acceptance criteria còn thiếu

Task này không thay thế `/analyze <ModuleName>`.

---

# 5. `/create-module <ModuleName>`

File:

```text
.codex/tasks/create-module.md
```

## Khi nào dùng?

Dùng khi cần tạo **module hoàn toàn mới**.

Ví dụ:

```text
/create-module StudentHealth
```

## Quy trình

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

Plan nằm tại:

```text
docs/modules/StudentHealth/CREATE_PLAN.md
```

## Cách làm khuyến nghị

Lần 1:

```text
/create-module StudentHealth
Hãy lập kế hoạch, chưa implement.
```

Sau khi đọc `CREATE_PLAN.md`, nếu đồng ý:

```text
Tôi duyệt CREATE_PLAN.md của StudentHealth. Tiếp tục implement theo plan.
```

AI chỉ được implement sau bước duyệt này.

---

# 6. `/create-module-from-docs <SourceModule> <TargetModule>`

File:

```text
.codex/tasks/create-module-from-docs.md
```

## Khi nào dùng?

Dùng khi muốn tạo một module mới dựa trên **nghiệp vụ/ý tưởng của module đã tồn tại**, nhưng module mới phải độc lập.

Ví dụ:

```text
/create-module-from-docs Website WebsiteV2
```

## Quan trọng

Task này **không clone source** và **không implement ngay**.

Nó đọc:

```text
Source docs
+
Source code để xác minh
+
Canonical standards
```

rồi tạo:

```text
docs/modules/WebsiteV2/CREATE_PLAN.md
```

Sau đó STOP để người dùng duyệt.

Sau khi duyệt, implementation phải theo canonical `/create-module` workflow.

## Khi nào nên dùng?

Ví dụ:

```text
Website cũ
    ↓
muốn WebsiteV2
    ↓
không muốn clone technical debt
    ↓
/create-module-from-docs
```

---

# 7. `/refactor <ModuleName>`

File:

```text
.codex/tasks/refactor-module.md
```

## Khi nào dùng?

Dùng khi module đã hoạt động và muốn cải tiến nhưng **giữ phần lớn behavior/public contract hiện tại**.

Nên chạy `/analyze` trước.

Ví dụ:

```text
/analyze Administrative
```

Sau khi xem analysis:

```text
/refactor Administrative
```

Task tạo:

```text
docs/modules/Administrative/REFACTOR_PLAN.md
```

rồi STOP.

Sau khi duyệt:

```text
Tôi duyệt REFACTOR_PLAN.md của Administrative. Tiếp tục implement.
```

## Refactor phù hợp khi

- business logic đặt sai layer
- query chậm
- thiếu transaction
- thiếu authorization
- duplicate code
- Livewire quá lớn
- Service cần chuẩn hóa
- test coverage thiếu
- technical debt nhưng không cần thiết kế lại toàn module

---

# 8. `/rebuild <ModuleName>`

File:

```text
.codex/tasks/rebuild-module.md
```

## Khi nào dùng?

Dùng khi module có vấn đề kiến trúc lớn hoặc cần thiết kế lại đáng kể.

Không nên dùng rebuild chỉ để sửa vài lỗi nhỏ.

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

Ví dụ:

```text
/rebuild LegacyInventory
```

Sau khi kiểm tra specification:

```text
Tôi duyệt REBUILD_SPEC.md của LegacyInventory. Tiếp tục rebuild theo spec.
```

## Rebuild cần đặc biệt kiểm tra

- backward compatibility
- route names
- permissions
- database/schema
- migration strategy
- storage paths
- import/export
- queue/events
- rollback
- test coverage

---

# 9. `/import-export <ModuleName>`

File:

```text
.codex/tasks/create-import-export.md
```

## Khi nào dùng?

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

## Planning

Task tạo:

```text
docs/modules/Product/IMPORT_EXPORT_PLAN.md
```

Plan phải mô tả:

```text
Import
- format
- headers
- mapping
- validation
- duplicate handling
- transaction
- errors
- chunk/batch
- queue/progress
- cleanup

Export
- filters
- columns
- query
- large dataset
- storage
- download lifecycle
```

Sau đó STOP.

Khi đồng ý:

```text
Tôi duyệt IMPORT_EXPORT_PLAN.md của Product. Tiếp tục implement.
```

---

# 10. Chọn task nào?

```text
Tôi có MODULE ĐÃ TỒN TẠI
        │
        ├── Muốn hiểu module
        │       → /analyze
        │
        ├── Muốn cải tiến
        │       → /analyze → /refactor
        │
        ├── Muốn thiết kế lại
        │       → /analyze → /rebuild
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

# 11. Quy trình khuyến nghị cho Production

Đối với module đã tồn tại:

```text
/analyze
   ↓
đọc ANALYSIS.md
   ↓
quyết định
   │
   ├── không cần sửa
   │
   ├── /refactor
   │      ↓
   │   REFACTOR_PLAN.md
   │
   └── /rebuild
          ↓
       REBUILD_SPEC.md

PLAN / SPEC
   ↓
USER APPROVAL
   ↓
IMPLEMENT
   ↓
TEST
   ↓
/analyze lại nếu thay đổi lớn
```

Đối với module mới:

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

# 12. Cách duyệt Plan/Spec

Không cần câu lệnh đặc biệt. Người dùng chỉ cần nói rõ đã duyệt file nào.

Ví dụ:

```text
Tôi duyệt REFACTOR_PLAN.md của Administrative.
Tiếp tục implement đúng plan, không mở rộng scope.
```

Hoặc:

```text
CREATE_PLAN.md chưa ổn.
Hãy sửa phần database, chưa được implement.
```

AI phải hiểu trường hợp thứ hai là **chưa được phép coding**.

---

# 13. Nguyên tắc chống AI tự mở rộng scope

Khi implement:

- Chỉ làm nội dung đã được duyệt.
- Không tự đổi architecture ngoài plan/spec.
- Không tự đổi database nếu plan không cho phép.
- Không tự đổi route/permission/public contract.
- Không refactor module khác vì "tiện thể".
- Không thêm package/framework mới nếu chưa được duyệt.
- Không rewrite migration đã áp dụng chỉ để làm code đẹp hơn.
- Nếu phát hiện vấn đề mới ảnh hưởng scope, phải báo lại trước.

---

# 14. Documentation lifecycle

Các file có vai trò khác nhau:

```text
ANALYSIS.md
    = module đang có vấn đề gì?

INFORMATION.md
    = module hiện đang có gì?

README.md
    = developer cần biết gì để sử dụng/bảo trì?

CREATE_PLAN.md
    = module mới sẽ được tạo như thế nào?

REFACTOR_PLAN.md
    = module hiện tại sẽ được cải tiến như thế nào?

REBUILD_SPEC.md
    = kiến trúc mới phải trở thành gì?

IMPORT_EXPORT_PLAN.md
    = import/export sẽ hoạt động như thế nào?
```

Không dùng một file thay cho tất cả mục đích.

---

# 15. Ví dụ với Administrative

Phân tích:

```text
/analyze Administrative
```

Kết quả:

```text
docs/modules/Administrative/
├── ANALYSIS.md
├── INFORMATION.md
└── README.md
```

Nếu muốn refactor:

```text
/refactor Administrative
```

Kết quả planning:

```text
REFACTOR_PLAN.md
```

Sau khi kiểm tra:

```text
Tôi duyệt REFACTOR_PLAN.md của Administrative.
Tiếp tục implement và chạy targeted tests.
```

Nếu thay vào đó muốn rebuild:

```text
/rebuild Administrative
```

AI tạo `REBUILD_SPEC.md` trước và chờ duyệt.

---

# 16. Checklist trước khi cho AI implement

Người dùng nên kiểm tra:

```text
[ ] Scope đúng chưa?
[ ] Business rule đúng chưa?
[ ] Database có thay đổi không?
[ ] Route/permission có thay đổi không?
[ ] Có phá backward compatibility không?
[ ] Có cần migration/rollback không?
[ ] Có ảnh hưởng module khác không?
[ ] Test plan đủ chưa?
[ ] Import/export/storage có rủi ro không?
[ ] Có phần nào AI đang Assumption/Unknown không?
```

Nếu còn điểm quan trọng chưa rõ, hãy yêu cầu AI sửa plan/spec trước khi approve.

---

# 17. Tóm tắt nhanh

| Nhu cầu | Task |
|---|---|
| Phân tích module | `/analyze <Module>` |
| Phân tích Markdown/spec | `/analyze-md <file>` |
| Tạo module mới | `/create-module <Module>` |
| Tạo module mới dựa trên module cũ | `/create-module-from-docs <Source> <Target>` |
| Refactor module | `/refactor <Module>` |
| Rebuild module | `/rebuild <Module>` |
| Tạo/cải tiến Import Export | `/import-export <Module>` |

Quy tắc cần nhớ nhất:

```text
ANALYZE / PLAN / SPEC
        ↓
USER APPROVAL
        ↓
IMPLEMENT
```
