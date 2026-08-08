# Auth Module Analysis

## Phạm vi và phương pháp

- Module được phân tích: `Modules/Auth`.
- Tài liệu định hướng đã đọc trước: `ROADMAP.md` (assessment date `2026-06-14`).
- Luồng đã truy vết: Route -> Controller -> Page Blade -> Livewire PHP -> Livewire Blade -> Shared Components -> Service -> Import/Export -> Model -> Migration.
- Các tệp ngoài `Modules/Auth` chỉ được đọc khi là dependency trực tiếp của Auth hoặc cần xác minh guard/schema; không phân tích lan sang nghiệp vụ module khác.
- Không có code nào được refactor trong lần phân tích này.
- `php artisan route:list` chưa xác minh được toàn bộ vì route của module khác tham chiếu class không tồn tại: `Modules\Users\Http\Controllers\Api\UsersController`. Danh sách route bên dưới được lập trực tiếp từ source.

## 1. Module purpose

`Modules/Auth` là module hỗ trợ xác thực cho khu vực quản trị, gồm:

- Trang đăng nhập admin bằng email/password.
- Đăng nhập Google OAuth qua Laravel Socialite.
- Đăng xuất guard `admin`.
- Một API health/placeholder công khai tại `/api/auth`.
- Các migration hạ tầng dùng chung cho cache, queue và session.

Module hiện được khai báo là `shell` tại `Modules/Auth/config/module.php`, nhưng `Modules/ModuleServiceProvider.php` lại coi Auth là `support` trong fallback. Về trách nhiệm kiến trúc, Auth phù hợp với `support` hơn vì nó cung cấp xác thực cho shell Admin thay vì là presentation shell.

Dependency trực tiếp:

- `Modules/Admin/Services/AuthService.php`: xử lý Google user.
- `Modules/Admin/Models/Setting.php`: đọc nội dung/branding trang đăng nhập.
- `App/Models/User.php`: authenticatable model của cả guard `web` và `admin`.
- `config/auth.php`: cấu hình guard/provider.
- `config/services.php`: cấu hình Google Socialite.

## 2. Route list

### Web routes

Nguồn: `Modules/Auth/routes/web.php`.

| Method | URI | Name | Middleware | Handler | Nhận xét |
|---|---|---|---|---|---|
| GET | `/admin/login` | `admin.login` | `web` | `AuthController@login` | Trang đăng nhập admin. |
| GET | `/auth/google` | `google` | `web` | `GoogleController@redirectToGoogle` | Bắt đầu Google OAuth; không có `guest` hoặc throttle riêng. |
| GET | `/auth/google/callback` | `google.callback` | `web` | `GoogleController@handleGoogleCallback` | Callback OAuth; tự provision user qua service. |
| GET | `/login` | `login` | `web` | `AuthController@login` | Thực tế vẫn là đăng nhập guard `admin`, không phải guard `web`. |
| GET | `/register` | `register` | `web` | `AuthController@register` | Handler `register()` không tồn tại. |
| POST | `/admin/logout` | `admin.logout` | `web`, `auth:admin` | `AuthController@logout` | Đăng xuất admin và invalidate toàn session. |

### API routes

Nguồn: `Modules/Auth/routes/api.php`; prefix `/api` và middleware `api` được thêm tại `Modules/ModuleServiceProvider.php`.

| Method | URI | Name | Middleware | Handler | Nhận xét |
|---|---|---|---|---|---|
| GET | `/api/auth` | Không có | `api` | `Api\AuthController@index` | Endpoint công khai, chỉ trả chuỗi placeholder. |

### Route findings

- **[P0]** Google OAuth routes có thể cấp session `admin` cho bất kỳ tài khoản Google nào vì không có middleware/policy hoặc allowlist domain ở `Modules/Auth/routes/web.php`, và service tự tạo user tại `Modules/Admin/Services/AuthService.php`.
- **[P1]** Route `/register` gọi method không tồn tại, chắc chắn gây lỗi khi truy cập: `Modules/Auth/routes/web.php`, `Modules/Auth/Http/Controllers/AuthController.php`.
- **[P1]** `/login` và `/admin/login` cùng dùng một handler chỉ kiểm tra/đăng nhập guard `admin`, làm mơ hồ ranh giới user/admin: `Modules/Auth/routes/web.php`, `Modules/Auth/Http/Controllers/AuthController.php`, `Modules/Auth/Livewire/Auth/LoginForm.php`.
- **[P2]** API `/api/auth` là endpoint placeholder công khai, không có giá trị xác thực thực tế: `Modules/Auth/routes/api.php`, `Modules/Auth/Http/Controllers/Api/AuthController.php`.

