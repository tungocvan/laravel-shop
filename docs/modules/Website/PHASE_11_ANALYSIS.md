# Phase 11 Analysis — Homepage Builder System

## 1. Mục tiêu

Phase 11 chuẩn hóa `/admin/homepage-settings` theo cùng triết lý đã áp dụng cho Header và Footer:

- không hardcode renderer/section metadata ở nhiều nơi;
- Builder thao tác trên state trước, chỉ publish khi người quản trị bấm lưu;
- hỗ trợ drag/drop, duplicate, hide/restore an toàn;
- có Responsive Preview Desktop/Mobile;
- có Presentation System và Layout Themes;
- mọi form admin mới/refactor phải tuân `ADMIN_UI_INPUT_STANDARD.md`;
- từng bước rút compatibility `home_*` sau khi structured homepage đã ổn định.

Phase 11 không xây Homepage lại từ đầu. Nền dữ liệu `WebsitePage -> WebsiteSection -> WebsiteSectionItem` hiện tại được giữ và trở thành source-of-truth dài hạn.

---

## 2. Hiện trạng đã xác nhận

Homepage hiện có:

- `Modules/Website/Livewire/Admin/Home/HomeSettings.php`;
- `HomepageContentService` đọc structured sections và fallback `home_*` settings;
- `HomepageContentWriteService` compatibility-write `home_*` rồi backfill structured data;
- `HomepageSectionManagerService` hỗ trợ duplicate/remove/restore;
- frontend `HomeList` render section theo thứ tự;
- visibility hỗ trợ `all`, `desktop`, `mobile`, `none/hidden`;
- caching composition qua `HomepageContentService::CACHE_KEY`.

Các điểm này tiếp tục được giữ làm nền cho Phase 11.

---

## 3. Vấn đề kiến trúc hiện tại

### 3.1 Section metadata và renderer bị hardcode nhiều nơi

Các section canonical đang xuất hiện lặp lại trong:

- property `$layout` của `HomeSettings`;
- mảng `$sections` trong Blade Admin;
- `HomepageContentService::sectionKeys()`;
- `HomepageSectionManagerService::isCanonical()`;
- `@switch($sectionType)` tại frontend `home-list.blade.php`.

Hệ quả:

- thêm section type mới phải sửa nhiều file;
- metadata Admin và renderer frontend có thể lệch nhau;
- khó kiểm soát section nào duplicatable/configurable;
- test contract dễ phụ thuộc implementation.

### 3.2 Builder mutation chưa preview-first

Hiện `duplicateSection()`, `removeSection()`, `restoreSection()` gọi `HomepageSectionManagerService` và thay đổi DB ngay.

Trong khi UI có thể khiến người dùng hiểu rằng phải bấm "Lưu thay đổi" mới publish.

Contract cần sửa thành:

```text
Builder interaction
    -> local Builder state
    -> preview
    -> Save
    -> transaction persist
```

### 3.3 SortableJS CDN trong Admin

Homepage Admin đang load SortableJS trực tiếp từ CDN.

Phase 11 không phụ thuộc CDN cho core Builder interaction. Drag/drop phải dùng cơ chế nội bộ/native giống contract đã ổn định ở Header/Footer.

### 3.4 Hai nguồn dữ liệu còn tồn tại song song

Các key legacy như:

```text
home_show_*
home_category_ids
home_featured_ids
home_new_arrivals_count
home_best_sellers_count
home_blog_count
home_promo_banner
home_newsletter
home_trust_badges
```

vẫn được đọc/ghi để rollback compatibility.

Không xóa ngay trong 11A–11E. Việc rút legacy chỉ thực hiện ở 11F sau khi Builder mới đã qua test và UI regression.

### 3.5 Responsive visibility có hardcode ngoài engine

`trust_badges` frontend hiện có wrapper `hidden md:block`, làm `mobile/all` trong Admin không có hiệu lực đầy đủ.

Visibility phải chỉ do một engine quyết định.

