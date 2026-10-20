# Muasamcong Personal Session — Windows helper

Bộ công cụ này giảm thao tác copy Cookie thủ công khi Personal Page Session hết hạn.

## Endpoint portal hiện tại

Windows helper mở Personal Page trên `https://muasamcong.mof.gov.vn` và chỉ lấy cookie áp dụng cho API Personal Page trên cùng host MOF. Nếu SSO chuyển sang trang đăng nhập, người dùng cần đăng nhập thủ công trước khi thử làm mới session. Tool không tự chuyển cookie MPI sang MOF.

## Cơ chế

Mua sắm công có nhiều lớp session. Cookie Keycloak/SSO có thể còn hiệu lực trong khi portal `JSESSIONID` dùng cho Personal Page API đã hết hạn. Vì vậy có trường hợp API lịch sử nhà thầu báo hết session nhưng khi mở lại Mua sắm công, trình duyệt vào thẳng mà không cần nhập lại tài khoản.

Tool tận dụng Chrome profile riêng để mở lại Personal Page. Nếu SSO còn hiệu lực, portal có thể tự phát hành session mới. Tool sau đó đọc Cookie bằng Chrome DevTools Protocol (CDP), gửi bằng HTTPS POST về Laravel và để server xác minh trực tiếp API `get-list-notify-contractor-join`.

Không tự động nhập username/password, không vượt CAPTCHA/OTP và không đọc Chrome profile mặc định.

## Điều kiện

- Windows có Google Chrome.
- Truy cập được website Laravel/VPS bằng HTTPS.
- Đã chạy migration Module Muasamcong.
- Tải ZIP mới nhất tại `/admin/muasamcong/session-tool/windows` (có thể truy cập từ `/admin/muasamcong/config`).
- Sau khi cập nhật source trên môi trường phục vụ Laravel, tải lại ZIP và giải nén vào thư mục mới để tránh dùng script MPI cũ.

Chrome riêng dùng profile:

```text
%LOCALAPPDATA%\Muasamcong-CDP-Profile
```

CDP chỉ bind localhost `127.0.0.1:9222`.

## Menu

Chạy:

```text
Muasamcong-Session-Tool.bat
```

Menu hiện tại:

```text
1. Kiem tra Cookie/SSO tren Chrome rieng
2. Lam moi Session tu dong va cap nhat Server
3. Mo Chrome de dang nhap Mua sam cong
4. Gui Cookie hien tai len Server
0. Thoat
```

### 1. Kiểm tra Cookie/SSO

Tool đọc Cookie áp dụng cho Personal Page API và chỉ hiển thị metadata an toàn như số Cookie, số `JSESSIONID` và thời điểm hết hạn của `KEYCLOAK_IDENTITY` nếu Chrome cung cấp. Giá trị Cookie không được in ra console.

Lưu ý: Cookie còn tồn tại trong Chrome không đồng nghĩa API server còn chấp nhận. Việc xác minh thật chỉ diễn ra khi gửi session lên Laravel.

### 2. Làm mới Session tự động

Đây là luồng khuyến nghị khi API Personal Page hết hạn:

```text
/admin/muasamcong/config
  -> Tạo Link cập nhật Windows (token dùng một lần)
  -> chạy tool, chọn menu 2
  -> dán link
  -> tool mở Personal Page bằng Chrome profile riêng
  -> nếu SSO còn hạn, portal tự tạo session mới
  -> tool lấy Cookie mới qua CDP
  -> POST Cookie lên /api/muasamcong/update-cookie
  -> Laravel xác minh API
  -> lưu Cookie mã hóa vào database
```

Nếu Chrome chuyển về trang đăng nhập, SSO cũng đã hết hoặc cần xác thực lại. Khi đó đăng nhập bình thường trên cửa sổ Chrome vừa mở, hoàn tất CAPTCHA/OTP nếu có, rồi chạy menu 2 lại với link cập nhật mới.

### 3. Mở Chrome để đăng nhập

Dùng khi SSO không còn hiệu lực. Tool không tự động thao tác đăng nhập.

### 4. Gửi Cookie hiện tại lên Server

Dùng khi Chrome đã có session hợp lệ và không cần mở/refresh Personal Page trước. Tool vẫn yêu cầu link cập nhật dùng một lần từ UI.

