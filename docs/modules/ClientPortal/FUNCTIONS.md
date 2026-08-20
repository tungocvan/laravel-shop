# ClientPortal — Detailed Functional Guide

Updated: 2026-08-20  
Module: `Modules/ClientPortal`

## 1. Vai trò tổng thể

`ClientPortal` là lớp ứng dụng dành cho người dùng Client/PWA. Module này không thay thế các domain module như `Muasamcong`, `Invoices`, `Admission` hay `Pharma`; nó cung cấp một cổng truy cập thống nhất để người dùng đăng nhập bằng guard `web`, nhìn thấy các ứng dụng được cấp quyền, sử dụng các chức năng của từng ứng dụng, theo dõi tác vụ Queue và tải/chia sẻ các kết quả được tạo ra.

Mô hình tổng quát:

```text
Người dùng Client
    -> /my-apps
    -> ApplicationRegistry
    -> kiểm tra client.{application}.access
    -> mở /apps/{application}
    -> kiểm tra quyền feature/action
    -> Client adapter
    -> gọi service/model của domain module nguồn
```

Nguyên tắc sở hữu:

- `ClientPortal` sở hữu trải nghiệm Client/PWA, permission Client, trạng thái Queue của Client, public share và artifact do Client tạo.
- Domain module nguồn vẫn sở hữu dữ liệu nghiệp vụ, model, service và cấu hình Admin của domain đó.
- Adapter trong `ClientPortal` chỉ tiêu thụ API/service của domain module nguồn; domain module không được phụ thuộc ngược vào `ClientPortal`.

---

## 2. Application Launcher — `/my-apps`

### Mục đích

Đây là màn hình đầu tiên sau khi Client đăng nhập. Nó đóng vai trò giống "App Launcher" để một tài khoản có thể dùng nhiều ứng dụng trong cùng hệ thống.

### Luồng xử lý

```text
GET /my-apps
    -> auth:web
    -> PortalController@index
    -> ApplicationRegistry::forUser()
    -> lọc các application đang khả dụng
    -> lọc theo quyền user
    -> render danh sách ứng dụng
```

### Điều kiện một application được hiển thị

Một ứng dụng chỉ xuất hiện khi đồng thời thỏa:

1. Có adapter trong `Modules/ClientPortal/Applications/{Application}`.
2. Có `manifest.php` hợp lệ.
3. `source_module` của adapter tồn tại và đang enabled.
4. User có permission `client.{application}.access`, nếu manifest yêu cầu.

### Trải nghiệm PWA

Launcher có:

- metadata cho PWA;
- nút cài ứng dụng khi browser hỗ trợ `beforeinstallprompt`;
- service worker;
- responsive mobile/desktop;
- trạng thái rỗng khi tài khoản chưa được cấp application.

---

## 3. Application Registry

Service: `Modules\ClientPortal\Services\ApplicationRegistry`

### Nhiệm vụ

Registry tự động quét:

```text
Modules/ClientPortal/Applications/*/manifest.php
```

Mỗi manifest mô tả:

- key của application;
- module nguồn;
- tên/mô tả/icon;
- route mặc định;
- application permission;
- feature;
- action của từng feature;
- thứ tự hiển thị.

### Ý nghĩa

Khi bổ sung một ứng dụng Client mới, AI không cần sửa một danh sách hard-code trung tâm. Chỉ cần tạo adapter đúng convention, Registry sẽ tự phát hiện.

### Source module isolation

Ví dụ adapter `Muasamcong` khai báo:

```text
source_module = Muasamcong
```

Nếu `Muasamcong` bị disable, adapter Client tương ứng biến mất khỏi launcher nhưng route/Admin/domain của module nguồn không bị ClientPortal can thiệp.

---

## 4. Hệ thống phân quyền Client

Service: `ApplicationPermissionService`

### Namespace

```text
client.{application}.access
client.{application}.{feature}.view
client.{application}.{feature}.{action}
```

Ví dụ hiện tại:

```text
client.muasamcong.access
client.muasamcong.drug-pricing.view
client.muasamcong.drug-pricing.sync
client.muasamcong.history.view
client.muasamcong.wishlist.view
client.muasamcong.price-list.view
client.muasamcong.price-list.export
client.muasamcong.contractors.view
client.muasamcong.analytics.view
```

### Guard

- Client permissions: `web`.
- Admin quản trị permission: `admin`.

### Chức năng Admin

Route gốc:

```text
/admin/client-apps
```

Admin có thể:

- xem tất cả Client Application được Registry phát hiện;
- xem feature/action và permission tương ứng;
- quét manifest để tạo permission còn thiếu;
- đồng bộ toàn bộ Client permission cho các Super Admin phù hợp;
- gán trực tiếp `client.*` permission cho User;
- gán `client.*` permission cho Role guard `web`.

### Middleware quyền truy cập

`EnsureApplicationAccess` kiểm tra quyền mức application.

`EnsureFeatureAccess` kiểm tra quyền mức feature.

Nhờ đó route Client được bảo vệ theo hai tầng:

```text
auth:web
    -> client.application:{application}
    -> client.feature:{application},{feature}
```

Action đặc biệt như sync/export tiếp tục kiểm tra permission action ở server-side.

---

## 5. PWA Application Shell

View chính:

```text
ClientPortal::layouts.application
```

### Chức năng

- header cố định;
- quay về launcher;
- tên application hiện tại;
- route Dashboard tùy application;
- logout;
- mobile bottom navigation;
- service worker registration;
- responsive layout;
- safe area cho thiết bị mobile;
- queue status UI cho workflow đồng bộ hiện tại.

### Service Worker

Service worker hiện chỉ cache các shell asset an toàn:

```text
offline page
manifest
PWA icons
```

Navigation có đăng nhập dùng network-first và không ghi response authenticated vào Cache Storage.

Điều này quan trọng vì dữ liệu cá nhân/permission-sensitive không bị lưu nhầm thành cache dùng chung.

---

# 6. Application: Mua sắm công

Adapter hiện tại:

```text
Modules/ClientPortal/Applications/Muasamcong
```

Application này là client-facing layer của `Modules/Muasamcong`.

Các feature hiện được manifest công bố:

```text
Tra cứu thuốc trúng thầu
Lịch sử tra cứu
Danh sách quan tâm
Bảng Giá
Nhà thầu
Phân tích
```

Trong đó `Nhà thầu` và `Phân tích` hiện mới có permission/manifest entry; chưa có Client route hoàn chỉnh trong adapter đã kiểm tra.

---

## 7. Tra cứu thuốc trúng thầu

Route chính:

```text
/apps/muasamcong/drug-pricing
```

Permission:

```text
client.muasamcong.drug-pricing.view
```

### 7.1 Tìm kiếm

Người dùng nhập từ khóa có thể là:

- tên thuốc;
- hoạt chất;
- mã TBMT;
- nhóm thuốc;
- thông tin nhà sản xuất/đơn vị trúng thầu tùy dữ liệu hiện có.

### 7.2 Chiến lược database-first

`ClientPricingSearchService` thực hiện theo thứ tự:

```text
1. dữ liệu PricingResult đã đồng bộ trong database
2. PricingSearchSnapshot đã lưu
3. gọi API Mua sắm công
```

Mục tiêu:

- tăng tốc độ phản hồi;
- giảm số lần gọi API ngoài;
- vẫn có khả năng lấy dữ liệu mới nhất.

Khi user chọn "Tra cứu dữ liệu mới nhất", `refresh=1` bỏ qua local DB/snapshot và gọi API trực tiếp.

### 7.3 Bộ lọc

Kết quả có thể lọc thêm theo:

- tên thuốc;
- hoạt chất;
- nhóm thuốc;
- công ty trúng thầu;
- giá tăng dần/giảm dần.

UI dùng progressive disclosure: bộ lọc được mở trong panel thay vì chiếm toàn bộ màn hình.

### 7.4 Thống kê nhanh

Sau khi lọc, Client tính và hiển thị:

- tổng kết quả;
- giá thấp nhất;
- giá trung bình;
- giá cao nhất.

