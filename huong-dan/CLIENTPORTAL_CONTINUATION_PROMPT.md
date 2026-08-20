Bạn đang tiếp tục phát triển dự án Laravel 12 hiện có.

Repository:
git@github.com:tungocvan/laravel-shop.git

Module cần tiếp tục:
Modules/ClientPortal

QUAN TRỌNG:
- Không được giả định kiến trúc.
- Không được rebuild module từ đầu.
- Không được thay đổi DB/schema/architecture nếu chưa xác định rõ nguyên nhân.
- Ưu tiên đọc source + tài liệu hiện tại trước khi đề xuất.
- Giữ backward compatibility tối đa.
- Không refactor lan sang module khác nếu không có nguyên nhân trực tiếp.
- Mọi thay đổi phải theo từng phase nhỏ, có test và checkpoint rõ ràng.
- Không merge main nếu chưa có xác nhận test PASS thực tế.
- Nếu thấy tài liệu và source lệch nhau, source + test hiện tại là nguồn xác thực cuối cùng, sau đó cập nhật lại docs.

================================================================
1. BOOTSTRAP BẮT BUỘC PHẢI ĐỌC
================================================================

Trước khi làm bất kỳ việc gì, đọc theo thứ tự:

.codex/bootstrap/CODEX_BOOTSTRAP.md
.codex/bootstrap/PROJECT_BOOTSTRAP.md
.codex/bootstrap/AI_PROJECT_CONTEXT.md

.codex/standards/MODULE_STANDARD.md
.codex/standards/ADMIN_UI_STANDARD.md

.codex/tasks/analyze-module.md
.codex/tasks/refactor-module.md
.codex/tasks/analyze-livewire.md
.codex/tasks/refactor-livewire.md

Sau đó đọc toàn bộ tài liệu ClientPortal:

docs/modules/ClientPortal/README.md
docs/modules/ClientPortal/DEBUG_NOTES.md
docs/modules/ClientPortal/PWA.md
docs/modules/ClientPortal/PRICE_LIST_EXCEL.md
docs/modules/ClientPortal/FUNCTIONS.md
docs/modules/ClientPortal/INFORMATION.md
docs/modules/ClientPortal/ANALYSIS.md

Mục tiêu là phải hiểu module trước khi sửa code.

================================================================
2. KIẾN TRÚC TỔNG THỂ CLIENTPORTAL
================================================================

ClientPortal là support module cho Client/PWA, không phải business-domain module.

Dependency đúng:

Public Website / Installed PWA
        ↓
Client login + /my-apps
        ↓
Application Registry + permissions
        ↓
ClientPortal Application Adapter
        ↓
Source Domain Module

Quy tắc dependency:

ClientPortal -> Muasamcong / domain modules
Domain module -X-> ClientPortal

Không được đảo chiều dependency.

Domain module sở hữu:
- canonical data
- business services
- business persistence rules

ClientPortal sở hữu:
- PWA shell
- client UX
- client permissions
- application launcher
- adapter/controller presentation
- client workflow state
- queue orchestration phía client
- client-specific share/delivery state

================================================================
3. CLIENTPORTAL HIỆN ĐANG CÓ GÌ
================================================================

Application Launcher:
- /my-apps
- authenticated bằng guard web
- chỉ hiển thị app được cấp quyền

PWA Login:
- /my-apps/login
- route name: client.apps.login
- mobile-first
- dùng lại Modules\Auth\Livewire\Auth\LoginForm
- variant = pwa
- guard = web
- không tạo hệ login riêng
- logout client phải quay về /my-apps/login

Website PWA Installer:
- Android Chromium: beforeinstallprompt
- iPhone/iPad Safari: hướng dẫn Share -> Add to Home Screen
- iOS non-Safari: hướng dẫn mở bằng Safari
- standalone mode: hiển thị trạng thái đã cài
- manifest start_url = /my-apps

Primary Client application:
Modules/ClientPortal/Applications/Muasamcong

