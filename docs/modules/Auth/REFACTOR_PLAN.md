# Auth Module Refactor Plan

## Scope

Tài liệu này chuyển toàn bộ finding trong `docs/modules/Auth/ANALYSIS.md` thành kế hoạch refactor có thể triển khai theo giai đoạn.

Nguồn định hướng:

- `docs/modules/Auth/ANALYSIS.md`
- `ROADMAP.md`

Nguyên tắc:

- Không viết hoặc thay đổi code trong giai đoạn lập kế hoạch.
- Ưu tiên đóng lỗ hổng cấp session admin trước khi thay đổi kiến trúc hoặc giao diện.
- Guard chỉ xác định cơ chế xác thực; permission/policy mới là ranh giới authorization.
- Logic xác thực phải fail closed, dùng chung invariant giữa password và OAuth.
- Controller và Livewire component chỉ điều phối; nghiệp vụ, transaction và invariant nằm trong service thuộc đúng module.
- Mọi thay đổi P0/P1 phải có automated regression tests trước khi cleanup.

## Estimation Scale

### Estimated Risk

- **Critical:** Có thể cấp quyền trái phép, lộ secret/token hoặc phá vỡ ranh giới admin.
- **High:** Có thể gây regression xác thực, session, dữ liệu identity hoặc fresh migration.
- **Medium:** Ảnh hưởng cục bộ đến correctness, performance hoặc maintainability.
- **Low:** Cleanup hoặc UX có khả năng hồi quy thấp.

### Estimated Effort

- **XS:** Dưới 0.5 ngày.
- **S:** 0.5-1 ngày.
- **M:** 2-3 ngày.
- **L:** 4-7 ngày.
- **XL:** 1-2 tuần, thường có dependency liên module hoặc migration strategy.

## P0 Critical

### AUTH-P0-01 - Chặn Google OAuth tự cấp quyền truy cập admin

**Issue**

Google callback hiện chấp nhận Google account, tự tạo `users` record active, gán role `User` và đăng nhập guard `admin`. Không có kiểm tra hosted domain, verified email, invitation, allowlist hoặc capability quản trị.

**Root Cause**

`Modules/Admin/Services/AuthService.php` đang đồng nhất ba khái niệm khác nhau: xác thực Google identity, provision tài khoản và cấp quyền vào admin. Route/controller không định nghĩa eligibility policy và service mặc định cho phép thay vì mặc định từ chối.

**Business Impact**

Người ngoài tổ chức có thể có quyền vào khu vực quản trị nếu hoàn tất Google OAuth. Đây là rủi ro truy cập trái phép dữ liệu nghiệp vụ, cấu hình và chức năng vận hành.

**Technical Impact**

Authorization phụ thuộc vào việc có session `admin`, trong khi session đó được cấp trước khi chứng minh quyền quản trị. Role `User` không bù được thiếu sót này nếu downstream routes chỉ dùng `auth:admin`.

**Proposed Solution**

- Định nghĩa một admin eligibility policy rõ ràng: tài khoản phải tồn tại hoặc có invitation hợp lệ, email phải verified, domain phải thuộc allowlist nếu dùng Google Workspace, `is_active = true`, và phải có role/permission cho admin.
- Không tự tạo active admin principal từ Google callback. Nếu business cần auto-provision, tạo trạng thái pending không có quyền và yêu cầu approval.
- Validate Socialite identity thành DTO trước khi gọi service.
- Chỉ tạo session sau khi mọi điều kiện authorization đã pass.
- Thêm denied tests cho external domain, unverified email, no invitation, inactive user và user không có admin capability.

**Files To Change**

- `Modules/Auth/routes/web.php`
- `Modules/Auth/Http/Controllers/GoogleController.php`
- `Modules/Admin/Services/AuthService.php`
- `Modules/Auth/Services/AuthService.php` (service owner đề xuất)
- `Modules/Auth/Data/GoogleIdentityData.php` (DTO đề xuất)
- `Modules/Auth/Policies/AdminLoginPolicy.php` (policy đề xuất)
- `config/services.php`
- `config/auth.php`
- `App/Models/User.php`
- `tests/Feature/Modules/Auth/GoogleLoginTest.php` (test đề xuất)

**Estimated Risk:** Critical  
**Estimated Effort:** L

### AUTH-P0-02 - Áp dụng eligibility invariant thống nhất cho password và OAuth

**Issue**

Password login không kiểm tra `is_active`, trong khi OAuth chỉ kiểm tra trạng thái sau khi đã update Google fields. Hai flow có thể đưa ra kết quả authorization khác nhau cho cùng một user.

**Root Cause**

Password authentication nằm trực tiếp trong Livewire component, còn OAuth authentication nằm trong Admin service. Không có application service hoặc policy dùng chung để kiểm tra active status, soft delete status và admin capability trước mutation/session creation.

**Business Impact**

Tài khoản đã bị khóa vẫn có thể đăng nhập bằng mật khẩu. Quy trình khóa tài khoản không đáng tin cậy và không đáp ứng yêu cầu kiểm soát truy cập nội bộ.

**Technical Impact**

Invariant bị sao chép và áp dụng không đồng nhất. Mỗi phương thức đăng nhập mới có thể tiếp tục tạo một biến thể authorization khác.

**Proposed Solution**

- Tạo một eligibility check dùng chung cho cả password và Google login.
- Kiểm tra `is_active`, soft-delete state và admin capability trước update identity hoặc tạo session.
- Chuyển password flow khỏi `LoginForm` vào Auth application service; Livewire chỉ validate, gọi service và xử lý response.
- Dùng domain exception riêng cho inactive/unauthorized identity, không dùng exception chung.

**Files To Change**