## 3. Controllers

### `Modules/Auth/Http/Controllers/AuthController.php`

Public methods:

- `login()`: nếu guard `admin` đã đăng nhập thì redirect `admin.dashboard`; nếu chưa thì render `Auth::pages.auth.login`.
- `logout()`: logout guard `admin`, invalidate session, regenerate CSRF token, redirect route `login`.

Findings:

- **[P1]** Thiếu `register()` dù route đã khai báo: `Modules/Auth/Http/Controllers/AuthController.php`, `Modules/Auth/routes/web.php`.
- **[P1]** Logout redirect tới route chung `login` thay vì `admin.login`, tiếp tục làm mờ guard đích: `Modules/Auth/Http/Controllers/AuthController.php`.
- **[P1]** `session()->invalidate()` đăng xuất hiệu dụng mọi state dùng chung session, không chỉ guard admin; cần xác định đây có phải hành vi mong muốn khi `web` và `admin` cùng provider/session hay không: `Modules/Auth/Http/Controllers/AuthController.php`, `config/auth.php`.

### `Modules/Auth/Http/Controllers/GoogleController.php`

Public methods:

- `__construct(AuthService $authService)`: inject service đang thuộc module Admin.
- `redirectToGoogle()`: redirect sang Google.
- `handleGoogleCallback()`: lấy Google user, gọi `handleGoogleUser()`, redirect dashboard; bắt mọi `Exception`.

Findings:

- **[P0]** Không kiểm tra Google Workspace hosted domain, email allowlist, invitation, role quản trị hoặc quyền trước khi cấp session admin: `Modules/Auth/Http/Controllers/GoogleController.php`, `Modules/Admin/Services/AuthService.php`.
- **[P1]** Trả nguyên `$e->getMessage()` cho trình duyệt có thể làm lộ lỗi OAuth, database hoặc cấu hình nội bộ: `Modules/Auth/Http/Controllers/GoogleController.php`.
- **[P1]** Log nối chuỗi exception thiếu context có cấu trúc/correlation ID và có nguy cơ ghi dữ liệu nhạy cảm do provider trả về: `Modules/Auth/Http/Controllers/GoogleController.php`.
- **[P1]** Auth phụ thuộc ngược vào Admin presentation shell, trái hướng ownership nêu trong `ROADMAP.md`: `Modules/Auth/Http/Controllers/GoogleController.php`, `Modules/Admin/Services/AuthService.php`.

### `Modules/Auth/Http/Controllers/Api/AuthController.php`

Public methods:

- `index()`: trả JSON `{"status":"Api Auth success"}`.

Finding:

- **[P2]** Class không cung cấp login/token/logout API và tên `AuthController` gây hiểu nhầm về capability: `Modules/Auth/Http/Controllers/Api/AuthController.php`.

## 4. Page Blade files

### `Modules/Auth/resources/views/pages/auth/login.blade.php`

- Extend `Auth::layouts.auth`.
- Mount Livewire alias `auth.auth.login-form`.

Findings:

- Không có logic nghiệp vụ trực tiếp.
- **[P2]** Tên alias lặp `auth.auth` do cả module và thư mục class đều mang tên Auth; hoạt động theo convention của `Modules/ModuleServiceProvider.php` nhưng khó đọc: `Modules/Auth/resources/views/pages/auth/login.blade.php`, `Modules/Auth/Livewire/Auth/LoginForm.php`.

### `Modules/Auth/resources/views/layouts/auth.blade.php`

- Layout hỗ trợ cả `$slot` và `@yield('content')`.
- Load Tailwind/Vite và Livewire assets.
- Xuất `NODEJS_SERVER_URL` và `NODEJS_SERVER_PORT` vào JavaScript toàn cục.

