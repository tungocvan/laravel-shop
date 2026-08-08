# Phân tích module Pharma

Ngày phân tích lại: 2026-07-20. Phạm vi gồm `Modules/Pharma`, tài liệu hiện có, shared import/export được module gọi trực tiếp, Admin menu, bootstrap/config/routes liên quan và workbook nguồn `storage/app/excel/BANG_GIA_TONG_HOP.xlsx`.

## 1. Executive Summary

Pharma quản lý hồ sơ thuốc, kết quả trúng thầu, theo dõi nhà cung cấp và chức năng mới tạo báo giá Excel. Luồng báo giá có phân lớp tốt: controller mỏng, Livewire quản lý tương tác, `PriceListService` điều phối, `WorkbookAnalyzer` phân tích workbook, `PriceListWorkbookBuilder` tạo XLSX, output mặc định nằm trong private storage và được xóa sau khi tải. Workbook thực tế tồn tại (67.259 byte), sheet XML không chứa công thức và ba unit test xác nhận header, 44 sản phẩm, lọc, chọn cột không liên tục, style, drawing và print area.

Tuy vậy, module chưa sẵn sàng cho dữ liệu thương mại production. Toàn bộ web route chỉ dùng `auth:admin`, không dùng bốn permission đã khai báo; mọi mutation Livewire, import/export và tạo báo giá đều thiếu authorization tại action. Đặc biệt, `PriceList\Create::$analysis` là public Livewire state chứa `products[*].values` A:X, nên toàn bộ nội dung bảng giá được serialize về browser dù UI chỉ hiển thị vài trường. Shared import/export panel còn nhận `serviceClass` qua public state và resolve động, tạo bề mặt gọi service ngoài dự kiến. Export dữ liệu Pharma lưu trên public disk và không có cleanup rõ ràng.

Khuyến nghị: **Major Refactor**. Giữ lại domain/model/service hiện tại và pipeline báo giá, nhưng phải đóng P0 về quyền, dữ liệu Livewire, service động và file public trước; sau đó chuẩn hóa import/export, hiệu năng, transaction, validation và test.

## 2. Module Overview

Evidence: `config/module.php` khai báo module `domain`, enabled, bốn permission và ba table. Code hiện tại có bốn feature: Medicine, DrugBidAward, SupplierTracking, PriceList. PriceList không có model/table riêng; nó tạo file tạm từ workbook chuẩn trong storage.

Evidence: `Modules/ModuleServiceProvider.php` tự đăng ký routes, views, migrations, Livewire và console command. Không có `PharmaServiceProvider`; module không cần binding riêng.

## 3. Dependency Graph

```text
Web Route -> Controller -> Admin page Blade -> Pharma Livewire
  Medicine -> MedicineService -> MedicineImportExport -> Shared BaseImportExportService -> Medicine -> DB
  DrugBidAward -> DrugBidAwardService -> DrugBidAwardImportExport -> Shared Base -> DrugBidAward -> DB
  SupplierTracking -> SupplierTrackingService / ImportExport -> Shared Base -> SupplierTracking -> DB
  PriceList -> PriceListService -> WorkbookAnalyzer -> private source XLSX
                                -> PriceListWorkbookBuilder -> private generated XLSX -> download/delete

API Route -> empty Api\PharmaController@index (method absent)
Shared panel -> public serviceClass -> app(serviceClass) -> import/export/template
Console -> ImportMedicineCommand / GeneratePriceListCommand -> Pharma services
Views -> Admin::layouts.master
```

Không thấy circular class dependency. Cross-module trực tiếp: Pharma -> Shared import/export và Pharma views -> Admin shell.

## 4. Route Analysis

Evidence: tất cả admin route có `web`, `auth:admin`, nhưng không có `permission:*`. Route giá mới: `GET /admin/pharma/price-lists/create`, tên `admin.pharma.price-lists.create`.

Evidence: route `supplier-trackings/import-export` gọi `SupplierTrackingController::importExport()` không tồn tại. API `GET /api/pharma` public gọi `Api\PharmaController::index()` không tồn tại.

Evidence: `{id}` edit chưa có `whereNumber`; route model binding/policy không được dùng.

## 5. Controller Analysis

Các controller web mỏng, chỉ trả view và truyền ID, phù hợp kiến trúc. Tuy nhiên không controller nào authorize. `PharmaController` còn middleware permission bị comment. `PriceListController::create()` không kiểm tra capability tạo/xuất báo giá. API controller là scaffold rỗng.