- `Modules/Auth/Livewire/Auth/LoginForm.php`
- `Modules/Auth/Http/Controllers/GoogleController.php`
- `Modules/Admin/Services/AuthService.php`
- `Modules/Auth/Services/AuthService.php` (service owner đề xuất)
- `Modules/Auth/Policies/AdminLoginPolicy.php` (policy đề xuất)
- `App/Models/User.php`
- `tests/Feature/Modules/Auth/PasswordLoginTest.php` (test đề xuất)
- `tests/Feature/Modules/Auth/GoogleLoginTest.php` (test đề xuất)

**Estimated Risk:** Critical  
**Estimated Effort:** M

### AUTH-P0-03 - Thêm rate limiting và audit cho password login

**Issue**

Livewire action `login()` không có rate limiting theo normalized email/IP, cho phép brute-force và credential stuffing. Form cũng có thể phát nhiều request đồng thời.

**Root Cause**

Flow login gọi thẳng `Auth::guard('admin')->attempt()` mà không có limiter, lockout/backoff hoặc security event strategy. Livewire view chưa disable submit trong lúc request đang chạy.

**Business Impact**

Tăng xác suất chiếm đoạt tài khoản admin, gây tải database không cần thiết và thiếu dữ liệu điều tra khi có tấn công.

**Technical Impact**

Endpoint có chi phí xác thực lặp không giới hạn. Không có test chứng minh limiter key, thời gian lockout hoặc reset limiter sau login thành công.

**Proposed Solution**

- Dùng Laravel `RateLimiter` với key từ normalized email và IP.
- Dùng backoff/lockout có thông báo chung, không tiết lộ tài khoản có tồn tại.
- Clear limiter sau login thành công.
- Ghi security event có cấu trúc cho success, failure và throttled attempt; không log password/token.
- Thêm `wire:loading.attr="disabled"` và target đúng action để chặn duplicate submit phía UI.

**Files To Change**

- `Modules/Auth/Livewire/Auth/LoginForm.php`
- `Modules/Auth/resources/views/livewire/auth/login-form.blade.php`
- `Modules/Auth/routes/web.php`
- `Modules/Auth/Services/AuthService.php` (service đề xuất)
- `app/Providers/AppServiceProvider.php` hoặc provider chuyên biệt được chọn cho limiter
- `tests/Feature/Modules/Auth/LoginRateLimitTest.php` (test đề xuất)

**Estimated Risk:** Critical  
**Estimated Effort:** M

### AUTH-P0-04 - Thiết lập ranh giới thực giữa web user và admin

**Issue**

Guard `web` và `admin` cùng dùng provider `users`. Route admin có thể coi `auth:admin` là bằng chứng quyền quản trị dù guard name không chứng minh role hoặc capability.

**Root Cause**

Cấu hình guard chỉ tách session guard nhưng không tách principal hoặc authorization policy. Module Auth cũng khai báo generic CRUD permissions nhưng không enforce chúng.

**Business Impact**

Một user thông thường có thể được xác thực vào guard admin qua flow sai và tiếp cận các route chỉ được bảo vệ bằng `auth:admin`.

**Technical Impact**

Ranh giới bảo mật phân tán và phụ thuộc vào convention. Việc thêm route admin mới rất dễ quên permission middleware/policy.

**Proposed Solution**

- Chọn và ghi nhận một trong hai mô hình:
  - Provider/model admin riêng; hoặc
  - Shared user provider nhưng mọi admin entry/action bắt buộc admin capability middleware/policy.
- Với repository hiện tại, ưu tiên shared identity + explicit capability để tránh duplicate user data, nhưng phải audit toàn bộ routes tiêu thụ guard `admin`.
- Định nghĩa permission có nghĩa như `admin.access`; không dùng `view_auth/create_auth/edit_auth/delete_auth`.
- Thêm route/policy tests chứng minh authenticated user không có capability vẫn bị từ chối.

**Files To Change**

- `config/auth.php`
- `App/Models/User.php`
- `Modules/Auth/routes/web.php`
- `Modules/Auth/config/module.php`
- `Modules/ModuleServiceProvider.php`
- Các route admin tiêu thụ guard, tối thiểu `Modules/Admin/routes/web.php`
- `tests/Feature/Modules/Auth/AdminGuardAuthorizationTest.php` (test đề xuất)

**Estimated Risk:** Critical  
**Estimated Effort:** XL

### AUTH-P0-05 - Loại bỏ hoặc mã hóa Google access/refresh tokens

**Issue**

Google access token và refresh token được lưu plaintext trong bảng `users`.

**Root Cause**

Service persist toàn bộ provider response theo mặc định, dù module hiện chỉ cần identity để login. Model chỉ `hidden` token khi serialize; `hidden` không mã hóa dữ liệu tại rest.

**Business Impact**

Database leak có thể làm lộ token truy cập Google. Tùy scope, attacker có thể truy cập dữ liệu ngoài hệ thống INAFO.

**Technical Impact**

Token lifecycle, scope, rotation và revocation không được quản lý. Cột token nằm trong identity table làm tăng bề mặt secret exposure và backup sensitivity.

**Proposed Solution**

- Xác minh có consumer thực sự cần Google API token hay không.
- Nếu không cần, ngừng lưu và lập migration loại bỏ cột theo quy trình an toàn.
- Nếu cần, dùng encrypted cast, scope tối thiểu, key rotation plan, expiration metadata và revoke flow.
- Không log provider payload hoặc token trong exception context.
- Thêm test chứng minh token không lưu plaintext.

**Files To Change**

- `Modules/Admin/Services/AuthService.php`
- `Modules/Auth/Services/AuthService.php` (service owner đề xuất)
- `App/Models/User.php`
- `Modules/User/database/migrations/2026_04_27_214255_add_profile_and_social_fields_to_users_table.php` (chỉ tham chiếu lịch sử; không sửa migration đã chạy)
- `Modules/User/database/migrations/2026_06_15_000000_secure_or_remove_google_tokens_from_users_table.php` (migration mới đề xuất)
- `tests/Feature/Modules/Auth/GoogleTokenStorageTest.php` (test đề xuất)

