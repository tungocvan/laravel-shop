# SKILL — Configurable Excel Export Profiles

## 1. Mục tiêu

Skill này hướng dẫn AI/Codex triển khai chức năng **xuất Excel có cấu hình lưu lại theo user** cho bất kỳ module Laravel nào trong repository.

Nguồn tham khảo đầu tiên trong repository:

```text
Modules/Muasamcong/Services/SyncedPricingExportPreferenceService.php
Modules/Muasamcong/Models/SyncedExportProfile.php
Modules/Muasamcong/Livewire/SyncedPricingList.php
Modules/Muasamcong/Http/Controllers/SyncedPricingExportController.php
Modules/Muasamcong/resources/views/livewire/partials/synced-export-config-modal.blade.php
docs/modules/Muasamcong/SYNCED.md
```

Không copy mù code Muasamcong. Hãy tái sử dụng **pattern**, đổi mapping/domain theo module đích.

---

## 2. Khi nào dùng skill này

Dùng khi user yêu cầu một hoặc nhiều chức năng:

- checkbox chọn record để export;
- chọn cột nào được xuất;
- kéo thả thứ tự cột;
- đổi tên header;
- canh lề từng cột;
- width theo pixel;
- kiểu dữ liệu Auto/Number/String/Date;
- decimal cho Number;
- nhiều cấu hình export;
- cấu hình mặc định;
- nhân đôi cấu hình;
- Header/Footer báo giá;
- logo/chữ ký;
- export theo profile đã lưu.

Không cần dùng full skill nếu chỉ export CSV/XLSX cố định vài cột.

---

## 3. Nguyên tắc kiến trúc

Phải tách 4 trách nhiệm:

```text
1. Domain list / Livewire UI
2. Export Profile persistence
3. Export preference service
4. Export renderer/controller
```

Không để toàn bộ logic vào Livewire component.

Pattern:

```text
List Component
   │
   ├─ selectedIds
   ├─ open/save profile config
   └─ profile selector
          ↓
ExportPreferenceService
          ↓
ExportProfile Model

POST export
   ↓
ExportController
   ├─ validate selected IDs
   ├─ load profile by authenticated user
   ├─ query domain records
   ├─ map value by whitelist column definition
   └─ render XLSX
```

---

## 4. Export Profile schema

Tên bảng tùy module. Khuyến nghị:

```text
{module}_export_profiles
```

Các cột tối thiểu:

```text
id
user_id
name
is_default
column_order        JSON
selected_columns    JSON
headers             JSON
alignments          JSON
widths              JSON
data_types           JSON
decimals             JSON nullable
header_footer        JSON nullable
logo_path            string nullable
signature_path       string nullable
created_at
updated_at
```

Index:

```text
index(user_id)
index(user_id, is_default)
```

Nếu hệ thống dùng nhiều guard/user model, không hard-code foreign key nếu architecture hiện tại không dùng foreign key chéo module.

---

## 5. Model

Model phải cast các JSON field:

```php
protected $casts = [
    'is_default' => 'boolean',
    'column_order' => 'array',
    'selected_columns' => 'array',
    'headers' => 'array',
    'alignments' => 'array',
    'widths' => 'array',
    'data_types' => 'array',
    'decimals' => 'array',
    'header_footer' => 'array',
];
```

Profile phải luôn query theo `user_id`.

Không được:

```php
ExportProfile::findOrFail($profileId);
```

Phải:

```php
ExportProfile::query()
    ->where('user_id', $userId)
    ->findOrFail($profileId);
```

---

## 6. Column Definition Registry

Mỗi exporter cần một whitelist server-side.

Ví dụ:

```php
public const COLUMNS = [
    'stt' => [
        'label' => 'STT',
        'align' => 'center',
        'width' => 60,
        'type' => 'number',
    ],
    'name' => [
        'label' => 'Tên',
        'align' => 'left',
        'width' => 180,
        'type' => 'string',
    ],
    'amount' => [
        'label' => 'Số tiền',
        'align' => 'right',
        'width' => 120,
        'type' => 'number',
    ],
    'issued_at' => [
        'label' => 'Ngày',
        'align' => 'center',
        'width' => 120,
        'type' => 'date',
    ],
];
```

Không cho client truyền arbitrary database column name.

Mọi key từ profile phải normalize qua `COLUMNS`.

---

## 7. Service contract

Service nên cung cấp tối thiểu:

```text
profilesForUser(userId)
forUser(userId, profileId = null)
saveProfile(...)
duplicateProfile(userId, profileId)
deleteProfile(userId, profileId)
setDefault(userId, profileId)
```

### `forUser`

Quy tắc:

```text
Nếu profileId hợp lệ -> load đúng profile của user.
Nếu không truyền -> ưu tiên is_default = true.
Nếu user chưa có profile -> trả defaults in-memory.
```

Không tự tạo record DB chỉ vì mở page.

### `saveProfile`

Phải normalize:

- name;
- order;
- selected columns;
- custom headers;
- alignments;
- widths;
- types;
- decimals;
- Header/Footer.

Nếu profile đầu tiên của user thì có thể tự đặt default.

### `duplicateProfile`

Dùng `replicate()` hoặc copy tương đương.

Bản sao phải giữ:

```text
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

Nhưng:

```text
is_default = false
name = unique copy name
```

---

## 8. Normalize rules

### Order

- bỏ key không thuộc whitelist;
- unique;
- append các key còn thiếu ở cuối để config không hỏng khi code thêm cột mới.

### Selected columns

- intersection với whitelist;
- giữ theo `column_order`.

### Header

- trim;
- max length;
- empty => default label.

### Alignment

Chỉ nhận:

```text
left
center
right
```

### Width

UI hiển thị pixel.

Khuyến nghị clamp:

```text
min 40 px
max 600 px
```

### Type

Chỉ nhận:

```text
auto
number
string
date
```

### Decimal

Chỉ áp dụng Number.

Khuyến nghị:

```text
0..6
```

Default:

```text
0
```

---

## 9. Livewire state

Component nên có:

```php
public array $selectedIds = [];
public array $exportProfiles = [];
public ?int $activeExportProfileId = null;
public string $exportProfileName = 'Mặc định';
public bool $exportProfileDefault = false;
public array $exportColumnOrder = [];
public array $exportSelectedColumns = [];
public array $exportHeaders = [];
public array $exportAlignments = [];
public array $exportWidths = [];
public array $exportDataTypes = [];
public array $exportDecimals = [];
public array $exportHeaderFooter = [];
```

Nếu upload logo/chữ ký:

```php
use WithFileUploads;
```

Giữ upload temporary object riêng khỏi persisted path.

---

## 10. UI cấu hình

Modal configuration nên chia thành 3 vùng.

### A. Profile toolbar

```text
Profile selector
Tên cấu hình
Mặc định
+ Cấu hình mới
Nhân đôi
Xóa
```

### B. Header/Footer

Tùy nghiệp vụ:

```text
Enable Header/Footer
Logo
Company name
Address
Tax code
Phone
Email
Title
Recipient
Intro
Footer location
Footer year
Signatory title
Signature image
Signatory name
```

### C. Column editor

Mỗi row:

```text
Drag | Position | Enabled | Original label | Export header | Type | Decimal | Align | Width(px)
```

Phải có `wire:key` theo column key.

Drag/drop chỉ thay order state, không save tự động.

---

## 11. Save UX

Save config chỉ lưu profile.

Không được:

```text
Save Config -> tự download Excel
```

Phải:

```text
Save Config -> success feedback
User chọn records -> chọn profile -> Export
```

Khuyến nghị dùng shared success modal/toast:

```text
Đã lưu cấu hình thành công
Đã cập nhật dữ liệu thành công
Đã nhân đôi cấu hình thành công
```

Nếu project đã có notification component chuẩn, phải dùng component chuẩn thay vì tạo hệ thống notification riêng.

---

## 12. Export request

Browser chỉ gửi identifier:

```text
selected_ids[]
export_profile_id
```

Không gửi:

```text
raw rows
column SQL names
style PHP objects
filesystem paths
```

Controller phải query DB lại.

Validation ví dụ:

```php
$request->validate([
    'selected_ids' => ['required', 'array', 'min:1', 'max:5000'],
    'selected_ids.*' => ['required', 'integer', 'min:1'],
    'export_profile_id' => ['nullable', 'integer', 'min:1'],
]);
```

---

## 13. Typed writer

### String

```php
$cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
$cell->getStyle()->getNumberFormat()->setFormatCode('@');
```

Dùng cho:

- mã số;
- số đăng ký;
- mã có leading zero;
- chuỗi dài Excel dễ convert scientific notation.

### Number

```php
$cell->setValueExplicit((float) $value, DataType::TYPE_NUMERIC);
```

Format:

```text
Decimal 0 => #,##0
Decimal 2 => #,##0.00
```

Không pre-format bằng `number_format()` rồi ghi string nếu muốn Excel có numeric semantics.

### Date

Convert thành Excel date serial và:

```text
dd/mm/yyyy
```

Không chỉ ghi text `18/08/2026` nếu user đã chọn type Date.

### Auto

Giữ value tự nhiên.

---

## 14. Wrap Text, width, height

Default toàn vùng bảng:

```text
Wrap Text = true
Vertical = center
```

Width:

```php
$sheet->getColumnDimension($column)
    ->setAutoSize(false)
    ->setWidth($pixels, 'px');
