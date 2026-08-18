# Muasamcong — `/admin/muasamcong/synced`

## 1. Mục đích

`/admin/muasamcong/synced` là màn hình quản trị các bản ghi Smart Pricing/KQLCNT đã được người dùng chọn và đồng bộ về database nội bộ. Đây là lớp dữ liệu ổn định để tiếp tục hiệu chỉnh, lập báo giá, xuất Excel/BBG và tái sử dụng mà không phải gọi lại API Mua sắm công.

Luồng tổng quát:

```text
Smart Pricing / KQLCNT
        ↓
Người dùng chọn bản ghi
        ↓
Đồng bộ
        ↓
muasamcong_pricing_results
        ↓
/admin/muasamcong/synced
        ├─ tìm kiếm
        ├─ chọn nhiều bản ghi
        ├─ sửa dữ liệu bổ sung
        ├─ xóa hàng loạt
        ├─ cấu hình Export Profile
        ├─ xuất Excel cấu hình động
        └─ xuất BBG
```

## 2. Route chính

```text
GET  /admin/muasamcong/synced
name: muasamcong.synced
```

Controller chỉ trả page shell; nghiệp vụ tương tác chính nằm trong Livewire component `Modules\Muasamcong\Livewire\SyncedPricingList`.

Các route export liên quan:

```text
POST /admin/muasamcong/synced/export-selected
name: muasamcong.synced.export-selected
handler: SyncedPricingExportController

POST /admin/muasamcong/synced/export-bbg
name: muasamcong.synced.export-bbg
handler: SyncedPricingBbgExportController
```

Tất cả route trên nằm dưới middleware module và middleware quyền xem Muasamcong. Quyền thay đổi dữ liệu trong Livewire còn được kiểm tra bằng permission `muasamcong.pricing.sync`.

## 3. Dữ liệu nguồn

Model chính:

```text
Modules\Muasamcong\Models\PricingResult
```

Bản ghi đồng bộ giữ snapshot các thông tin quan trọng như:

- tên thuốc;
- nhóm thuốc;
- hoạt chất;
- nồng độ/hàm lượng;
- đường dùng;
- dạng bào chế;
- đơn vị tính;
- quy cách đóng gói;
- GĐKLH/GPNK;
- hạn dùng;
- cơ sở/nước sản xuất;
- giá trúng thầu;
- số lượng;
- mã/tên đơn vị trúng thầu;
- chủ đầu tư/bên mời thầu;
- mã TBMT;
- hình thức dự thầu;
- địa điểm;
- số/ngày quyết định;
- ngày đăng KQLCNT;
- metadata đồng bộ.

Ngoài dữ liệu upstream, màn hình cho phép bổ sung thủ công ba field phục vụ báo giá vì nguồn Mua sắm công không cung cấp đầy đủ:

```text
stt_tt20_2022
 gia_kk_kkl
 don_gia_vat
```

## 4. Tìm kiếm và phân trang

`SyncedPricingList` query trực tiếp database nội bộ, không gọi upstream API.

Tìm kiếm hiện hỗ trợ các nhóm thông tin chính:

- tên thuốc;
- hoạt chất;
- nhóm thuốc;
- mã TBMT;
- chủ đầu tư/bên mời thầu;
- số quyết định;
- tên/mã nhà thầu trúng;
- STT TT20/2022.

Pagination mặc định là 20 bản ghi/trang.

## 5. Checkbox và bulk actions

Component giữ `selectedIds` trong Livewire state.

Hỗ trợ:

- chọn từng dòng;
- chọn/bỏ chọn toàn bộ trang hiện tại;
- giữ selection khi thao tác trong component;
- hiển thị tổng số bản ghi đang chọn;
- `Sửa đã chọn`: yêu cầu đúng 1 bản ghi;
- `Xóa đã chọn`: hỗ trợ nhiều bản ghi;
- `Xuất Excel`: xuất đúng các ID đã chọn;
- `Xuất BBG`: xuất đúng các ID đã chọn.