Findings:

- **[P1]** Dùng `env()` trực tiếp trong Blade thay vì `config()`, dễ sai khi config cache và đưa cấu hình chat không liên quan vào trang đăng nhập: `Modules/Auth/resources/views/layouts/auth.blade.php`.
- **[P2]** Layout hỗ trợ đồng thời hai composition style (`$slot` và section), làm tăng bề mặt không cần thiết khi hiện chỉ có page dùng `@extends`: `Modules/Auth/resources/views/layouts/auth.blade.php`, `Modules/Auth/resources/views/pages/auth/login.blade.php`.

## 5. Livewire PHP classes

### `Modules/Auth/Livewire/Auth/LoginForm.php`

Public state:

- Credentials: `$email`, `$password`, `$remember`.
- Branding: `$logo`, `$login_name_line_1`, `$login_name_line_2`, `$login_description`.

Public methods:

- `mount()`: chạy bốn lần `Setting::getValue()` để lấy branding.
- `login()`: validate email/password, gọi `Auth::guard('admin')->attempt()`, regenerate session và redirect dashboard.
- `render()`: render `Auth::livewire.auth.login-form`.

Findings:

- **[P0]** Password login không thêm điều kiện `is_active = true`; tài khoản bị khóa vẫn có thể đăng nhập nếu mật khẩu đúng: `Modules/Auth/Livewire/Auth/LoginForm.php`.
- **[P0]** Không có rate limiting theo email/IP cho action `login()`, tạo bề mặt brute-force/credential stuffing: `Modules/Auth/Livewire/Auth/LoginForm.php`.
- **[P1]** Không normalize email (`trim`, lowercase/canonicalization) trước validate/attempt, có thể gây lỗi đăng nhập không nhất quán: `Modules/Auth/Livewire/Auth/LoginForm.php`.
- **[P1]** `$remember` không có validation boolean và public properties không khai báo kiểu: `Modules/Auth/Livewire/Auth/LoginForm.php`.
- **[P1]** Branding của Auth phụ thuộc trực tiếp model Admin thay vì service/config contract: `Modules/Auth/Livewire/Auth/LoginForm.php`, `Modules/Admin/Models/Setting.php`.
- **[P1]** `mount()` phát 4 query tuần tự cho 4 key settings; đây không phải N+1 theo relation nhưng là query amplification trên mọi lần mở login: `Modules/Auth/Livewire/Auth/LoginForm.php`, `Modules/Admin/Models/Setting.php`.

## 6. Livewire Blade views

### `Modules/Auth/resources/views/livewire/auth/login-form.blade.php`

- Form email/password/remember gọi `wire:submit="login"`.
- Hiển thị validation errors.
- Link đăng nhập Google qua route `google`.

Findings:

- **[P1]** Nút submit không có `wire:loading.attr="disabled"`, người dùng có thể gửi nhiều request đồng thời: `Modules/Auth/resources/views/livewire/auth/login-form.blade.php`.
- **[P1]** Input thiếu `autocomplete="username"` và `autocomplete="current-password"`, làm giảm khả năng dùng password manager đúng cách: `Modules/Auth/resources/views/livewire/auth/login-form.blade.php`.
- **[P2]** Comment `LOGO` bị lặp và comment `SCHOOL NAME` không phù hợp domain hiện tại: `Modules/Auth/resources/views/livewire/auth/login-form.blade.php`.
- **[P2]** Class `w-128` không phải utility Tailwind mặc định; cần xác minh CSS output hoặc thay bằng utility hợp lệ: `Modules/Auth/resources/views/livewire/auth/login-form.blade.php`.

## 7. Shared Components

Không có class Blade component hoặc anonymous Blade component trong `Modules/Auth`.

Các thành phần dùng chung thực tế:

- Layout nội bộ: `Modules/Auth/resources/views/layouts/auth.blade.php`.
- Livewire registration convention: `Modules/ModuleServiceProvider.php`.
- Assets dùng chung: `resources/css/tailwind.css`, `resources/js/tailwind.js`.