### 7.5 Pagination

Kết quả hiển thị theo trang, hiện tại 20 dòng/trang.

### 7.6 Chi tiết bản ghi

Route detail nhận `sourceId` UUID và từ khóa gốc để re-resolve dữ liệu. Trang chi tiết cho biết thông tin thuốc, giá, TBMT, đơn vị trúng thầu và trạng thái đã sync/Wishlist.

---

## 8. Đồng bộ dữ liệu đã chọn

Action permission:

```text
client.muasamcong.drug-pricing.sync
```

### Chức năng

User có thể tick các kết quả chưa có trong database và đưa chúng vào Queue để đồng bộ.

### Giới hạn hiện tại

- tối đa 100 source UUID/request;
- keyword bắt buộc;
- source ID phải là UUID.

### Luồng

```text
User chọn bản ghi
    -> POST sync
    -> tạo SyncRequest(status=queued)
    -> dispatch SyncPricingResultsJob
    -> job gọi lại nguồn để xác minh dữ liệu
    -> PricingResultSyncService::syncSelected()
    -> cập nhật inserted/duplicates/missing
    -> status completed hoặc failed
```

### Trạng thái

```text
queued
processing
completed
failed
```

### Theo dõi tiến trình

Sau khi dispatch, UI giữ `sync_request_id` và poll endpoint status theo owner.

Kết quả hoàn thành hiển thị:

- số bản ghi mới;
- số bản ghi đã tồn tại;
- số source bị thiếu;
- lỗi nếu job thất bại.

### Queue worker

`SyncPricingResultsJob`:

```text
tries   = 3
timeout = 180 giây
queue   = default
```

---

## 9. Lịch sử tra cứu

Route:

```text
/apps/muasamcong/history
```

Permission:

```text
client.muasamcong.history.view
```

### Nội dung

Trang History hợp nhất hai nguồn hoạt động của chính user:

1. `PricingSearchSnapshot` — lịch sử tìm kiếm.
2. `SyncRequest` — lịch sử yêu cầu đồng bộ.

### Bộ lọc

Có thể lọc:

```text
all
search
sync
```

và trạng thái sync:

```text
queued
processing
completed
failed
```

Có tìm kiếm theo keyword.

Mỗi loại dữ liệu hiện giới hạn 100 record gần nhất trước khi hợp nhất theo thời gian.

---

## 10. Wishlist — Danh sách quan tâm

Route:

```text
/apps/muasamcong/wishlist
```

Permission:

```text
client.muasamcong.wishlist.view
```

### Chức năng

- thêm thuốc vào Wishlist;
- toggle thêm/bỏ ngay từ trang tìm kiếm;
- tìm kiếm Wishlist;
- phân trang;
- xóa item;
- dùng Wishlist làm nguồn tạo Bảng Giá.

### Ownership

Mọi thao tác đều scope theo `user_id` của authenticated `web` user.

### Lưu snapshot

Khi thêm Wishlist, adapter re-resolve source item và lưu các field chính cùng snapshot để có thể sử dụng lại.

Dữ liệu Wishlist vẫn thuộc model/domain `Muasamcong`, không phải model riêng của ClientPortal.

---

## 11. Public Share — Chi tiết thuốc

### Tạo link

Từ một kết quả thuốc, user có thể tạo public link.

Route public:

```text
/share/muasamcong/drug/{token}
```

Token ngẫu nhiên 64 ký tự.

### Dữ liệu công khai

Không lưu toàn bộ payload thô. Controller dùng allowlist chỉ lấy các field được phép chia sẻ, ví dụ:

- tên thuốc;
- hoạt chất;
- nồng độ;
- nhóm thuốc;
- đường dùng;
- dạng bào chế;
- đơn vị tính;
- giá;
- số lượng;
- nhà thầu;
- mã TBMT;
- số quyết định;
- nhà sản xuất;
- nước sản xuất.

### Quản lý link

User tạo link có thể:

- xem danh sách link;
- đặt hạn 7 ngày;
- đặt hạn 30 ngày;
- không hết hạn;
- revoke link.

### Tracking

Public share lưu:

```text
views_count
last_viewed_at
expires_at
revoked_at
```

Link hết hạn/revoked trả trạng thái không còn khả dụng.

---

# 12. Bảng Giá

Route chính:

```text
/apps/muasamcong/price-list
```

View permission:

```text
client.muasamcong.price-list.view
```

Action permission:

```text
client.muasamcong.price-list.export
```

Đây là workflow lớn nhất hiện tại của ClientPortal.

---

## 13. Nguồn dữ liệu Bảng Giá

User có thể chọn một trong hai nguồn:

```text
synced    -> dữ liệu PricingResult đã đồng bộ
wishlist  -> Wishlist của chính user
```

Danh sách nguồn có:

- tìm kiếm;
- pagination;
- checkbox chọn record;
- giới hạn tối đa 200 record cho một export request.

Server xác minh lại toàn bộ selected ID trước khi tạo export để tránh browser gửi ID ngoài scope.

---

## 14. Export Profile

Client sử dụng cấu hình Excel do domain `Muasamcong` cung cấp qua:

```text
SyncedExportProfile
SyncedPricingExportPreferenceService
```

Profile có thể cấu hình:

- thứ tự cột;
- cột được xuất;
- tiêu đề cột;
- alignment;
- width;
- kiểu dữ liệu;
- số chữ số thập phân;
- header/footer;
- logo;
- chữ ký;
- page setup;
- default profile.

### Page Setup

Có hỗ trợ:

- A4;
- portrait/landscape;
- margin;
- căn giữa horizontal/vertical;
- fit width/fit height;
- print-friendly configuration.

Cấu hình mặc định hiện ưu tiên A4 landscape, fit 1 trang chiều ngang.

---

## 15. Tạo Excel bằng Queue

Khi user bấm tạo Bảng Giá:

```text
POST /price-list
    -> xác minh export permission
    -> validate source/profile/selected IDs
    -> tạo PriceListExport(status=queued)
    -> dispatch GeneratePriceListExport
```

### GeneratePriceListExport

Job chịu trách nhiệm:

1. đọc export record;
2. đọc profile;
3. resolve selected source rows;
4. dựng workbook bằng PhpSpreadsheet;
5. render Header;
6. render logo nếu cấu hình;
7. render table;
8. áp dụng data type/format/width/alignment;
9. render Footer/Signature;
10. áp dụng Page Setup;
11. lưu XLSX vào private local storage;
12. cập nhật trạng thái artifact.

### Data types

Export profile hỗ trợ:

```text
string
number
date
auto
```

Mục tiêu là giữ dữ liệu Excel đúng kiểu thay vì xuất mọi thứ thành text.

### Trạng thái export

```text
queued
processing
completed
failed
```

Trang Client poll status để biết khi nào file sẵn sàng.

---

## 16. Quản lý các Bảng Giá đã tạo

Mỗi user chỉ nhìn thấy export của mình.

Có thể:

- lọc theo trạng thái;
- tìm theo file/source;
- phân trang;
- tải Excel;
- mở lại cấu hình/selection;
- tạo lại export;
- xóa export;
- tạo PDF;
- tải PDF;
- tạo share link;
- gửi email;
- xem lịch sử giao nhận gần đây.

### Xóa

Khi xóa export, hệ thống xóa record và các file Excel/PDF tương ứng khỏi local storage.

---

## 17. Tạo lại / Recreate

`recreate` không sửa file cũ tại chỗ.

Nó tạo một `PriceListExport` mới dựa trên:

- profile ID cũ;
- source cũ;
- selected IDs cũ.

Sau đó Queue tạo artifact mới bằng cấu hình profile hiện tại.

Điều này giữ được lịch sử các export thay vì ghi đè record cũ.

---

## 18. Chuyển Excel sang PDF

Action:

```text
POST /price-list/{exportId}/pdf
```

### Điều kiện

- export thuộc user hiện tại;
- Excel đã completed;
- Excel artifact còn tồn tại.

### Queue job

`GeneratePriceListPdf` thực hiện:

```text
XLSX private storage
    -> PhpSpreadsheet normalize row heights
    -> temporary XLSX
    -> libreoffice --headless
    -> PDF
    -> private storage
```

### Lý do normalize row height

Excel và LibreOffice có thể tính chiều cao row wrap khác nhau. Job đóng băng chiều cao các row dữ liệu để giảm tình trạng footer/chữ ký bị đẩy lệch khi convert PDF trên server.

### Storage

PDF được lưu theo export ID trong private local disk và chỉ download qua authenticated owner-scoped route.

### Cleanup

Temp files được xóa trong `finally`.

---

## 19. Download Excel/PDF

Các file Client không được phát trực tiếp qua `public/storage`.

Luồng:

```text
authenticated request
    -> tìm PriceListExport
    -> kiểm tra owner
    -> kiểm tra status
    -> kiểm tra Storage::exists()
    -> Storage::disk('local')->download()
```

Điều này giúp tránh đoán URL trực tiếp để lấy file của user khác.

---

## 20. Share public Bảng Giá

User có thể tạo một public share token cho Excel Bảng Giá.

Route:

```text
/share/muasamcong/price-list/{token}
```

Hệ thống:

- tạo/reuse token ngẫu nhiên 64 ký tự;
- chỉ cho download khi export completed;
- kiểm tra file còn tồn tại;
- ghi một delivery-history entry channel `share`.

Hiện workflow này chưa có expiry/revoke riêng như Public Drug Share. Đây là mục P1 đã được ghi trong `ANALYSIS.md`.

---

## 21. Gửi Bảng Giá qua Email

Action yêu cầu:

```text
email
content
attach_excel
attach_pdf
```

User phải chọn ít nhất một file đính kèm.

Nếu chọn PDF thì PDF phải completed trước.

### Queue

`SendPriceListExportEmail`:

1. load export;
2. kiểm tra artifact;
3. resolve file private;
4. gửi email text;
5. attach Excel/PDF theo lựa chọn;
6. ghi delivery history.

### Delivery history

Hiện lưu tối đa 20 entry gần nhất trong JSON:

```text
channel
recipient
content
formats
sent_at
```

---

## 22. Models riêng của ClientPortal

### SyncRequest

Theo dõi một yêu cầu sync Client.

```text
client_portal_sync_requests
```

Dùng để:

- Queue progress;
- owner-scoped polling;
- thống kê inserted/duplicate/missing;
- lưu failure state.

### PublicShare

Theo dõi public drug share.

```text
client_portal_public_shares
```

Dùng để:

- token;
- payload công khai;
- expiry;
- revoke;
- view tracking.

### PriceListExport

Theo dõi artifact Bảng Giá.

```text
client_portal_price_list_exports
```

Dùng để:

- owner;
- source;
- selected IDs;
- profile;
- trạng thái Excel;
- trạng thái PDF;
- đường dẫn private artifact;
- share token;
- delivery history;
- error state.

---

## 23. Database tables ClientPortal

Module hiện sở hữu trực tiếp:

```text
client_portal_sync_requests
client_portal_public_shares
client_portal_price_list_exports
```

Các bảng/domain mà adapter Muasamcong sử dụng nhưng không sở hữu gồm những bảng của `Modules/Muasamcong`, ví dụ:

```text
muasamcong_pricing_results
muasamcong_pricing_wishlists
muasamcong_pricing_search_snapshots
muasamcong_synced_export_profiles
```

AI tiếp tục phát triển phải giữ ranh giới ownership này.

---

## 24. Các dependency quan trọng

```text
ClientPortal
├── Auth
├── App\Models\User
├── Spatie Permission
├── Laravel Queue
├── Laravel Mail
├── Laravel Storage
├── Laravel Process
├── PhpSpreadsheet
├── LibreOffice runtime (PDF conversion)
└── Muasamcong
    ├── MuaSamCongService
    ├── PricingSearchSnapshotService
    ├── PricingResultSyncService
    ├── PricingTbmtPaginationService
    ├── PricingResult
    ├── PricingWishlist
    ├── SyncedExportProfile
    └── SyncedPricingExportPreferenceService
```

---

