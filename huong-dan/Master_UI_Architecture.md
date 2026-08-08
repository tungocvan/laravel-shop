# 📘 TÀI LIỆU KỸ THUẬT: MASTER UI HOMEPAGE ARCHITECTURE
**Project:** FlexBiz E-commerce  
**Style:** Master UI (Minimalist, Modern, High-Performance)  
**Tech Stack:** Laravel, Livewire 3, Tailwind CSS v4, Alpine.js

---

## 🏗️ TỔNG QUAN CẤU TRÚC (HOME VIEW)

Trang chủ được chia thành 10 Blocks độc lập (Components). Chiến lược tải trang:
1.  **Eager Load (Tải ngay):** Hero Banner, Category Highlight (để đảm bảo First Contentful Paint nhanh nhất).
2.  **Lazy Load (Tải chậm):** Tất cả các block còn lại (để giảm tải Server và tăng tốc độ render ban đầu).

---

## 🧩 CHI TIẾT TỪNG COMPONENT & YÊU CẦU QUẢN TRỊ (ADMIN)

### 1. Hero Banner (Slider)
* **Vị trí:** Đầu trang (Above the fold).
* **Chức năng:** Gây ấn tượng thị giác, điều hướng campaign chính.
* **Logic Frontend:** Slider chạy tự động hoặc thủ công.
* **Dữ liệu:** Bảng `wp_theme_options` (key: `home_sliders`) hoặc bảng riêng `banners`.
* **📝 Yêu cầu Admin:**
    * [ ] Form upload ảnh (Desktop/Mobile).
    * [ ] Nhập Title, Subtitle, Button Text.
    * [ ] Nhập Link đích (Link tới Product/Category/Post).
    * [ ] Sắp xếp thứ tự (Sort order).

### 2. Category Highlight
* **Vị trí:** Ngay dưới banner.
* **Chức năng:** Điều hướng nhanh vào các nhóm hàng chính.
* **Style:** Story Ring (Vòng tròn giống Instagram Story).
* **Dữ liệu:** Model `Category` (type=`product`, parent_id=`null`).
* **📝 Yêu cầu Admin:**
    * [ ] Upload Icon/Ảnh đại diện cho danh mục.
    * [ ] Checkbox "Hiển thị trang chủ".
    * [ ] Sắp xếp thứ tự hiển thị.

### 3. Flash Sale ⚡
* **Vị trí:** Block kích thích mua hàng khẩn cấp.
* **Chức năng:** Đếm ngược thời gian, hiển thị sản phẩm giảm giá sâu.
* **Logic:** Kiểm tra thời gian thực (`MarketingService`).
* **Dữ liệu:** `wp_settings` (lưu thời gian kết thúc), `wp_products` (lọc theo sale_price).
* **📝 Yêu cầu Admin:**
    * [ ] Datepicker chọn ngày giờ kết thúc (End Time).
    * [ ] Chọn danh sách sản phẩm tham gia Flash Sale.
    * [ ] Cài đặt mức giảm giá hàng loạt (hoặc tự động tính từ giá sale).

### 4. Promo Banner (Visual Break)
* **Vị trí:** Ngắt nhịp giữa các danh sách sản phẩm.
* **Chức năng:** Quảng cáo chiến dịch đơn lẻ, Parallax effect.
* **Dữ liệu:** `wp_theme_options` (key: `promo_banner`).
* **📝 Yêu cầu Admin:**
    * [ ] Upload ảnh Background (Khổ rộng).
    * [ ] Nhập Title, Subtitle.
    * [ ] **Quan trọng:** 2 ô nhập Link riêng biệt (1 cho nút Mua, 1 cho link Chi tiết).

### 5. Featured Products (Core Business)
* **Vị trí:** Trọng tâm trang chủ.
* **Style:** Grid Layout, Clean background.
* **Dữ liệu:** Model `Product` (lọc theo tags=`featured` hoặc `is_featured=1`).
* **📝 Yêu cầu Admin:**
    * [ ] Trong trang Sửa Sản Phẩm: Có checkbox "Sản phẩm nổi bật".
    * [ ] Hoặc trang quản lý riêng: Kéo thả sản phẩm vào danh sách nổi bật.

