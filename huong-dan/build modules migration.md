# 🚀 Module CLI Commands Guide (Enterprise Version)

Tài liệu này tổng hợp toàn bộ command quản lý Module + Migration trong hệ thống Laravel modular của bạn.

---

# 📦 1. Tạo Migration

```bash
php artisan module:make-migration Blog create_posts_table
```

## 👉 Mô tả

* Tạo migration trong module
* Đường dẫn: `Modules/Blog/database/migrations`

---

# 🗄️ 2. Chạy Migration Module

```bash
php artisan module:migrate Blog
```

## 👉 Mô tả

* Chạy toàn bộ migration của module
* Tự động track vào bảng `module_migrations`

---

# 🔁 3. Rollback Module

```bash
php artisan module:rollback Blog --step=1
```

## 👉 Mô tả

* Rollback đúng migration thuộc module
* Không ảnh hưởng module khác

## ⚙️ Options

* `--step=1` : số migration rollback

---

# ♻️ 4. Fresh Module (Reset)

```bash
php artisan module:fresh Blog
```

## 👉 Mô tả

* Xóa toàn bộ table của module
* Chạy lại migration từ đầu

---

## 🔹 Fresh 1 table

```bash
php artisan module:fresh Blog posts
```

## 👉 Mô tả

* Drop table `blog_posts`
* Chạy lại migration liên quan table đó

---

## 🔹 Delete table only

```bash
php artisan module:fresh Blog posts --delete
```

## 👉 Mô tả

* Chỉ xóa table
* Không migrate lại

---

## ⚙️ Options

* `--delete` : chỉ xóa table
* `--force` : bỏ confirm
* `--dry-run` : preview

---

# 🧨 5. Uninstall Module

```bash
php artisan module:uninstall Blog
```

## 👉 Mô tả

* Xóa toàn bộ table của module
* Xóa thư mục module
* Xóa tracking trong `module_migrations`

---

# 🧠 6. Migration Tracker System

## 👉 Bảng sử dụng

```text
module_migrations
```

## 👉 Mục đích

* Track migration theo module
* Track table đã tạo
* Hỗ trợ rollback / fresh chính xác

---

# 🔍 7. Quy tắc đặt tên

| Input        | Output     |
| ------------ | ---------- |
| Blog + posts | blog_posts |
| Blog + users | blog_users |

---

# ⚠️ 8. Lưu ý quan trọng

* Luôn prefix table theo module
* Không dùng chung table giữa module
* Migration phải rõ ràng (create/add/drop)

---

# 🚀 9. Best Practice

* Mỗi module độc lập DB
* Không phụ thuộc migration module khác
* Sử dụng `module:fresh` thay vì `migrate:fresh`

---

# 🔥 10. Flow chuẩn sử dụng

```bash
# Tạo migration
php artisan module:make-migration Blog create_posts_table

# Chạy migration
php artisan module:migrate Blog

# Rollback
php artisan module:rollback Blog --step=1

# Reset module
php artisan module:fresh Blog

# Xóa module
php artisan module:uninstall Blog
```

---

# 🏁 Kết luận

Hệ thống command này giúp bạn:

* Quản lý migration theo module
* Tránh xung đột database
* Scale hệ thống dễ dàng
* Đảm bảo an toàn khi thao tác DB

👉 Đây là nền tảng để build **internal framework Laravel modular chuẩn production**.


# 🚀 Module CLI Commands Guide (Enterprise Version)

Tài liệu này tổng hợp toàn bộ command quản lý Module + Migration trong hệ thống Laravel modular của bạn.

---

# 📦 1. Tạo Migration (module:make-migration)

```bash
php artisan module:make-migration Blog create_posts_table
```

## 👉 Mô tả

* Tạo migration trực tiếp vào module
* Tự động parse table từ tên migration
* Tự động prefix theo module
* Tự động cập nhật `config/module.php`

---

## 🧠 Cách hoạt động

### 1. Parse table name

```text
create_posts_table → blog_posts
```

### 2. Tạo migration file

```text
Modules/Blog/database/migrations/xxxx_create_posts_table.php
```

### 3. Generate stub

```php
Schema::create('blog_posts', function (Blueprint $table) {
    $table->id();
    $table->timestamps();
});
```

### 4. Update config/module.php

```php
return [
    'tables' => [
        'blog_posts'
    ]
];
```

---

## ⚠️ Lưu ý quan trọng

* Tên migration bắt buộc đúng format:

```bash
create_<table>_table
```

* Ví dụ hợp lệ:

```bash
create_posts_table
create_users_table
```

* Ví dụ sai:

```bash
posts ❌
add_posts ❌
```
 
---

## 🔥 Ưu điểm

* Không cần nhớ table name
* Không cần update config thủ công
* Tránh duplicate table
* Đồng bộ module + database

---

# 🗄️ 2. Chạy Migration Module

```bash
php artisan module:migrate Blog
```

## 👉 Mô tả

* Chạy toàn bộ migration của module
* Tự động track vào bảng `module_migrations`

---

# 🔁 3. Rollback Module

```bash
php artisan module:rollback Blog --step=1
```

## 👉 Mô tả

* Rollback đúng migration thuộc module
* Không ảnh hưởng module khác

## ⚙️ Options

* `--step=1` : số migration rollback

---

# ♻️ 4. Fresh Module (Reset)

```bash
php artisan module:fresh Blog
```

## 👉 Mô tả

* Xóa toàn bộ table của module
* Chạy lại migration từ đầu

---

## 🔹 Fresh 1 table

```bash
php artisan module:fresh Blog posts
```

## 👉 Mô tả

* Drop table `blog_posts`
* Chạy lại migration liên quan table đó

---

## 🔹 Delete table only

```bash
php artisan module:fresh Blog posts --delete
```

## 👉 Mô tả

* Chỉ xóa table
* Không migrate lại

---

## ⚙️ Options

* `--delete` : chỉ xóa table
* `--force` : bỏ confirm
* `--dry-run` : preview

---

# 🧨 5. Uninstall Module

```bash
php artisan module:uninstall Blog
```

## 👉 Mô tả

* Xóa toàn bộ table của module
* Xóa thư mục module
* Xóa tracking trong `module_migrations`

---

# 🧠 6. Migration Tracker System

## 👉 Bảng sử dụng

```text
module_migrations
```

## 👉 Mục đích

* Track migration theo module
* Track table đã tạo
* Hỗ trợ rollback / fresh chính xác

---

# 🔍 7. Quy tắc đặt tên

| Input        | Output     |
| ------------ | ---------- |
| Blog + posts | blog_posts |
| Blog + users | blog_users |

---

# ⚠️ 8. Lưu ý quan trọng

* Luôn prefix table theo module
* Không dùng chung table giữa module
* Migration phải rõ ràng (create/add/drop)

---

# 🚀 9. Best Practice

* Mỗi module độc lập DB
* Không phụ thuộc migration module khác
* Sử dụng `module:fresh` thay vì `migrate:fresh`

---

# 🔥 10. Flow chuẩn sử dụng

```bash
# Tạo migration
php artisan module:make-migration Blog create_posts_table

# Chạy migration
php artisan module:migrate Blog

# Rollback
php artisan module:rollback Blog --step=1

# Reset module
php artisan module:fresh Blog

# Xóa module
php artisan module:uninstall Blog
```

---

# 🏁 Kết luận

Hệ thống command này giúp bạn:

* Quản lý migration theo module
* Tránh xung đột database
* Scale hệ thống dễ dàng
* Đảm bảo an toàn khi thao tác DB

👉 Đây là nền tảng để build **internal framework Laravel modular chuẩn production**.
