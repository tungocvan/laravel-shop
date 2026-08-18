# Muasamcong — Route Reference

Tài liệu này mô tả các route hiện có của module `Muasamcong`, mục đích nghiệp vụ, middleware, handler và dữ liệu liên quan. `Modules/Muasamcong/routes/web.php` và `Modules/Muasamcong/routes/api.php` là source of truth cuối cùng.

## 1. Middleware tổng quát

Web routes dùng:

```php
config('muasamcong.route_middleware', ['web', 'auth:admin'])
```

Prefix:

```text
/admin/muasamcong
```

Route name prefix:

```text
muasamcong.
```

Nhóm route xem dữ liệu dùng:

```php
config('muasamcong.view_middleware', ['permission:view_muasamcong,admin'])
```

Nhóm cấu hình dùng:

```php
config('muasamcong.config_middleware', ['permission:muasamcong.config.manage,admin'])
```

Lưu ý: một số Livewire action thay đổi dữ liệu còn kiểm tra permission server-side riêng, điển hình `muasamcong.pricing.sync`.

---

## 2. Dashboard / Smart Pricing

### `GET /admin/muasamcong`

```text
name    : muasamcong.index
handler : MuasamcongController@index
access  : view_middleware
```

Mục đích:

- entry page của module;
- tra cứu Smart Pricing;
- hiển thị dữ liệu thuốc/KQLCNT;
- dùng snapshot database-first;
- chọn record để đồng bộ;
- điều hướng đến synced/wishlist/contractor/config.

Page shell:

```text
Muasamcong::muasamcong
```

### `POST /admin/muasamcong/pricing/export-selected`

```text
name    : muasamcong.pricing.export-selected
handler : MuasamcongController@exportSelectedPricing
access  : view_middleware
```

Mục đích:

- xuất Excel các kết quả Smart Pricing đang được checkbox chọn;
- không phải route export của `/synced`;
- request phải chứa ID/source key hợp lệ theo implementation hiện tại.

### `DELETE /admin/muasamcong/pricing/history/item`

```text
name    : muasamcong.pricing.history.destroy
handler : PricingSearchHistoryController@destroy
access  : view_middleware
```

Mục đích:

- xóa một snapshot/lịch sử tra cứu Smart Pricing;
- dùng để quản lý `Tra cứu gần đây`.

### `DELETE /admin/muasamcong/pricing/history`

```text
name    : muasamcong.pricing.history.clear
handler : PricingSearchHistoryController@clear
access  : view_middleware
```

Mục đích:

- clear toàn bộ lịch sử Smart Pricing theo scope implementation;
- không đồng nghĩa xóa `muasamcong_pricing_results` đã đồng bộ.

---

## 3. Nhà thầu / Contractor history

### `GET /admin/muasamcong/contractors`

```text
name    : muasamcong.contractors
handler : MuasamcongController@contractors
access  : view_middleware
```

Mục đích:

- màn hình tra cứu nhà thầu;
- tra theo tên, contractor code `vn...`, mã số thuế;
- đọc lịch sử đã lưu trước khi quyết định gọi upstream;
- hỗ trợ queue cho truy vấn lớn.

Page shell:

```text
Muasamcong::contractors
```

### `GET /admin/muasamcong/contractors/history`

```text
name    : muasamcong.contractors.history
handler : MuasamcongController@contractorSearches
access  : view_middleware
```

Mục đích:

- danh sách các lần tra cứu nhà thầu đã persist;
- xem lại mà không phải gọi upstream lại;
- có thể hiển thị file danh mục đã tạo/lưu nếu nghiệp vụ có dữ liệu.

### `GET /admin/muasamcong/contractors/history/{contractorSearch}`

```text
name    : muasamcong.contractors.history.show
handler : MuasamcongController@contractorSearchDetail
binding : ContractorSearch
access  : view_middleware
```

Mục đích:

- mở chi tiết một lịch sử nhà thầu đã lưu;
- dùng route model binding `ContractorSearch`.

