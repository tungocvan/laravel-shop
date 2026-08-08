# HƯỚNG DẪN SỬ DỤNG CODEX WORKSPACE CHO LARAVEL 12 MODULE

Tài liệu này hướng dẫn cách sử dụng bộ **Codex Workspace Laravel 12** trong VS Code để làm việc với các module Laravel theo quy trình chuyên nghiệp.

Mục tiêu của bộ workspace này là giúp bạn có thể gọi các AI Agent như một team kỹ thuật gồm:

- `@architect` — phân tích kiến trúc module
- `@laravel-developer` — rebuild/refactor code Laravel
- `@livewire-developer` — xử lý Livewire PHP và Blade
- `@import-export-specialist` — tạo import/export Excel
- `@ui-designer` — thiết kế giao diện Admin UI
- `@database-architect` — phân tích migration/model/database
- `@service-layer-specialist` — tối ưu service layer
- `@security-reviewer` — kiểm tra bảo mật
- `@performance-reviewer` — kiểm tra hiệu năng
- `@documentation-writer` — tạo tài liệu module
- `@reviewer` — review tổng thể module

---

## 1. Cấu trúc Codex Workspace

Sau khi tạo workspace, dự án sẽ có cấu trúc như sau:

```text
.codex/
├── agents/
│   ├── architect.md
│   ├── laravel-developer.md
│   ├── livewire-developer.md
│   ├── import-export-specialist.md
│   ├── ui-designer.md
│   ├── database-architect.md
│   ├── service-layer-specialist.md
│   ├── security-reviewer.md
│   ├── performance-reviewer.md
│   ├── documentation-writer.md
│   └── reviewer.md
├── commands/
│   ├── architect-analyze.md
│   ├── architect-plan.md
│   ├── developer-rebuild.md
│   ├── import-export-create.md
│   ├── reviewer-review.md
│   └── docs-generate.md
└── workflows/
    ├── module-analysis-workflow.md
    ├── module-rebuild-workflow.md
    ├── import-export-workflow.md
    └── review-workflow.md
```

Ngoài ra, dự án nên có thêm các tài liệu gốc:

```text
docs/
├── CODEX_BOOTSTRAP.md
├── AI_PROJECT_CONTEXT.md
├── PROJECT_BOOTSTRAP.md
└── modules/
    └── <ModuleName>/
        ├── ANALYSIS.md
        ├── REFACTOR_PLAN.md
        ├── REBUILD_SPEC.md
        ├── INFORMATION.md
        ├── REVIEW.md
        ├── SECURITY_REVIEW.md
        └── PERFORMANCE_REVIEW.md
```

---

## 2. Nguyên tắc sử dụng chung

Trước khi yêu cầu Codex làm việc với bất kỳ module nào, luôn yêu cầu Codex đọc các file sau:

```text
docs/CODEX_BOOTSTRAP.md
docs/AI_PROJECT_CONTEXT.md
docs/PROJECT_BOOTSTRAP.md
ROADMAP.md
Modules/ModuleServiceProvider.php
composer.json
```

Nếu module đã có tài liệu phân tích, Codex cần đọc thêm:

```text
docs/modules/<ModuleName>/ANALYSIS.md
docs/modules/<ModuleName>/REFACTOR_PLAN.md
docs/modules/<ModuleName>/REBUILD_SPEC.md
docs/modules/<ModuleName>/INFORMATION.md
```

Quy tắc quan trọng:

- Không sửa module khác nếu không được yêu cầu.
- Không sửa `composer.json` nếu không thật sự cần thiết.
- Không tạo ServiceProvider mới nếu kiến trúc autoload module không yêu cầu.
- Không viết code trước khi đã phân tích module.
- Ưu tiên Service Layer, tránh để Controller hoặc Livewire Component quá nặng.
- Mọi thay đổi lớn nên có tài liệu trong `docs/modules/<ModuleName>/`.