Không được dùng checkbox UI làm nguồn dữ liệu duy nhất cho backend. Mọi export/delete phải gửi ID và backend phải query lại database.

## 6. Modal Sửa

Modal sửa cho phép cập nhật metadata KQLCNT và dữ liệu báo giá bổ sung.

Các field KQLCNT có thể chỉnh:

```text
Đơn vị trúng thầu
Mã nhà thầu
Số quyết định
Ngày ban hành quyết định
```

Các field bổ sung thủ công:

```text
STT TT20/2022
Giá KK / KKL
Đơn giá (VAT)
```

Nguyên tắc:

- không sửa payload upstream ngoài phạm vi field cho phép;
- dữ liệu số phải validate `numeric`, `min:0`;
- ngày lưu dạng database date/datetime;
- sau save phải phản hồi rõ trạng thái thành công/thất bại.

## 7. Export Profile — cấu hình xuất Excel động

Đây là phần có thể tái sử dụng cho module khác.

Service trung tâm:

```text
Modules\Muasamcong\Services\SyncedPricingExportPreferenceService
```

Model profile:

```text
Modules\Muasamcong\Models\SyncedExportProfile
```

Mỗi user có thể có nhiều profile độc lập. Một profile lưu:

```text
name
is_default
column_order
selected_columns
headers
alignments
widths
data_types
decimals
header_footer
logo_path
signature_path
```

### 7.1 Cấu hình từng cột

Mỗi cột có thể thiết lập:

- bật/tắt;
- kéo thả thay đổi thứ tự;
- rename header;
- type `Auto | Number | String | Date`;
- Decimal `0..6` cho Number;
- alignment `left | center | right`;
- width theo pixel.

Toàn bộ cell dữ liệu mặc định Wrap Text và row height để Excel tự tính.

### 7.2 Quy tắc kiểu dữ liệu

`Auto`:

- giữ cách ghi mặc định từ value.

`Number`:

- ghi cell bằng kiểu numeric;
- Decimal = 0 => format `#,##0`;
- Decimal = 2 => format `#,##0.00`;
- không convert number thành chuỗi chỉ để tạo dấu phân cách.

`String`:

- dùng explicit string;
- format `@`;
- dùng cho mã có thể mất leading zero hoặc bị Excel chuyển scientific notation.

`Date`:

- ghi Excel serial date;
- format `dd/mm/yyyy`.

Riêng `GĐKLH / GPNK` phải ép String dù cấu hình sai để tránh Excel tự chuyển sang số.

### 7.3 Quy tắc Nhóm thuốc

Khi export, nhóm thuốc được normalize về phần số:

```text
Nhóm 1  -> 1
NHÓM 4  -> 4
N2      -> 2
```

Không thay đổi dữ liệu database; chỉ normalize ở tầng export.

## 8. Nhiều cấu hình và nhân đôi

Người dùng có thể:

- tạo profile mới;
- chọn profile đang dùng;
- đặt profile mặc định;
- lưu lại;
- xóa;
- nhân đôi.

Nhân đôi phải copy toàn bộ:

- selection cột;
- order;
- header rename;
- alignment;
- width;
- type;
- decimal;
- Header/Footer;
- logo;
- chữ ký.

Bản sao không tự trở thành default.

## 9. Header/Footer của Excel báo giá

Khi `header_footer.enabled = true`, bảng dữ liệu bắt đầu từ dòng 9.

Bố cục chuẩn:

```text
A1:B5       Logo công ty

C1:last     Tên công ty
C2:last     Địa chỉ
C3:last     Mã số thuế
C4:last     Số điện thoại
C5:last     Email

A6:last     BẢNG BÁO GIÁ
A7:last     Kính gửi: ...
A8:last     Nội dung giới thiệu

A9:last     Header bảng
A10...      Dữ liệu
```

Footer cách bảng một dòng và dùng 3 cột cuối:

```text
3 cột cuối  Tp.HCM, ngày…..tháng…...năm YYYY
3 cột cuối  GIÁM ĐỐC CÔNG TY
3 cột cuối  [ẢNH CHỮ KÝ]
3 cột cuối  [HỌ VÀ TÊN NGƯỜI KÝ]
```

Năm để trống thì lấy năm hiện tại.

Logo và chữ ký:

- lưu private storage;
- chỉ lưu path trong profile;
- export bằng PhpSpreadsheet `Drawing`;
- giữ tỷ lệ ảnh;
- canh giữa vùng merge;
- không đưa binary/base64 vào database profile.

Font workbook khi xuất báo giá: `Times New Roman`, mặc định size 11.

## 10. Cấu trúc code nên giữ

```text
Livewire UI
  SyncedPricingList
      ↓
Export profile service
  SyncedPricingExportPreferenceService
      ↓
SyncedExportProfile

POST export-selected
      ↓
SyncedPricingExportController
      ↓
PricingResult query theo selected_ids
      ↓
PhpSpreadsheet
      ↓
XLSX download
```

Controller export không được tin dữ liệu cell từ browser. Browser chỉ gửi:

```text
selected_ids[]
export_profile_id
```

Backend tự load profile theo user và query record theo ID.

## 11. Security invariants

- Profile phải scoped bằng `user_id`.
- Không cho user sử dụng `export_profile_id` của user khác.
- Upload logo/chữ ký phải validate image type + size.
- File ảnh lưu private disk.
- Không serialize secret/upstream cookie/token vào profile.
- Export chỉ lấy field đã whitelist trong `COLUMNS`.
- Không nhận tên column/raw SQL từ request.
- Bulk delete phải authorize server-side.

## 12. UX invariants

- Nút export disabled khi chưa chọn record.
- Profile có thể cấu hình ngay cả khi chưa chọn record.
- Save profile không tự export.
- Dropdown profile phải thể hiện profile mặc định.
- Thao tác save/update/delete/duplicate cần feedback rõ ràng.
- Với thao tác lưu quan trọng nên dùng success modal/toast chung thay vì chỉ text nhỏ trên trang.
- Drag/drop phải có `wire:key` ổn định.
- Width hiển thị bằng px để người dùng dễ hình dung.

## 13. Kiểm thử tối thiểu

Targeted test nên bao phủ:

```text
profile create/update
multiple profiles/user
profile ownership
set default
duplicate profile
delete profile
column order
selected columns
custom header
alignment
width clamp
data type normalize
decimal clamp
Header/Footer persistence
logo/signature path persistence
Number format
String preservation
Date dd/mm/yyyy
GĐKLH forced text
route authorization
export selected IDs only
```

Lệnh kiểm tra:

```bash
vendor/bin/pint --test Modules/Muasamcong tests/Feature/Muasamcong
php artisan test tests/Feature/Muasamcong
```

## 14. File quan trọng

```text
Modules/Muasamcong/Livewire/SyncedPricingList.php
Modules/Muasamcong/resources/views/livewire/synced-pricing-list.blade.php
Modules/Muasamcong/resources/views/livewire/partials/synced-export-config-modal.blade.php
Modules/Muasamcong/Services/SyncedPricingExportPreferenceService.php
Modules/Muasamcong/Models/SyncedExportProfile.php
Modules/Muasamcong/Http/Controllers/SyncedPricingExportController.php
Modules/Muasamcong/Http/Controllers/SyncedPricingBbgExportController.php
Modules/Muasamcong/routes/web.php
```

## 15. Nguyên tắc khi mở rộng

Không copy nguyên class `SyncedPricing...` sang module khác. Hãy dùng skill chung `.codex/skills/configurable-excel-export/SKILL.md` để lấy pattern và đổi domain-specific mapping. Mục tiêu lâu dài là tách phần profile/export engine thành reusable infrastructure nếu từ hai module trở lên cùng sử dụng.
