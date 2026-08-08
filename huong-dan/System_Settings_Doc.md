# TÀI LIỆU HƯỚNG DẪN: MODULE CẤU HÌNH HỆ THỐNG (SYSTEM SETTINGS)

## 1. Tổng quan
Module **System Settings** giúp Quản trị viên (Admin) quản lý toàn bộ các thông tin biến động của website mà không cần can thiệp vào mã nguồn (Source Code). Dữ liệu được lưu trữ linh hoạt dưới dạng `Key-Value` trong Database kết hợp với cơ chế `Cache` để tối ưu hiệu suất.

**Các tính năng chính:**
* **Cấu hình Cố định:** Thông tin chung, SEO, Mạng xã hội, Scripts.
* **Cấu hình Động (Custom Fields):** Tự động tạo thêm trường dữ liệu mới (Text, Ảnh, HTML, Gallery...).
* **Hiệu suất cao:** Tự động Cache dữ liệu, giảm tải truy vấn Database.

---

## 2. Cấu trúc Database (`wp_settings`)

| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | BigInt | Primary Key |
| `key` | String (Unique) | Mã định danh để gọi trong code (VD: `site_name`) |
| `value` | Text/LongText | Giá trị lưu trữ (Text, Path ảnh, JSON...) |
| `label` | String | Tên hiển thị trong trang Admin (VD: "Logo Website") |
| `type` | String | Loại dữ liệu: `text`, `textarea`, `image`, `html`, `gallery` |
| `group_name` | String | Nhóm: `general`, `seo`, `custom`... |

---

## 3. Hướng dẫn sử dụng (Dành cho Admin)

Truy cập: `Admin Panel` -> `Cấu hình hệ thống`

### A. Các Tab Cố định
1.  **Thông tin chung:** Nhập tên web, hotline, email, địa chỉ.
2.  **Hình ảnh:** Upload Logo, Favicon (Có chế độ xem trước).
3.  **SEO & MXH:** Cấu hình Meta Title/Description mặc định, Link Facebook/Zalo, Header Scripts (Google Analytics).

### B. Tab "Cấu hình mở rộng" (Tạo trường mới)
Đây là khu vực để Admin tự mở rộng hệ thống.

**Quy trình thêm trường mới:**
1.  **Nhập Label:** Tên hiển thị (VD: `Banner Khuyến Mãi`).
2.  **Nhập Key:** Mã định danh (VD: `promo_banner`). *Lưu ý: Viết liền không dấu, dùng dấu gạch dưới.*
3.  **Chọn Loại dữ liệu:**
    * `Văn bản ngắn`: Cho các link, slogan, tiêu đề.
    * `Văn bản dài`: Cho ghi chú, mô tả ngắn.
    * `Hình ảnh`: Cho banner đơn, ảnh quảng cáo.
    * `Soạn thảo văn bản (HTML)`: Dùng CKEditor cho bài giới thiệu, footer.
    * `Album ảnh (Gallery)`: Cho slider trang chủ, thư viện ảnh (Hỗ trợ upload nhiều ảnh).
4.  Bấm **Thêm mới** -> Sau đó nhập liệu vào ô vừa sinh ra -> Bấm **Lưu cấu hình**.

---

## 4. Hướng dẫn Lập trình (Dành cho Developer)

Sử dụng Model `Setting` để lấy dữ liệu ra view (Blade).

**Khai báo đầu file Blade:**
```blade
@use('Modules\Website\Models\Setting')