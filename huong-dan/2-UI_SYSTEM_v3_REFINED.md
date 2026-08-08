<!-- ============================= -->
<!-- 🚀 UI SYSTEM v3 – REFINED & ENFORCED -->
<!-- ENTERPRISE DESIGN SYSTEM -->
<!-- START -->
<!-- ============================= -->

# 🚀 UI SYSTEM v3 — ENTERPRISE DESIGN SYSTEM (REFINED & ENFORCED)

> 🔥 Production-ready UI System  
> TailwindCSS 4 • Livewire 3 • Laravel Modules  
> Config-driven • Component-based • Scalable • SaaS-ready

---

# 🎯 0. VISION (MỤC TIÊU TỐI CAO)

UI SYSTEM v3 không chỉ là Blade UI — mà là:

> **A Full Design System + Component Architecture + UX Engine**

### 🎯 Mục tiêu hệ thống

* Consistent UI toàn hệ thống  
* Tái sử dụng 100%  
* Scale team dễ dàng  
* UX chuẩn SaaS (NOT CRUD UI thông thường)

---

# ⚠️ 1. CORE PRINCIPLE (LUẬT BẮT BUỘC)

```text
❌ KHÔNG được viết code ngay
✅ PHẢI define cấu trúc trước

UI = Component (Blade - Stateless)
State = Livewire
Logic = Service Layer
Style = Config-driven (NO hardcode)
UX = First-class citizen
```

---

# 🧠 2. CORE ARCHITECTURE (BẮT BUỘC)

```text
Design Tokens → Config → Components → Pages
```

---

# 🧭 3. AI WORKFLOW (ENFORCED FLOW)

## 🚫 TUYỆT ĐỐI KHÔNG VIẾT CODE NGAY

---

## STEP 1 — XÁC ĐỊNH COMPONENT

AI phải xác định:

```text
- Tên component
- Thuộc module nào
- Loại component:
    + UI component (pure blade)
    + Livewire component
    + Layout component
```

---

## STEP 2 — KHAI BÁO FILE STRUCTURE (MANDATORY)

```text
Component: UserTable

Files:
- resources/views/livewire/user-table.blade.php
- app/Livewire/UserTable.php

UI Components used:
- x-ui.table
- x-ui.button
- x-ui.modal
- x-ui.input

Included Blade:
- livewire.user.partials.filters
- livewire.user.partials.actions
```

---

## STEP 3 — UI DEPENDENCY DECLARATION

```text
UI Dependencies:

- x-ui.table
- x-ui.button (primary, danger)
- x-ui.dropdown
- x-ui.modal
```

---

## STEP 4 — REVIEW GATE (CRITICAL)

```text
✔ Có thiếu component không?
✔ Có duplicate không?
✔ Có reuse được không?
✔ Có vi phạm UI SYSTEM không?
✔ Có hardcode UI không?
✔ Có tách partial hợp lý chưa?
✔ Có đúng naming convention không?
```

⛔ CHỈ sau khi pass bước này → mới được code

---

# 🧱 4. FILE TREE CHUẨN (ENFORCE)

```text
livewire/
└── user/
    ├── user-table.blade.php
    ├── user-form.blade.php
    └── partials/
        ├── filters.blade.php
        ├── actions.blade.php
        ├── columns.blade.php
```

---

# 🎨 5. DESIGN TOKENS

```php
return [
    'color' => [
        'primary' => 'indigo',
        'success' => 'green',
        'danger'  => 'red',
        'warning' => 'yellow',
    ],
];
```

---

# 🧩 6. COMPONENT SYSTEM

```text
Component = Stateless UI
State = Livewire
Logic = Service
```

---

# 📊 7. DATATABLE SYSTEM

Features:
- Sorting
- Filtering
- Bulk actions
- Pagination
- Empty state
- Loading skeleton

---

# 🧩 8. MODAL SYSTEM

Reusable modal with Alpine + Livewire
```blade
@props(['show' => false])

<div 
    x-data="{ open: @entangle($attributes->wire('model')) }"
    x-show="open"
    class="fixed inset-0 flex items-center justify-center bg-black/50 backdrop-blur-sm"
>
    <div class="bg-white dark:bg-gray-900 rounded-xl p-6 w-full max-w-lg">
        {{ $slot }}
    </div>
</div>
```


---

# 🔔 9. TOAST SYSTEM

```blade
<x-ui.toast type="success" message="Saved!" />
```

Features:

* Auto hide
* Stackable
* Animated


---

# ⚡ 10. LIVEWIRE RULES

✔ wire:model.live
✔ wire:click
✔ wire:loading
✔ wire:target

❌ NO business logic  
❌ NO query DB  

---

# 🎯 11. UI STATES

- Loading
- Empty
- Error

## Loading

```blade
<x-ui.loading />
```

## Empty

```blade
<x-ui.empty message="No data found" />
```

## Error

```blade
<x-ui.error />


---

# 🌙 12. DARK MODE

```html
dark:bg-gray-900
dark:text-white
dark:border-gray-700
```

Enable bằng:

```
<html class="dark">

```

---

# 🎞 13. ANIMATION SYSTEM
 
* fade-in
* scale modal
* hover lift
* loading shimmer

```html
transition-all duration-200 ease-in-out
```
---

# ♿ 14. ACCESSIBILITY


* aria-label
* role="button"
* focus:ring
* keyboard navigation

---

# 🧠 15. UX PATTERNS

- Confirm dialog
- Toast feedback
- Inline validation
- Disable loading

---
# 🧠 16-1. UX PATTERNS (SaaS LEVEL)

BẮT BUỘC:

* Confirm dialog
* Toast feedback
* Inline validation
* Disable khi loading
* Optimistic UI

# ❌ 16-2. ANTI-PATTERN


❌ Hardcode UI
❌ Không component hóa
❌ Logic trong Blade
❌ Vừa code vừa nghĩ cấu trúc
❌ Nhét toàn bộ UI vào 1 file
❌ Không khai báo dependency
❌ Duplicate UI component
❌ Không tách partial

---

# 🏗 17. LAYOUT

```text
Sidebar | Header
        | Content
```

---

## Container

```html
max-w-7xl mx-auto px-4 sm:px-6 lg:px-8
```

# 🏆 FINAL

```text
UI SYSTEM v3 = Enterprise Design System
```

---

<!-- ============================= -->
<!-- 🔚 END UI SYSTEM V3 -->
<!-- ============================= -->