```

Row height:

```php
$sheet->getRowDimension($row)->setRowHeight(-1);
```

Mục tiêu: width user quyết định, height tự co giãn theo nội dung.

---

## 15. Header/Footer báo giá

Nếu feature này được bật, dùng row offsets thay vì hard-code logic bảng ở row 1.

Ví dụ:

```php
$tableHeaderRow = $withHeaderFooter ? 9 : 1;
$dataStartRow = $tableHeaderRow + 1;
$dataEndRow = $dataStartRow + $items->count() - 1;
$footerStartRow = $dataEndRow + 2;
```

Mọi border/alignment/freeze pane phải dựa vào các biến này.

Không viết:

```php
freezePane('A2');
```

nếu table header có thể đổi vị trí.

Phải:

```php
freezePane('A'.$dataStartRow);
```

---

## 16. Logo và chữ ký

- validate image;
- lưu private disk;
- profile chỉ giữ path;
- dùng PhpSpreadsheet `Drawing`;
- `setResizeProportional(true)`;
- không stretch ảnh;
- không base64 ảnh trong JSON profile.

Nếu profile duplicate, có thể dùng chung asset path. Chỉ xóa physical file khi đã kiểm tra không còn profile nào tham chiếu nếu implement cleanup.

---

## 17. Font

Nếu nghiệp vụ yêu cầu font toàn workbook:

```php
$spreadsheet->getDefaultStyle()->getFont()
    ->setName('Times New Roman')
    ->setSize(11);
