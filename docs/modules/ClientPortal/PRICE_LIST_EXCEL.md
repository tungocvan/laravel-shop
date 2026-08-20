# ClientPortal — Price List Excel / PDF Rendering Notes

Tài liệu này ghi lại contract hiện tại của chức năng Bảng Giá để AI/dev không vô tình phá layout/typing đã được test thực tế.

## 1. Ownership và nguồn cấu hình

PWA `ClientPortal` **không tự định nghĩa cấu hình Excel riêng**.

Cấu hình export được Admin quản lý từ domain `Muasamcong` và PWA tái sử dụng profile đó. Vì vậy khi sửa export phải kiểm tra cả hai luồng:

```text
Admin /admin/muasamcong/synced
    -> SyncedExportProfile / export preference service

ClientPortal /apps/muasamcong/price-list
    -> GeneratePriceListExport
    -> GeneratePriceListPdf
```

Một profile phải cho kết quả nhất quán giữa Admin export trực tiếp và Client/PWA Queue export.

## 2. Không sửa dữ liệu canonical để làm đẹp Excel

Các chuẩn hóa dành riêng cho Bảng Giá phải nằm ở renderer/export layer.

Ví dụ hiện tại:

```text
Nhóm thuốc:
N1 / Nhóm 1 / NHÓM 1 -> 1
N2 / Nhóm 2           -> 2
...

Hạn dùng:
24 tháng -> 24
36 tháng -> 36
```

Database nguồn vẫn giữ dữ liệu nghiệp vụ nguyên bản.

## 3. Data types là contract, không chỉ là format nhìn thấy

Profile cột hỗ trợ các kiểu quan trọng:

```text
string
numeric
integer/number
currency/price
percentage (nếu cấu hình)
date
```

Renderer phải ghi **đúng kiểu cell**, sau đó mới áp dụng number format.

### String

Dùng cho mã có thể bị Excel tự chuyển sang số/scientific notation, ví dụ:

```text
GĐKLH / GPNK
Số quyết định
Mã định danh
```

Không để Excel tự đoán kiểu.

### Numeric / Price / Quantity

Phải là numeric thật để sort/filter/formula được. Format hiển thị cần có phân cách hàng nghìn theo workbook locale/number format đã định nghĩa.

### Date

Dữ liệu ISO/timestamp phải được parse thành Excel date serial và format hiển thị:

```text
dd/mm/yyyy
```

Không chỉ cắt chuỗi bằng substring nếu có thể parse được ngày hợp lệ.

## 4. Page Setup mặc định cho Bảng Giá thuốc

Mặc định hiện tại đã được người dùng chốt:

```text
Paper          : A4
Orientation    : Landscape / giấy ngang
Left margin    : 0.3 cm
Right margin   : 0.3 cm
Top margin     : 0.8 cm
Bottom margin  : 0.8 cm
Center on page : Horizontally = true
                 Vertically   = false
Scaling        : Fit to 1 page wide
                 Height auto / nhiều trang nếu cần
```

Các tham số này phải nằm trong profile và có thể thay đổi linh động từ Admin. UI nên để nhóm ít thay đổi trong accordion/collapse thay vì bày toàn bộ trên modal.

## 5. Header / Footer

`Hiển thị Header/Footer` mặc định bật cho profile báo giá chuẩn.

Header có thể gồm:

- Logo công ty;
- tên công ty;
- địa chỉ;
- mã số thuế;
- số điện thoại;
- email;
- tiêu đề `BẢNG BÁO GIÁ`;
- `Kính gửi`;
- nội dung giới thiệu.

Dòng **Kính gửi** phải được in đậm.

Footer/khu vực ký gồm:

- địa điểm/ngày ký;
- chức danh người ký;
- ảnh chữ ký/con dấu;
- họ tên người ký.

## 6. Logo và chữ ký

Không stretch ảnh theo cell một cách tùy tiện. Profile có Width/Height riêng cho logo và chữ ký để người dùng tinh chỉnh theo ảnh thực tế.

Ví dụ kích thước logo từng được người dùng dùng làm mốc:

```text
Width  : 2.48 cm
Height : 3.83 cm
```

Đây là tham số cấu hình, không phải hard-code bắt buộc cho mọi profile.

UI upload logo/chữ ký nên:

- preview ảnh sau upload;
- có nút xóa ảnh;
- hiển thị current image rõ ràng;
- đưa Width/Height vào nhóm nâng cao nếu ít thay đổi.

## 7. Vùng chữ ký phải là 4 cột cuối

Không căn chữ ký theo toàn worksheet.

Renderer phải tính động:

```text
signatureStart = lastColumn - 3
signatureEnd   = lastColumn
```

Toàn bộ:

```text
Ngày ký
Chức danh
Ảnh chữ ký/con dấu
Họ tên
```

phải căn giữa trong cùng vùng 4 cột cuối. Khi Admin thêm/bớt cột, vùng ký phải tự chạy theo cột cuối mới.

## 8. Excel -> PDF qua LibreOffice

PDF được tạo qua Queue và LibreOffice headless. Đây không phải renderer giống Excel desktop 100%.

Vấn đề đã gặp: LibreOffice tính row height/wrap text khác Excel làm floating drawing (chữ ký/con dấu) dịch lên bảng.

Do đó luồng PDF hiện nên giữ nguyên nguyên tắc:

```text
XLSX gốc đã hoàn chỉnh
    -> tạo/load workbook tạm cho PDF
    -> ổn định row heights/layout cần thiết
    -> LibreOffice headless convert
    -> lưu PDF private artifact
```

Không làm biến đổi XLSX gốc chỉ để phù hợp LibreOffice.

## 9. Private artifacts và permission

XLSX/PDF được lưu private, ví dụ dưới:

```text
storage/app/client-portal/price-lists/{user_id}/...
```

Route download kiểm tra owner/permission rồi mới trả file.

Sau Queue generation, file và parent directories phải có permission/group cho web process đọc được. Đã từng gặp Queue chạy `root` tạo file nhưng PHP-FPM `www-data` không đọc được → 404 dù file có thật.

Job artifact phải tự bảo đảm quyền hợp lệ; không coi `chmod` thủ công là workflow bình thường.

## 10. Queue lifecycle

Các tác vụ nặng:

```text
Generate XLSX
Convert PDF
Send email
```

đều dùng Queue.

Trạng thái UI phải theo transition thật:

```text
queued -> processing -> completed / failed
```

Polling không được reload trang chỉ vì XLSX đã `completed` trong khi đang chờ PDF.

## 11. Import / Export profile JSON

Profile cấu hình cần Import/Export JSON để backup/chuyển cấu hình giữa môi trường. JSON phải mang theo tối thiểu:

- columns + order;
- label/header;
- type;
- width/alignment/number/date format;
- Header/Footer settings;
- logo/signature settings;
- logo/signature Width/Height;
- `page_setup`;
- các cờ hiển thị liên quan.

Import phải normalize defaults cho profile cũ hoặc JSON thiếu field mới.

## 12. UI configuration principles

Modal cấu hình phải ưu tiên hierarchy:

```text
1. Nội dung & thương hiệu
2. Cột dữ liệu
3. Thiết lập trang in
4. Kích thước/nâng cao
```

Nguyên tắc:

- phần hay dùng mở mặc định;
- phần ít thay đổi thu gọn;
- luôn có summary ngắn cho Page Setup, ví dụ:

```text
A4 · Ngang · Fit 1 trang rộng · Lề 0,3/0,8 cm · Căn giữa ngang
```

- save có feedback/modal/toast rõ;
- preview logo/chữ ký trực quan;
- không để modal cao bị che footer/action bar.

## 13. Price List Workspace UI

Mobile và desktop không dùng một action layout y hệt nhau.

Contract hiện tại:

```text
Mobile:
[Excel icon] [PDF icon] [Share icon] [Gửi bảng giá] [More]

Desktop:
[Excel icon + label] [PDF icon + label] [Share icon + label]
[Gửi bảng giá] [More]
```

`Gửi bảng giá` là business CTA chính. Chỉnh sửa/tạo lại/xóa nằm trong `More`.

Delivery history nên hiển thị người nhận/kênh/file gần nhất để người dùng biết báo giá đã được gửi.

## 14. Checklist trước merge khi sửa Excel/PDF

```bash
php -l Modules/ClientPortal/Jobs/GeneratePriceListExport.php
php -l Modules/ClientPortal/Jobs/GeneratePriceListPdf.php
php artisan test tests/Feature/ClientApps/MuasamcongPriceListTest.php
php artisan test tests/Feature/ClientApps/ClientPwaFoundationTest.php
```

Manual test bắt buộc:

1. Tạo **file Excel mới** (không dùng file cũ để đánh giá renderer mới).
2. Kiểm tra string/numeric/date trong Excel.
3. Kiểm tra Print Preview/Page Layout.
4. Kiểm tra logo/chữ ký và vùng 4 cột cuối.
5. Convert **PDF mới**.
6. Kiểm tra chữ ký/con dấu không nhảy vào bảng.
7. Tải XLSX/PDF bằng web user, không chạy chmod thủ công.
8. Test mobile + desktop workspace.

Nếu Excel PASS nhưng PDF FAIL, ưu tiên debug conversion layer. Nếu cả Excel và PDF sai, ưu tiên workbook/profile layer.