Recommendation:

- **[P2]** Giữ form login là component nội bộ cho đến khi có ít nhất một luồng xác thực khác cần tái sử dụng; chưa có bằng chứng cần tạo shared component mới: `Modules/Auth/resources/views/livewire/auth/login-form.blade.php`.

## 8. Services and public methods

Module Auth không có thư mục `Services`.

Dependency service trực tiếp:

### `Modules/Admin/Services/AuthService.php`

Public methods:

- `handleGoogleUser($googleUser)`: tìm user theo `google_id` hoặc email; cập nhật/tạo user; tạo role `User` guard `admin`; gán role; kiểm tra `is_active`; login guard `admin`; cập nhật `last_login_at`; trả user.

Findings:

- **[P0]** Tự động tạo mọi Google account thành user active rồi cấp session guard `admin`; role tên `User` không thay thế kiểm soát quyền vào admin vì nhiều route chỉ yêu cầu `auth:admin`: `Modules/Admin/Services/AuthService.php`, `Modules/Auth/Http/Controllers/GoogleController.php`, `config/auth.php`.
- **[P0]** Access token và refresh token Google được lưu plaintext vào database: `Modules/Admin/Services/AuthService.php`, `App/Models/User.php`, `Modules/User/database/migrations/2026_04_27_214255_add_profile_and_social_fields_to_users_table.php`.
- **[P1]** Không có transaction cho chuỗi create/update user -> create role -> assign role -> update last login; lỗi giữa chừng để lại trạng thái một phần: `Modules/Admin/Services/AuthService.php`.
- **[P1]** Race condition khi hai callback đồng thời cùng email có thể cùng không tìm thấy user rồi cùng insert, một request vỡ unique constraint: `Modules/Admin/Services/AuthService.php`, `Modules/User/database/migrations/-0001_11_30_000006_create_users_table.php`.
- **[P1]** User hiện hữu được liên kết với Google chỉ bằng email mà không có quy trình xác nhận liên kết tài khoản: `Modules/Admin/Services/AuthService.php`.
- **[P1]** Service dùng exception chung và thực hiện cả persistence, role provisioning, authentication và audit timestamp, khó kiểm thử và khó áp invariant nhất quán: `Modules/Admin/Services/AuthService.php`.

## 9. Models and database tables

### Model nội bộ

`Modules/Auth/Models/Auth.php`:

- Eloquent model rỗng dùng `HasFactory`.
- Theo convention sẽ trỏ tới bảng `auths`, nhưng module không có migration tạo bảng này.
- Không tìm thấy reference đến class này trong application source.

### Models/tables Auth sử dụng trực tiếp

| Model/source | Table | Vai trò |
|---|---|---|
| `App/Models/User.php` | `users` | Principal cho cả guard `web` và `admin`. |
| `Modules/Admin/Models/Setting.php` | `settings` | Branding trang login. |
| Spatie `Role` | `roles`, pivot permission tables | Gán role khi provision Google user. |

### Tables do migration Auth tạo

- `cache`
- `cache_locks`
- `jobs`
- `job_batches`
- `failed_jobs`
- `sessions`

Findings:

- **[P0]** Guard `web` và `admin` cùng dùng provider `users`; không có principal/provider riêng cho admin, nên việc dùng guard `admin` không tự tạo ranh giới đặc quyền: `config/auth.php`, `App/Models/User.php`.
- **[P1]** `Modules/Auth/Models/Auth.php` là placeholder không có bảng tương ứng và có vẻ không dùng.
- **[P1]** Bảng hạ tầng cache/queue/session thuộc platform infrastructure hơn là Auth domain; ownership hiện tại làm việc disable module Auth có thể ảnh hưởng migration nền tảng: `Modules/Auth/database/migrations/*.php`, `Modules/Auth/config/module.php`.

## 10. Import/Export classes

Không có Import/Export class, route hoặc service import/export trong `Modules/Auth`.

Không có recommendation riêng ngoài việc không thêm abstraction import/export vào module này.

## 11. Authorization/security risks