## 6. Page Blade Analysis

Evidence: page Blade extend `Admin::layouts.master` và mount đúng alias. Output dùng escaped echo. Link tài liệu ngoài có `target="_blank"`; cần xác nhận tất cả đều có `rel="noopener noreferrer"` (supplier có, một số medicine/award cần chuẩn hóa).

Evidence: các nút create/edit/delete/import/export không được bọc `@can`; đây không thay thế server authorization nhưng làm UX không phản ánh quyền. Admin menu chưa có mục tạo báo giá; code route tồn tại nhưng menu chỉ có ba feature cũ.

## 7. Livewire Analysis

Evidence: không component nào gọi Gate/policy/`authorize()`. Các action save/delete/deleteSelected/import/export/generate chỉ dựa vào admin session của page ban đầu. Livewire action phải tự authorize.

Evidence: `PriceList\Create` validate sheet, columns, row IDs và ba chuỗi hiển thị; service xác minh selected row và allowed columns lại từ workbook. Đây là defense-in-depth tốt cho row/column tampering.

Evidence: `PriceList\Create::$analysis` public chứa `WorkbookAnalysis::toArray()`, trong đó mỗi sản phẩm có `values` cho mọi cột. Public Livewire state được đưa tới client. `filteredProducts()` còn đọc lại workbook mỗi render thay vì lọc snapshot an toàn.

Evidence: `loadWorkbook()` và `generate()` flash/addError trực tiếp `$exception->getMessage()`, có thể lộ đường dẫn, sheet/header hoặc chi tiết hệ thống. `useColumns()` là public action nhưng service vẫn validate lại.

Evidence: Medicine/DrugBidAward hỗ trợ `All` bằng `999999`; select-all DrugBidAward tải toàn bộ ID. Supplier select-all cũng pluck toàn bộ filtered IDs. Public filter/perPage chưa validate/cap.

Evidence: form Medicine/DrugBidAward flash raw exception. DrugBidAward form query toàn bộ Medicine trực tiếp trong `render()`; SupplierTracking service cũng tải toàn bộ Medicine cho select.

## 8. Shared UI Component Analysis

Evidence: ba index mount `shared.import-export.panel`. `Panel::$serviceClass` là public, sau đó `app($this->serviceClass)` và gọi `import/export/exportTemplate`; không allowlist/interface check và không authorization. Một request Livewire bị sửa có thể đổi class đích.

Evidence: upload validate xlsx/csv/10 MB, nhưng export/import actions không permission. Filters cũng là public state. Panel trả file từ public disk.

## 9. Service Analysis

CRUD service dùng Eloquent và transaction; query list đã eager-load relation khi cần. Supplier calculated fields được server tính lại.

PriceList strengths: source path cố định; selected rows và column set được đối chiếu server-side; output mặc định private; tên ngẫu nhiên; builder tạo directory; source workbook không có formula ở snapshot hiện tại.

PriceList risks: `generate()` chấp nhận `output_path` tùy ý và builder tạo directory/write trực tiếp—an toàn cho CLI tin cậy nhưng service chưa bảo vệ nếu caller web tương lai truyền input; mỗi request có thể load workbook nhiều lần; tác vụ đồng bộ có thể nặng; không có audit/correlation; user text ghi trực tiếp vào cell và chưa chặn spreadsheet formula prefix cho signature fields.

Evidence: `WorkbookAnalyzer` quét đến 50 cột để tìm STT, 200 cột để tìm last header, rồi mọi data row; toàn workbook được `IOFactory::load()`. Builder load workbook lần nữa. Trong `generate()` UI còn analyze trước, service generate analyze lại, rồi builder load lại.

## 10. Import Analysis

Medicine A–U, DrugBidAward A–L và SupplierTracking A–V đều dùng `BaseImportExportService`, mode mặc định `update_or_create`, bỏ qua null khi update, có validation và fixtures/tests một phần. Supplier derived fields được tính lại.

Evidence: Shared base gọi FastExcel `import()` rồi giữ toàn collection, mở một transaction cho toàn file nhưng tiếp tục sau lỗi row và commit các row hợp lệ. Đây là partial import có transaction toàn file về mặt kỹ thuật nhưng không atomic về nghiệp vụ.