**Estimated Risk:** Critical  
**Estimated Effort:** M

## P1 Important

### AUTH-P1-01 - Chuyển ownership nghiệp vụ xác thực từ Admin về Auth

**Issue**

`GoogleController` trong Auth phụ thuộc vào `Modules/Admin/Services/AuthService.php`; password logic lại nằm trong Livewire component. Auth được khai báo là `shell` dù fallback architecture coi nó là `support`.

**Root Cause**

Module boundaries hình thành theo vị trí giao diện thay vì ownership nghiệp vụ. Chưa có canonical application service cho authentication.

**Business Impact**

Thay đổi login yêu cầu sửa nhiều module, tăng chi phí bảo trì và rủi ro regression ở khu vực quản trị.

**Technical Impact**

Dependency direction bị đảo: support module phụ thuộc presentation shell. Service hiện làm persistence, provisioning, authorization, session login và audit trong một method.

**Proposed Solution**

- Khai báo Auth là `support`.
- Tạo Auth-owned application service và chia trách nhiệm rõ: identity mapping, eligibility, provisioning transaction, session authentication.
- Chuyển caller dần sang service mới rồi xóa Admin-owned AuthService sau khi tests pass.
- Dùng domain exceptions/result objects thay vì `Exception` chung.
- Thêm architecture test ngăn `Modules/Auth` phụ thuộc `Modules/Admin/Services`.

**Files To Change**

- `Modules/Auth/config/module.php`
- `Modules/Auth/Http/Controllers/GoogleController.php`
- `Modules/Auth/Livewire/Auth/LoginForm.php`
- `Modules/Admin/Services/AuthService.php`
- `Modules/Auth/Services/AuthService.php` (service đề xuất)
- `Modules/Auth/Exceptions/AuthenticationException.php` (exception đề xuất)
- `Modules/ModuleServiceProvider.php`
- `tests/Architecture/ModuleDependencyTest.php` (test đề xuất)

**Estimated Risk:** High  
**Estimated Effort:** L

### AUTH-P1-02 - Transaction hóa và làm idempotent Google provisioning/linking

**Issue**

Google callback thực hiện find/create/update user, create role, assign role, update login timestamp và login session mà không có transaction. Concurrent callbacks có thể tranh chấp unique email/google_id. User có thể bị update token/google_id trước khi eligibility fail.

**Root Cause**

Toàn bộ workflow được viết tuyến tính trong một service method, không xác định transaction boundary, idempotency key hoặc thứ tự validate-before-mutate.

**Business Impact**

Có thể tạo tài khoản/link identity dở dang, lỗi login ngẫu nhiên hoặc gán role không nhất quán khi callback retry.

**Technical Impact**

Database state không atomic; unique constraint trở thành exception không được xử lý; session có thể được tạo trước khi trạng thái durable hoàn tất.

**Proposed Solution**

- Validate identity và eligibility trước mutation.
- Bọc user linking/provisioning, role assignment và audit timestamp trong `DB::transaction()`.
- Chỉ login sau commit.
- Dùng unique constraints làm source of truth, xử lý duplicate-key retry có giới hạn.
- Thiết kế workflow idempotent theo provider + provider user ID; không tự link email hiện hữu nếu chưa có chính sách xác nhận.

**Files To Change**

- `Modules/Admin/Services/AuthService.php`
- `Modules/Auth/Services/AuthService.php` (service đề xuất)
- `App/Models/User.php`
- `Modules/User/database/migrations/-0001_11_30_000006_create_users_table.php` (schema reference)
- `Modules/User/database/migrations/2026_04_27_214255_add_profile_and_social_fields_to_users_table.php` (schema reference)
- `tests/Feature/Modules/Auth/GoogleProvisioningTransactionTest.php` (test đề xuất)

**Estimated Risk:** High  
**Estimated Effort:** L

### AUTH-P1-03 - Sửa route contract và chuẩn hóa login/logout navigation

**Issue**

Route `/register` gọi method không tồn tại. `/login` và `/admin/login` cùng phục vụ admin guard. Logout redirect về route chung `login`; `session()->invalidate()` có thể xóa state của các guard dùng chung session.

**Root Cause**

Route được scaffold nhưng không được đối chiếu với controller capability. User-facing login và admin login chưa có ownership/guard contract riêng.

**Business Impact**

Người dùng gặp lỗi runtime tại `/register`, redirect khó dự đoán và có thể bị đăng xuất khỏi nhiều context ngoài ý muốn.

**Technical Impact**

Route names không thể hiện guard đích, làm middleware và test khó chính xác. Route boot/list cũng khó dùng làm acceptance gate.

**Proposed Solution**

- Chọn `admin.login` làm canonical route cho admin.
- Xóa `/register` nếu không hỗ trợ self-registration; không tạo implementation chỉ để giữ route.
- Chỉ giữ `/login` khi có flow guard `web` thực sự.
- Redirect logout về `admin.login`.
- Xác định rõ yêu cầu invalidate toàn session; nếu hệ thống cho phép đồng thời nhiều guard, thêm tests trước khi thay đổi.
- Thêm `guest:admin` cho admin login/OAuth entry nếu phù hợp.

**Files To Change**

- `Modules/Auth/routes/web.php`
- `Modules/Auth/Http/Controllers/AuthController.php`
- `Modules/Auth/Http/Controllers/GoogleController.php`
- `Modules/Auth/resources/views/livewire/auth/login-form.blade.php`
- `config/auth.php`
- `tests/Feature/Modules/Auth/AuthRoutesTest.php` (test đề xuất)
- `tests/Feature/Modules/Auth/LogoutTest.php` (test đề xuất)