### 3.6 Chưa có Homepage Presentation System

Frontend hiện còn các giá trị layout chung hardcode như container, padding và section gap.

Cần đưa về `homepage.presentation` và resolver có bounds/presets như Header/Footer.

### 3.7 UI Admin chưa đồng bộ chuẩn input mới

Homepage Admin phải áp dụng:

`docs/modules/Website/ADMIN_UI_INPUT_STANDARD.md`

đặc biệt cho input/select/textarea, repeatable cards, product picker, empty state và save action.

---

## 4. Kiến trúc đích

### 4.1 HomepageSectionRegistry

Tạo service trung tâm, ví dụ:

```php
HomepageSectionRegistry
```

Registry chịu trách nhiệm cung cấp metadata section type:

```php
[
    'hero' => [
        'label' => 'Banner Slider',
        'description' => 'Slider chính đầu trang',
        'renderer' => 'website.home.hero-banner',
        'duplicatable' => true,
        'configurable' => true,
    ],
]
```

Renderer path chỉ tồn tại trong Registry/config, không persist vào DB/theme.

Admin và frontend cùng resolve từ Registry.

### 4.2 Builder state

Builder cần một state riêng, tối thiểu:

```php
$builderSections = [
    [
        'key' => 'hero',
        'type' => 'hero',
        'visibility' => 'all',
        'enabled' => true,
        'config' => [],
    ],
];
```

Các thao tác reorder/duplicate/remove/restore chỉ thay state này cho tới khi Save.

### 4.3 Renderer

Frontend không giữ `@switch` dài.

Luồng mong muốn:

```text
WebsiteSection
   -> HomepageSectionRegistry::resolve(type)
   -> renderer/component
   -> sanitized config/data
```

Unknown section type phải được skip an toàn thay vì throw ở storefront.

### 4.4 Section Settings Panel

Thay vì dồn config vào một form lớn, Builder cho chọn từng section và mở panel cấu hình tương ứng.

Ví dụ Product Grid:

```text
Section Settings
- Tiêu đề
- Nguồn dữ liệu
- Giới hạn sản phẩm
- Sản phẩm được chọn
- Visibility
- Presentation
```

Config schema cụ thể do section definition quản lý.

### 4.5 Responsive Preview

Builder phải có:

```text
Desktop | Mobile
```

Preview phản ánh state chưa publish.

Preview không được ghi DB.

### 4.6 Homepage Presentation

Thêm setting:

```text
homepage.presentation
```

Baseline đề xuất:

```php
[
    'mode' => 'basic',
    'container' => 'standard',
    'spacing' => 'comfortable',
    'background' => '#ffffff',
    'custom' => [
        'container_width' => 1280,
        'padding_top' => 32,
        'padding_bottom' => 32,
        'section_gap' => 48,
    ],
]
```

Resolver phải clamp values trong bounds.

Global font-size/font-family vẫn thuộc global design tokens, không duplicate vào homepage presentation.

---

## 5. Homepage Layout Themes

Setting:

```text
homepage.layout_themes
```

Theme snapshot chỉ được chứa layout/presentation, không chứa business content.

Schema:

```php
[
    'version' => 1,
    'name' => 'Commerce Classic',
    'layout' => [...],
    'presentation' => [...],
    'updated_at' => '...',
]
```

Theme KHÔNG chứa:

- product IDs;
- category IDs;
- banner images;
- newsletter text;
- trust badge content;
- renderer/view paths.

Apply theme chỉ nạp vào Builder + Preview.

Storefront chỉ đổi sau khi bấm Save/Publish.

---

## 6. UI Input Standard

Mọi input mới/refactor trong Homepage Admin bắt buộc theo:

```text
docs/modules/Website/ADMIN_UI_INPUT_STANDARD.md
```

Baseline input:

```text
rounded-lg
border border-gray-300
bg-white
px-3 py-2.5
text-sm text-gray-900
shadow-sm
hover:border-gray-400
focus:border-blue-500
focus:ring-2 focus:ring-blue-100
```

