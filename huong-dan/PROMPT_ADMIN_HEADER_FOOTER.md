# 🚀 PROJECT HANDOVER: ECOMMERCE ADMIN SYSTEM 

Tôi đang phát triển hệ thống Ecommerce và cần bạn tiếp tục xây dựng phần **Admin Backend** để quản trị header và footer.
Dưới đây là toàn bộ bối cảnh, quy tắc kỹ thuật và yêu cầu chi tiết. Hãy đọc kỹ trước khi viết code.

---

## 1️⃣ CÔNG NGHỆ & KIẾN TRÚC (HARD RULES)

**Framework:** Laravel 12
**Realtime:** Livewire 3.1 (Class-based, ❌ Volt)
**UI Framework:** TailwindCSS ^4.0.0
**Architecture:** HMVC (Hierarchical Model-View-Controller)
- Frontend: `Modules/Website`
- Backend: `Modules/Admin`
**Database Pattern:** Service-Repository Pattern (Livewire -> Service -> Model).
**Validation:** Form Request.
AI đóng vai **Senior Laravel Developer (10+ năm kinh nghiệm)**  
Tư duy: **Production System Engineer**
---

## 2️⃣ NHIỆM VỤ: QUẢN TRỊ TRANG CHỦ (HOMEPAGE MANAGER)

- Sử dụng: MASTER PROMPT v2 – WEBSITE ECOMMERCE (PRODUCTION · KHÓA CỨNG) tôi đã từng gửi cho bạn
- Bạn phải tạo Giao diện Frontend đạt chuẩn **Master UI** về thẩm mỹ và **Core Web Vitals** về hiệu suất.
Frontend (`Modules/Website`) đã hoàn thiện với HEADER VÀ FOOTER.
Nhiệm vụ của bạn là xây dựng hệ thống Admin (`Modules/Admin`) để quản lý nội dung của HEADER VÀ FOOTER này theo chiến lược **Hybrid (Lai ghép)**:

1.  **Quản lý Thực thể (Entity):** Dùng bảng riêng cho dữ liệu phức tạp .
2.  **Quản lý Cấu hình (Settings):** Dùng bảng Key-Value JSON cho dữ liệu cấu hình.


---

## 3️⃣ THIẾT KẾ DATABASE (SCHEMA)


---

## 4️⃣ YÊU CẦU CHỨC NĂNG & UI (MASTER UI ADMIN)

Xây dựng các Livewire Component trong `Modules/Admin` với tiêu chuẩn **Master UI** (Đẹp, Hiện đại, UX cao).

---

## 5️⃣ QUY TRÌNH THỰC HIỆN (YÊU CẦU AI TUÂN THỦ)

1.  **Bước 1:** Viết Migration.
2.  **Bước 2:** Tạo Models tương ứng 
3.  **Bước 3:** Tạo `Service` để xử lý logic lưu/lấy settings JSON
4.  **Bước 4:** Tạo route cho header và footer
    - Route::get('/header-settings', [HeaderSettingsController::class, 'index'])
    - Route::get('/footer-settings', [FooterSettingsController::class, 'index'])
5.  **Bước 5:** Bắt đầu viết Livewire Component HEADER VÀ FOOTER, bên trong nên chia ra nhiều component để quản lý

---

## 6️⃣ LƯU Ý KHI CODE

- **Service-Repository:** Logic xử lý dữ liệu đẩy vào Service, Livewire chỉ handle UI.
- **Master UI:**
    - Input có border tinh tế, shadow-sm, focus ring.
    - Modal dùng `@teleport`, `z-index` cao để không bị che.
    - Thông báo dùng Toast Notification (SweetAlert hoặc dispatch browser event).
- **Clean Code:** PSR-12, comment rõ ràng các đoạn logic phức tạp (đặc biệt là xử lý JSON setting).



## 3️⃣ CẤU TRÚC MODULE (KHÓA CỨNG)

> ❌ ĐÃ LOẠI BỎ `Http/Controllers/Web`  
> ✔ Controller web đặt trực tiếp trong `Http/Controllers`

```
Modules/Website/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── Seeders/
├── Helpers/
├── Http/
│   ├── Controllers/
│   ├── Components/
│   ├── Middleware/
│   └── Requests/
├── Livewire/
│   │   └── Home/
│   │       └── HomeList.php
├── Models/
├── Resources/
│   ├── Views/
│   │   ├── layouts/
│   │   │   └── frontend.blade.php
│   │   ├── pages/
│   │   │   └── home.blade.php
│   │   ├── livewire/
│   │   │       └── home/
│   │   │           └── home-list.blade.php
│   │   └── components/
│   ├── Lang/
│   ├── Css/
│   └── Js/
├── Routes/
│   ├── web.php
│   └── api.php
├── Services/

```

---

## 4️⃣ LUẬT KIẾN TRÚC

### Controller
- Chỉ điều hướng
- ❌ Không business logic
- ✔ Gọi Service

### Livewire
- Chỉ xử lý state & UI
- ❌ Không nghiệp vụ
- ✔ Gọi Service

### Service
- **BẮT BUỘC**
- Toàn bộ business logic

### Model
- Relationship, cast, scope đơn giản
- ❌ Không nghiệp vụ

---

## 5️⃣ FLOW BẮT BUỘC

```
Route
 → Controller
     → Blade Page (pages)
         → Livewire Component
             → Service
                 → Model
```

---

## 6️⃣ LIVEWIRE RULESET

- ❌ Volt
- ✔ Class-based
- ✔ View riêng
- ❌ Logic trong Blade

---

## 7️⃣ VIEW / PAGE CALLING RULES (BẮT BUỘC)

### 7.1 Gọi layout & page trong module

- **Namespace view module:** `Website::`

Ví dụ page homepage:

```blade
@extends('Website::layouts.frontend')

@section('title', 'HOMEPAGE')

@section('content')
    @livewire('website.home.home-list')
@endsection
```