**Estimated Risk:** High  
**Estimated Effort:** M

### AUTH-P1-04 - Chuẩn hóa Livewire 3 state và validation

**Issue**

Email/password chỉ có rule tối thiểu, không giới hạn length hoặc normalize email. `$remember` không validate boolean và public properties không khai báo type.

**Root Cause**

Component dùng public state dạng scaffold và gọi `validate()` trực tiếp mà chưa định nghĩa input boundary đầy đủ.

**Business Impact**

Login có thể thất bại không nhất quán vì whitespace/case, và input quá lớn có thể gây tải hoặc log noise không cần thiết.

**Technical Impact**

State contract không rõ; component khó static analysis và khó tái sử dụng validation trong service/tests.

**Proposed Solution**

- Dùng typed public properties phù hợp với Livewire 3.
- Normalize email trước rate-limit key và authentication lookup.
- Thêm `string`, `max` hợp lý và `boolean` cho remember.
- Không mutate password ngoài phạm vi cần thiết; reset sensitive state sau failure khi UX cho phép.
- Đưa business validation/eligibility vào service, giữ shape validation tại Livewire boundary.

**Files To Change**

- `Modules/Auth/Livewire/Auth/LoginForm.php`
- `Modules/Auth/Services/AuthService.php` (service đề xuất)
- `tests/Feature/Modules/Auth/PasswordLoginValidationTest.php` (test đề xuất)

**Estimated Risk:** Medium  
**Estimated Effort:** S

### AUTH-P1-05 - Chuẩn hóa OAuth error handling và logging

**Issue**

Google callback trả nguyên exception message cho người dùng và log bằng nối chuỗi không có structured context/redaction.

**Root Cause**

Controller bắt `Exception` tổng quát và dùng cùng message cho operational log lẫn UI feedback.

**Business Impact**

Có thể lộ chi tiết cấu hình, database hoặc provider; thông báo lỗi kỹ thuật làm trải nghiệm người dùng kém và tăng rủi ro social engineering.

**Technical Impact**

Log khó tìm kiếm/correlation, có nguy cơ chứa PII/token, và không phân biệt lỗi business với lỗi hạ tầng.

**Proposed Solution**

- Trả một thông báo người dùng cố định, không chứa exception detail.
- Dùng domain exception cho denied/inactive/invalid identity và map sang response phù hợp.
- Structured log với event name, correlation ID, provider và internal user ID khi có; redact token và PII không cần thiết.
- Không log toàn bộ Socialite payload.

**Files To Change**

- `Modules/Auth/Http/Controllers/GoogleController.php`
- `Modules/Auth/Services/AuthService.php` (service đề xuất)
- `Modules/Auth/Exceptions/AuthenticationException.php` (exception đề xuất)
- `bootstrap/app.php` hoặc exception mapping location được repository chọn
- `tests/Feature/Modules/Auth/GoogleLoginErrorHandlingTest.php` (test đề xuất)

**Estimated Risk:** High  
**Estimated Effort:** M

### AUTH-P1-06 - Thay permissions giả bằng capability được enforce

**Issue**

Manifest khai báo `view_auth`, `create_auth`, `edit_auth`, `delete_auth` nhưng không có reference/enforcement. Các tên CRUD cũng không phù hợp với authentication capability.

**Root Cause**

Permission list được sinh theo module CRUD template thay vì threat model của Auth.

**Business Impact**

Tạo cảm giác hệ thống đã có authorization trong khi route thực tế không kiểm tra permission, làm audit bảo mật sai lệch.

**Technical Impact**

Permission registry chứa quyền không dùng, khó hiểu và không thể dùng làm contract trong tests.

**Proposed Solution**

- Xóa generic CRUD permissions.
- Khai báo capability có nghĩa nếu cần, tối thiểu `admin.access`; các capability nhạy cảm thuộc module đích, không thuộc Auth.
- Enforce capability bằng middleware/policy và thêm allowed/denied tests.
- Đồng bộ guard name giữa Spatie roles/permissions và principal strategy đã chọn.

**Files To Change**

- `Modules/Auth/config/module.php`
- `Modules/Auth/routes/web.php`
- `Modules/Role/database/migrations/-0001_11_30_000010_create_permissions_table.php` (schema reference)
- `Modules/Role/database/seeders/PermissionSeeder.php` (seeder đề xuất nếu chưa tồn tại)
- `App/Models/User.php`
- `tests/Feature/Modules/Auth/AdminGuardAuthorizationTest.php` (test đề xuất)

**Estimated Risk:** High  
**Estimated Effort:** M

### AUTH-P1-07 - Xây regression suite và route/module boot gate

**Issue**

Repository chỉ có example tests. Auth chưa có test cho guard separation, inactive account, OAuth restrictions, rate limit, logout, transaction, route boot hoặc Livewire behavior. `artisan route:list` hiện còn bị chặn bởi route class lỗi ở module khác.

**Root Cause**

Không có module-level test strategy hoặc CI gate trước khi thêm authentication features.

**Business Impact**

Các sửa đổi bảo mật có thể hồi quy âm thầm; release không có bằng chứng rằng user trái phép bị từ chối.

**Technical Impact**

Không thể refactor service ownership, guard hoặc route một cách an toàn. Broken route ở module khác cũng che khuất lỗi Auth route.

**Proposed Solution**

- Tạo feature tests cho password/OAuth allowed và denied cases.
- Test Livewire validation, rate limit, session regeneration, remember behavior và duplicate submit server behavior.
- Test transaction rollback/idempotency cho Google provisioning.
- Thêm module route boot test không phụ thuộc việc render toàn bộ route list nếu repository còn route lỗi; đồng thời xử lý route lỗi ở owner module trong workstream riêng.
- Đưa Auth security suite vào CI trước refactor kiến trúc rộng.