### `GET /admin/muasamcong/contractors/{contractorCode}/kqlcnt/{notifyNo}/manual-lots`

```text
name    : muasamcong.contractors.manual-lots.show
handler : MuasamcongController@manualContractorLots
access  : view_middleware
```

Validation quan trọng trong controller:

```text
contractorCode phải dạng ^vn\d+$
notifyNo chỉ cho A-Z, a-z, 0-9, _, -
```

Mục đích:

- xem danh mục lô/thuốc đã được người dùng xác nhận cho một nhà thầu + TBMT;
- chỉ hiển thị dữ liệu đã persist;
- không tự suy đoán mapping lô ↔ winner.

### `GET /admin/muasamcong/contractors/{contractorCode}/kqlcnt/{notifyNo}/manual-lots/download`

```text
name    : muasamcong.contractors.manual-lots.download
handler : MuasamcongController@downloadManualContractorLots
access  : view_middleware
```

Mục đích:

- tải danh mục lô/thuốc đã xác nhận thành file;
- tập hợp field thuốc, giá, số lượng, nhà thầu, chủ đầu tư, quyết định, NSX và nguồn xác minh;
- có dòng tổng cộng số lượng/thành tiền theo implementation controller.

---

## 4. HSMT

### `GET /admin/muasamcong/hsmt`

```text
name    : muasamcong.hsmt
handler : MuasamcongController@hsmt
access  : view_middleware
```

Mục đích:

- màn hình tra cứu/thao tác HSMT;
- tải catalogue theo TBMT;
- reuse server snapshot nếu đã có;
- chỉ gọi upstream khi snapshot chưa tồn tại hoặc nghiệp vụ yêu cầu refresh;
- phục vụ đối chiếu với KQLCNT/Smart Pricing.

---

## 5. Danh sách đã đồng bộ

### `GET /admin/muasamcong/synced`

```text
name    : muasamcong.synced
handler : MuasamcongController@synced
access  : view_middleware
```

Mục đích:

- quản trị `PricingResult` đã đồng bộ;
- search + pagination;
- sửa metadata KQLCNT;
- nhập dữ liệu báo giá thủ công;
- bulk selection/delete;
- chọn Export Profile;
- xuất Excel hoặc BBG.

Tài liệu chi tiết:

```text
docs/modules/Muasamcong/SYNCED.md
```

### `POST /admin/muasamcong/synced/export-selected`

```text
name    : muasamcong.synced.export-selected
handler : SyncedPricingExportController::__invoke
access  : view_middleware
```

Input tối thiểu:

```text
selected_ids[]      required array
export_profile_id   nullable integer
```

Backend flow:

```text
validate IDs
  ↓
load Export Profile theo admin user
  ↓
whitelist column definitions
  ↓
query PricingResult theo selected_ids
  ↓
apply profile: order/header/type/decimal/alignment/width
  ↓
optional Header/Footer + logo + chữ ký
  ↓
PhpSpreadsheet
  ↓
XLSX download
```

Security:

- không lấy raw row data từ browser;
- profile phải thuộc user đang đăng nhập;
- column key phải thuộc whitelist `SyncedPricingExportPreferenceService::COLUMNS`.

### `POST /admin/muasamcong/synced/export-bbg`

```text
name    : muasamcong.synced.export-bbg
handler : SyncedPricingBbgExportController::__invoke
access  : view_middleware
```

Mục đích:

- xuất các bản ghi đã chọn theo format BBG chuyên biệt;
- khác với configurable Excel exporter: BBG ưu tiên một bố cục nghiệp vụ cố định/template-style.

---

## 6. Wishlist

### `GET /admin/muasamcong/wishlist`

```text
name    : muasamcong.wishlist
handler : MuasamcongController@wishlist
access  : view_middleware
```

Mục đích:

- xem danh sách thuốc/record người dùng đánh dấu theo dõi;
- dữ liệu scoped theo user theo implementation model/service.