---

## 3. Quy trình Analyze Module

Dùng khi bạn muốn Codex phân tích một module trước khi viết code.

### Lệnh mẫu

```text
@architect analyze Category
```

### Prompt đầy đủ nên dùng

```text
@architect analyze Category

Before doing anything, read these files in order:

1. docs/CODEX_BOOTSTRAP.md
2. docs/AI_PROJECT_CONTEXT.md
3. docs/PROJECT_BOOTSTRAP.md
4. ROADMAP.md
5. Modules/ModuleServiceProvider.php
6. composer.json

Analyze this module only:

Modules/Category

Do not change any code.

Analyze by this flow:

Route
→ Controller
→ Page Blade
→ Livewire PHP
→ Livewire Blade
→ Shared UI Components
→ Services
→ Import
→ Export
→ Shared Services
→ Model
→ Migration
→ Database

Generate these files:

- docs/modules/Category/ANALYSIS.md
- docs/modules/Category/REFACTOR_PLAN.md
- docs/modules/Category/REBUILD_SPEC.md

Requirements:

- Write in professional Vietnamese.
- Clearly identify current issues.
- Suggest safe refactor direction.
- Do not modify application code.
- Do not modify unrelated modules.
```

### Kết quả mong muốn

Sau khi chạy xong, bạn sẽ có:

```text
docs/modules/Category/ANALYSIS.md
docs/modules/Category/REFACTOR_PLAN.md
docs/modules/Category/REBUILD_SPEC.md
```

---

## 4. Quy trình Rebuild Module

Dùng khi module đã được phân tích xong và bạn muốn Codex viết lại/refactor module theo tài liệu `REBUILD_SPEC.md`.

### Lệnh mẫu

```text
@laravel-developer rebuild Category
```

### Prompt đầy đủ nên dùng

```text
@laravel-developer rebuild Category

Before doing anything, read these files in order:

1. docs/CODEX_BOOTSTRAP.md
2. docs/AI_PROJECT_CONTEXT.md
3. docs/PROJECT_BOOTSTRAP.md
4. ROADMAP.md
5. Modules/ModuleServiceProvider.php
6. composer.json
7. docs/modules/Category/ANALYSIS.md
8. docs/modules/Category/REFACTOR_PLAN.md
9. docs/modules/Category/REBUILD_SPEC.md
10. docs/modules/Category/INFORMATION.md if exists

Then rebuild this module safely:

Modules/Category

Goal:

Rewrite/refactor the Category module according to REBUILD_SPEC.md.

Important rules:

- Follow the actual module autoload architecture from PROJECT_BOOTSTRAP.md.
- Follow coding standards from AI_PROJECT_CONTEXT.md.
- Follow implementation priorities from ROADMAP.md.
- Follow module-specific analysis, refactor plan, and rebuild spec.
- Do not modify unrelated modules.
- Do not create a new ServiceProvider unless PROJECT_BOOTSTRAP.md requires it.
- Do not change composer.json unless absolutely required.
- Preserve existing database data unless migration changes are explicitly required.
- Keep Controller and Livewire components thin.
- Move business logic into Services.
- Update documentation after code changes.

After coding, generate/update:

- docs/modules/Category/INFORMATION.md
- docs/modules/Category/REVIEW.md

Finally, summarize:

1. Files changed
2. What was improved
3. Possible risks
4. Manual testing steps
```

---

## 5. Quy trình Design UI

Dùng khi bạn muốn Codex thiết kế hoặc tối ưu giao diện Admin UI cho module.

### Lệnh mẫu

```text
@ui-designer design User
```

### Prompt đầy đủ nên dùng

