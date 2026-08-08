# Module database dependencies

## Quy tắc

- `shell`: module bắt buộc, luôn bật và không được tắt từ màn hình quản trị.
- `support` và `domain`: có thể tắt khi không có module đang bật phụ thuộc vào nó.
- `depends`: khai báo dependency cứng. Khi module được bật, toàn bộ dependency trực tiếp và gián tiếp phải tồn tại và được bật.
- Chỉ migration của module đang bật mới được nạp. Tắt module không tự động xóa bảng hoặc rollback dữ liệu của module đó.
- Bảng dùng chung chỉ có một module sở hữu migration tạo bảng. Module khác sử dụng bảng phải khai báo `depends`.

## Shell Modules — không được tắt

| Module | Vai trò database/hệ thống |
|---|---|
| `Auth` | Cache, queue jobs, failed jobs, sessions và vòng đời xác thực |
| `User` | Sở hữu bảng `users`, gốc của phần lớn foreign key người dùng |
| `Role` | Permissions, roles và các bảng pivot phân quyền |
| `Admin` | Shell quản trị, settings và menu quản trị |
| `System` | Quản trị cấu hình, module và database |
| `Shared` | Component và service dùng chung, gồm hạ tầng import/export |

## Dependency graph

| Module | Loại | Dependency cứng | Lý do database chính |
|---|---|---|---|
| Account | domain | User | Alter `users`; profile/meta FK đến `users` |
| Admin | shell | Auth, User, Role | Shell quản trị và phân quyền |
| Admission | domain | — | Chỉ dùng các bảng `admission_*` nội bộ |
| Auth | shell | — | Hạ tầng bắt buộc |
| Category | support | — | Sở hữu `category_types`, `categories` |
| Chat | support | Admin, User | Chat models quản trị; FK đến `users` |
| Facebook | domain | User | Kết nối Facebook thuộc người quản trị |
| Identity | domain | User | Các profile/identifier FK đến `users` |
| Invoices | domain | — | Bảng hóa đơn độc lập |
| Muasamcong | domain | — | Không có migration quan hệ chéo |
| Order | domain | User, Product | FK đến `users`, `wp_products` |
| Partner | domain | — | Sở hữu bảng `partners` độc lập |
| Pharma | domain | Shared | Các bảng `pharma_*` phụ thuộc nội bộ; dùng import/export chung |
| Post | domain | User, Category, Shared | FK đến `users`, `categories`; dùng import/export chung |
| Product | domain | User, Category | FK đến `users`, `categories` |
| PromptEngine | support | — | Không có migration quan hệ chéo |
| Role | shell | User | Phân quyền cho user/model hệ thống |
| System | shell | Admin, Role | Quản trị hệ thống nằm trên shell Admin |
| Shared | shell | — | Hạ tầng dùng chung không có migration |
| User | shell | Shared | Bảng gốc `users`; dùng import/export chung |
| Website | domain | User, Product, Category, Post, Order | Storefront dùng user, catalog, post và order |

## Các lỗi đã xử lý

1. `wp_tags` từng được tạo đồng thời bởi Post và Website với cùng tên migration. Post hiện là owner duy nhất; Website phụ thuộc Post.
2. Migration `internal_messages` từng nằm ở `database/migrations`, khiến nó luôn chạy kể cả khi Chat bị tắt. Migration đã chuyển vào module Chat.
3. Module loader giờ từ chối shell module bị disable, dependency thiếu/tắt, self-dependency và dependency vòng.
4. UI module manager khóa toggle của Shell Modules và chặn tắt module đang có dependent bật.

## Lưu ý vận hành

- Disable không đồng nghĩa uninstall: dữ liệu và bảng cũ được giữ nguyên để có thể bật lại module.
- Trước khi xóa hẳn module, phải kiểm tra inbound foreign key từ module khác và gỡ dependency theo migration riêng.
- Không dùng `Schema::disableForeignKeyConstraints()` để che lỗi dependency trong migration production.
- Migration thay đổi bảng của module khác phải khai báo module sở hữu bảng trong `depends`.
