# Muasamcong Module

Module Laravel tra cứu dữ liệu công khai trên Hệ thống mạng đấu thầu quốc gia:

- Tra cứu cơ sở dữ liệu giá thuốc trúng thầu.
- Tra cứu thông báo mời thầu/hồ sơ mời thầu theo từ khóa và khoảng ngày.
- Chọn và xuất kết quả HSMT ra Excel.
- Cung cấp API nội bộ cho chức năng tra cứu giá.

Phiên bản module: `2.0.0`  
Namespace: `Modules\Muasamcong`  
Laravel đã kiểm thử: `12.x`  
PHP đã kiểm thử: `8.4`  
Livewire: `3.x`

## 1. Cảnh báo về API upstream

Các endpoint trong module là endpoint nội bộ được frontend của `muasamcong.mpi.gov.vn` sử dụng, không phải API tích hợp công khai có hợp đồng ổn định.

- Endpoint, token, cookie và cấu trúc response có thể thay đổi.
- Smart token và session cookie có thời hạn.
- Không commit token/cookie vào Git.
- Không đưa token/cookie vào public property của Livewire, HTML, JavaScript hoặc log.
- Cần tuân thủ điều khoản sử dụng, giới hạn tần suất và quy định khai thác dữ liệu của Hệ thống mạng đấu thầu quốc gia.

Phiên bản cũ của module từng chứa nguyên smart-token và session cookie trong `App\Services\MuaSamCongService`. Bản `2.0.0` đã loại bỏ hoàn toàn các secret này khỏi source.

## 2. Kiến trúc

```text
Modules/Muasamcong
├── Console/Commands
│   ├── TestHsmtCommand.php
│   └── TestPricingCommand.php
├── Exports/HsmtExport.php
├── Http/Controllers
│   ├── Api/MuasamcongController.php
│   └── MuasamcongController.php
├── Livewire
│   ├── SearchHsmt.php
│   └── TracuuThuoctrungthau.php
├── Providers/MuasamcongServiceProvider.php
├── Services/MuaSamCongService.php
├── config/muasamcong.php
├── resources/views
├── routes
├── .env.example
├── composer.json
└── module.json
```

Các file `Livewire/Hsmt.php`, `resources/views/livewire/hsmt.blade.php` và `Models/Muasamcong.php` là scaffold mở rộng, hiện không chứa nghiệp vụ hoặc dữ liệu lưu trữ.

Module hiện không yêu cầu migration/database.

## 3. Luồng nghiệp vụ

### Tra cứu thuốc trúng thầu

```text
/muasamcong
→ TracuuThuoctrungthau
→ MuaSamCongService::searchPricing()
→ endpoint search_prc
→ hiển thị tên thuốc, hoạt chất, giá, số lượng, quyết định và mã TBMT
```

### Tra cứu HSMT

```text
/muasamcong/hsmt
→ SearchHsmt
→ validate từ khóa và khoảng ngày
→ MuaSamCongService::searchSmartV2()
→ endpoint contractor-selection smart search
→ chọn kết quả
→ HsmtExport
→ tải XLSX
```

## 4. Endpoint mặc định

Giá thuốc:

```text
POST https://muasamcong.mpi.gov.vn/o/egp-portal-personal-page/services/smart/search_prc
```

HSMT:

```text
POST https://muasamcong.mpi.gov.vn/o/egp-portal-contractor-selection-v2/services/smart/search?token=<smart-token>
```

Endpoint có thể thay đổi. Tất cả URL được đặt trong `config/muasamcong.php`, không hard-code trong Livewire/controller.

## 5. Yêu cầu

- PHP `8.2+`
- Laravel `11` hoặc `12`
- Livewire `3`
- Authentication của Laravel
- Laravel Sanctum nếu sử dụng API
- Tailwind CSS 4 và layout admin của dự án
- Maatwebsite Excel
- Spatie Laravel Permission nếu giữ middleware permission

Cài package:

```bash
composer require \
  livewire/livewire:^3.0 \
  maatwebsite/excel:^3.1 \
  spatie/laravel-permission:^6.0
```

## 6. Copy sang dự án khác

1. Copy nguyên thư mục:

```text
Modules/Muasamcong
```