Các chức năng hiện có:
- Tra cứu giá thuốc
- Database-first lookup
- API fallback theo kiến trúc hiện tại
- Đồng bộ dữ liệu chọn lọc qua Queue
- Lịch sử tìm kiếm / đồng bộ
- Wishlist
- Public Drug Sharing
- Quản lý link chia sẻ
- Bảng Giá
- Queue tạo Excel
- Queue convert PDF
- Private download
- Public Price List sharing
- Gửi bảng giá qua email
- Chọn Excel/PDF/cả hai khi gửi
- Delivery/share tracking
- Responsive Price List workspace UI

================================================================
4. PERMISSION CONTRACT
================================================================

Client permission dùng guard web.

Convention:

client.{application}.access
client.{application}.{feature}.view
client.{application}.{feature}.{action}

Ví dụ:

client.muasamcong.access
client.muasamcong.drug-pricing.view
client.muasamcong.drug-pricing.sync
client.muasamcong.history.view
client.muasamcong.wishlist.view
client.muasamcong.price-list.view
client.muasamcong.price-list.export
client.muasamcong.contractors.view
client.muasamcong.analytics.view

Không được dùng permission .view để mặc định cấp quyền mutation/destructive nếu chưa có contract rõ ràng.

Nếu bổ sung chức năng mới:
- định nghĩa permission riêng
- cập nhật registry/feature config
- cập nhật seeder/permission sync nếu kiến trúc hiện tại yêu cầu
- có test authorization

================================================================
5. PWA CONTRACT
================================================================

Các file quan trọng:

public/manifest.webmanifest
public/service-worker.js
public/pwa/icon.svg
public/pwa/icon-maskable.svg

ClientPortal login:
Modules/ClientPortal/resources/views/pages/login.blade.php

Auth logic:
Modules/Auth/Livewire/Auth/LoginForm.php
Modules/Auth/Http/Controllers/AuthController.php

