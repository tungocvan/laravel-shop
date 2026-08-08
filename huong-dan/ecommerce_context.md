```markdown
# 🛒 MASTER CONTEXT: WEBSITE ECOMMERCE (LARAVEL 12)

Tài liệu này chứa toàn bộ ngữ cảnh, quy tắc và cấu trúc file bắt buộc cho dự án Website Ecommerce. AI phải đọc và tuân thủ tuyệt đối.

---

## 0️⃣ TECH STACK & VAI TRÒ (KHÓA CỨNG)

**Vai trò:** Senior Laravel Developer (10+ năm kinh nghiệm).
**Tư duy:** Production System (Không làm demo, không shortcut).

### Công nghệ bắt buộc:
*   **Framework:** Laravel 12
*   **Realtime:** Livewire 3.1 (Class-based, ❌ TUYỆT ĐỐI KHÔNG dùng Volt)
*   **UI:** TailwindCSS `^4.1.18`
*   **Architecture:** Modules (Tất cả code nằm trong `Modules/Website/`)
*   **Database:** MySQL

---

## 1️⃣ NGUYÊN TẮC BẤT BIẾN (SYSTEM LAW)

1.  **Phạm vi:** Mọi file phải nằm trong thư mục gốc `Modules/Website/`.
2.  **Logic:** Tuân thủ chuẩn **MVC + Livewire**:
    *   **Controller:** Chỉ điều hướng, không chứa business logic.
    *   **Blade:** Không query Database trực tiếp.
    *   **Livewire:** Không viết HTML inline, phải trả về view.
3.  **Dữ liệu:** Phải query từ Database thật (Models). **CẤM** hardcode, fake data, JSON giả.
4.  **Flow:** Phải xây dựng **FULL FLOW** (Frontend Customer & Backend Admin).

---
⛔ **TUYỆT ĐỐI CẤM**
- Hardcode dữ liệu
- Fake data / JSON giả
- Code minh họa
- Lệch namespace `Modules`
- Sinh file ngoài cây đã định nghĩa


🎯 **NHIỆM VỤ DUY NHẤT**

Xây dựng **Website Ecommerce hoàn chỉnh** trong module:

```
Modules/Website
```

Bao gồm **FULL FLOW**:
FrontEnd:
- Category (đa cấp – taxonomy lõi)
- Danh sách sản phẩm -> có add-to-cart
- Chi tiết sản phẩm
- Giỏ hàng (session-based)
- Checkout
- Đặt hàng thành công
- Thanh toán
BackEnd:
- Quản trị Category
- Quản trị Product
- Quản trị Order

```
## 2️⃣ QUY TRÌNH LÀM VIỆC (AI WORKFLOW)



**AI TUYỆT ĐỐI KHÔNG TỰ SINH CODE KHI CHƯA ĐƯỢC DUYỆT.**

Với mỗi yêu cầu, AI thực hiện đúng 4 bước:
1.  **Phân tích:** Đánh giá yêu cầu.
2.  **Liệt kê:** Liệt kê danh sách file cần tạo và logic chính.
3.  **DỪNG LẠI:** Chờ người dùng xác nhận ("OK BƯỚC X").
4.  **Viết Code:** Chỉ sinh code sau khi nhận được xác nhận.

---

## 3️⃣ CẤU TRÚC FILE (PROJECT TREE)

Tất cả các file dưới đây đều nằm trong `Modules/Website/`.

### A. Database (Migrations & Seeders)
*   `database/migrations/2025_01_01_000000_create_website_ecommerce_tables.php` (Chứa 7 bảng: `wp_products`, `categories`, `category_product`, `carts`, `cart_items`, `wp_orders`, `order_items`)

### Migration
- [ ] Đủ 7 bảng -> viết 1 file migration
- [ ] Đúng khóa ngoại
- [ ] Không đổi schema
- [ ] ĐÓNG BĂNG (KHÔNG ĐƯỢC SỬA)

⛔ **CẤM**
- Thêm / bớt cột
- Đổi tên bảng
- Đổi kiểu dữ liệu

### Seeders
*   `database/Seeders/CategorySeeder.php`
*   `database/Seeders/ProductSeeder.php`
*   `database/Seeders/WebsiteDatabaseSeeder.php`
* Dùng link ảnh đại diện: https://placehold.co/....
Toàn bộ nằm trong: Modules/Website/Database/Seeders/
CategorySeeder.php: Tạo danh mục mẫu (vì sản phẩm cần danh mục để gán vào).
ProductSeeder.php: Tạo sản phẩm với số lượng tùy chọn, 1 ảnh chính và 3 ảnh gallery.
WebsiteDatabaseSeeder.php: File tổng để điều phối việc chạy seeder

### B. Models (Eloquent ORM)
*   `Models/Category.php`
*   `Models/WpProduct.php`
*   `Models/Cart.php`
*   `Models/CartItem.php`
*   `Models/Order.php`
*   `Models/OrderItem.php`
### Models
- [ ] Đúng namespace `Modules\\Website\\Models`
- [ ] Đủ relationships
- [ ] Có casts / accessors
- [ ] Có thể viết hàm create trong model

### C. Http Layer (Controllers & Requests)
**Frontend:**
*   `Http/Controllers/ProductController.php`
*   `Http/Controllers/CartController.php`
*   `Http/Controllers/CheckoutController.php`
*   `Http/Requests/CheckoutRequest.php`