```

Không cần bundle font file vào application chỉ để Excel dùng font name; máy mở Excel quyết định font rendering dựa trên font đã cài.

---

## 18. Domain-specific transformations

Các transform như:

```text
Nhóm 1 -> 1
GĐKLH bắt buộc String
status code -> label
boolean -> Có/Không
```

phải đặt ở mapper/export layer, không mutate database.

Mỗi module cần ghi rõ invariants của riêng mình.

---

## 19. Authorization

Phải kiểm tra ít nhất 3 lớp:

```text
Route middleware
Livewire mutation authorization
Profile ownership / domain record scope
```

Ẩn button không phải authorization.

---

## 20. Tests bắt buộc

Tối thiểu:

```text
create profile
update profile
multiple profiles per user
cannot load another user's profile
default profile selection
duplicate profile
profile delete
order normalization
unknown key rejected/ignored
custom headers
width clamp
type normalize
decimal clamp
Header/Footer persistence
selected IDs only
number cell semantics
string preservation
date formatting
route authorization
```

Nếu exporter rất quan trọng, mở file XLSX bằng PhpSpreadsheet trong test và assert:

```text
cell value
cell data type
number format
column width
font
merge ranges
```

---

## 21. Migration compatibility

Khi thêm field mới vào export profile:

- migration additive;
- field nullable/default an toàn;
- service phải có defaults để profile cũ vẫn chạy;
- không bắt user cấu hình lại.

Ví dụ thêm `decimals` sau này:

```text
DB null -> service normalize về 0
```

---

## 22. Anti-patterns

Không làm:

```text
❌ serialize cả dataset vào Livewire public state để export
❌ gửi raw data từ browser sang export controller
❌ cho request chọn arbitrary DB column
❌ profile không scope user
❌ dùng number_format() rồi ghi number thành string
❌ hard-code row 1 khi có Header/Footer optional
❌ save config rồi export ngay
❌ duplicate mất width/type/header/footer
❌ upload logo vào public path nếu không cần public access
❌ sửa database data chỉ để đáp ứng format Excel
```

---

## 23. Quy trình triển khai cho module mới

### Phase 1 — Analyze

Xác định:

```text
Domain model
List page/component
Available fields
Auth guard
Permission
Current export library
Existing notification/UI standard
```

### Phase 2 — Design

Tạo mapping `COLUMNS` và quyết định:

```text
profile table
route names
controller/service names
header/footer requirements
special data rules
```

### Phase 3 — Persistence

Tạo migration + model + service.

### Phase 4 — UI

Thêm selection + profile selector + config modal.

### Phase 5 — Export

Tạo dedicated controller/renderer.

### Phase 6 — Tests

Targeted test trước, sau đó module regression.

### Phase 7 — Docs

Cập nhật README/route docs/module docs.

---

## 24. Naming template

Ví dụ module `Invoices`:

```text
InvoiceExportProfile
InvoiceExportPreferenceService
InvoiceExportController
InvoiceList
invoices.export-selected
```

Ví dụ module `Pharma`:

```text
PharmaExportProfile
PharmaExportPreferenceService
PharmaExportController
```

Không bắt buộc tên giống Muasamcong, nhưng trách nhiệm phải tương đương.

---

## 25. Prompt dùng skill

Có thể yêu cầu Codex/AI:

```text
Hãy sử dụng `.codex/skills/configurable-excel-export/SKILL.md` để bổ sung Export Profile cho Modules/<Module>.

Yêu cầu:
- phân tích module hiện tại trước;
- không copy cứng code Muasamcong;
- tái sử dụng pattern profile + column whitelist + typed Excel writer;
- tuân thủ architecture và ADMIN_UI_STANDARD của repository;
- tạo migration backward-compatible;
- thêm targeted tests;
- chạy Pint + module tests;
- báo cáo file thay đổi và test result.
```

Nếu chỉ muốn phân tích:

```text
Analyze Modules/<Module> theo `.codex/skills/configurable-excel-export/SKILL.md` và đề xuất cách áp dụng configurable Excel export, chưa implement.
```

---

## 26. Definition of Done

Hoàn thành khi:

```text
[ ] Có multi-profile per user
[ ] Profile ownership an toàn
[ ] Có select/order/rename/type/decimal/align/width
[ ] Save config không auto export
[ ] Export đúng selected IDs
[ ] Number/String/Date đúng semantics
[ ] Wrap Text + auto row height
[ ] Optional Header/Footer hoạt động
[ ] Duplicate giữ toàn bộ settings
[ ] Route authorization test PASS
[ ] Pint PASS
[ ] Targeted/module test PASS
[ ] Documentation cập nhật
```
