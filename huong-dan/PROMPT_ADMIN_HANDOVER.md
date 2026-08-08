# 🚀 PROJECT HANDOVER: ECOMMERCE ADMIN SYSTEM (HOMEPAGE MANAGEMENT)

Tôi đang phát triển hệ thống Ecommerce và cần bạn tiếp tục xây dựng phần **Admin Backend** để quản trị trang chủ (Homepage).
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

---

## 2️⃣ NHIỆM VỤ: QUẢN TRỊ TRANG CHỦ (HOMEPAGE MANAGER)

- Sử dụng: MASTER PROMPT v2 – WEBSITE ECOMMERCE (PRODUCTION · KHÓA CỨNG) tôi đã từng gửi cho bạn
- Bạn phải tạo Giao diện Frontend đạt chuẩn **Master UI** về thẩm mỹ và **Core Web Vitals** về hiệu suất.
Frontend trang chủ (`Modules/Website`) đã hoàn thiện với 10 components.
Nhiệm vụ của bạn là xây dựng hệ thống Admin (`Modules/Admin`) để quản lý nội dung của 10 component này theo chiến lược **Hybrid (Lai ghép)**:

1.  **Quản lý Thực thể (Entity):** Dùng bảng riêng cho dữ liệu phức tạp (Banners, Flash Sale).
2.  **Quản lý Cấu hình (Settings):** Dùng bảng Key-Value JSON cho dữ liệu cấu hình (Chọn danh mục, Bật/tắt section, Giới hạn hiển thị).

### Danh sách 10 Component cần quản lý:
1.  Hero Banner (Slider)
2.  Category Highlight (Danh mục nổi bật)
3.  Flash Sale
4.  Promo Banner
5.  Featured Products (Sản phẩm nổi bật)
6.  New Arrivals (Hàng mới về)
7.  Best Sellers (Top bán chạy)
8.  Trust Badges (Cam kết)
9.  Blog Highlight
10. Newsletter & Footer

---

## 3️⃣ THIẾT KẾ DATABASE (SCHEMA)

Bạn cần tạo Migration cho các bảng sau trong `Modules/Admin`:

### A. Bảng `wp_banners` (Quản lý Hero Banner & Promo Banner)
- `id`
- `title` (string)
- `image_desktop` (string)
- `image_mobile` (string - nullable)
- `link` (string - nullable)
- `position` (enum: 'hero', 'promo_1', 'promo_2'...)
- `order` (integer - để sắp xếp)
- `is_active` (boolean)
- `timestamps`

### B. Bảng `wp_flash_sales` (Chương trình Flash Sale)
- `id`
- `title` (string)
- `start_time` (datetime)
- `end_time` (datetime)
- `is_active` (boolean)
- `timestamps`

### C. Bảng `wp_flash_sale_items` (Sản phẩm trong Flash Sale)
- `id`
- `flash_sale_id` (FK)
- `product_id` (FK)
- `price` (decimal - giá sale)
- `quantity` (int - số lượng suất sale)
- `sold` (int - đã bán)

### D. Bảng `wp_settings` (Lưu cấu hình toàn trang)
*Lưu ý: Dùng bảng này để lưu config cho các mục: Category Highlight, Featured Products, Trust Badges, Logic hiển thị.*
- `key` (string - unique, primary)
- `value` (text/json - nullable)
- `group_name` (string - để phân nhóm config, vd: 'homepage', 'general')
- `type` (string) default('text'); // text, image, textarea
- `label` (string -nullable)
---

## 4️⃣ YÊU CẦU CHỨC NĂNG & UI (MASTER UI ADMIN)

Xây dựng các Livewire Component trong `Modules/Admin` với tiêu chuẩn **Master UI** (Đẹp, Hiện đại, UX cao).

### Chức năng 1: Quản lý Banners (`BannerManager`)
- **List:** Hiển thị ảnh thumbnail, toggle bật/tắt nhanh.
- **Sort:** Cho phép kéo thả hoặc nhập số thứ tự.
- **Create/Edit:** Modal hoặc Form upload ảnh (có preview).

### Chức năng 2: Quản lý Flash Sale (`FlashSaleManager`)
- **List:** Hiển thị trạng thái (Đang chạy, Sắp chạy, Kết thúc).
- **Form:**
    - Datepicker chọn giờ bắt đầu/kết thúc.
    - **Product Picker (Quan trọng):** Modal tìm kiếm và chọn nhiều sản phẩm vào Flash Sale. Cho phép set giá sale hàng loạt.