Các form dài phải có sticky primary save action.

Repeatable collections phải có:

- card boundary rõ;
- label cho từng field;
- add/remove controls rõ;
- empty state;
- validation inline.

---

## 7. Data ownership

### Structured source-of-truth dài hạn

- `website_pages`
- `website_sections`
- `website_section_items`

### Legacy compatibility tạm thời

- `home_*` settings

11A–11E tiếp tục dual-write/backfill để rollback an toàn.

11F mới xem xét cutover hoàn toàn.

---

## 8. Cache contract

Mọi persist thành công liên quan homepage composition phải invalidate:

```php
HomepageContentService::clearCache();
```

Preview state không được invalidate production cache vì chưa publish.

Frontend cache không được chứa Builder draft.

---

## 9. Authorization

Mọi mutation persistent hoặc Builder mutation quan trọng phải tiếp tục dùng permission:

```text
website.home.manage
```

Không cho client cung cấp renderer, model class hoặc arbitrary service identifier.

Server phải validate section key/type qua Registry.

---

## 10. Kế hoạch Phase 11

### Phase 11A — Homepage Section Registry & Renderer

Mục tiêu:

- tạo Registry;
- gom section metadata về một source;
- frontend render qua Registry;
- loại `@switch` renderer dài;
- giữ UI/storefront tương đương hiện tại.

### Phase 11B — Builder State & Safe Drag/Drop

Mục tiêu:

- draft Builder state;
- native/internal drag/drop;
- duplicate/remove/restore preview-first;
- Save mới persist;
- không dùng CDN SortableJS cho core behavior.

### Phase 11C — Section Configuration & Admin UI Standard

Mục tiêu:

- section settings panel;
- chuẩn hóa data pickers;
- UI Input Standard;
- sticky save;
- giảm query trực tiếp trong `render()`.

### Phase 11D — Presentation & Responsive Preview

Mục tiêu:

- `homepage.presentation`;
- presets/bounds;
- Desktop/Mobile preview;
- bỏ hardcode container/section gap;
- visibility chỉ có một source.

### Phase 11E — Homepage Layout Themes

Mục tiêu:

- Save / Apply / Update / Rename / Delete theme;
- theme chỉ chứa layout + presentation;
- preview trước publish;
- có thể bổ sung demo theme seeder sau khi schema ổn định.

### Phase 11F — Structured Content Consolidation

Mục tiêu:

- đánh giá rollback window;
- dừng dual-write khi đủ an toàn;
- giảm hoặc bỏ legacy `home_*`;
- migration/backfill final nếu cần;
- regression tests cho storefront/admin.

---

## 11. Test strategy

Không chạy toàn project.

Chỉ chạy test Website/Homepage và các test liên quan trực tiếp đến service được thay đổi.

Mỗi sub-phase cần có configuration/behavior tests riêng.

Gate cuối Phase 11:

```bash
php artisan test tests/Feature/Website
```

chỉ chạy sau khi targeted tests và UI đều PASS.

---

## 12. Acceptance criteria tổng thể

Phase 11 chỉ được xem là hoàn thành khi:

- section renderer không hardcode phân tán;
- Builder reorder/duplicate/remove không publish trước Save;
- Desktop/Mobile visibility đúng thực tế;
- responsive preview hoạt động;
- homepage themes không chứa business content;
- UI Admin tuân input standard;
- storefront không regression;
- structured homepage là source-of-truth rõ ràng;
- legacy compatibility có kế hoạch đóng cụ thể.

---

## 13. Quyết định triển khai

Thứ tự bắt buộc:

```text
11A Registry
 -> 11B Builder lifecycle
 -> 11C Section settings/UI
 -> 11D Presentation/Preview
 -> 11E Themes
 -> 11F Legacy consolidation
```

Không triển khai themes trước Registry/Builder lifecycle.
Không xóa legacy `home_*` trước 11F.
Không thay đổi business content khi apply layout theme.