Yêu cầu:
- guest /my-apps -> /my-apps/login
- guest /apps/* -> /my-apps/login
- login thành công -> /my-apps
- logout -> /my-apps/login
- admin login/logout không bị ảnh hưởng

Không cache authenticated HTML navigation trong service worker.

Không thêm Google Login cho PWA nếu chưa xây web-guard Client OAuth riêng.
Google OAuth hiện tại là Admin-oriented.

================================================================
6. PRICE LIST WORKSPACE
================================================================

Route chính:

/apps/muasamcong/price-list

UI đã được polish theo hướng mobile app.

Nguyên tắc UI hiện tại:

Mobile:
- card gọn
- metadata compact
- Excel/PDF/Share dùng SVG icon-only
- Gửi bảng giá là CTA chính
- menu ... chứa action phụ
- delivery history hiển thị “Đã gửi gần nhất”

Desktop:
- icon + label:
  Excel
  PDF
  Chia sẻ
  Gửi bảng giá
  ...
- không dùng layout icon-only giống mobile

Không được tái sử dụng CSS mobile cho desktop mà không kiểm tra responsive.

File polish:

Modules/ClientPortal/resources/views/applications/muasamcong/partials/price-list-workspace-polish.blade.php

UI phải giữ:
- mobile-first
- touch target hợp lý
- không overflow ở 430px
- không phá desktop
- action destructive để trong menu phụ
- action chính nổi bật

================================================================
7. PRICE LIST EXCEL — RẤT QUAN TRỌNG
================================================================

Đọc kỹ:

docs/modules/ClientPortal/PRICE_LIST_EXCEL.md

Nguồn cấu hình Excel KHÔNG nằm riêng trong ClientPortal.

Admin cấu hình tại:

/admin/muasamcong/synced

Cấu hình sử dụng:

SyncedExportProfile
SyncedPricingExportPreferenceService

ClientPortal chỉ tái sử dụng profile Admin đã tạo.

Không duplicate cấu hình Excel bên ClientPortal.

Renderer chính:

Modules/ClientPortal/Jobs/GeneratePriceListExport.php

PDF:

Modules/ClientPortal/Jobs/GeneratePriceListPdf.php

Email:

Modules/ClientPortal/Jobs/SendPriceListExportEmail.php

================================================================
8. EXCEL DATA TYPE CONTRACT
================================================================

Không được chỉ format “nhìn giống”.

Cell type phải đúng thực tế.

String:
- GĐKLH / GPNK
- số quyết định
- mã định danh
- các field dễ bị scientific notation

Phải ghi explicit string.

Numeric / price / quantity:
- phải là numeric thật
- không ghi string rồi format
- number format phải có thousands separator

Date:
- parse thành Excel date serial
- format dd/mm/yyyy
- không chỉ substring string

Chuẩn hóa hiện tại chỉ ở export layer:

Nhóm thuốc:
N1 / Nhóm 1 / NHÓM 1 -> 1
N2 / Nhóm 2 -> 2
...
chỉ hiển thị 1,2,3,4,5

Hạn dùng:
24 tháng -> 24
36 tháng -> 36

Không sửa canonical DB chỉ để làm đẹp Excel.

================================================================
9. EXCEL PAGE SETUP CONTRACT
================================================================

Default báo giá danh mục thuốc:

Orientation:
Landscape

Paper:
A4

Margins:
Left   = 0.3 cm
Right  = 0.3 cm
Top    = 0.8 cm
Bottom = 0.8 cm

Center on page:
Horizontally = true
Vertically   = false

Fit:
Fit to 1 page wide
Height auto / appropriate

“Fit Sheet on Page” phải giữ hiệu quả tương đương.

Kính gửi:
- bold

Font mặc định workbook:
- Times New Roman

Header/Footer:
- có thể bật/tắt bằng profile
- default hiện tại là bật nếu profile quy định

Logo:
- có preview trong Admin
- upload/delete
- width/height cấu hình theo cm
- renderer dùng đúng kích thước cấu hình
- không stretch sai aspect ratio

Signature:
- upload/delete
- width/height cấu hình
- nằm trong vùng 4 cột cuối
- ngày ký / chức danh / chữ ký / họ tên đều căn giữa trong 4 cột cuối

Nếu bảng kết thúc ở T:
signature range = Q:R:S:T

Không căn giữa toàn worksheet.

================================================================
10. EXCEL -> PDF CONTRACT
================================================================

PDF dùng LibreOffice headless.

Lỗi thực tế đã gặp:
- Excel đúng nhưng PDF chữ ký nhảy vào trong bảng

Nguyên nhân:
LibreOffice recalculates row heights/wrapped text khác Excel.

Giải pháp hiện tại:

XLSX gốc
 -> load bằng PhpSpreadsheet
 -> normalize row height cho bản convert tạm
 -> LibreOffice headless
 -> PDF

Không sửa Excel gốc chỉ để phục vụ PDF.

Nếu PDF lệch:
- kiểm tra row height
- drawing anchor
- signature range
- print area
- page setup

Không chỉnh offsetX/offsetY thủ công trước khi xác định đúng nguyên nhân.

================================================================
11. PRIVATE FILE / DOWNLOAD CONTRACT
================================================================

Price List XLSX/PDF là private artifact.

Storage:

storage/app/client-portal/price-lists/{user_id}/...

Không public trực tiếp file.

Download qua controller/authorized route.

Public sharing chỉ qua explicit share token.

Lỗi thực tế đã gặp:
- DB có record
- status completed
- file tồn tại bằng ls
- download 404

Nguyên nhân:
queue chạy root
web/php-fpm chạy www-data
permission/traverse mismatch

Debug nhanh:

php artisan route:list --path=apps/muasamcong/price-list -v

ps aux | grep -E "queue:work|queue:listen|horizon" | grep -v grep

readlink -f /proc/<QUEUE_PID>/cwd

find storage/app/client-portal/price-lists \
  -maxdepth 5 \
  -printf '%M %u:%g %p\n' | tail -40

Job tạo file phải tự normalize:
- group www-data
- file 664
- directory 775
hoặc theo chuẩn runtime hiện tại

Tốt hơn lâu dài:
queue worker chạy đúng application user/group.

Không được yêu cầu người dùng chown/chmod thủ công sau mỗi job.

================================================================
12. QUEUE CONTRACT
================================================================

Các tác vụ dài:
- sync
- XLSX generation
- PDF conversion
- email delivery

Phải giữ Queue.

State:
queued
processing
completed
failed

Polling UI:
- chỉ poll job đang pending
- không reload page mỗi 2–3 giây vô hạn
- reload tối đa một lần khi trạng thái chuyển completed/failed
- không để completed record cũ kích reload lặp

Nếu có polling bug:
kiểm tra:
data-pending
data-pdf-pending
status endpoint
JS transition detection

================================================================
13. EMAIL / DELIVERY
================================================================

“Gửi email” hiện được gọi là:
Gửi bảng giá

Modal phải cho nhập:
- Người nhận
- Nội dung
- chọn Excel
- chọn PDF
- hoặc cả hai

Sau khi gửi:
card Bảng Giá hiển thị:
- người nhận gần nhất
- thời gian
- loại file đã gửi

Nếu chia sẻ:
ghi nhận trạng thái/share history tương ứng.

Các side effect external như email:
- cần retry rõ ràng
- tránh gửi duplicate khi job retry
- về lâu dài nên có idempotency key/delivery state bền vững

Không expose raw exception/process output ra Client UI.

================================================================
14. PUBLIC SHARING
================================================================

Drug sharing hiện có:
- token
- expiry
- revoke

Price List sharing vẫn còn các cải tiến lifecycle trong ANALYSIS.md.

Nếu phát triển tiếp Price List sharing:
nên có:
- expiry
- revoke
- audit
- payload/file scope
- retention
- owner check
- high entropy token

================================================================
15. DEBUG NOTES BẮT BUỘC ĐỌC
================================================================

docs/modules/ClientPortal/DEBUG_NOTES.md

Các lỗi đã gặp cần nhớ:

1. Download 404 dù file tồn tại
   -> permission / queue user / storage path

2. PDF chữ ký nhảy vào bảng
   -> LibreOffice row height recalculation

3. Chữ ký nằm giữa toàn bảng
   -> footer merge A:last sai
   -> phải dùng last 4 columns

4. Polling refresh liên tục
   -> status completed cũ bị hiểu nhầm là job vừa hoàn tất

5. Export profile lưu nhưng Client không thấy
   -> kiểm tra DB connection, scope, profile ownership, active/default

6. GĐKLH/GPNK thành scientific notation
   -> explicit string

7. Price không có thousands separator
   -> numeric thật + number format

8. Date hiển thị timestamp
   -> parse Excel serial + dd/mm/yyyy

9. Mobile icon đẹp nhưng desktop vỡ
   -> CSS mobile-only nhưng JS mutate DOM global

10. Test fail chỉ vì spacing JS
    -> không viết assertion quá phụ thuộc formatting

11. Logout quay về /login
    -> clientLogout phải redirect client.apps.login

================================================================
16. TEST CONTRACT
================================================================

Sau mỗi thay đổi ClientPortal:

php artisan optimize:clear

Targeted tests:

php artisan test tests/Feature/ClientApps/ClientPwaFoundationTest.php

php artisan test tests/Feature/ClientApps/MuasamcongPriceListTest.php

Full ClientApps:

php artisan test tests/Feature/ClientApps

Nếu thay đổi Excel/PDF:
- tạo bảng giá mới
- tải Excel mới
- mở Print Preview
- convert PDF mới
- tải PDF
- test permission download
- test queue worker
- test mobile UI
- test desktop UI

Không dùng file cũ để kết luận renderer mới sai/đúng.

================================================================
17. GIT WORKFLOW
================================================================

Bắt đầu từ main mới nhất:

git checkout main
git pull origin main

Tạo branch riêng:

git checkout -b agent/clientportal-<feature-name>

Không code trực tiếp trên main.

Mỗi phase:
- code nhỏ
- test
- commit rõ ràng
- user test thực tế
- chỉ khi user xác nhận PASS mới merge main

Trước merge:

git fetch origin

git log --oneline --left-right --graph \
origin/main...HEAD

Nếu behind main:
merge/rebase phù hợp trước

Sau đó:
- create PR
- review diff
- merge main

Không merge nếu:
- targeted test fail
- UI chưa được user test
- migration chưa kiểm tra
- queue chưa kiểm tra nếu feature phụ thuộc queue

================================================================
18. PHƯƠNG PHÁP LÀM VIỆC VỚI TÔI
================================================================

Khi tôi đưa yêu cầu mới:

Bước 1:
Phân tích yêu cầu dựa trên source hiện tại.

Bước 2:
Nêu:
- scope
- file dự kiến ảnh hưởng
- rủi ro
- có cần migration hay không
- có ảnh hưởng domain module không
- test cần chạy

Bước 3:
Chờ tôi đồng ý nếu thay đổi lớn.

Bước 4:
Implement trên branch riêng.

Bước 5:
Cho tôi lệnh pull/test cụ thể.

Bước 6:
Tôi test UI/nghiệp vụ thực tế.

Bước 7:
Nếu tôi nói PASS:
- cập nhật docs nếu cần
- merge main

Không nói chung chung.
Không làm lại kiến trúc khi chưa cần.
Không tự ý mở rộng scope.

================================================================
19. ƯU TIÊN PHÁT TRIỂN TIẾP
================================================================

Trước khi làm feature mới, đọc ANALYSIS.md.

Các nhóm P1/P2 cần xem xét tiếp:

- Authorization tách view/action rõ hơn
- Export Profile scope/publication model
- Price List share expiry/revoke
- Email/PDF/share idempotency
- Delivery lifecycle
- Artifact retention
- Unique immutable artifact paths
- Controller/Job giảm trách nhiệm
- Service extraction
- Tăng workflow tests
- Full regression sau major change

Không xử lý tất cả cùng lúc.

Nên đi theo từng nhóm nhỏ.

================================================================
20. OUTPUT TÔI MONG MUỐN TỪ AI
================================================================

Khi bắt đầu phiên làm việc mới, hãy trả lời cho tôi:

1. Đã đọc những file nào.
2. Tóm tắt kiến trúc ClientPortal hiện tại.
3. Xác nhận branch/main hiện tại.
4. Liệt kê chức năng ClientPortal đang có.
5. Liệt kê P1/P2 còn mở.
6. Nêu đề xuất phase tiếp theo.
7. Không sửa code cho đến khi scope được xác nhận, trừ khi tôi yêu cầu implement trực tiếp.

Sau khi làm việc:
- luôn ghi commit hash
- branch
- file thay đổi
- test command
- kết quả mong đợi
- migration nếu có
- queue restart nếu cần

================================================================
21. CHECKPOINT HIỆN TẠI
================================================================

Main đã chứa:
- PWA Installer
- /my-apps/login
- logout -> /my-apps/login
- PWA logo fallback
- Price List workspace UI polish
- responsive SVG actions
- Excel renderer improvements
- PDF conversion queue
- email/share delivery
- debug notes
- Excel rendering notes
- ClientPortal docs đầy đủ

Tài liệu continuation hiện tại:

docs/modules/ClientPortal/
├── README.md
├── DEBUG_NOTES.md
├── PWA.md
├── PRICE_LIST_EXCEL.md
├── FUNCTIONS.md
├── INFORMATION.md
└── ANALYSIS.md

Bắt đầu bằng việc đọc toàn bộ các file trên trước khi đưa ra bất kỳ thay đổi nào.

Sau khi đọc xong, hãy báo:
“Đã nắm ClientPortal hiện tại”
rồi tóm tắt ngắn kiến trúc + chức năng + rủi ro + đề xuất bước tiếp theo.