```text
@ui-designer design User

Before doing anything, read:

1. docs/CODEX_BOOTSTRAP.md
2. docs/AI_PROJECT_CONTEXT.md
3. docs/PROJECT_BOOTSTRAP.md
4. ROADMAP.md
5. docs/modules/User/ANALYSIS.md if exists
6. docs/modules/User/REBUILD_SPEC.md if exists

Design or refactor the Admin UI for this module:

Modules/User

UI requirements:

- Professional admin layout.
- Clean table design.
- Search, filter, sort, pagination if needed.
- Checkbox select all and bulk delete if needed.
- Clear create/edit form.
- Use existing shared Blade components first.
- If combobox/searchable select is needed, use:
  components/select-search.blade.php
- Number or currency inputs must be formatted for easier viewing.
- Avoid duplicated Blade logic.
- Keep UI compatible with the current project stack.

Do not modify business logic unless required for UI integration.

After finishing, summarize:

1. Blade files changed
2. Livewire files changed
3. UI improvements
4. Manual test checklist
```

---

## 6. Quy trình Import/Export

Dùng khi muốn tạo hoặc refactor chức năng import/export Excel cho module.

### Lệnh mẫu

```text
@import-export-specialist create Pharma
```

### Prompt đầy đủ nên dùng

```text
@import-export-specialist create Pharma

Before doing anything, read:

1. docs/CODEX_BOOTSTRAP.md
2. docs/AI_PROJECT_CONTEXT.md
3. docs/PROJECT_BOOTSTRAP.md
4. ROADMAP.md
5. Modules/ModuleServiceProvider.php
6. composer.json
7. docs/modules/Pharma/ANALYSIS.md if exists
8. docs/modules/Pharma/REBUILD_SPEC.md if exists

Create or refactor Import/Export for:

Modules/Pharma

Important requirements:

- Use the project's Shared Import/Export Foundation if available.
- Use module-specific folders:

Modules/Pharma/Import/
Modules/Pharma/Export/
Modules/Pharma/Services/ImportExport.php

- Keep Services/ImportExport.php as a thin orchestrator.
- If logic exceeds 200–300 lines, split logic into Import and Export classes.
- Support header mapping and Excel column mapping when needed.
- Support one-sheet and multi-sheet Excel import when required.
- Export should default to model `$fillable`.
- If model has `protected array $exceptExport = [...]`, exclude those fields from export.
- Normalize date, number, currency, empty cells.
- Add debug logs for troubleshooting import errors.
- Avoid undefined index errors.
- Avoid reading properties from array incorrectly.

Before writing code, verify these inputs exist:

1. Excel sample file
2. Migration
3. Model
4. Mapping preference: header mapping or Excel column mapping

If required input is missing, create a clear TODO list instead of guessing.

After finishing, summarize:

1. Import files created/changed
2. Export files created/changed
3. Service files created/changed
4. UI integration steps
5. Manual testing checklist
```

---

## 7. Quy trình Review Module

Dùng khi bạn muốn Codex kiểm tra chất lượng code sau khi đã viết hoặc refactor.

### Lệnh mẫu

```text
@reviewer review User
```

### Prompt đầy đủ nên dùng

```text
@reviewer review User

Before doing anything, read:

1. docs/CODEX_BOOTSTRAP.md
2. docs/AI_PROJECT_CONTEXT.md
3. docs/PROJECT_BOOTSTRAP.md
4. ROADMAP.md
5. Modules/ModuleServiceProvider.php
6. composer.json
7. docs/modules/User/ANALYSIS.md if exists
8. docs/modules/User/REFACTOR_PLAN.md if exists
9. docs/modules/User/REBUILD_SPEC.md if exists
10. docs/modules/User/INFORMATION.md if exists

Review this module only:

Modules/User

Review checklist:

- Architecture consistency
- Route structure
- Controller quality
- Livewire component quality
- Blade UI quality
- Service layer quality
- Model fillable/casts/relationships
- Migration safety
- Validation quality
- Authorization/security
- Import/export quality if exists
- Performance and N+1 risks
- Documentation completeness
- Risks and suggested fixes

Do not modify code unless explicitly requested.

Generate:

- docs/modules/User/REVIEW.md

The review file must include:

1. Summary
2. Good points
3. Issues found
4. Risk level
5. Suggested fixes
6. Priority checklist
7. Manual testing checklist
```