### `POST /admin/muasamcong/wishlist/export-selected`

```text
name    : muasamcong.wishlist.export-selected
handler : PricingWishlistBulkController@export
access  : view_middleware
```

Mục đích:

- xuất Excel các wishlist record đã checkbox chọn.

### `DELETE /admin/muasamcong/wishlist/selected`

```text
name    : muasamcong.wishlist.destroy-selected
handler : PricingWishlistBulkController@destroy
access  : view_middleware
```

Mục đích:

- xóa hàng loạt wishlist đã chọn;
- phải giữ scope user, không xóa wishlist của user khác.

---

## 7. Config / Personal Page Session

### `GET /admin/muasamcong/config`

```text
name    : muasamcong.config
handler : MuasamcongController@config
access  : config_middleware
```

Mục đích:

- quản trị cấu hình tích hợp;
- Personal Page Session/cookie workflow;
- environment/config doctor;
- tạo link/token hỗ trợ cập nhật session.

Route này yêu cầu quyền cao hơn route xem dữ liệu.

### `GET /admin/muasamcong/session-tool/windows`

```text
name    : muasamcong.session-tool.windows
handler : MuasamcongController@downloadWindowsSessionTool
access  : config_middleware
```

Mục đích:

- sinh/tải package Windows tool để lấy Personal Page Session từ trình duyệt người dùng;
- package được build server-side từ `Modules/Muasamcong/tools/windows`.

Không hard-code secret/cookie vào package.

---

## 8. API routes

### `POST /api/muasamcong/update-cookie`

```text
name       : muasamcong.session-import
handler    : PersonalSessionImportController::__invoke
middleware : api, throttle:6,1
```

Mục đích:

- endpoint nhận Personal Page Session do Windows tool gửi lên;
- dùng token/link cập nhật ngắn hạn theo implementation;
- throttle 6 request/phút.

Đây là endpoint đặc biệt, không nằm trong `auth:sanctum` group chung.

### `GET /api/muasamcong`

```text
handler    : Api\MuasamcongController@index
middleware : config('muasamcong.api_middleware', ['api', 'auth:sanctum'])
```

Mục đích:

- API index/status của module.

### `POST /api/muasamcong/search-pricing`

```text
handler    : Api\MuasamcongController@searchPricing
middleware : config('muasamcong.api_middleware', ['api', 'auth:sanctum'])
```

Mục đích:

- cung cấp Smart Pricing search qua API authenticated;
- không dùng thay thế cho UI state nếu màn hình admin đã có snapshot database-first.

---

## 9. Route → domain map

```text
/admin/muasamcong
    Smart Pricing / PricingSearchSnapshot

/admin/muasamcong/contractors*
    ContractorSearch / ContractorBid / KQLCNT / ContractorManualLot

/admin/muasamcong/hsmt
    HSMT detail/catalogue/server snapshot

/admin/muasamcong/synced*
    PricingResult / SyncedExportProfile / Excel/BBG

/admin/muasamcong/wishlist*
    PricingWishlist

/admin/muasamcong/config
    integration config / PersonalSession

/api/muasamcong/update-cookie
    PersonalSession import token/session update
```

---

## 10. Quy tắc khi thêm route mới

Khi thêm route vào module:

1. Đặt đúng group quyền: view hay config/manage.
2. Dùng route name `muasamcong.*` nhất quán.
3. Mutation phải có server-side authorization, không chỉ dựa vào ẩn button.
4. Không đưa secret/token/cookie vào query string nếu tránh được.
5. Download/export phải validate ID và query lại DB.
6. Route có parameter phải validate format/binding.
7. Cập nhật `MuasamcongRouteAuthorizationTest` hoặc test route tương ứng.
8. Cập nhật file `ROUTES.md` này.

Kiểm tra route:

```bash
php artisan route:list --path=muasamcong
php artisan test tests/Feature/Muasamcong/MuasamcongRouteAuthorizationTest.php
```
