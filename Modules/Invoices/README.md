# Invoices Module

Module Laravel quản lý hóa đơn điện tử, đăng nhập và lấy dữ liệu từ Cổng Hóa đơn điện tử của Cục Thuế (GDT), lưu dữ liệu cục bộ, tra cứu, import/export Excel và tải PDF từ MeInvoice.

Phiên bản tài liệu: `2.0.0`  
Namespace: `Modules\Invoices`  
Laravel đã kiểm thử: `12.x`  
PHP đã kiểm thử: `8.4`  
Livewire: `3.x`

## 1. Chức năng

- Lấy captcha và đăng nhập GDT.
- Giữ token GDT trong cache phía server; token không được đưa vào state Livewire hoặc HTML.
- Tìm hóa đơn bán ra theo khoảng ngày.
- Đồng bộ hóa đơn bán ra/mua vào theo từng tháng.
- Phân trang API GDT bằng cursor `state`.
- Xuất dữ liệu GDT ra Excel và import Excel vào bảng `invoices`.
- Lọc, thống kê và xuất các hóa đơn đã chọn.
- Tải PDF hàng loạt từ MeInvoice khi có token tích hợp.
- Cung cấp API lọc hóa đơn cục bộ có validation và authentication.
- Cung cấp command chạy trực tiếp hoặc qua queue.

## 2. Kiến trúc

```text
Modules/Invoices
├── Console/Commands
│   ├── GetGdtInvoices.php
│   └── ImportInvoicesCommand.php
├── Exports
├── Http/Controllers
├── Jobs/ProcessGdtInvoicesJob.php
├── Livewire
│   ├── GdtLogin.php
│   ├── GdtInvoice.php
│   ├── SearchHoadon.php
│   └── HoadonList.php
├── Models/Invoices.php
├── Providers/InvoicesServiceProvider.php
├── Services
│   ├── GdtApiService.php
│   ├── GdtInvoiceService.php
│   ├── InvoiceService.php
│   ├── InvoiceImportService.php
│   ├── InvoiceExportService.php
│   └── ScanInvoiceService.php
├── config/invoices.php
├── database/migrations
├── resources/views
├── routes
├── .env.example
├── composer.json
└── module.json
```

### Luồng GDT

1. `GdtLogin` gọi `GdtApiService::loadCaptcha()`.
2. Người dùng nhập captcha; `GdtApiService::login()` gọi endpoint authenticate.
3. Token được lưu bằng cache key cấu hình tại `invoices.gdt.cache_key`.
4. `GdtInvoice` tìm nhanh và hiển thị dữ liệu trực tiếp.
5. `SearchHoadon` hoặc command gọi `GdtInvoiceService::processRange()`.
6. Service chia khoảng ngày theo tháng, gọi API theo cursor `state`, xuất Excel.
7. `InvoiceImportService` nhập Excel vào database và bỏ qua bản ghi trùng.

### Quy ước API GDT hiện tại

Base URL:

```text
https://hoadondientu.gdt.gov.vn/api
```

Các endpoint đang dùng:

```text
GET  /captcha
POST /security-taxpayer/authenticate
GET  /query/invoices/sold
GET  /query/invoices/purchase
```

Danh sách hóa đơn dùng:

```text
sort=tdlap:desc
size=50
search=tdlap=ge=DD/MM/YYYYT00:00:00;tdlap=le=DD/MM/YYYYT23:59:59
state=<cursor của response trước>
```

Không dùng cổng `30000`, nhiều trường sort trong một request, hoặc tham số `page`. Các dạng cũ có thể làm API GDT trả lỗi.

> Đây là API được frontend GDT sử dụng nhưng không phải API tích hợp công khai có hợp đồng ổn định. Khi GDT thay đổi frontend, cần kiểm tra lại endpoint và payload.

## 3. Yêu cầu

Các package chính được khai báo trong `composer.json` của module:

```bash
composer require \
  livewire/livewire:^3.0 \
  jeroennoten/laravel-adminlte:^3.0 \
  maatwebsite/excel:^3.1 \
  rap2hpoutre/fast-excel:^5.0 \
  setasign/fpdi:^2.0 \
  smalot/pdfparser:^2.0 \
  spatie/laravel-permission:^6.0
```

