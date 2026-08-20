# ClientPortal — Quick Debug Notes

Mục tiêu của file này là giúp AI/dev khoanh vùng lỗi nhanh trước khi refactor. Ưu tiên kiểm tra đúng symptom → đúng lớp lỗi, không sửa lan sang module khác.

## 1. Download XLSX/PDF trả 404 dù database có record

Triệu chứng điển hình:

- route tồn tại;
- export record tồn tại, `status=completed`;
- `file_path` có giá trị;
- file nhìn thấy bằng `ls`;
- nhưng web download trả 404 hoặc `Storage::exists()` khác kết quả mong đợi.

Kiểm tra theo thứ tự:

```bash
php artisan route:list --path=apps/muasamcong/price-list -v
ps aux | grep -E "queue:work|queue:listen|horizon" | grep -v grep
readlink -f /proc/<QUEUE_PID>/cwd
find storage/app/client-portal/price-lists -maxdepth 5 -printf '%M %u:%g %p\n' | tail -40
```

Nguyên nhân đã gặp: Queue chạy bằng `root`, PHP-FPM/web chạy bằng `www-data`; file tồn tại nhưng web process không traverse/read được thư mục/file.

Quy tắc fix: job tạo artifact phải tự chuẩn hóa permission/group sau khi ghi file; không dựa vào thao tác `chown/chmod` thủ công sau mỗi job. Tốt hơn nữa là chuẩn hóa worker production chạy đúng user/group ứng dụng.

## 2. PDF convert thành công nhưng chữ ký/con dấu nhảy vào bảng

Nguyên nhân đã gặp: LibreOffice headless tính lại chiều cao row/wrap-text khác Excel. Floating drawing có thể bị dịch vị trí khi convert.

Hướng xử lý hiện tại:

```text
XLSX gốc
 -> load bằng PhpSpreadsheet
 -> chuẩn hóa row height cho bản convert tạm
 -> LibreOffice headless
 -> PDF
```

Không sửa file XLSX gốc chỉ để chữa layout PDF.

Nếu Excel đúng nhưng PDF sai, debug `GeneratePriceListPdf` trước; đừng sửa lại workbook layout đang PASS.

## 3. Chữ ký nằm giữa trang thay vì cuối bảng

Nguyên nhân đã gặp: footer merge/căn giữa từ `A` đến cột cuối.

Quy tắc hiện tại: vùng ký được tính động trên **4 cột cuối của bảng**:

```text
lastColumn - 3  ->  lastColumn
```

Ngày ký, chức danh, ảnh chữ ký/con dấu và họ tên người ký đều phải dùng cùng vùng này. Không hard-code tên cột vì Admin có thể thêm/bớt cột export.

## 4. Trang Bảng Giá refresh liên tục khoảng 2–3 giây

Nguyên nhân đã gặp: polling PDF đọc `status=completed` của XLSX và hiểu nhầm job đang theo dõi vừa hoàn thành, dẫn đến `location.reload()` lặp lại.

Quy tắc hiện tại: tách trạng thái đang chờ theo từng workflow (`data-pending`, `data-pdf-pending`) và chỉ reload khi đúng transition đang được theo dõi chuyển sang completed/failed.

Khi debug polling, kiểm tra Network tab trước khi thay backend Queue.

## 5. Profile Excel Admin đã lưu nhưng PWA nhận danh sách rỗng

Kiểm tra theo thứ tự:

```bash
php artisan migrate:status | grep -i muasamcong
php artisan tinker --execute="dump(Schema::hasTable('muasamcong_price_list_profiles'));"
php artisan tinker --execute="dump(Modules\\Muasamcong\\Models\\PriceListProfile::query()->get()->toArray());"
```

Nếu UI báo lưu thành công nhưng query rỗng, xác minh đúng database/connection và đúng model/table trước khi nghi PWA cache.

## 6. File XLSX mở được nhưng kiểu dữ liệu sai

Các lỗi đã gặp:

- GĐKLH/GPNK hiển thị dạng scientific notation;
- giá/số lượng không có number format;
- ngày quyết định còn ISO timestamp thay vì `dd/mm/yyyy`;
- cấu hình `string/numeric/date` bị ghi như text chung.

Debug tại `GeneratePriceListExport` và profile column configuration. Không sửa dữ liệu canonical trong database chỉ để phục vụ format Excel.

## 7. UI mobile đúng nhưng desktop vỡ icon/action

Nguyên nhân đã gặp: JavaScript chèn SVG cho mọi viewport trong khi CSS định hình icon-only chỉ nằm trong media query mobile.

Quy tắc UI:

```text
Mobile  : icon-only action 44x44 + CTA chính có label
Desktop : icon + label ngắn + chiều cao/action thống nhất
```

Nếu dùng JS để decorate DOM, CSS phải định nghĩa trạng thái cho cả mobile và desktop. Không dùng pseudo-content chữ `X`, `PDF`, `↗` thay cho icon thật.

## 8. Test UI fail chỉ vì spacing/formatting JavaScript

Đã gặp assertion tìm chuỗi chính xác như:

```text
data-action-icon = type
```

trong khi runtime đúng nhưng source không có khoảng trắng như assertion.

Quy tắc test: assert marker/hành vi ổn định (`data-action-icon`, class, route, output), tránh khóa test vào formatting nội bộ không có ý nghĩa nghiệp vụ.

## 9. PWA logout quay về login chung

Client/PWA logout phải quay về:

```text
/my-apps/login
```

Admin logout vẫn độc lập và quay về `/admin/login`. Không đổi global auth flow khiến Admin bị ảnh hưởng.

## 10. Lệnh kiểm tra nhanh trước khi sửa

```bash
php artisan optimize:clear
php artisan route:list --path=my-apps -v
php artisan route:list --path=apps/muasamcong -v
php artisan test tests/Feature/ClientApps/ClientPwaFoundationTest.php
php artisan test tests/Feature/ClientApps/MuasamcongPriceListTest.php
```

Khi lỗi liên quan Queue/artifact:

```bash
ps aux | grep -E "queue:work|queue:listen|horizon" | grep -v grep
php artisan queue:restart
```

Nguyên tắc chung: xác nhận route → auth/permission → database record → storage path/permission → Queue state → UI polling, theo đúng thứ tự trước khi refactor.