**Files To Change**

- `tests/Feature/Modules/Auth/AuthRoutesTest.php` (test đề xuất)
- `tests/Feature/Modules/Auth/PasswordLoginTest.php` (test đề xuất)
- `tests/Feature/Modules/Auth/GoogleLoginTest.php` (test đề xuất)
- `tests/Feature/Modules/Auth/LoginRateLimitTest.php` (test đề xuất)
- `tests/Feature/Modules/Auth/LogoutTest.php` (test đề xuất)
- `tests/Unit/Modules/Auth/AuthServiceTest.php` (test đề xuất)
- `phpunit.xml`
- CI workflow hiện hữu; nếu chưa có: `.github/workflows/tests.yml` (workflow đề xuất)

**Estimated Risk:** Medium  
**Estimated Effort:** L

### AUTH-P1-08 - Tách branding dependency và giảm bốn query settings

**Issue**

`LoginForm::mount()` gọi `Setting::getValue()` bốn lần, tạo bốn query trên mỗi lần mở login. Auth phụ thuộc trực tiếp model thuộc Admin.

**Root Cause**

Settings model cung cấp helper một-key-mỗi-query và được dùng như service toàn cục. Chưa có read contract/cache policy cho public branding.

**Business Impact**

Trang login là mục tiêu bot phổ biến; query amplification làm tăng tải database và coupling khiến thay đổi branding khó kiểm soát.

**Technical Impact**

Không phải relation N+1 nhưng là query hotspot cố định. Dependency Auth -> Admin tiếp tục vi phạm module ownership.

**Proposed Solution**

- Định nghĩa branding/settings read service ở owner phù hợp.
- Fetch bốn key bằng một query `whereIn`.
- Chỉ cache nếu có invalidation rõ khi settings thay đổi.
- Thêm query-count test cho render login.

**Files To Change**

- `Modules/Auth/Livewire/Auth/LoginForm.php`
- `Modules/Admin/Models/Setting.php`
- `Modules/Admin/Services/SettingsService.php`
- `Modules/Shared/Contracts/SettingsReader.php` (contract đề xuất nếu Shared là owner được chọn)
- `tests/Feature/Modules/Auth/LoginPageQueryTest.php` (test đề xuất)

**Estimated Risk:** Medium  
**Estimated Effort:** M

### AUTH-P1-09 - Loại bỏ `env()` và chat config khỏi Auth Blade

**Issue**

Auth layout đọc `NODEJS_SERVER_URL`/`PORT` bằng `env()` trực tiếp và xuất chat configuration trên trang login dù login không dùng chat.

**Root Cause**

Layout được sao chép từ layout dùng chung và chưa được thu hẹp theo responsibility. Configuration access không tuân thủ Laravel config cache practice.

**Business Impact**

Trang login mang dependency không cần thiết; cấu hình có thể sai sau `config:cache`, gây lỗi môi trường khó chẩn đoán.

**Technical Impact**

Blade phụ thuộc environment trực tiếp, làm test/config override kém ổn định và tăng dữ liệu global JavaScript.

**Proposed Solution**

- Xóa chat globals khỏi Auth layout nếu không có consumer.
- Nếu cần thật, đọc qua `config('services.nodejs...')`, không gọi `env()` trong view.
- Bổ sung port vào config file thay vì fallback trong Blade.
- Thêm render test khi config cache semantics được mô phỏng.

**Files To Change**

- `Modules/Auth/resources/views/layouts/auth.blade.php`
- `config/services.php`
- `tests/Feature/Modules/Auth/LoginPageTest.php` (test đề xuất)

**Estimated Risk:** Medium  
**Estimated Effort:** S

### AUTH-P1-10 - Cải thiện behavior và accessibility của Livewire login form

**Issue**

Submit không disable khi loading; email/password thiếu autocomplete attributes. Duplicate click có thể tạo concurrent attempts.

**Root Cause**

View chỉ đổi loading text nhưng không khóa control hoặc khai báo browser credential semantics.

**Business Impact**

Người dùng có thể gửi nhiều login request, gặp UX không ổn định và password manager hoạt động kém.

**Technical Impact**

Tăng số request Livewire đồng thời và làm rate-limit/audit noise. Form thiếu browser-level hints tiêu chuẩn.

**Proposed Solution**

- Thêm `wire:loading.attr="disabled"` và `wire:target="login"` cho submit.
- Dùng `autocomplete="username"` và `autocomplete="current-password"`.
- Đảm bảo loading state không làm submit nhiều lần bằng keyboard.
- Thêm component/render assertions cho attributes quan trọng.

**Files To Change**

- `Modules/Auth/resources/views/livewire/auth/login-form.blade.php`
- `tests/Feature/Modules/Auth/LoginFormViewTest.php` (test đề xuất)

**Estimated Risk:** Medium  
**Estimated Effort:** XS

### AUTH-P1-11 - Sửa migration ownership, ordering và fresh-install reliability

**Issue**

Các migration Auth dùng filename năm âm và chứa bảng platform `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`. Disable Auth có thể làm mất platform schema. Identity schema nằm ở User, OAuth logic nằm ở Admin, làm ownership phân tán.

**Root Cause**

Migration được chia theo thứ tự scaffold thay vì bounded context/platform ownership. Module loader tự load migration của module enabled, nên manifest state ảnh hưởng fresh install.

**Business Impact**

Fresh deployment hoặc recovery có thể thất bại/thiếu bảng nền tảng. Đổi module state có thể gây hậu quả database ngoài dự kiến.

**Technical Impact**

Ordering khó dự đoán, migration history khó sửa an toàn, và không có smoke test cho MySQL/SQLite compatibility.

**Proposed Solution**