Evidence: lookup existing Medicine/Supplier mỗi row gây N+1. Supplier fallback theo tên lấy record đầu tiên nếu tên trùng. Column mapping theo vị trí array, không thực sự xác minh header tương ứng. Mode do public panel cho người dùng đổi, kể cả `create_only`/`skip_duplicate`, thay đổi semantics nghiệp vụ.

Evidence: file tạm Livewire do framework quản lý; retention/cleanup production chưa được tài liệu hóa. Debug report chứa exception nội bộ trong `debug`, có nguy cơ hiển thị nếu shared Blade render trường này.

## 11. Export Analysis

Các service áp dụng filter và map cột rõ, eager-load relation. Tuy nhiên `exportRows()` đều `get()` toàn bộ rồi map collection; Shared base lưu vào `storage/app/public/...`. Không thấy expiration/cleanup hoặc tokenized private download. Dữ liệu giá, nhà cung cấp, hợp đồng và thầu có thể tồn tại trên public disk sau download.

PriceList export khác: file nằm private và `deleteFileAfterSend(true)`, tốt hơn shared export. Nhưng không có cleanup cho file còn lại khi process lỗi/response không hoàn tất.

## 12. Shared Service Analysis

`BaseImportExportService` cung cấp mapping, normalization, report và storage, đúng hướng roadmap. Các vấn đề shared tác động trực tiếp Pharma: unbounded collection, public export storage, dynamic service selection ở panel, mode do client chọn, transaction/report semantics và thiếu authorization contract.

## 13. Model Analysis

Medicine, DrugBidAward, SupplierTracking có `$fillable` và casts hợp lý. DrugBidAward/SupplierTracking có `belongsTo Medicine`; Medicine chưa khai báo inverse relations. Không model nào soft delete/audit actor/version.

Supplier `$exceptExport=[]`, trái tài liệu cũ từng nói loại contract/status/note. Derived fields vẫn fillable; current UI/service tính lại nhưng caller khác có thể mass assign trực tiếp. Status/type chưa có enum/cast.

Model scaffold `Pharma.php` không có table và có vẻ không dùng.

## 14. Database Analysis

Ba migration sở hữu đúng table; Medicine và DrugBidAward có composite unique; foreign key rõ. Supplier có index `(medicine_id,supplier_name)` và status nhưng không có unique `(medicine_id,supplier_name,working_date)` dù import dùng key này. `working_date` nullable trong DB/form nhưng required trong import, làm semantics duplicate khác nhau.

Supplier cascade delete khi xóa Medicine có thể xóa lịch sử thương mại không phục hồi; Medicine không soft delete. Không có audit `created_by/updated_by`, check constraint status, hay index phù hợp mọi filter/search. PriceList không persist quotation record, người tạo, recipient, selected products, hash nguồn, file hash hoặc thời điểm phát hành.

## 15. Security Analysis

### PH-P0-01

Priority:
P0

File:
`Modules/Pharma/routes/web.php`; `Modules/Pharma/Livewire/**`; `Modules/Shared/Livewire/ImportExport/Panel.php`

Evidence:
Chỉ `auth:admin`; không permission middleware/policy/action authorization. Manifest có `view/create/edit/delete_pharma`.

Problem:
Mọi admin đều có thể đọc, sửa, xóa, import/export và tạo báo giá.

Impact:
Truy cập trái phép dữ liệu thuốc, giá, hợp đồng, nhà cung cấp; thay đổi/xóa dữ liệu hoặc phát hành báo giá.

Recommendation:
Định nghĩa permission theo capability (`view/create/update/delete/import/export/price-list.generate`), enforce ở route và từng Livewire action/policy, kiểm tra ID server-side, thêm denied tests.

### PH-P0-02

Priority:
P0

File:
`Modules/Pharma/Livewire/PriceList/Create.php`; `Modules/Pharma/DTOs/WorkbookAnalysis.php`

Evidence:
Public `$analysis` chứa `products[*].values` A:X; `mount()` load toàn workbook.

Problem:
Toàn bộ dữ liệu workbook được serialize vào Livewire browser state.

Impact:
Lộ cột giá/nội dung thương mại không được UI hiển thị cho bất kỳ admin đăng nhập nào truy cập URL.

Recommendation:
Chỉ đưa DTO projection tối thiểu (`row,stt,name,active_ingredient,registration_number`) ra client; giữ workbook/column values server-side; authorize trước khi analyze.

### PH-P0-03

Priority:
P0

File:
`Modules/Shared/Livewire/ImportExport/Panel.php`

Evidence:
Public `$serviceClass` được resolve bằng `app()` trong ba action; không interface/allowlist/authorization.

