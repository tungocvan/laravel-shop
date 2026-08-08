# 🚀 Livewire Module Guide – NTD Application System

## 🎯 Mục tiêu
Tài liệu này giúp bạn tiếp tục build 8 component Livewire còn lại theo chuẩn:

- ⚡ Performance-first
- ⚡ UX admin nhanh
- 🎨 Design System (Tailwind 4)
- 🧱 Kiến trúc Module (Laravel Modules)

---

## 🧱 1. Kiến trúc bạn đang dùng

### Namespace Model
```
Modules\Ntd\Models
```

👉 LUÔN import đúng:
```php
use Modules\Ntd\Models\Application;
use Modules\Ntd\Models\Student;
```

---

## 🧩 2. Danh sách 9 Livewire Components

### ✅ Đã hoàn thành
1. ApplicationIndex

### 🔥 Còn lại (8 components)

```
ApplicationForm (create)
ApplicationForm (edit)
ApplicationShow
StepPersonalInfo
StepFamilyInfo
StepAddress
StepHealth
StepReview
StepSubmit
```

---

## 🎯 3. Nguyên tắc viết Livewire Component

---

## 🧠 3.1 STATE

```php
// ======================
// STATE
// ======================
public $form = [];
public $step = 1;
```

---

## ⚡ 3.2 LIFECYCLE

```php
// ======================
// LIFECYCLE
// ======================
public function mount()
{
    // load data khi edit
}
```

---

## 🔁 3.3 ACTIONS

```php
// ======================
// ACTIONS
// ======================
public function save()
{
    // validate + save
}
```

---

## 🎨 4. Quy tắc Blade (RẤT QUAN TRỌNG)

---

## ❗ 4.1 Search / Filter

👉 LUÔN dùng:

```blade
wire:model.live="search"
wire:model.live="statusFilter"
```

👉 KHÔNG dùng:

```blade
wire:model.defer ❌
```

---

## 🧩 4.2 Binding JSON

```blade
wire:model.live="form.full_name"
wire:model.live="form.identity_number"
```

---

## 🎯 5. Structure mỗi component

---

## 📄 File PHP

```php
<?php

namespace Modules\Ntd\Livewire\Application;

use Livewire\Component;

class ExampleComponent extends Component
{
    // ======================
    // STATE
    // ======================

    // ======================
    // LIFECYCLE
    // ======================

    // ======================
    // ACTIONS
    // ======================

    // ======================
    // RENDER
    // ======================
    public function render()
    {
        return view('Ntd::livewire.application.example');
    }
}
```

---

## 🎨 File Blade

```blade
<div class="space-y-6">

    {{-- HEADER --}}

    {{-- FORM / CONTENT --}}

</div>
```

---

## ⚠️ 6. Quy tắc COMMENT (QUAN TRỌNG)

👉 LUÔN chia block rõ:

```php
// ======================
// STATE
// ======================

// ======================
// QUERY
// ======================

// ======================
// ACTIONS
// ======================
```

---

## ⚡ 7. Performance Rules

- Không nested component quá nhiều
- Không query trong loop
- Dùng eager loading
- Pagination server-side

---

## 🧠 8. UX Rules

- 1 click = 1 action
- Không dropdown dư thừa
- Không animation nặng

---

## 🔥 9. Những lỗi cần tránh

- ❌ with() để sort
- ❌ groupBy sai
- ❌ thiếu null check
- ❌ dùng defer sai chỗ

---

## 🚀 10. Workflow tiếp theo

1. Build ApplicationForm (multi-step)
2. Bind JSON form
3. Save DB
4. Edit + Update
5. Show detail
6. Export Word

---

## 🎯 11. Checklist trước khi build tiếp

- [x] Index stable
- [x] Search OK
- [x] Filter OK
- [x] Sort OK
- [x] Pagination OK

---

## 🔥 Kết luận

Bạn đang build theo hướng:

> Admin Platform chuẩn production

👉 Giữ nguyên các nguyên tắc này khi build 8 component còn lại.

---

**END**

🚀 Gợi ý bước tiếp theo (rất quan trọng)

Bạn nên đi theo thứ tự này:

ApplicationForm (core)
Multi-step (Step components)
Bind JSON
Save + Update
Show + Export

👉 Khi bạn qua chat mới, chỉ cần nói:

“build ApplicationForm step 1 cho tôi”

Mình sẽ tiếp tục theo đúng chuẩn bạn đã định 🔥