- Xác định owner platform cho cache/queue/session migrations, không gắn chúng với module Auth có thể disable.
- Lập migration-baseline strategy; không rename/delete trực tiếp migration đã chạy production.
- Giữ identity/social fields trong User ownership, còn Auth service chỉ tiêu thụ contract.
- Thêm fresh migration smoke test cho production MySQL path và test path được CI sử dụng.
- Document chủ ý không có foreign key cho `sessions.user_id`, hoặc thêm FK chỉ sau khi đánh giá session retention/delete semantics.

**Files To Change**

- `Modules/Auth/database/migrations/-0001_11_30_000000_create_cache_table.php`
- `Modules/Auth/database/migrations/-0001_11_30_000001_create_cache_locks_table.php`
- `Modules/Auth/database/migrations/-0001_11_30_000002_create_jobs_table.php`
- `Modules/Auth/database/migrations/-0001_11_30_000003_create_job_batches_table.php`
- `Modules/Auth/database/migrations/-0001_11_30_000004_create_failed_jobs_table.php`
- `Modules/Auth/database/migrations/-0001_11_30_000008_create_sessions_table.php`
- `Modules/Auth/config/module.php`
- `Modules/ModuleServiceProvider.php`
- `Modules/User/database/migrations/-0001_11_30_000006_create_users_table.php`
- `Modules/User/database/migrations/2026_04_27_214255_add_profile_and_social_fields_to_users_table.php`
- `tests/Feature/Architecture/FreshMigrationTest.php` (test đề xuất)
- Tài liệu baseline đề xuất: `docs/database/MIGRATION_BASELINE.md`

**Estimated Risk:** High  
**Estimated Effort:** XL

### AUTH-P1-12 - Xóa model Auth placeholder sau khi có safety net

**Issue**

`Modules/Auth/Models/Auth.php` không có reference, không có bảng `auths` và chỉ chứa mapping comment.

**Root Cause**

Model được sinh từ module template nhưng không đại diện domain entity thực.

**Business Impact**

Developer có thể hiểu sai rằng Auth có aggregate/table riêng, dẫn đến code hoặc migration không cần thiết.

**Technical Impact**

Dead code làm tăng catalog/autoload surface và gây nhiễu khi phân tích ownership.

**Proposed Solution**

- Xác nhận không có dynamic class reference bằng route/module boot tests và source search.
- Xóa model sau khi regression suite đã có.
- Không thay bằng abstraction mới nếu chưa có domain entity thật.

**Files To Change**

- `Modules/Auth/Models/Auth.php`
- `tests/Architecture/UnusedModuleArtifactsTest.php` (test đề xuất)

**Estimated Risk:** Medium  
**Estimated Effort:** XS

## P2 Nice to have

### AUTH-P2-01 - Xóa hoặc chuyển ownership API Auth placeholder

**Issue**

`GET /api/auth` chỉ trả `"Api Auth success"`; controller mang tên Auth nhưng không cung cấp login/token/logout API. File route còn chứa Sanctum code comment.

**Root Cause**

Đây là artifact scaffold/health check chưa được hoàn thiện hoặc xóa.

**Business Impact**

Public endpoint không có giá trị nghiệp vụ, có thể gây hiểu nhầm cho API consumer và tăng bề mặt được scan.

**Technical Impact**

Route/controller/comment chết làm sai architecture catalog và route inventory.

**Proposed Solution**

- Xác minh không có consumer.
- Xóa route/controller/comment nếu không dùng.
- Nếu đây là health endpoint, chuyển sang health subsystem với tên và response contract rõ.

**Files To Change**

- `Modules/Auth/routes/api.php`
- `Modules/Auth/Http/Controllers/Api/AuthController.php`
- `tests/Feature/Modules/Auth/AuthApiRouteTest.php` (test xác nhận route removal/ownership đề xuất)

**Estimated Risk:** Low  
**Estimated Effort:** XS

### AUTH-P2-02 - Đơn giản hóa Livewire alias

**Issue**

Alias `auth.auth.login-form` lặp từ module name và class folder, khó đọc.

**Root Cause**

Convention auto-registration trong `Modules/ModuleServiceProvider.php` ghép module prefix với toàn bộ relative class path.

**Business Impact**

Ảnh hưởng nhỏ đến khả năng đọc và onboarding.

**Technical Impact**

Rename class/folder có thể làm vỡ Blade reference nếu không có component boot test.

**Proposed Solution**

- Chỉ đơn giản hóa sau khi Auth route/component tests tồn tại.
- Ưu tiên chuyển class thành `Modules\Auth\Livewire\LoginForm` để alias thành `auth.login-form`; không thêm special case vào global module provider chỉ cho một component.

**Files To Change**

- `Modules/Auth/Livewire/Auth/LoginForm.php`
- `Modules/Auth/Livewire/LoginForm.php` (đường dẫn đích đề xuất)
- `Modules/Auth/resources/views/pages/auth/login.blade.php`
- `tests/Feature/Modules/Auth/LoginPageTest.php` (test đề xuất)

**Estimated Risk:** Low  
**Estimated Effort:** S

### AUTH-P2-03 - Chọn một Blade layout composition style

**Issue**

Auth layout hỗ trợ cả `$slot` và `@yield('content')`, trong khi page hiện dùng `@extends`.

**Root Cause**

Layout kết hợp convention của Blade component và template inheritance.

**Business Impact**

Không gây lỗi trực tiếp nhưng làm view contract khó hiểu.

**Technical Impact**

Tăng nhánh render không được sử dụng/test và làm refactor view phức tạp hơn.

**Proposed Solution**

- Giữ template inheritance hiện tại và bỏ slot branch, hoặc chuyển nhất quán sang layout component trong một thay đổi có test.
- Không duy trì song song hai style nếu không có consumer.

**Files To Change**

- `Modules/Auth/resources/views/layouts/auth.blade.php`
- `Modules/Auth/resources/views/pages/auth/login.blade.php`
- `tests/Feature/Modules/Auth/LoginPageTest.php` (test đề xuất)

