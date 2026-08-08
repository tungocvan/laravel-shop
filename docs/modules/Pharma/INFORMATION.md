# Pharma Module Information

## Purpose

Quản lý hồ sơ thuốc, kết quả trúng thầu, theo dõi nhà cung cấp/giá vốn/lợi nhuận và tạo báo giá XLSX từ workbook tổng hợp.

## Features

- CRUD/list/filter/bulk delete cho ba aggregate.
- Import/export Medicine A–U, DrugBidAward A–L, SupplierTracking A–V.
- Tính server-side chênh lệch hóa đơn, phí, giá vốn, lợi nhuận.
- Tạo báo giá: phân tích sheet/header, tìm/chọn sản phẩm, chọn cột, recipient/chữ ký, giữ style/drawing/print setup và tải XLSX.
- CLI import Medicine và generate PriceList.

## Routes

Admin prefix `/admin/pharma`, middleware hiện tại `web, auth:admin`:

- `/hssp`: index/create/{id}/edit.
- `/drug-bid-awards`: index/create/{id}/edit.
- `/supplier-trackings`: index/create/{id}/edit và `/import-export` (route hỏng vì method thiếu).
- `/price-lists/create`: tạo báo giá.
- Public `GET /api/pharma` hiện hỏng vì controller thiếu `index`.

## Permissions

Manifest: `view_pharma`, `create_pharma`, `edit_pharma`, `delete_pharma`. Chưa được enforce ở routes/actions. Chưa có `import_pharma`, `export_pharma`, `generate_price_list`.

## Dependencies

Laravel 12/PHP 8.3, Livewire 3, FastExcel, Maatwebsite Excel, PhpSpreadsheet (đang dùng qua transitive dependency), Shared import/export, Admin layout/menu, private/public Storage.

## Services

CRUD: `MedicineService`, `DrugBidAwardService`, `SupplierTrackingService`. Import/export: `MedicineImportExport`, `DrugBidAwardImportExport`, `ImportExport` (Supplier), compatibility `MedicineImportService`. PriceList: `PriceListService`, `WorkbookAnalyzer`, `PriceListWorkbookBuilder`.

## Imports

Mode mặc định update-or-create, giữ giá trị cũ khi cell null, report theo row. Medicine key registration+packaging; Award key notice+medicine+company; Supplier key service-level medicine+supplier+working date. Import hiện giữ toàn collection và chạy sync.

## Exports

Ba data export dùng Shared base, filter + map nhưng `get()` toàn bộ và lưu public disk. PriceList dùng source cố định `storage/app/excel/BANG_GIA_TONG_HOP.xlsx`, output private `storage/app/private/exports/price-lists`, download rồi delete.

## Models

`Medicine`, `DrugBidAward`, `SupplierTracking`; scaffold `Pharma` không thấy sử dụng. PriceList chưa có model/audit record.

## Database Tables

`pharma_medicines`, `pharma_drug_bid_awards`, `pharma_supplier_trackings`. Không có table quotation. Supplier chưa có unique key mà importer giả định.

## Events

Không có domain event/listener.

## Jobs

Không có queue job. Import/export/generate chạy đồng bộ.

## Configuration

`config/module.php` quản lý enabled/type/permissions/table catalog. PriceList source path là constant, không có config feature riêng.

## Environment Variables

Không có environment variable Pharma-specific được quan sát.

## Known Risks

- Thiếu permission/action authorization.
- Full workbook values nằm trong public Livewire state.
- Shared panel resolve class từ public client state.
- Business exports tồn tại trên public disk.
- API và supplier route gọi method thiếu.
- Workbook được load lặp, import/export unbounded.
- Raw exceptions/formula-like spreadsheet input/output path boundary.
- Thiếu audit/version/lifecycle cho báo giá.
