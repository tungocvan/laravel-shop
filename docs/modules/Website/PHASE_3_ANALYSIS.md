# Website Phase 3 — Database Restructure Analysis

## Trạng thái

- Giai đoạn: `3 — Database Restructure`
- Phân tích: `HOÀN TẤT`
- Thay đổi database: `CHƯA THỰC HIỆN`
- Quyết định: `SẴN SÀNG XIN PHÊ DUYỆT TRIỂN KHAI`
- Giai đoạn trước: `Phase 2 — CLOSED / TESTED / APPROVED`

## Hiện trạng đã xác minh

- MySQL production hiện có 486 bảng; không được giả định đây là database riêng của Website.
- Toàn bộ migration `-0001_*` liên quan Website/Product/Order/Post/User đã chạy trong batch 1.
- Không được đổi tên, sửa nội dung hoặc chạy lại các migration đã áp dụng này.
- Website chưa có bảng page, section hoặc section item.
- `wp_settings` đang chứa cả global settings và dữ liệu homepage có cấu trúc.
- Homepage hiện lưu trạng thái section, danh sách Product/Category ID, promo banner,
  newsletter và trust badge trong key/value hoặc JSON.
- Banner, header menu, footer column/link và social link đã có bảng cấu trúc riêng.
- Production `wp_orders` chưa có `coupon_code`; đây là bằng chứng schema giữa môi
  trường và model có thể lệch, nên mọi migration mới phải có upgrade test.

## Phân loại dữ liệu

### Tiếp tục dùng `wp_settings`

- site identity, logo, favicon;
- contact và header/footer text đơn giản;
- theme configuration;
- analytics/custom script đã được bảo vệ permission;
- default SEO và các giá trị global scalar.

### Chuyển sang dữ liệu cấu trúc

- thứ tự và trạng thái các section trên từng page;
- danh sách Product/Category/Post được chọn và thứ tự của chúng;
- promo/trust badge/newsletter section configuration;
- page title, slug, template, publish state và SEO theo page.

## Schema mục tiêu

### `website_pages`

- `id`, unique `slug`, `title`;
- `status` (`draft`, `published`, `archived`);
- `template`, nullable SEO title/description/image;
- nullable `published_at`, timestamps;
- chưa thêm soft delete trong lát cắt đầu vì chưa có yêu cầu khôi phục page.

### `website_sections`

- foreign key `website_page_id` với cascade delete;
- stable `key` trong page, `type`, `position`, `is_enabled`, `variant`;
- JSON `config` được validate theo section type;
- unique `(website_page_id, key)` và index `(website_page_id, position)`.

### `website_section_items`

- foreign key `website_section_id` với cascade delete;
- `reference_type`, `reference_id`, `position`, `is_enabled`, JSON `config`;
- unique item identity trong một section và index cho reverse lookup;
- canonical entity tồn tại được kiểm tra qua Product/Category/Post service trong
  write transaction. Quan hệ đa loại không thể có một foreign key SQL chung, nên
  phải có integrity test và cleanup policy rõ ràng.

## Tận dụng bảng hiện có

### Menus

Giữ `header_menus` và `header_menu_items`. Cấu trúc parent/order/active/route đã đủ
cho lát cắt đầu. Chỉ cân nhắc migration bổ sung `reference_type/reference_id` sau
khi đánh giá toàn bộ UI; không tạo menu engine thứ hai.

### Banners

Giữ `wp_banners`; migration hiệu chỉnh có thể thêm `alt_text`, `starts_at`,
`ends_at`. `position`, `order`, desktop/mobile image và CTA đã tồn tại.

### Footer và social

Giữ `footer_columns`, `footer_links`, `social_links`. Không hợp nhất với menu trong
Phase 3A vì sẽ tăng rủi ro backfill mà chưa đem lại lợi ích runtime rõ ràng.

## Chiến lược chuyển đổi production

1. Chỉ tạo migration mới có timestamp hiện tại; không sửa migration batch 1.
2. Phase 3A thiết lập canonical System settings, audit và migrate riêng global keys.
3. Phase 3B tạo page/section/item schema và model, chưa đổi frontend read path.
4. Phase 3C backfill idempotent homepage từ `wp_settings` trong command/service có
   dry-run, transaction và báo cáo record lỗi.
5. Phase 3D dual-read: ưu tiên structured data, fallback settings khi chưa backfill.
6. Phase 3E chuyển admin writes sang structured data, đồng thời compatibility-write
   settings trong một giai đoạn ngắn nếu rollback ứng dụng còn cần.
7. Phase 3F dừng legacy writes, xác minh parity rồi mới đề xuất xóa key cũ.

## Trình tự lát cắt khóa

### 3A — Canonical settings foundation

- System sở hữu model/service settings canonical;
- audit conflict và dry-run global key migration;
- bỏ qua `home_*`; không xóa `wp_settings`;
- switch caller chỉ sau khi dữ liệu canonical được xác minh.

### 3B — Core page/section schema

- corrective migrations mới;
- model/relationship/cast/constraint tests;
- fresh SQLite migration test và MySQL upgrade dry-run;
- không thay đổi UI hoặc dữ liệu production.

### 3C — Homepage backfill

- ánh xạ 10 section hiện hữu theo đúng thứ tự;
- chuyển category/product IDs thành section items có position;
- chuyển promo/newsletter/trust badges thành config/items đã validate;
- dry-run, idempotency và orphan report bắt buộc.

### 3D — Dual-read storefront

- structured homepage query contract;
- fallback về settings;
- parity test HTML/query result cho dữ liệu hiện tại.

### 3E — Admin structured writes

- update/reorder transaction;
- permission `website.home.manage` giữ nguyên;
- optimistic/concurrent update policy;
- rollback compatibility.

### 3F — Existing CMS table corrections

- banner scheduling/alt text nếu UI cần;
- menu reference strategy sau audit caller;
- không thay footer/social chỉ để đồng nhất hình thức.

## Cổng kiểm thử Phase 3

- migrate fresh bằng SQLite và MySQL-compatible schema;
- upgrade từ bản sao schema hiện tại;
- rollback migration 3A không ảnh hưởng bảng legacy;
- unique/FK/index và reorder transaction;
- backfill chạy lặp không nhân đôi dữ liệu;
- missing Product/Category/Post tạo report, không làm hỏng migration;
- structured/legacy parity;
- không mất settings hoặc file media;
- backup và restore rehearsal trước khi switch production writes.

## Ngoài phạm vi

- Không sửa hoặc đổi tên migration đã chạy.
- Không xóa key `home_*` trong lát cắt đầu.
- Không thay schema Product/Order/Post/User.
- Không thiết kế lại giao diện admin/frontend.
- Không chạy migration production khi chưa có phê duyệt riêng.

## Đề xuất

Triển khai **3A — Canonical settings foundation** trước, sau đó mới tạo core
page/section schema ở 3B. Cả hai lát cắt đều additive và không xóa legacy data.