| Priority | Risk | Exact paths | Recommendation |
|---|---|---|---|
| P0 | Bất kỳ Google account hợp lệ nào cũng có thể được provision và nhận session `admin`. | `Modules/Auth/routes/web.php`; `Modules/Auth/Http/Controllers/GoogleController.php`; `Modules/Admin/Services/AuthService.php` | **[P0]** Fail closed: yêu cầu invitation/allowlist domain, `is_active`, role/permission admin rõ ràng trước khi gọi `Auth::guard('admin')->login()`. |
| P0 | User bị khóa vẫn đăng nhập bằng password vì attempt không lọc `is_active`. | `Modules/Auth/Livewire/Auth/LoginForm.php` | **[P0]** Đưa trạng thái active vào credential/invariant và test cả password lẫn OAuth. |
| P0 | Không rate-limit password login. | `Modules/Auth/Livewire/Auth/LoginForm.php`; `Modules/Auth/routes/web.php` | **[P0]** Thêm limiter theo email chuẩn hóa + IP, lockout/backoff và audit failed attempts. |
| P0 | Guard admin và web dùng cùng provider `users`; `auth:admin` đang bị dùng như authorization boundary. | `config/auth.php`; `App/Models/User.php`; `Modules/Auth/routes/web.php` | **[P0]** Quyết định mô hình principal: provider admin riêng hoặc bắt buộc capability middleware/policy trên mọi admin route; không coi guard name là quyền. |
| P0 | Google OAuth token lưu plaintext. | `Modules/Admin/Services/AuthService.php`; `App/Models/User.php`; `Modules/User/database/migrations/2026_04_27_214255_add_profile_and_social_fields_to_users_table.php` | **[P0]** Không lưu token nếu không cần; nếu cần thì encrypted cast/key management, scope tối thiểu và rotation/revocation. |
| P1 | Raw exception được log và trả cho user. | `Modules/Auth/Http/Controllers/GoogleController.php` | **[P1]** Trả thông báo cố định, log exception có cấu trúc và redaction. |
| P1 | Permissions `view_auth/create_auth/edit_auth/delete_auth` được khai báo nhưng không được enforce hoặc tham chiếu. | `Modules/Auth/config/module.php`; `Modules/Auth/routes/web.php` | **[P1]** Thay generic CRUD permissions bằng capability thực (`auth.login`, `auth.google`, hoặc admin access policy) hoặc xóa manifest permissions không có nghĩa. |
| P1 | Chưa có test xác thực/authorization. | `tests/Feature/ExampleTest.php`; `tests/Unit/ExampleTest.php` | **[P1]** Thêm regression tests cho guard separation, inactive account, OAuth allowlist, brute force, logout và denied access. |

## 12. Validation problems

| Priority | Problem | Exact paths | Recommendation |
|---|---|---|---|
| P1 | Chỉ validate `required|email` và `required`; không giới hạn độ dài input. | `Modules/Auth/Livewire/Auth/LoginForm.php` | **[P1]** Thêm `string`, giới hạn max hợp lý, normalize email trước lookup và validate `$remember` là boolean. |
| P1 | Google payload được dùng trực tiếp mà không kiểm tra email tồn tại/verified/domain/shape. | `Modules/Auth/Http/Controllers/GoogleController.php`; `Modules/Admin/Services/AuthService.php` | **[P1]** Validate DTO từ Socialite và enforce verified email + tenant/domain policy trong service. |
| P1 | Route `/register` không có implementation/validation. | `Modules/Auth/routes/web.php`; `Modules/Auth/Http/Controllers/AuthController.php` | **[P1]** Xóa route nếu không hỗ trợ self-registration; nếu hỗ trợ thì thiết kế workflow riêng với validation, verification và policy. |

## 13. Transaction risks