---

## 8. Quy trình Security Review

Dùng khi cần kiểm tra bảo mật module.

### Lệnh mẫu

```text
@security-reviewer review User
```

### Prompt đầy đủ nên dùng

```text
@security-reviewer review User

Review security for this module only:

Modules/User

Check:

- Route middleware
- Authorization policy/gate
- Validation
- Mass assignment
- File upload security
- Import file security
- Export data leakage
- User input escaping
- SQL injection risks
- XSS risks
- Permission/role handling

Do not modify code.

Generate:

- docs/modules/User/SECURITY_REVIEW.md
```

---

## 9. Quy trình Performance Review

Dùng khi cần kiểm tra hiệu năng module.

### Lệnh mẫu

```text
@performance-reviewer review Pharma
```

### Prompt đầy đủ nên dùng

```text
@performance-reviewer review Pharma

Review performance for this module only:

Modules/Pharma

Check:

- N+1 queries
- Missing eager loading
- Missing indexes
- Heavy Livewire rendering
- Pagination quality
- Large import/export memory risks
- Query optimization
- Cache opportunities
- Unnecessary loops
- Large Blade rendering risks

Do not modify code.

Generate:

- docs/modules/Pharma/PERFORMANCE_REVIEW.md
```

---

## 10. Quy trình Documentation

Dùng khi muốn tạo tài liệu hoàn chỉnh cho module.

### Lệnh mẫu

```text
@documentation-writer generate Category
```

### Prompt đầy đủ nên dùng

```text
@documentation-writer generate Category

Read:

1. docs/CODEX_BOOTSTRAP.md
2. docs/AI_PROJECT_CONTEXT.md
3. docs/PROJECT_BOOTSTRAP.md
4. docs/modules/Category/ANALYSIS.md if exists
5. docs/modules/Category/REFACTOR_PLAN.md if exists
6. docs/modules/Category/REBUILD_SPEC.md if exists
7. docs/modules/Category/REVIEW.md if exists

Generate documentation for:

Modules/Category

Output:

- docs/modules/Category/INFORMATION.md
- Modules/Category/README.md

Documentation must include:

1. Module purpose
2. Folder structure
3. Routes
4. Controllers
5. Livewire components
6. Blade views
7. Services
8. Models
9. Migrations
10. Import/export if exists
11. Known risks
12. Developer notes
13. Manual testing checklist
```

---

## 11. Workflow đề xuất khi làm một module mới

Khi bắt đầu với một module mới hoặc module cũ cần refactor, nên chạy theo thứ tự:

```text
@architect analyze Category
@database-architect analyze Category
@architect rebuild-spec Category
@laravel-developer rebuild Category
@ui-designer design Category
@reviewer review Category
@security-reviewer review Category
@performance-reviewer review Category
@documentation-writer generate Category
```

---

## 12. Workflow đề xuất khi chỉ muốn phân tích

Nếu chỉ muốn phân tích, chưa viết code:

```text
@architect analyze Category
```

Kết quả cần có:

```text
docs/modules/Category/ANALYSIS.md
docs/modules/Category/REFACTOR_PLAN.md
docs/modules/Category/REBUILD_SPEC.md
```

---

## 13. Workflow đề xuất khi muốn rebuild/refactor

Nếu đã có tài liệu phân tích:

```text
@laravel-developer rebuild Category
@reviewer review Category
```

Nếu có giao diện phức tạp:

```text
@ui-designer design Category
@livewire-developer refactor Category
@reviewer review Category
```

---

## 14. Workflow đề xuất cho Import/Export

```text
@import-export-specialist create Pharma
@livewire-developer fix-ui Pharma
@reviewer review Pharma
```