Ứng dụng phải autoload namespace:

```json
{
  "autoload": {
    "psr-4": {
      "Modules\\": "Modules/"
    }
  }
}
```

Sau khi sửa `composer.json`:

```bash
composer dump-autoload
```

## 4. Cài module vào dự án khác

1. Copy nguyên thư mục:

```text
Modules/Invoices
```

2. Cài các package ở mục 3.

3. Thêm namespace `Modules\\` vào PSR-4 nếu dự án chưa có.

4. Đăng ký provider trong `bootstrap/providers.php`:

```php
return [
    // ...
    Modules\Invoices\Providers\InvoicesServiceProvider::class,
];
```

Với Laravel 10 trở xuống, thêm provider vào `config/app.php`.

5. Copy các biến từ `Modules/Invoices/.env.example` vào `.env` và nhập credential thật.

6. Xóa cache và chạy migration:

```bash
php artisan optimize:clear
php artisan migrate
```

7. Nếu dùng permission middleware, tạo các quyền:

```text
invoices-list
invoices-create
invoices-edit
invoices-delete
```

Nếu dự án không dùng `spatie/laravel-permission`, bỏ middleware permission trong constructor của `Http/Controllers/InvoicesController.php`.

8. Nếu chạy queue:

```bash
php artisan queue:work --timeout=600
```

## 5. Biến môi trường

| Biến | Bắt buộc | Mô tả |
|---|---:|---|
| `GDT_API_BASE_URL` | Có | Base URL API GDT, mặc định `/api` |
| `GDT_API_USERNAME` | Có | Mã số thuế/tài khoản GDT |
| `GDT_API_PASSWORD` | Có | Mật khẩu GDT |
| `GDT_API_VERIFY_SSL` | Khuyên dùng | Phải là `true` ở production |
| `GDT_API_TIMEOUT` | Không | Timeout request, giây |
| `GDT_TOKEN_TTL` | Không | Thời gian cache token, giây |
| `GDT_TOKEN_CACHE_KEY` | Không | Cache key token |
| `MEINVOICE_API_BASE_URL` | Khi tải PDF | Base URL tích hợp MeInvoice |
| `MEINVOICE_API_TOKEN` | Khi tải PDF | Token tích hợp MeInvoice |
| `INVOICES_EXPORT_DIRECTORY` | Không | Thư mục Excel dưới `storage/app` |
| `INVOICES_PDF_DIRECTORY` | Không | Thư mục PDF dưới `storage/app` |

Không commit `.env`, token, captcha, mật khẩu hoặc nội dung Authorization vào Git/log.

## 6. Route

Web routes dùng middleware `web`, `auth`:

| Method | URI | Tên |
|---|---|---|
| GET | `/invoices` | `invoices.index` |
| GET | `/invoices/create-token` | `invoices.create-token` |
| GET | `/invoices/hoadon` | `invoices.hoadon` |
| GET | `/invoices/hoadon-list` | `invoices.hoadon-list` |
| GET | `/invoices/download/{lookup_code}` | `invoices.download` |

API routes dùng `api`, `auth:sanctum`:

| Method | URI |
|---|---|
| GET | `/api/invoices` |
| POST | `/api/invoices` |

Middleware có thể chỉnh trong `config/invoices.php`.

## 7. Command

Đồng bộ và xuất Excel:

```bash
php artisan gdt:invoices 2026-07-01 2026-07-31
php artisan gdt:invoices 2026-07-01 2026-07-31 --vatIn
php artisan gdt:invoices 2026-07-01 2026-07-31 --queue
```

Import Excel:

```bash
php artisan gdt:import-excel storage/app/gdt/vat_out/vat_out_2026-07-01_2026-07-31.xlsx --type=sold
```

## 8. Database

Bảng `invoices` lưu:

- Thông tin nhận diện: `lookup_code`, `symbol`, `invoice_number`, `type`.
- Ngày hóa đơn: `issued_date`.
- Đối tác: `tax_code`, `name`, `address`, `email`, `phone`.
- Giá trị: `tax_rate`, `vat_amount`, `amount_before_vat`, `total_amount`.
- Chiều hóa đơn: `invoice_type` (`sold` hoặc `purchase`).

