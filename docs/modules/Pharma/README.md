# Pharma Module

## Module Overview

Pharma là domain owner của hồ sơ thuốc, thuốc trúng thầu, theo dõi nhà cung cấp và tạo báo giá Excel. Trạng thái đánh giá hiện tại: cần **Major Refactor** trước khi dùng dữ liệu giá/hợp đồng ở production.

## Installation / Registration

Module được `Modules\ModuleServiceProvider` tự discover từ `config/module.php`, load routes/views/migrations/Livewire/console. Cần chạy migrations, bảo đảm workbook nguồn tồn tại và private storage có quyền ghi. Không dùng các lệnh `module:*` cũ trong `Modules/Pharma/readme.md` làm runbook chính thức.

## Routes

Admin UI dưới `/admin/pharma`: `hssp`, `drug-bid-awards`, `supplier-trackings`, `price-lists/create`. API `/api/pharma` và supplier import-export page hiện có contract hỏng; xem `ANALYSIS.md`.

## Permissions

Các permission đã khai báo: view/create/edit/delete Pharma, nhưng code hiện chưa enforce. Cần bổ sung import/export/generate-price-list và authorization từng Livewire action trước production.

## Features

CRUD/filter/bulk actions, spreadsheet import/export, Supplier financial calculations, và PriceList builder chọn sheet/columns/products/recipient/signature để tạo file Excel.

## Dependencies

Admin shell, Shared import/export, Laravel Storage/DB/Livewire, FastExcel và PhpSpreadsheet. Nên khai báo PhpSpreadsheet trực tiếp trong Composer vì Pharma import namespace của package này.

## Import

Medicine A–U, Award A–L, Supplier A–V; update-or-create và row report. Hiện xử lý đồng bộ, collection-based; cần chốt mode và transaction contract theo aggregate.

## Export

Shared exports cần chuyển từ public sang private và chunk/expire. Báo giá đã dùng private output và delete-after-send, nhưng cần audit/cleanup fallback và authorization.

## Configuration

Workbook báo giá mặc định: `storage/app/excel/BANG_GIA_TONG_HOP.xlsx`, sheet `TỔNG HỢP`, cột mặc định `A:X`. Snapshot phân tích ngày 2026-07-20: header row 9, 24 cột, 44 sản phẩm, không có formula trong sheet XML.

## Events

Chưa có. Nên phát event safe-metadata cho import/export/quotation lifecycle sau commit.

## Jobs

Chưa có. Large import/export/quotation nên queue theo threshold với progress, retry và cleanup.

## Operations Notes

- Không cấp URL PriceList cho admin không có nhu cầu cho đến khi permission và Livewire data leak được sửa.
- Không coi generated quotation là audit record; hiện file tạm không persist history.
- Giữ workbook nguồn private, version/hash/backup và giới hạn người cập nhật.
- Theo dõi public export cũ và lên kế hoạch migration/cleanup.
- PHP không có trong shell phân tích nên test không được chạy; kết quả test được đánh giá từ code.

## Future Improvements

Thực hiện `REFACTOR_PLAN.md`: P0 authorization/data exposure/private storage, rồi route correctness, quotation lifecycle, Supplier invariant, bounded processing, queue/cache/audit/monitoring và test đầy đủ.