2. Thêm PSR-4 vào `composer.json` của dự án đích:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Modules\\": "Modules/"
        }
    }
}
```

3. Chạy:

```bash
composer dump-autoload
```

4. Đăng ký provider trong Laravel 11/12 tại `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    Modules\Muasamcong\Providers\MuasamcongServiceProvider::class,
];
```

Với Laravel 10 trở xuống, đăng ký provider trong `config/app.php`.

5. Copy biến từ `Modules/Muasamcong/.env.example` vào `.env`.

6. Chạy:

```bash
php artisan optimize:clear
```

Module không có migration.

Command kiểm tra HSMT:

```bash
php artisan msc:test-hsmt "thuốc generic" "2026-07-01:2026-07-31"
```

Command kiểm tra endpoint giá bằng payload JSON:

```bash
php artisan msc:test --payload=Modules/Muasamcong/examples/pricing-payload.json
```

Command `msc:login` của phiên bản cũ đã bị loại vì nhận mật khẩu qua command line, in OTP, tắt SSL verification và lưu session cookie không mã hóa.

## 7. Biến môi trường

```env
MUASAMCONG_ORIGIN=https://muasamcong.mpi.gov.vn
MUASAMCONG_VERIFY_SSL=true
MUASAMCONG_TIMEOUT=20
MUASAMCONG_USER_AGENT="Mozilla/5.0 (compatible; Laravel Muasamcong Module)"

MUASAMCONG_SMART_TOKEN=
MUASAMCONG_SESSION_COOKIE=

MUASAMCONG_PRICING_ENDPOINT=https://muasamcong.mpi.gov.vn/o/egp-portal-personal-page/services/smart/search_prc
MUASAMCONG_CONTRACTOR_ENDPOINT=https://muasamcong.mpi.gov.vn/o/egp-portal-contractor-selection-v2/services/smart/search
MUASAMCONG_PORTAL_REFERER=https://muasamcong.mpi.gov.vn/
MUASAMCONG_PRICING_REFERER="https://muasamcong.mpi.gov.vn/web/guest/profile-info?menu=bid-pricing"
MUASAMCONG_PAGE_SIZE=20
```

| Biến | Mô tả |
|---|---|
| `MUASAMCONG_SMART_TOKEN` | Token query dùng cho tra cứu HSMT |
| `MUASAMCONG_SESSION_COOKIE` | Header Cookie của phiên nếu endpoint yêu cầu |
| `MUASAMCONG_VERIFY_SSL` | Luôn để `true` ở production |
| `MUASAMCONG_TIMEOUT` | Timeout request, giây |
| `MUASAMCONG_PAGE_SIZE` | Số kết quả một request |

Không đặt token/cookie thật trong `.env.example`.

## 8. Cách lấy smart token an toàn

Token/cookie cần được lấy từ phiên hợp lệ của chính người sử dụng:

1. Đăng nhập hoặc mở chức năng tra cứu trên `https://muasamcong.mpi.gov.vn`.
2. Mở Developer Tools của trình duyệt.
3. Chọn tab `Network`.
4. Thực hiện một lượt tra cứu HSMT.
5. Tìm request tới đường dẫn chứa:

```text
/o/egp-portal-contractor-selection-v2/services/smart/search
```

6. Lấy giá trị query parameter `token` và đặt vào:

```env
MUASAMCONG_SMART_TOKEN=...
```

7. Chỉ khi request yêu cầu session, lấy header `Cookie` của chính phiên đó và đặt vào:

```env
MUASAMCONG_SESSION_COOKIE="..."
```

8. Chạy:

```bash
php artisan optimize:clear
```

Token/cookie có thể hết hạn. Không tự động thu thập hoặc chia sẻ cookie của người khác.

## 9. Route

Web, trong dự án này dùng `web`, `auth:admin` và permission `view_muasamcong` của guard `admin`:

| Method | URI | Tên |
|---|---|---|
| GET | `/muasamcong` | `muasamcong.index` |
| GET | `/muasamcong/hsmt` | `muasamcong.hsmt` |

API, mặc định dùng `api` và `auth:sanctum`:

| Method | URI | Chức năng |
|---|---|---|
| GET | `/api/muasamcong` | Health/info |
| POST | `/api/muasamcong/search-pricing` | Tra cứu giá thuốc |

Request API:

```json
{
    "keyword": "paracetamol"
}
```

Middleware có thể thay đổi trong `config/muasamcong.php`. Không nên bỏ `auth:sanctum` nếu endpoint nội bộ không được phép public.

## 10. Permission

Route web hiện sử dụng:

```text
view_muasamcong (guard admin)
```

Super Admin được phép qua `Gate::before` của dự án. Các vai trò khác cần được gán
permission `view_muasamcong`; route vẫn luôn được bảo vệ bởi `auth:admin`.

## 11. Kiểm thử sau khi cài