## 25. Feature chưa hoàn thiện

Manifest hiện có:

```text
client.muasamcong.contractors.view
client.muasamcong.analytics.view
```

Nhưng adapter đã kiểm tra chưa có route/page Client hoàn chỉnh cho hai feature này.

AI tiếp theo không nên hiểu permission tồn tại là feature đã hoàn thành.

---

## 26. Điểm cần xử lý trước/hoặc ngay sau merge

Chi tiết kỹ thuật nằm trong `ANALYSIS.md`. Các mục quan trọng nhất:

1. Excel filename hiện có nguy cơ collision nếu cùng user tạo nhiều export trong cùng một giây; nên chuyển sang path có `export_id`/UUID.
2. Export Profile có `user_id`, nhưng Client hiện cần contract rõ profile nào được publish cho Client.
3. Một số mutation đang nằm dưới feature-level `view`; cần action permission rõ hơn khi mở rộng hệ thống.
4. Public Price List share cần expiry/revoke lifecycle.
5. Không trả raw exception/process error cho Client.
6. Email/share/PDF nên có idempotency/delivery state rõ hơn.
7. Generic PWA layout không nên chứa logic Queue riêng của Muasamcong lâu dài.
8. Controller Price List đang quá lớn, nên tách service/workflow.
9. Cần behavioural tests cho owner isolation, export Queue, artifact download, PDF, email, share lifecycle và file collision.

---

## 27. Hướng mở rộng một Client Application mới

Ví dụ tạo `Invoices` Client app:

```text
Modules/ClientPortal/Applications/Invoices/
├── manifest.php
├── routes.php
├── Http/Controllers/
├── Services/
└── Jobs/          # nếu workflow riêng của Client cần Queue
```

Quy trình:

```text
1. khai báo source_module=Invoices
2. khai báo client.invoices.access
3. khai báo feature permissions
4. tạo routes có auth:web
5. gắn client.application:invoices
6. gắn client.feature cho từng nhóm route
7. reuse service/model của Modules/Invoices
8. không copy business logic sang ClientPortal
9. thêm views dưới ClientPortal/resources/views/applications/invoices
10. sync permissions ở /admin/client-apps
11. thêm tests/Feature/ClientApps
```

---

## 28. Checklist cho AI tiếp tục phát triển

Trước khi sửa `ClientPortal`, AI nên đọc:

```text
.codex/bootstrap/CODEX_BOOTSTRAP.md
.codex/bootstrap/PROJECT_BOOTSTRAP.md
.codex/bootstrap/AI_PROJECT_CONTEXT.md
.codex/standards/MODULE_STANDARD.md
.codex/standards/ADMIN_UI_STANDARD.md
ROADMAP.md
docs/modules/ClientPortal/ANALYSIS.md
docs/modules/ClientPortal/INFORMATION.md
docs/modules/ClientPortal/FUNCTIONS.md
docs/modules/ClientPortal/README.md
Modules/ClientPortal/README.md
```

Sau đó xác định rõ task thuộc:

```text
Client shell / PWA       -> ClientPortal
Client permission        -> ClientPortal
Client queue tracking    -> ClientPortal
Client-specific artifact -> ClientPortal
Domain data/business     -> source domain module
Admin domain config      -> source domain module
```

Không thay đổi ownership chỉ để giảm số file hoặc gom logic cho tiện.

---

## 29. Tóm tắt ngắn cho AI khác

`ClientPortal` là một application platform dành cho Client/PWA, hiện có một adapter hoàn chỉnh nhất cho `Muasamcong`. Nó cung cấp launcher, permission discovery, application/feature middleware, PWA shell, database-first drug search, Queue sync, History, Wishlist, public drug sharing và workflow Bảng Giá gồm Excel -> PDF -> download/share/email. `Muasamcong` vẫn là domain owner của dữ liệu và export profile; `ClientPortal` chỉ sở hữu trải nghiệm Client và các artifact/tracking record của Client. Giữ nguyên kiến trúc này, xử lý các P1 trong `ANALYSIS.md` bằng refactor có mục tiêu, không rebuild toàn bộ module.