### 6. New Arrivals (Hàng mới về)
* **Style:** Horizontal Scroll (Trượt ngang), hỗ trợ vuốt trên Mobile.
* **Dữ liệu:** Model `Product` (order by `created_at` desc).
* **📝 Yêu cầu Admin:**
    * [ ] Tự động lấy 10 sản phẩm mới nhất (Thường không cần cấu hình thủ công).
    * [ ] Tùy chọn: Nhập số lượng sản phẩm muốn hiển thị (Limit).

### 7. Best Sellers (Bảng xếp hạng) 🏆
* **Style:** Ranking Board (Top 1 to, Top 2-5 nhỏ).
* **Dữ liệu:** Model `Product` (order by `sold_count` desc hoặc manual selection).
* **📝 Yêu cầu Admin:**
    * [ ] **Option 1 (Auto):** Tự động tính toán từ đơn hàng đã hoàn thành.
    * [ ] **Option 2 (Manual):** Cho phép Admin "gán" vị trí Top 1, Top 2... thủ công (để đẩy hàng tồn hoặc hàng Marketing).

### 8. Trust Badges (Cam kết)
* **Style:** Minimalist Grid (Icon + Text).
* **Dữ liệu:** Static array hoặc `wp_theme_options`.
* **📝 Yêu cầu Admin:**
    * [ ] CRUD 4 block cam kết (Icon SVG code, Tiêu đề, Mô tả ngắn).

### 9. Blog Highlight (Tạp chí) 📰
* **Style:** Magazine Layout (1 Hero + 3 List).
* **Dữ liệu:** Model `Post` (type=`post`, status=`published`).
* **📝 Yêu cầu Admin:**
    * [ ] Trang quản lý bài viết (Post CRUD).
    * [ ] Checkbox "Nổi bật" (để chọn bài làm Hero Post).
    * [ ] Trình soạn thảo văn bản chuẩn (CKEditor/TinyMCE) để nội dung lên đẹp.

### 10. Newsletter Signup (Pre-footer)
* **Style:** Dark Mode / Glassmorphism.
* **Dữ liệu:** Model `Newsletter` (bảng `newsletters`).
* **📝 Yêu cầu Admin:**
    * [ ] Xem danh sách Email đã đăng ký.
    * [ ] Nút "Export Excel" để xuất danh sách đi gửi mail Marketing.

---

## 🛠️ DATABASE SCHEMA NOTES

Để hệ thống vận hành trơn tru, đây là các bảng (Tables) cốt lõi đã sử dụng:

1.  **`wp_products`**: Sản phẩm (quan trọng cột: `is_featured`, `sale_price`, `views`, `sold_count`).
2.  **`wp_posts`**: Bài viết Blog & Trang tĩnh (quan trọng cột: `type`='post'/'page', `status`='published').
3.  **`categories`**: Danh mục chung (phân biệt bằng cột `type`='product'/'post').
4.  **`wp_theme_options` / `wp_settings`**: Lưu cấu hình (Banner, Logo, Màu sắc, Flash Sale timer...).
5.  **`newsletters`**: Lưu email đăng ký nhận tin.

---

## 🚀 NEXT STEPS (Lộ trình tiếp theo)

1.  **Xây dựng Admin Panel:** Dùng FilamentPHP hoặc tự code Admin Controller dựa trên các yêu cầu ở trên.
2.  **Product Detail Page:** Thiết kế trang chi tiết sản phẩm với Gallery ảnh, Chọn biến thể (Size/Color) và Reviews.
3.  **Cart & Checkout:** Xử lý luồng mua hàng.

> **Note:** Giao diện Frontend hiện tại đã đạt chuẩn **Master UI** về thẩm mỹ và **Core Web Vitals** về hiệu suất. Hãy giữ vững tiêu chuẩn này khi code Backend!
