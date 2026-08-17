# Muasamcong Personal Session — Windows helper

Bộ công cụ này giảm thao tác copy Cookie thủ công khi Personal Page Session hết hạn.

## Mục tiêu

Luồng hoạt động:

```text
Open-Muasamcong-Chrome.bat
  -> Chrome profile riêng + CDP chỉ trên 127.0.0.1:9222
  -> người dùng tự đăng nhập / CAPTCHA / OTP
  -> Update-Muasamcong-Session.bat
  -> PowerShell đọc cookie CHỈ của muasamcong.mpi.gov.vn qua CDP
  -> cookie đi qua STDIN vào WSL
  -> php artisan msc:import-personal-session --stdin --test
  -> PersonalSessionService mã hóa bằng Laravel Crypt và lưu database
```

Không tự động vượt CAPTCHA/OTP. Không đọc profile Chrome mặc định. Không ghi Cookie vào file tạm, clipboard hoặc command-line argument.

## Điều kiện

- Windows có Google Chrome.
- WSL có project tại `/var/www/source-laravel12`.
- Đã chạy migration của Module Muasamcong.
- Chrome riêng phải được mở bằng `Open-Muasamcong-Chrome.bat`.

Nếu project WSL nằm ở path khác, sửa `-WslProjectPath` trong `Update-Muasamcong-Session.bat`.

## Sử dụng

### 1. Mở Chrome riêng

Double-click:

```text
Open-Muasamcong-Chrome.bat
```

Chrome dùng profile riêng:

```text
%LOCALAPPDATA%\Muasamcong-CDP-Profile
```

và CDP chỉ bind localhost port `9222`.

### 2. Đăng nhập

Đăng nhập `muasamcong.mpi.gov.vn` trên cửa sổ Chrome này. CAPTCHA/OTP vẫn thực hiện bằng tay.

### 3. Cập nhật session

Double-click:

```text
Update-Muasamcong-Session.bat
```

Script chỉ lấy cookie của domain `muasamcong.mpi.gov.vn`, yêu cầu phải có `JSESSIONID`, sau đó gửi qua STDIN vào Laravel.

Nếu thành công sẽ thấy thông báo session đã được lưu và API lịch sử nhà thầu được xác minh.

## Artisan command

Có thể import thủ công qua STDIN:

```bash
cat cookie.txt | php artisan msc:import-personal-session --stdin --test
```

Không khuyến nghị lưu cookie vào file. Cách trên chỉ minh họa interface của command.

## Security

- Không mở remote-debugging ra LAN/Internet.
- Không đổi `--remote-debugging-address=127.0.0.1` thành `0.0.0.0`.
- Không dùng profile Chrome chính của người dùng.
- Không commit file chứa Cookie/HAR/token.
- Cookie được lưu database dưới dạng Laravel `Crypt::encryptString()`.
- PowerShell không in giá trị Cookie ra console.
- Cookie chỉ được truyền vào PHP qua STDIN.

## Khi session hết hạn

Không cần sửa `.env`.

```text
Mở Chrome riêng -> đăng nhập lại -> chạy Update-Muasamcong-Session.bat
```

Trang `/admin/muasamcong/config` vẫn cho phép dán Cookie thủ công như fallback và hiển thị thời gian xác minh gần nhất.