Problem:
Client có thể thay đổi class service được container resolve.

Impact:
Có thể gọi ngoài ý muốn các method import/export/template trên class khác, mở rộng quyền và phạm vi dữ liệu.

Recommendation:
Không tin class từ Livewire state; dùng server-owned registry/key đã ký/locked, bắt buộc interface, allowlist và capability mapping cho từng service/action.

### PH-P0-04

Priority:
P0

File:
`Modules/Shared/Services/ImportExport/BaseImportExportService.php`

Evidence:
Export ghi `storage/app/public`; không thấy cleanup/expiry; Pharma exports chứa giá, thầu, supplier, URL hợp đồng.

Problem:
File dữ liệu nghiệp vụ nhạy cảm tồn tại trên public storage.

Impact:
URL bị đoán/rò log/history có thể cho phép tải không qua authorization; file tích tụ lâu dài.

Recommendation:
Chuyển private disk, tải qua controller/token ngắn hạn có authorization, đặt retention và scheduled cleanup/audit.

### PH-P1-SEC-05

Priority:
P1

File:
`Modules/Pharma/Livewire/PriceList/Create.php`; `Modules/Pharma/Services/Spreadsheet/PriceListWorkbookBuilder.php`

Evidence:
`signatureDate`/`signatureTitle` là input string ghi bằng `setCellValue`; raw exception được trả UI.

Problem:
Chưa neutralize spreadsheet formula prefix và lỗi nội bộ bị lộ.

Impact:
File mở trong Excel có thể thực thi công thức do người dùng chèn; người dùng thấy chi tiết nội bộ.

Recommendation:
Ghi user text bằng explicit string/sanitize prefix; map exception sang message an toàn, log có correlation ID.

SQL injection trực tiếp không thấy: query dùng Eloquent binding; `whereRaw` có parameter binding. XSS Blade giảm nhờ escaped output.

## 16. Performance Analysis

### PH-P1-PERF-01

Priority:
P1

File:
`Modules/Pharma/Livewire/PriceList/Create.php`; `WorkbookAnalyzer.php`; `PriceListService.php`; `PriceListWorkbookBuilder.php`

Evidence:
Workbook được load lại trong render/filter, generate validation, service generate và builder.

Problem:
Phân tích/tạo XLSX đồng bộ và lặp I/O/memory.

Impact:
Workbook lớn gây chậm Livewire, timeout hoặc memory exhaustion.

Recommendation:
Phân tích một lần thành server-side cached snapshot theo file hash/mtime, chỉ gửi projection; generate dùng snapshot tin cậy hoặc queue khi vượt threshold; đặt giới hạn kích thước/hàng/cột.

### PH-P1-PERF-02

Priority:
P1

File:
`Modules/Pharma/Services/*ImportExport.php`; `BaseImportExportService.php`; các Index Livewire

Evidence:
Import/export dùng collections/get; `All=999999`; select-all tải toàn ID; lookup relation theo row.

Problem:
Luồng dữ liệu lớn không bounded/chunk/lazy.

Impact:
Tăng memory, query count và thời gian request.

Recommendation:
Loại All, cap page size, chunk/lazy/queue import-export, preload lookup keys, progress và cleanup.

## 17. Technical Debt Analysis

### PH-P1-COR-01

Priority:
P1

File:
`Modules/Pharma/routes/api.php`; `Modules/Pharma/routes/web.php`; controllers tương ứng

Evidence:
Hai route gọi method không tồn tại.

Problem:
Route contract hỏng và API public ngoài chủ đích.

Impact:
500 khi truy cập, bề mặt API không kiểm soát, route regression.

Recommendation:
Gỡ route scaffold hoặc triển khai action + guard + permission + tests; thêm numeric constraints.

### PH-P1-COR-02

Priority:
P1

File:
`Modules/Pharma/database/migrations/2026_05_23_141810_create_supplier_trackings_table.php`; `Services/ImportExport.php`

Evidence:
Import update_or_create dùng triple key nhưng DB không unique; working_date nullable ở CRUD và required ở import.

Problem:
Duplicate/invariant không thống nhất.

Impact:
Concurrent import/CRUD tạo trùng hoặc update không xác định.

Recommendation:
Chốt business key/nullability, dọn duplicate, thêm forward unique constraint và validation thống nhất.

### PH-P1-COR-03

Priority:
P1