### Chức năng 3: Cấu hình Trang chủ (`HomeSettings`)
Đây là trang quan trọng nhất, quản lý các component còn lại thông qua bảng `wp_settings`.
Giao diện chia thành các Tabs hoặc Cards:
1.  **Layout Control:** Checkbox Bật/Tắt từng section (New Arrivals, Blog...).
2.  **Category Highlight:** Sử dụng **Select2/Multi-select** để chọn ID các danh mục muốn hiển thị.
3.  **Featured Products:**
    - Mode: "Tự động" hoặc "Thủ công".
    - Nếu "Thủ công": Hiện Product Picker để chọn ID sản phẩm ghim.
4.  **Trust Badges:** Form Repeater (Icon + Title + Subtitle) lưu dạng JSON.

---

## 5️⃣ QUY TRÌNH THỰC HIỆN (YÊU CẦU AI TUÂN THỦ)

1.  **Bước 1:** Viết Migration cho 4 bảng trên (`wp_banners`, `wp_flash_sales`, `wp_flash_sale_items`, `wp_settings`).
2.  **Bước 2:** Tạo Models tương ứng trong `Modules/Admin/Models`.
3.  **Bước 3:** Tạo `HomeSettingService` để xử lý logic lưu/lấy settings JSON.
4.  **Bước 4:** Bắt đầu viết Livewire Component cho **"Cấu hình Trang chủ" (`HomeSettings`)** trước (Vì đây là phần cốt lõi).

---

## 6️⃣ LƯU Ý KHI CODE

- **Service-Repository:** Logic xử lý dữ liệu đẩy vào Service, Livewire chỉ handle UI.
- **Master UI:**
    - Input có border tinh tế, shadow-sm, focus ring.
    - Modal dùng `@teleport`, `z-index` cao để không bị che.
    - Thông báo dùng Toast Notification (SweetAlert hoặc dispatch browser event).
- **Clean Code:** PSR-12, comment rõ ràng các đoạn logic phức tạp (đặc biệt là xử lý JSON setting).

**HÃY BẮT ĐẦU BẰNG VIỆC TẠO MIGRATION.**

🟢 NHÓM 1: CÁC KHỐI QUAN TRỌNG & ĐÃ CÓ LOGIC ADMIN (Ưu tiên làm ngay)
Những phần này chúng ta đã xây dựng logic kỹ ở bước Admin (HomeSettings & FlashSaleManager), nên việc đấu nối sẽ rất nhanh và thấy kết quả ngay.

Component 2: CategoryHighlight (Danh mục nổi bật)

Nhiệm vụ: Lấy danh sách danh mục theo category_ids đã chọn trong Admin. Hiển thị ảnh và tên danh mục.

Component 3: FlashSale (Sản phẩm giờ vàng)

Nhiệm vụ: Lấy chiến dịch đang chạy (theo thời gian thực). Hiển thị đồng hồ đếm ngược và danh sách sản phẩm, thanh tiến trình "Đã bán".

Component 8: TrustBadges (Cam kết)

Nhiệm vụ: Hiển thị Icon/Text từ cấu hình JSON (Repeater) trong Admin.

🟡 NHÓM 2: CÁC KHỐI SẢN PHẨM (Product Grids)
Nhóm này liên quan nhiều đến query sản phẩm (wp_products).

Component 5: FeaturedProducts (Sản phẩm nổi bật)

Nhiệm vụ: Query sản phẩm theo danh sách ID (featured_ids) ghim cứng từ Admin.
 
Component 6: NewArrivals (Hàng mới về)

Nhiệm vụ: Query tự động lấy sản phẩm mới nhất (created_at).

Component 7: BestSellers (Top bán chạy)

Nhiệm vụ: Query sản phẩm có sold_count cao nhất (Cần Lazy Load vì query nặng).

⚪ NHÓM 3: CÁC KHỐI NỘI DUNG PHỤ & TĨNH
Component 4: PromoBanner: Ảnh quảng cáo giữa trang (Thường là tĩnh hoặc config đơn giản).
Component 9: BlogHighlight: Lấy bài viết tin tức mới nhất.
Component 10: Newsletter: Form đăng ký email (Footer).