```bash
php artisan optimize:clear
php artisan route:list --path=muasamcong
vendor/bin/pint --test Modules/Muasamcong
composer validate Modules/Muasamcong/composer.json --strict --no-check-publish
```

Kiểm thử thủ công:

1. Mở `/muasamcong`.
2. Nhập từ khóa thuốc và tìm kiếm.
3. Xác minh lỗi upstream được hiển thị thân thiện, không phải HTTP 500.
4. Mở `/muasamcong/hsmt`.
5. Chọn khoảng ngày và từ khóa.
6. Nếu báo thiếu smart token, cấu hình token theo mục 8.
7. Chọn một số kết quả và xuất Excel.
8. Gọi API bằng tài khoản/token Sanctum hợp lệ.
9. Kiểm tra log không chứa smart token, cookie hoặc toàn bộ response nhạy cảm.

## 12. Những lỗi phiên bản cũ đã xử lý

- `MuaSamCongService` và `HsmtExport` nằm ngoài module.
- Smart token và session cookie hard-code trực tiếp trong source.
- SSL verification bị tắt trong luồng login cũ.
- API nội bộ public và không validate keyword.
- Livewire tự parse ngày, dễ gây exception.
- Raw body upstream được hiển thị cho người dùng.
- Không bắt `ConnectionException`, có nguy cơ HTTP 500.
- HTML chứa closing tag `</>` không hợp lệ.
- Tiêu đề trang vẫn là `New Module`.
- Module thiếu provider, config, manifest, composer metadata và tài liệu.

## 13. Giới hạn

- Token/cookie upstream không ổn định và cần cập nhật theo phiên.
- Module chỉ lấy trang đầu tiên của kết quả. Nếu cần tải toàn bộ, phải xác minh cơ chế phân trang hiện hành trước khi triển khai.
- Cấu trúc trường Elasticsearch có thể thay đổi.
- Module không cache kết quả để tránh lưu dữ liệu upstream ngoài ý muốn.
- Model `Muasamcong` hiện chỉ là scaffold và chưa được sử dụng.
- Giao diện dùng layout `Admin::layouts.master` và Tailwind CSS 4 của dự án hiện tại.

## 14. Prompt hoàn thiện module tại dự án mới

Sau khi copy module, gửi prompt sau cho AI/lập trình viên:

```text
Hãy tích hợp và hoàn thiện Modules/Muasamcong trong dự án Laravel hiện tại.

Trước khi thay đổi:
1. Đọc toàn bộ Modules/Muasamcong/README.md.
2. Audit phiên bản PHP, Laravel, Livewire, authentication, Sanctum, permission và Blade layout của dự án.
3. Đọc composer.json, module.json, .env.example, config/muasamcong.php và MuasamcongServiceProvider.php.
4. Kiểm tra composer.json cấp dự án đã autoload "Modules\\": "Modules/".
5. Không hard-code hoặc in ra log smart-token, cookie, credential hay Authorization header.

Yêu cầu:
1. Cài hoặc xác minh dependency của module.
2. Đăng ký Modules\Muasamcong\Providers\MuasamcongServiceProvider.
3. Tích hợp page Blade với layout hiện tại.
4. Điều chỉnh middleware auth, Sanctum và permission phù hợp dự án nhưng không làm API public ngoài ý muốn.
5. Giữ nguyên route nếu không xung đột.
6. Đưa tất cả cấu hình endpoint/token/cookie vào .env; chỉ thêm placeholder vào .env.example.
7. Xác minh endpoint hiện tại từ chính frontend muasamcong.mpi.gov.vn trước khi sửa.
8. Kiểm tra tra cứu giá thuốc và tra cứu HSMT bằng request nhỏ.
9. Bắt ConnectionException và lỗi upstream để giao diện không trả HTTP 500.
10. Xác minh cấu trúc response trước khi map field.
11. Kiểm tra export Excel.
12. Không tạo migration/database nếu chưa có yêu cầu lưu dữ liệu.
13. Không thay đổi dữ liệu hoặc file ngoài phạm vi module.
14. Chạy PHP lint, Laravel Pint, composer validate và kiểm tra route.

Khi hoàn tất, báo:
- File đã sửa.
- Dependency đã cài.
- Biến .env người dùng phải tự nhập.
- Route hoạt động.
- Kết quả kiểm thử từng luồng.
- Phần chưa kiểm thử được do thiếu token/cookie upstream.
```

Nếu dự án không dùng AdminLTE, bổ sung:

```text
Dự án không dùng AdminLTE. Hãy đổi hai page Blade ngoài cùng sang layout hiện tại nhưng giữ nguyên Livewire component và nghiệp vụ.
```