File:
`Modules/Pharma/Services/PriceListService.php`; `GeneratePriceListCommand.php`

Evidence:
Service cho phép caller truyền `output_path` bất kỳ; builder mkdir/write trực tiếp.

Problem:
Boundary ghi file không bị giới hạn ở service.

Impact:
Caller không tin cậy tương lai có thể ghi đè/tạo file ngoài export directory.

Recommendation:
Tách trusted CLI output contract hoặc bắt buộc resolved path nằm trong allowlisted directory; không truyền browser input vào path.

### PH-P2-MAINT-01

Priority:
P2

File:
`Modules/Pharma/Models/Pharma.php`; placeholder views; `Modules/Pharma/readme.md`

Evidence:
Scaffold/placeholder và README lệnh module cũ còn tồn tại.

Problem:
Dead artifacts và docs không mô tả module thực.

Impact:
Developer confusion.

Recommendation:
Xóa sau khi architecture/route tests chứng minh không có caller; cập nhật README.

## 18. Test Coverage Analysis

Có `PharmaImportExportTest` cho fixtures/dry-run/null preservation/partial row/calculation và `PriceListServiceTest` cho workbook thực, filter và builder. Đây là tiến bộ so với tài liệu cũ.

Thiếu: route boot/API/method; auth/permissions; từng Livewire mutation; tampered IDs/serviceClass/rows/columns; browser-state data leak; private/public download; cleanup; formula injection; output path traversal; workbook missing/corrupt/duplicate header/multiple sheets/large files; queue/memory; CRUD unique/transactions; Supplier concurrency; MySQL migration/query behavior.

## 19. Cross-Module Dependencies

Pharma phụ thuộc Shared import/export và Admin layout/menu. `phpoffice/phpspreadsheet` được dùng trực tiếp nhưng chỉ là transitive dependency từ Maatwebsite Excel trong `composer.lock`, chưa khai báo direct requirement—rủi ro dependency integrity. Không có event/job dependency; không queue nào trong module. Không thấy circular module dependency.

## 20. Documentation Drift

- Tài liệu 2026-07-14 chưa có PriceList route/Livewire/service/builder/command/test.
- Tài liệu cũ nói Supplier `$exceptExport` loại ba field; code hiện là `[]` và export đủ field.
- Tài liệu cũ ghi import/export “đã triển khai” nhưng vẫn mô tả mapping là đề xuất/chưa duyệt ở phần khác.
- Tài liệu cũ nói thiếu `$fillable` Medicine; code hiện đã có.
- Tài liệu cũ chưa ghi shared panel dynamic service, public export storage và P0 Livewire workbook exposure.
- `Modules/Pharma/readme.md` chỉ chứa lệnh scaffold không còn đại diện module.

## 21. Module Health Score

| Dimension | Điểm |
|---|---:|
| Architecture | 6.5/10 |
| Security/authorization | 2.5/10 |
| Correctness/data integrity | 5/10 |
| Performance | 4.5/10 |
| Import/export | 5/10 |
| Price-list implementation | 6/10 functional, 3/10 access control |
| Tests | 4.5/10 |
| Documentation | 4/10 trước lần cập nhật này |
| **Overall** | **4.7/10** |

## 22. Final Recommendation

- [ ] Minor Refactor
- [x] Major Refactor
- [ ] Full Rebuild

Không cần full rebuild vì model/schema, service CRUD, shared foundation và pipeline báo giá có thể giữ. Nhưng authorization, public Livewire data, dynamic service resolution, file storage, route hỏng, large-data architecture và DB invariants cần thay đổi phối hợp.

## 23. Open Questions

1. Ai được xem workbook nguồn và ai được tạo/phát hành báo giá?
2. Có cần permission riêng `generate_price_list` và audit người tạo/recipient/sản phẩm/hash file không?
3. Báo giá chỉ là file tạm hay phải persist phiên bản/lịch sử và trạng thái duyệt?
4. Cột nào trong A:X là bí mật và cột nào được phép đưa vào quotation?
5. Workbook nguồn được cập nhật bởi ai, quy trình kiểm duyệt/hash/version/backup thế nào?
6. Có cho phép CLI `--output` ngoài private export directory không?
7. Supplier business key có chính thức là medicine + supplier + working date, và working date có bắt buộc không?
8. Import mode có được người dùng chọn hay cố định theo từng aggregate?
9. Partial import hay atomic toàn file là contract chính thức?
10. API Pharma có yêu cầu thực tế không?