**Estimated Risk:** Low  
**Estimated Effort:** XS

### AUTH-P2-04 - Dọn Blade comments và Tailwind utility không xác định

**Issue**

Livewire view có comment `LOGO` lặp, comment `SCHOOL NAME` sai domain và class `w-128` không phải Tailwind utility mặc định.

**Root Cause**

View được sao chép/chỉnh sửa thủ công nhưng chưa được cleanup hoặc build verification.

**Business Impact**

Có thể làm logo không đạt kích thước mong muốn; comment sai gây nhầm lẫn cho maintainer.

**Technical Impact**

CSS class có thể không sinh output, tùy Tailwind configuration. Template noise làm review khó hơn.

**Proposed Solution**

- Xóa comment lặp và đổi terminology đúng domain.
- Thay `w-128` bằng utility hợp lệ hoặc arbitrary value có chủ đích.
- Xác minh bằng frontend build và login render/snapshot test nếu dự án dùng snapshot.

**Files To Change**

- `Modules/Auth/resources/views/livewire/auth/login-form.blade.php`
- `resources/css/tailwind.css`
- `vite.config.js`
- `tests/Feature/Modules/Auth/LoginFormViewTest.php` (test đề xuất)

**Estimated Risk:** Low  
**Estimated Effort:** XS

### AUTH-P2-05 - Không tạo shared component khi chưa có nhu cầu tái sử dụng

**Issue**

Auth hiện không có shared Blade component; phân tích xác định chưa có bằng chứng cần trích form login thành abstraction dùng chung.

**Root Cause**

Không phải defect hiện tại; đây là guardrail để tránh over-engineering trong lúc refactor.

**Business Impact**

Tránh tăng chi phí bảo trì do abstraction không có consumer.

**Technical Impact**

Giữ ownership form và state trong Auth, giảm API surface không cần thiết.

**Proposed Solution**

- Giữ form trong Auth.
- Chỉ trích shared component khi có consumer thứ hai với contract thực sự giống nhau.
- Nếu chỉ tái sử dụng input markup, ưu tiên component nhỏ theo design system thay vì di chuyển toàn bộ authentication form.

**Files To Change**

- `Modules/Auth/resources/views/livewire/auth/login-form.blade.php` (giữ ownership; chỉ thay đổi khi có consumer thật)
- `Modules/Auth/Livewire/Auth/LoginForm.php` hoặc `Modules/Auth/Livewire/LoginForm.php` sau AUTH-P2-02

**Estimated Risk:** Low  
**Estimated Effort:** XS

### AUTH-P2-06 - Đo execution plan và đặt query budget

**Issue**

Google lookup dùng `google_id OR email`. Cả hai cột có unique index nên chưa có bằng chứng là bottleneck, nhưng cần đo trên production-like database. Login page cũng chưa có query-count budget.

**Root Cause**

Performance assessment hiện dựa trên source inspection, chưa có profiling/test budget.

**Business Impact**

Rủi ro hiện thấp nhưng lookup/auth latency có thể tăng theo kích thước bảng users.

**Technical Impact**

Không có baseline để phát hiện regression sau khi thêm policy, role lookup hoặc settings cache.

**Proposed Solution**

- Ghi query count và latency cho password login, login page render và OAuth callback trên dữ liệu đại diện.
- Kiểm tra execution plan cho lookup `google_id OR email`.
- Chỉ rewrite query khi measurement chứng minh cần thiết.

**Files To Change**

- `Modules/Auth/Services/AuthService.php` (sau khi chuyển ownership)
- `Modules/Admin/Services/AuthService.php` (trước khi migration hoàn tất)
- `Modules/Auth/Livewire/Auth/LoginForm.php`
- `Modules/User/database/migrations/-0001_11_30_000006_create_users_table.php` (index reference)
- `Modules/User/database/migrations/2026_04_27_214255_add_profile_and_social_fields_to_users_table.php` (index reference)
- `tests/Feature/Modules/Auth/LoginPageQueryTest.php` (test đề xuất)
- `tests/Feature/Modules/Auth/GoogleLoginQueryTest.php` (test đề xuất)

**Estimated Risk:** Low  
**Estimated Effort:** S

### AUTH-P2-07 - Document `sessions.user_id` foreign-key decision

**Issue**

`sessions.user_id` có index nhưng không có foreign key. Đây có thể là chủ ý để tránh delete coupling, nhưng hiện không được document.

**Root Cause**

Migration dùng Laravel session schema kiểu mặc định mà không ghi nhận retention semantics của dự án.

**Business Impact**

Ảnh hưởng trực tiếp thấp; tuy nhiên session rows orphan có thể làm vận hành/audit khó hiểu.

**Technical Impact**

Maintainer có thể thêm FK hoặc cleanup sai cách, gây lỗi khi xóa user hoặc invalidate session.

**Proposed Solution**

- Quyết định rõ có giữ orphan session rows tạm thời hay cascade khi user bị xóa.
- Document quyết định trong migration baseline/architecture docs.
- Chỉ thêm FK bằng migration mới sau khi đánh giá production data và logout semantics.

**Files To Change**

- `Modules/Auth/database/migrations/-0001_11_30_000008_create_sessions_table.php` (schema reference; không sửa migration đã chạy)
- `docs/database/MIGRATION_BASELINE.md` (tài liệu đề xuất)
- `Modules/Auth/database/migrations/2026_06_15_000001_add_sessions_user_foreign_key.php` (chỉ nếu quyết định thêm FK)

**Estimated Risk:** Low  
**Estimated Effort:** S

## Recommended Implementation Order

### Phase 1 - Security Containment

Mục tiêu: đóng mọi đường cấp session admin trái phép trước khi refactor cấu trúc.