| Priority | Risk | Exact paths | Recommendation |
|---|---|---|---|
| P1 | Google provisioning gồm nhiều write không atomic. | `Modules/Admin/Services/AuthService.php` | **[P1]** Bọc user/role/linking/audit writes trong transaction; chỉ login sau commit. |
| P1 | Concurrent callbacks có thể tranh chấp unique email/google_id hoặc `firstOrCreate` role. | `Modules/Admin/Services/AuthService.php`; `Modules/User/database/migrations/-0001_11_30_000006_create_users_table.php`; `Modules/User/database/migrations/2026_04_27_214255_add_profile_and_social_fields_to_users_table.php` | **[P1]** Dùng idempotent upsert/retry theo unique constraint và khóa phù hợp. |
| P1 | User hiện hữu có thể được update token/google_id trước khi kiểm tra `is_active`, nên callback thất bại vẫn thay đổi dữ liệu. | `Modules/Admin/Services/AuthService.php` | **[P1]** Kiểm tra eligibility trước mutation và transaction hóa toàn bộ linking. |

Password login chỉ đọc user rồi cập nhật session, không có multi-record database transaction cần bổ sung tại `Modules/Auth/Livewire/Auth/LoginForm.php`.

## 14. N+1/query performance risks

Không thấy N+1 relation loop trong module.

Các query risk:

- **[P1]** `mount()` gọi `Setting::getValue()` bốn lần, mỗi lần một query: `Modules/Auth/Livewire/Auth/LoginForm.php`, `Modules/Admin/Models/Setting.php`.
- **[P1]** Login page là endpoint thường xuyên bị bot truy cập; bốn query settings cho mỗi request làm tăng tải không cần thiết: `Modules/Auth/Livewire/Auth/LoginForm.php`.
- **[P2]** Query Google user dùng `google_id OR email`; cả hai cột có unique index nên chấp nhận được, nhưng cần đo execution plan trên production DB: `Modules/Admin/Services/AuthService.php`, `Modules/User/database/migrations/-0001_11_30_000006_create_users_table.php`, `Modules/User/database/migrations/2026_04_27_214255_add_profile_and_social_fields_to_users_table.php`.

Recommendations:

- **[P1]** Lấy bốn setting bằng một query `whereIn`, hoặc dùng settings service/cache với invalidation rõ ràng: `Modules/Auth/Livewire/Auth/LoginForm.php`, `Modules/Admin/Models/Setting.php`.
- **[P2]** Thêm query-count test cho render login và OAuth callback: `tests/Feature`, `Modules/Auth/Livewire/Auth/LoginForm.php`.

## 15. Duplicate logic

- **[P1]** Hai URI `/login` và `/admin/login` trùng handler và cùng phục vụ admin login: `Modules/Auth/routes/web.php`.
- **[P1]** Authentication responsibility bị chia giữa `Modules/Auth/Livewire/Auth/LoginForm.php` và `Modules/Admin/Services/AuthService.php`; password flow nằm trong component, OAuth flow nằm trong Admin service.
- **[P1]** Eligibility check không nhất quán: OAuth kiểm tra `is_active`, password login không kiểm tra: `Modules/Admin/Services/AuthService.php`, `Modules/Auth/Livewire/Auth/LoginForm.php`.
- **[P2]** Layout hỗ trợ cả Blade section và component slot dù hiện chỉ dùng section: `Modules/Auth/resources/views/layouts/auth.blade.php`.

Recommendations:

- **[P1]** Đưa invariant đăng nhập chung (active, admin eligibility, audit, rate policy hooks) vào service thuộc Auth/support domain và dùng cho cả password/OAuth.
- **[P1]** Chọn route canonical `admin.login`; chỉ giữ `/login` nếu có user-facing authentication riêng và guard riêng.

## 16. Files that look unused

| Priority | File | Evidence | Recommendation |
|---|---|---|---|
| P1 | `Modules/Auth/Models/Auth.php` | Không có reference trong source; không có bảng `auths`; toàn bộ mapping chỉ là comment. | **[P1]** Xác nhận bằng architecture/coverage test rồi xóa placeholder. |
| P2 | `Modules/Auth/Http/Controllers/Api/AuthController.php` | Chỉ trả placeholder status, không có auth behavior. | **[P2]** Xóa endpoint/controller nếu không có consumer; nếu là health check, chuyển về health subsystem và đặt tên đúng. |
| P2 | `Modules/Auth/routes/api.php` | Chỉ khai báo placeholder `/api/auth`; phần Sanctum thật bị comment. | **[P2]** Xóa code comment và route nếu API auth chưa nằm trong scope. |
| P1 | Permissions trong `Modules/Auth/config/module.php` | Không tìm thấy reference ngoài manifest. | **[P1]** Thay bằng capability được enforce hoặc xóa declarations giả. |