## Vì sao vẫn cần Link cập nhật?

Link chứa token một lần, hiệu lực ngắn, dùng để xác thực việc POST Cookie từ máy Windows lên VPS. Không lưu API key dài hạn trong ZIP và không cho một tool cũ có quyền cập nhật session vô thời hạn.

## Security

- Không mở remote debugging ra LAN/Internet.
- Không đổi `--remote-debugging-address=127.0.0.1` thành `0.0.0.0`.
- Không dùng Chrome profile chính.
- Không commit Cookie/HAR/token.
- Không in giá trị Cookie ra console.
- Token cập nhật dùng một lần và hết hạn nhanh.
- Cookie được Laravel lưu mã hóa bằng `Crypt`.
- Server luôn xác minh API Personal Page trước khi coi session là hợp lệ.

## Khi session hết hạn

Ưu tiên:

```text
Tạo link mới trên Config -> menu 2 -> dán link
```

Nếu SSO còn hạn, thường không cần nhập lại tài khoản. Nếu SSO hết, menu 3 để đăng nhập lại rồi chạy menu 2.

## Quy trình vận hành đã nghiệm thu trên Windows (2026-10-20)

1. Tải ZIP từ `/admin/muasamcong/session-tool/windows`, giải nén, chạy `Muasamcong-Session-Tool.bat`.
2. Chọn menu **3** nếu cần mở Chrome profile riêng trên MOF và đăng nhập thủ công; không nhập thông tin đăng nhập vào tool.
3. Chọn menu **1** để kiểm tra cookie cục bộ. Kết quả thực tế do người dùng xác nhận: **12 cookie áp dụng, 1 JSESSIONID**; CDP/cookie detection PASS. Việc không đọc được expiry của `KEYCLOAK_IDENTITY` không tự chứng minh session lỗi.
4. Tại `/admin/muasamcong/config` tạo **link cập nhật Windows mới** (token một lần), chọn menu **2** và dán link vào tool. Chỉ coi cập nhật thành công khi server xác minh Personal Page API và UI xác nhận.
5. Menu **4** gửi cookie đang có lên server, không mở/làm mới portal; vẫn cần link cập nhật mới.

**Nghiệm thu:** Người dùng xác nhận **UI PASS** sau khi chuyển Windows helper sang MOF. Đây là xác nhận thao tác trên môi trường người dùng, không đồng nghĩa đã deploy Production hoặc chạy lại mọi automated test sau thay đổi script.

### Xử lý sự cố

- `Chua co JSESSIONID Personal Page`: xác nhận đang dùng ZIP MOF mới, Chrome profile riêng đã đăng nhập tại `muasamcong.mof.gov.vn`; chạy menu 3 rồi menu 1. Không bỏ điều kiện kiểm tra `JSESSIONID` để né lỗi.
- Menu 1 thấy `JSESSIONID` nhưng menu 2 lỗi: cookie tồn tại không chứng minh API chấp nhận; kiểm tra kết quả server và cấu hình endpoint Personal Page, không in cookie/token.
- Chrome hiển thị trang đăng nhập: hoàn tất đăng nhập/CAPTCHA/OTP thủ công rồi tạo link cập nhật mới.
- Link hết hạn hoặc đã sử dụng: tạo link một lần mới, không tái sử dụng link cũ và không chia sẻ phần token sau dấu `#`.
- Tránh mở nhiều Chrome CDP cùng cổng `127.0.0.1:9222`; không chuyển CDP sang interface mạng công cộng.

### Phạm vi thay đổi và rollback

Bản MOF đổi `$PortalUrl`, `$CookieUrl` trong `Muasamcong-Session-Tool.ps1` và URL mở Chrome trong `Open-Muasamcong-Chrome.bat`. Không đổi endpoint cập nhật HTTPS của Laravel, cách lấy cookie CDP, điều kiện JSESSIONID hay mã hóa lưu trữ server. Nếu upstream MOF thay đổi, audit endpoint và cookie trước khi chỉnh script; rollback bằng commit Git đã biết và nghiệm thu lại, không tự động khôi phục MPI vì endpoint MPI có thể redirect.