Lưu ý trước khi chạy import/export, nên chuẩn bị:

```text
1. File Excel mẫu
2. Migration
3. Model
4. Mapping mong muốn:
   - Header mapping
   - Hoặc Excel column mapping A/B/C
```

---

## 15. Cách lưu và chia sẻ workspace qua Git

Sau khi tạo hoặc cập nhật `.codex`, commit lên Git:

```bash
git add .codex docs/modules
 git commit -m "Add Laravel 12 Codex workspace and agent guide"
git push
```

Khi dùng ở máy khác hoặc tài khoản khác:

```bash
git clone git@github.com:tungocvan/your-project.git
cd your-project
code .
```

Sau đó mở Codex trong VS Code và sử dụng lại các prompt/agent như bình thường.

---

## 16. Gợi ý cách đặt tên module

Tên module nên dùng dạng PascalCase:

```text
Category
User
Pharma
Partner
Admission
Order
Medicine
SupplierTracking
```

Khi dùng trong prompt:

```text
@architect analyze SupplierTracking
@laravel-developer rebuild SupplierTracking
@reviewer review SupplierTracking
```

---

## 17. Checklist trước khi cho Codex rebuild code

Trước khi yêu cầu Codex viết lại code, kiểm tra:

```text
[ ] Đã có ANALYSIS.md
[ ] Đã có REFACTOR_PLAN.md
[ ] Đã có REBUILD_SPEC.md
[ ] Đã đọc PROJECT_BOOTSTRAP.md
[ ] Đã đọc AI_PROJECT_CONTEXT.md
[ ] Đã đọc CODEX_BOOTSTRAP.md
[ ] Đã đọc ROADMAP.md
[ ] Đã xác định module cần sửa
[ ] Đã xác định không sửa module khác
[ ] Đã backup hoặc commit code hiện tại
```

---

## 18. Prompt ngắn dùng nhanh

### Analyze nhanh

```text
@architect analyze Category
```

### Rebuild nhanh

```text
@laravel-developer rebuild Category
```

### Design UI nhanh

```text
@ui-designer design Category
```

### Import/Export nhanh

```text
@import-export-specialist create Pharma
```

### Review nhanh

```text
@reviewer review User
```

---

## 19. Prompt tổng hợp mạnh nhất cho một module

Dùng khi muốn Codex làm trọn quy trình từ phân tích đến review:

```text
Run full Laravel 12 module workflow for Category.

Use these agents in order:

1. @architect analyze Category
2. @database-architect analyze Category
3. @architect rebuild-spec Category
4. @laravel-developer rebuild Category
5. @ui-designer design Category
6. @livewire-developer refactor Category
7. @reviewer review Category
8. @security-reviewer review Category
9. @performance-reviewer review Category
10. @documentation-writer generate Category

Rules:

- Read all bootstrap files first.
- Do not modify unrelated modules.
- Follow module autoload architecture.
- Keep Service Layer clean.
- Keep Livewire components thin.
- Update documentation after changes.
- Summarize all changed files at the end.
```

---

## 20. Kết luận

Bộ Codex Workspace này giúp chuẩn hóa cách làm việc với Laravel 12 Module theo quy trình:

```text
Analyze → Plan → Rebuild → Design → Review → Document
```

Cách sử dụng khuyến nghị:

```text
@architect analyze <ModuleName>
@laravel-developer rebuild <ModuleName>
@ui-designer design <ModuleName>
@reviewer review <ModuleName>
```

Ví dụ:

```text
@architect analyze Category
@laravel-developer rebuild Category
@ui-designer design Category
@reviewer review Category
```

Khi dùng đúng quy trình này, Codex sẽ làm việc ổn định hơn, ít sửa sai module khác, dễ kiểm soát thay đổi và dễ dùng lại cho nhiều tài khoản hoặc nhiều máy khác nhau.