Không đánh dấu page/layout/Livewire view là unused vì chúng tạo thành luồng login đang được route gọi trực tiếp.

## 17. Migration analysis

| Migration | Table | Assessment |
|---|---|---|
| `Modules/Auth/database/migrations/-0001_11_30_000000_create_cache_table.php` | `cache` | Schema cache database tiêu chuẩn, nhưng ownership không thuộc Auth. |
| `Modules/Auth/database/migrations/-0001_11_30_000001_create_cache_locks_table.php` | `cache_locks` | Schema lock tiêu chuẩn. |
| `Modules/Auth/database/migrations/-0001_11_30_000002_create_jobs_table.php` | `jobs` | Schema queue tiêu chuẩn. |
| `Modules/Auth/database/migrations/-0001_11_30_000003_create_job_batches_table.php` | `job_batches` | Schema batch queue tiêu chuẩn. |
| `Modules/Auth/database/migrations/-0001_11_30_000004_create_failed_jobs_table.php` | `failed_jobs` | Schema failed jobs tiêu chuẩn. |
| `Modules/Auth/database/migrations/-0001_11_30_000008_create_sessions_table.php` | `sessions` | Session table tiêu chuẩn; `user_id` có index nhưng không có FK. |

Findings:

- **[P1]** Tất cả filename bắt đầu bằng năm âm `-0001`, là naming bất thường và được `ROADMAP.md` xác định cần sửa migration hygiene: `Modules/Auth/database/migrations/*.php`.
- **[P1]** Migration hạ tầng cache/queue/session nằm trong module có thể disable, làm fresh install phụ thuộc trạng thái Auth manifest: `Modules/Auth/database/migrations/*.php`, `Modules/Auth/config/module.php`, `Modules/ModuleServiceProvider.php`.
- **[P1]** Không có migration Auth-owned cho identity; schema user/social fields nằm ở User module, trong khi logic OAuth nằm ở Admin service: `Modules/User/database/migrations/*.php`, `Modules/Admin/Services/AuthService.php`.
- **[P2]** `sessions.user_id` không có foreign key; đây có thể là chủ ý để giữ session khi user bị xóa, nhưng cần document: `Modules/Auth/database/migrations/-0001_11_30_000008_create_sessions_table.php`.

Recommendations:

- **[P1]** Chuyển migration infrastructure về database/platform ownership, giữ thứ tự deterministic và thêm fresh-migration smoke test.
- **[P1]** Không rename migration đã chạy trực tiếp trên production nếu chưa có kế hoạch tương thích migration history; thực hiện migration-baseline strategy theo `ROADMAP.md`.

## 18. Refactor plan

### P0 Critical

1. **[P0] Chặn cấp quyền admin qua Google mặc định.**  
   Paths: `Modules/Auth/Http/Controllers/GoogleController.php`, `Modules/Admin/Services/AuthService.php`, `Modules/Auth/routes/web.php`, `config/auth.php`.  
   Yêu cầu allowlist/invitation/domain verified và capability admin trước login; deny by default.

2. **[P0] Áp eligibility thống nhất cho password và OAuth.**  
   Paths: `Modules/Auth/Livewire/Auth/LoginForm.php`, `Modules/Admin/Services/AuthService.php`, `App/Models/User.php`.  
   Tài khoản inactive/soft-deleted/không có quyền admin phải bị từ chối ở cả hai flow.

3. **[P0] Thêm rate limiting và security audit cho login.**  
   Paths: `Modules/Auth/Livewire/Auth/LoginForm.php`, `Modules/Auth/routes/web.php`.  
   Limit theo email chuẩn hóa + IP; ghi nhận success/failure an toàn; test lockout/backoff.

4. **[P0] Bảo vệ hoặc loại bỏ Google tokens.**  
   Paths: `Modules/Admin/Services/AuthService.php`, `App/Models/User.php`, `Modules/User/database/migrations/2026_04_27_214255_add_profile_and_social_fields_to_users_table.php`.  
   Không lưu nếu không sử dụng; nếu cần, mã hóa và quản lý vòng đời token.