**Backend (Admin):**
*   `Http/Controllers/Admin/CategoryController.php`
*   `Http/Controllers/Admin/ProductController.php`
*   `Http/Controllers/Admin/OrderController.php`

### D. Livewire Components (Logic Realtime)
**Frontend Logic:**
*   `Livewire/Products/ProductList.php`
*   `Livewire/Products/ProductDetail.php`
*   `Livewire/Cart/AddToCart.php`
*   `Livewire/Cart/CartIcon.php`
*   `Livewire/Cart/CartList.php`
*   `Livewire/Checkout/CheckoutForm.php`
*   `Livewire/Checkout/OrderSummary.php`
    


**Backend Logic (Admin):**
*   `Livewire/Admin/Category/CategoryList.php`
*   `Livewire/Admin/Category/CategoryForm.php`
*   `Livewire/Admin/Product/ProductList.php`
*   `Livewire/Admin/Product/ProductForm.php`
*   `Livewire/Admin/Order/OrderList.php`
*   `Livewire/Admin/Order/OrderDetail.php`

* Cách sử dụng livewire: 
### Livewire
- [ ] 1 class ↔ 1 blade
- [ ] View đúng `Website::livewire.*` (W phải là chữ In HOA của Website)
- [ ] Cách Gọi livewrie component trong blade `@livewire('website.products.product-list')`
- [ ] Không inline HTML,  phải trả về view. 

### E. Resources Views (Giao diện)

**1. Layouts:**
*   `Resources/views/layouts/frontend.blade.php` (Header, Footer, Slot Or content)
*   `Resources/views/layouts/backend.blade.php` (Header, Sidebar, Footer, Slot Or content)
*  ```php @isset($slot) {{ $slot }}  @else  @yield('content') @endisset ```
**2. Wrapper Views (Controller trả về file này):**
*   *Frontend:*
    *   `Resources/views/products/index.blade.php`
    *   `Resources/views/products/show.blade.php`
    *   `Resources/views/cart/index.blade.php`
    *   `Resources/views/checkout/index.blade.php`
    *   `Resources/views/checkout/success.blade.php`
*   *Backend:*
    *   `Resources/views/admin/categories/index.blade.php`
    *   `Resources/views/admin/categories/create.blade.php`
    *   `Resources/views/admin/categories/edit.blade.php`
    *   `Resources/views/admin/products/index.blade.php`
    *   `Resources/views/admin/products/create.blade.php`
    *   `Resources/views/admin/products/edit.blade.php`
    *   `Resources/views/admin/orders/index.blade.php`
    *   `Resources/views/admin/orders/show.blade.php`

**3. Livewire Views (Components):**
*   *Frontend:*
    *   `Resources/views/livewire/products/product-list.blade.php`
    *   `Resources/views/livewire/products/product-detail.blade.php`
    *   `Resources/views/livewire/cart/add-to-cart.blade.php`
    *   `Resources/views/livewire/cart/cart-icon.blade.php`
    *   `Resources/views/livewire/cart/cart-list.blade.php`
    *   `Resources/views/livewire/checkout/checkout-form.blade.php`
    *   `Resources/views/livewire/checkout/order-summary.blade.php`
*   *Backend:*
    *   `Resources/views/livewire/admin/category/category-list.blade.php`
    *   `Resources/views/livewire/admin/category/category-form.blade.php`
    *   `Resources/views/livewire/admin/product/product-list.blade.php`
    *   `Resources/views/livewire/admin/product/product-form.blade.php`
    *   `Resources/views/livewire/admin/order/order-list.blade.php`
    *   `Resources/views/livewire/admin/order/order-detail.blade.php`

### F. Routes
*   `Routes/web.php`

---

## 4️⃣ ROUTE CONTRACT

### 🌐 Frontend (Customer) - Middleware: `web`
*   **Home:** `GET /website` (Redirect to products)
*   **Products:**
    *   `GET /website/products` (Index)
    *   `GET /website/products/category/{category:slug}` (Filter by Category)
    *   `GET /website/products/{product:slug}` (Detail - SEO Slug)
*   **Cart (Session):**
    *   `GET /website/cart` (Index)
    *   `POST /website/cart/add` (Add Item)
    *   `PATCH /website/cart/update` (Update Qty)
    *   `DELETE /website/cart/remove/{item}` (Remove Item)
*   **Checkout:**
    *   `GET /website/checkout` (Form)
    *   `POST /website/checkout` (Submit Order)
    *   `GET /website/checkout/success/{order}` (Success Page)

### 🛠 Backend (Admin) - Middleware: `web, auth`
*   **Categories:** `GET|POST|PUT|DELETE /website/admin/categories` (CRUD)
*   **Products:** `GET|POST|PUT|DELETE /website/admin/products` (CRUD)
*   **Orders:**
    *   `GET /website/admin/orders` (List)
    *   `GET /website/admin/orders/{order}` (Detail)

---

## 5️⃣ YÊU CẦU BẮT ĐẦU
AI hãy bắt đầu bằng việc:
1.  Đọc hiểu toàn bộ ngữ cảnh trên. Sau đó bạn liệt kê các qui trình các bước bạn sẻ làm theo qui trình ! 
2.  Các Bước Database (Migrations & Seeder), Models xem như tôi đã hoàn thành. trừ khi bạn cần thiết tôi sẻ gửi bạn xem lại.
3.  Bạn có thể tạo checklist qui trình các bước bạn sẻ tiến hành phân tích và viết code, tôi có thể yêu cầu nhảy bước.
```