1. `AUTH-P1-07` tạo security regression harness tối thiểu cho denied/allowed behavior.
2. `AUTH-P0-04` xác lập admin capability boundary; audit route dùng `auth:admin`.
3. `AUTH-P0-01` chặn Google auto-provision/admin login mặc định.
4. `AUTH-P0-02` thống nhất active/admin eligibility cho password và OAuth.
5. `AUTH-P0-03` thêm rate limit, duplicate-submit protection và security audit.
6. `AUTH-P0-05` loại bỏ hoặc mã hóa Google tokens.
7. `AUTH-P1-05` ngừng trả/log raw exception.

**Phase 1 exit criteria**

- External/unapproved Google identity không thể nhận admin session.
- Inactive hoặc non-admin user bị từ chối ở cả password và OAuth.
- Password login có limiter và test lockout.
- Token Google không lưu plaintext.
- Security tests chứng minh cả allowed và denied paths.

### Phase 2 - Architecture and Correctness

Mục tiêu: đưa Auth về đúng ownership và làm workflow ổn định, transaction-safe.

1. `AUTH-P1-01` chuyển service ownership từ Admin về Auth/support.
2. `AUTH-P1-02` transaction hóa và làm idempotent Google provisioning.
3. `AUTH-P1-06` thay generic permissions bằng capability thực.
4. `AUTH-P1-03` sửa route contract, canonical login và logout semantics.
5. `AUTH-P1-04` chuẩn hóa Livewire state/validation.
6. `AUTH-P1-08` tách branding read contract và giảm query.
7. `AUTH-P1-09` loại `env()`/chat config khỏi Auth Blade.
8. `AUTH-P1-10` hoàn thiện Livewire form behavior/accessibility.
9. `AUTH-P1-11` lập migration baseline, ownership và fresh-install tests.

**Phase 2 exit criteria**

- Auth không còn phụ thuộc `Modules/Admin/Services/AuthService.php`.
- Google provisioning atomic, retry-safe và login chỉ xảy ra sau commit.
- Route Auth boot thành công và không còn handler thiếu.
- Login page có query budget; config cache không làm sai view.
- Fresh migration strategy được document và kiểm thử.

### Phase 3 - Cleanup and Optimization

Mục tiêu: xóa artifact, đơn giản hóa view/component và chỉ tối ưu theo measurement.

1. `AUTH-P1-12` xóa model placeholder sau safety checks.
2. `AUTH-P2-01` xóa/chuyển API placeholder.
3. `AUTH-P2-06` đo query plan và thiết lập performance baseline.
4. `AUTH-P2-07` document session FK/retention decision.
5. `AUTH-P2-02` đơn giản hóa Livewire alias nếu còn giá trị.
6. `AUTH-P2-03` chọn một Blade composition style.
7. `AUTH-P2-04` dọn comments và Tailwind utility.
8. `AUTH-P2-05` giữ form nội bộ, không tạo abstraction khi chưa có consumer.

**Phase 3 exit criteria**

- Không còn placeholder route/controller/model trong Auth.
- Component naming và Blade composition nhất quán.
- Frontend build pass và login page render đúng.
- Performance decisions có query measurements thay vì giả định.

## Analysis Issue Coverage

| Analysis finding group | Covered by |
|---|---|
| Google OAuth không có allowlist/domain/invitation/admin eligibility | `AUTH-P0-01`, `AUTH-P0-04` |
| Password login bỏ qua `is_active`; eligibility không nhất quán | `AUTH-P0-02` |
| Không có login throttle; duplicate submit | `AUTH-P0-03`, `AUTH-P1-10` |
| Guard `web`/`admin` dùng chung provider; `auth:admin` bị dùng như authorization | `AUTH-P0-04`, `AUTH-P1-06` |
| Google tokens lưu plaintext | `AUTH-P0-05` |
| Auth phụ thuộc Admin; service quá nhiều trách nhiệm | `AUTH-P1-01` |
| OAuth writes không transaction, race condition, mutate trước eligibility | `AUTH-P1-02` |
| `/register` thiếu handler; duplicate login routes; logout/session ambiguity | `AUTH-P1-03` |
| Email không normalize; validation thiếu type/max/boolean | `AUTH-P1-04` |
| Raw exception và unstructured log | `AUTH-P1-05` |
| Generic permissions không được dùng | `AUTH-P1-06` |
| Thiếu automated tests và route boot verification | `AUTH-P1-07` |
| Bốn settings queries và dependency trực tiếp Admin model | `AUTH-P1-08` |
| `env()` và chat globals trong Auth layout | `AUTH-P1-09` |
| Thiếu autocomplete/loading disable | `AUTH-P1-10` |
| Negative-year migrations, platform table ownership, identity ownership phân tán | `AUTH-P1-11` |
| `Modules/Auth/Models/Auth.php` không dùng | `AUTH-P1-12` |
| API/controller placeholder và commented Sanctum route | `AUTH-P2-01` |
| Alias `auth.auth.login-form` | `AUTH-P2-02` |
| Layout dùng đồng thời slot và section | `AUTH-P2-03` |
| Comment lặp/sai domain và `w-128` | `AUTH-P2-04` |
| Không có nhu cầu shared component thực tế | `AUTH-P2-05` |
| Google lookup execution plan và query-count tests | `AUTH-P2-06` |
| `sessions.user_id` không có FK/chưa document | `AUTH-P2-07` |

## Final Recommendation

Không bắt đầu bằng cleanup view hoặc di chuyển file. Delivery đầu tiên phải tạo denied-path tests và đóng quyền truy cập admin qua Google/password. Sau khi P0 pass, mới chuyển service ownership, transaction hóa provisioning và sửa migration architecture. Cleanup P2 chỉ thực hiện khi route/component/fresh-migration tests đã bảo vệ hành vi.