5. **[P0] Không dùng `auth:admin` như authorization duy nhất.**  
   Paths: `config/auth.php`, `Modules/Auth/routes/web.php`, các route admin tiêu thụ session này.  
   Tách provider/principal hoặc bắt buộc permission/policy trên capability quản trị.

### P1 Important

1. **[P1] Chuyển authentication service ownership về Auth/support domain.**  
   Paths: `Modules/Admin/Services/AuthService.php`, `Modules/Auth/Http/Controllers/GoogleController.php`, `Modules/Auth/Livewire/Auth/LoginForm.php`, `Modules/Auth/config/module.php`.

2. **[P1] Transaction hóa và làm idempotent Google provisioning.**  
   Paths: `Modules/Admin/Services/AuthService.php`, `Modules/User/database/migrations/-0001_11_30_000006_create_users_table.php`, `Modules/User/database/migrations/2026_04_27_214255_add_profile_and_social_fields_to_users_table.php`.

3. **[P1] Sửa route contract.**  
   Paths: `Modules/Auth/routes/web.php`, `Modules/Auth/Http/Controllers/AuthController.php`.  
   Xóa/sửa `/register`, chọn canonical admin login route, thêm `guest:admin` cho login/OAuth entry khi phù hợp.

4. **[P1] Chuẩn hóa validation và error handling.**  
   Paths: `Modules/Auth/Livewire/Auth/LoginForm.php`, `Modules/Auth/Http/Controllers/GoogleController.php`.  
   Typed state/DTO, max length, normalize email, generic user error, structured redacted logs.

5. **[P1] Giảm query settings và tách dependency branding.**  
   Paths: `Modules/Auth/Livewire/Auth/LoginForm.php`, `Modules/Admin/Models/Setting.php`.  
   Một query hoặc cache service có invalidation.

6. **[P1] Sửa migration ownership/hygiene.**  
   Paths: `Modules/Auth/database/migrations/*.php`, `Modules/Auth/config/module.php`, `Modules/ModuleServiceProvider.php`.  
   Chuyển infrastructure migrations về platform ownership và kiểm thử fresh install.

7. **[P1] Xây regression suite cho Auth.**  
   Paths: `tests/Feature`, `tests/Unit`, `Modules/Auth`.  
   Bao phủ route boot, inactive user, wrong password, rate limit, session regeneration, Google allowlist, concurrent callback, denied admin và logout.

8. **[P1] Xóa placeholder sau khi xác minh.**  
   Paths: `Modules/Auth/Models/Auth.php`, permissions tại `Modules/Auth/config/module.php`.

### P2 Nice to have

1. **[P2] Xóa hoặc đổi ownership API placeholder.**  
   Paths: `Modules/Auth/routes/api.php`, `Modules/Auth/Http/Controllers/Api/AuthController.php`.

2. **[P2] Dọn Blade semantics/accessibility.**  
   Paths: `Modules/Auth/resources/views/layouts/auth.blade.php`, `Modules/Auth/resources/views/livewire/auth/login-form.blade.php`.  
   Thêm autocomplete, disable khi loading, sửa utility/comment và chọn một composition style.

3. **[P2] Đơn giản hóa alias/naming.**  
   Paths: `Modules/Auth/Livewire/Auth/LoginForm.php`, `Modules/Auth/resources/views/pages/auth/login.blade.php`.  
   Cân nhắc alias dễ đọc hơn khi có thay đổi cấu trúc lớn; không đổi riêng lẻ nếu chưa có test route/component boot.

## Kết luận

Auth hiện chạy được theo cấu trúc tối thiểu nhưng có rủi ro P0 ở ranh giới admin: Google auto-provision, password login bỏ qua `is_active`, thiếu rate limit, guard `admin` dùng chung provider với `web`, và token OAuth lưu plaintext. Trước mọi refactor thẩm mỹ, cần khóa các đường cấp session admin và xây regression tests theo các mục P0 của `ROADMAP.md`.