Index chính:

- `invoice_type, issued_date`
- `tax_code`
- `invoice_number`

## 9. Storage

```text
storage/app/gdt/vat_in
storage/app/gdt/vat_out
storage/app/hoadon_temp
```

Tên thư mục có thể đổi bằng biến môi trường. Web server và queue worker phải có quyền ghi.

## 10. Kiểm thử sau khi cài

```bash
php artisan optimize:clear
php artisan migrate:status
php artisan list gdt
php artisan route:list --path=invoices
vendor/bin/pint --test Modules/Invoices
```

Kiểm thử thủ công:

1. Mở `/invoices/create-token`.
2. Xác nhận captcha hiển thị.
3. Đăng nhập và xác nhận giao diện chỉ báo “Đã có token”, không in token.
4. Tìm hóa đơn trong một khoảng ngày ngắn.
5. Đồng bộ một tháng bán ra và mua vào.
6. Import file vừa xuất.
7. Mở `/invoices/hoadon-list`, thử filter và export.
8. Nếu có token MeInvoice, thử tải PDF.
9. Xóa token và xác nhận request tiếp theo yêu cầu đăng nhập lại.

## 11. Checklist khi API GDT thay đổi

1. Mở frontend chính thức `https://hoadondientu.gdt.gov.vn`.
2. Kiểm tra bundle JavaScript để xác minh base URL và endpoint.
3. Gọi `/captcha` và xác minh response có `key`, `content`.
4. Kiểm tra payload authenticate có `username`, `password`, `ckey`, `cvalue`.
5. Kiểm tra header `Authorization: Bearer <token>`.
6. Kiểm tra query invoice, đặc biệt `sort`, `search`, `size`, `state`.
7. Cập nhật `config/invoices.php` hoặc service; không hard-code URL trong Livewire.
8. Thêm/bổ sung test trước khi deploy.

## 12. Đánh giá kỹ thuật và giới hạn

Các vấn đề của phiên bản cũ đã được xử lý trong bản rebuild:

- Service/job/command nằm ngoài module.
- URL GDT cổng `30000` hard-code nhiều nơi.
- Token GDT và token MeInvoice xuất hiện trong state/source.
- Request không bắt `ConnectionException`, gây HTTP 500.
- Phân trang GDT dùng `page` thay vì cursor `state`.
- API nội bộ không authentication/validation chặt.
- Route index gọi view không tồn tại.
- Migration không rollback và thiếu index.
- Filter thuế suất dùng chuỗi `%` trong khi database là decimal.
- Job queue thiếu tham số loại hóa đơn.

Giới hạn còn lại:

- `ScanInvoiceService` dùng regex theo mẫu PDF; nhà cung cấp đổi layout có thể cần parser riêng.
- Tải PDF MeInvoice phụ thuộc token tích hợp và giả định mỗi lookup code tương ứng một trang.
- UI page sử dụng layout AdminLTE. Nếu dự án đích dùng layout khác, thay các file page trong `resources/views`.

## 13. Thông tin cần cung cấp để rebuild từ đầu

Khi giao module này cho AI/lập trình viên ở dự án khác, cung cấp:

- Thư mục `Modules/Invoices`.
- Phiên bản PHP, Laravel, Livewire.
- Schema database hiện có.
- Loại cache và queue driver.
- Cách authentication/authorization của dự án.
- Layout Blade đang dùng.
- Mẫu response GDT đã ẩn token/thông tin nhạy cảm nếu API thay đổi.
- Yêu cầu dùng hay không dùng MeInvoice/PDF scan.

Yêu cầu rebuild mẫu:

> Đọc toàn bộ `Modules/Invoices/README.md`, giữ nguyên route và schema nếu không có migration chuyển đổi, đăng ký `InvoicesServiceProvider`, cấu hình các biến môi trường, chạy static checks và kiểm thử captcha → login → query → sync → export → import. Không hard-code credential/token và không đưa token vào thuộc tính public Livewire.
