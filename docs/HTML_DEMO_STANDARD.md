# HTML Demo → Laravel 12 Blade Workflow

> Tiêu chuẩn triển khai **Standalone Demo** trong `tungocvan/laravel-shop`. Đọc cùng `docs/PROJECT_INSTRUCTIONS.md` và `docs/GITHUB_COLLABORATION_WORKFLOW.md` từ `main` mới nhất. Tài liệu này không cho phép tự ý sửa code hoặc deploy khi người dùng chỉ gửi URL tham khảo.

## 1. Điều kiện kích hoạt và Phase 1 — Audit

Khi người dùng gửi file HTML, URL HTML, GitHub link chứa HTML, yêu cầu chuyển HTML sang Laravel hoặc tạo Demo độc lập:

1. Kiểm tra `main`, GitHub, route và Demo hiện có; đọc workflow và handoff.
2. Đọc **toàn bộ** HTML, CSS, JS, thư viện, dữ liệu nhúng, event handlers, DOM tạo động, localStorage, import/export, Excel và in ấn.
3. Xác định mục tiêu: tham khảo, phân tích hay triển khai. Không tự sửa code chỉ vì có liên kết.
4. Phân tích dependency, bảo mật, tương thích responsive, các rủi ro khi chuyển HTML → Blade.
5. Đánh giá khả năng bổ sung **thư viện JSON server** và **4 theme**; trình bày phạm vi và kế hoạch. Chờ phê duyệt đối với thay đổi lớn.
6. Nếu HTML không truy cập được/không đầy đủ, yêu cầu file; không đoán nội dung.

## 2. Kiến trúc mặc định

- Laravel 12/PHP 8.3; Demo độc lập, **không tạo Module**.
- Một GET route **chính** cho giao diện, Controller riêng, Blade layout riêng, pages và partials cho sidebar/header/modal.
- Có thể có **API GET/POST phụ trợ** cho thư viện JSON server khi được phê duyệt; không coi API là route giao diện bổ sung.
- CSS/JS tách riêng; bảo toàn DOM ID, CSS class, sự kiện, dữ liệu và hành vi gốc.
- **Bắt buộc tránh trùng đường dẫn:** không tạo thư mục vật lý `public/demo/{demo-slug}/` khi có route giao diện `/demo/{demo-slug}`. Dùng `public/demo-assets/{demo-slug}/` hoặc đường dẫn asset đã được audit là không xung đột.
- Không dùng database nếu không yêu cầu; localStorage giữ dữ liệu nghiệp vụ của từng trình duyệt.
- Không ảnh hưởng Module/Demo khác; không tự ý thêm route hoặc ghi đè Demo hiện có.

Cấu trúc tham khảo:

```text
app/Http/Controllers/Demo/{DemoName}Controller.php
app/Http/Controllers/Demo/{DemoName}DatasetController.php  # khi có API
resources/views/demo/{demo-slug}/
  index.blade.php
  layouts/app.blade.php
  pages/*.blade.php
  partials/*.blade.php
public/demo-assets/{demo-slug}/css/app.css
public/demo-assets/{demo-slug}/js/app.js
docs/demos/{DEMO_NAME}.md
```

### 2.1. Cảnh báo bắt buộc: Nginx 301 → 403 khi thư mục asset trùng route

**Sự cố thực tế (2026-10-28, Quản lý nhân sự):**

- Laravel có route `GET /demo/quan-ly-nhan-su`, nhưng source đồng thời tạo thư mục vật lý `public/demo/quan-ly-nhan-su/` để chứa CSS/JS.
- Nginx ưu tiên xử lý thư mục thực tế: request **không có slash cuối** bị chuyển `301 Location: /demo/quan-ly-nhan-su/`; request **có slash cuối** nhận `403 Forbidden` vì thư mục không có index được phép truy cập. Request không tới Laravel Controller.
- `php artisan route:list` vẫn hiển thị route chính xác; vì vậy **route tồn tại không chứng minh request đã vào Laravel**. Trang lỗi mang chữ ký `nginx/...` là manh mối cần đối chiếu bằng HTTP response và log.

**Quy tắc phòng ngừa (áp dụng cho mọi standalone demo mới):**

1. Trước khi tạo asset, đối chiếu `routes/web.php`, `public/demo/` và đường dẫn GET UI. Không đặt asset trong `public/demo/{demo-slug}/` nếu slug đó cũng là route.
2. Mặc định đặt CSS/JS tại `public/demo-assets/{demo-slug}/css|js/`; tham chiếu bằng `asset('demo-assets/{demo-slug}/...')`. Khi dùng `filemtime(public_path(...))` để cache-bust, đường dẫn phải khớp file thật.
3. Không tự ý đổi đường dẫn asset của demo cũ đang hoạt động; chỉ di chuyển khi đã audit và có regression.
4. Khi phát hiện 301/403, **kiểm tra read-only trước**:

   ```bash
   php artisan route:list --path=demo/{demo-slug}
   ls -ld public/demo/{demo-slug} public/demo-assets/{demo-slug} 2>&1
   curl -sSI http://<local-host>/demo/{demo-slug}
   curl -sSI http://<local-host>/demo/{demo-slug}/
   ```

   Thay `<local-host>` bằng domain môi trường kiểm thử thực tế. Không dùng lệnh này với dữ liệu nhạy cảm trong URL. Nếu cần xem log hoặc cấu hình Nginx của Production, **đọc `docs/PRODUCTION_DEBUG_PROMPT.md` trên main mới nhất trước**.
5. Ưu tiên xử lý ở cấu trúc asset/route của feature branch; **không tự sửa Nginx**, restart/rebuild/deploy hoặc thay `.env` để chữa triệu chứng.
6. Khi di chuyển file trên Git, Git chỉ loại bỏ **file tracked**. Thư mục cũ có thể còn do file **untracked**, nên `git pull` không bảo đảm thư mục biến mất. Kiểm tra `ls -la public/demo/{demo-slug}/` và `git status --short`; **không dùng `rm -rf` hoặc xóa file untracked** khi chưa xác định quyền sở hữu và được người dùng cho phép.
7. Sau khi xử lý, xác minh URL UI không slash cuối trả HTTP 200, kiểm tra hành vi URL có slash cuối theo routing hiện hành, CSS/JS tải thành công, không có console error, không ảnh hưởng demo khác. Chạy focused tests → impacted regression → UI acceptance theo workflow. Không tuyên bố PASS nếu chưa có output/kiểm tra thực tế.

**Gate bắt buộc cho PR HTML → Blade:** xác nhận route UI và thư mục vật lý asset **không trùng nhau**, kiểm tra cả hai dạng URL và có test/acceptance bảo vệ đường dẫn asset.

## 3. Thư viện dữ liệu JSON mẫu trên server

### Mục tiêu và hành vi

- Khách được xem danh sách, chọn và nạp bộ JSON mẫu từ server vào localStorage; mỗi browser profile có dữ liệu riêng.
- CRUD trong trình duyệt **không** cập nhật dữ liệu mẫu trên server.
- Có nút xuất JSON về máy; có nhập/khôi phục JSON với xác nhận trước khi thay dữ liệu.
- **Ghi bộ JSON mới lên server chỉ được phép sau khi server xác thực mã quản trị**; không xem modal `YES` là xác thực.
- Không tự động đồng bộ, xóa hoặc ghi đè file server.
- Nếu không phù hợp với Demo hoặc chưa được phê duyệt, chỉ đề xuất, không tự triển khai.

### Storage và API

Mỗi Demo có thư mục riêng trên Laravel local disk:

```text
storage/app/demo-datasets/warehouse-inventory/
storage/app/demo-datasets/sales-employees/
storage/app/demo-datasets/{demo-slug}/
```

Không để JSON private trong public webroot. Routes tham khảo:

```http
GET  /demo/{demo-slug}/datasets
GET  /demo/{demo-slug}/datasets/{dataset}
POST /demo/{demo-slug}/datasets
```

GET chỉ đọc, có validation dataset ID/path; POST có JSON schema validation theo nghiệp vụ Demo, xác thực mã quản trị, CSRF (nếu dùng web/session), rate limiting, giới hạn kích thước, số lượng file và không cho path traversal.

Hai Demo tham chiếu đã triển khai có giới hạn **10 MiB JSON, tối đa 100 file, 5 lần ghi/phút**; phải audit code/config mới nhất trước khi tái sử dụng, không tự ý đổi các giới hạn.

### Mã quản trị và UX

Hai Demo hiện tại sử dụng `WAREHOUSE_DEMO_BACKUP_CODE` qua `config('warehouse_demo.backup_code')` với yêu cầu mã tối thiểu 16 ký tự. Đây là **cơ chế hiện hành**, không phải giấy phép hardcode hoặc tự sửa `.env`. Nếu không cấu hình, API POST phải từ chối ghi. Không đưa mã vào Blade, JS, localStorage, log hoặc response. Khi thêm Demo, đánh giá tái sử dụng service/cấu hình chung thay vì duplicate controller logic; mọi refactor phải có regression.

Settings nên có: danh sách JSON, thông tin bộ dữ liệu, nút nạp, xác nhận thay localStorage, trạng thái lỗi/loading; tải JSON về máy; tên bản sao lưu; modal nhập chính xác `YES` và mã quản trị; thông báo **phân biệt** kết quả tải về máy và lưu server. Tránh gửi dữ liệu nhạy cảm không cần thiết lên server.

### Focused/security tests

- GET danh sách/nạp JSON hợp lệ, ẩn JSON hỏng.
- Chặn path traversal, ID không hợp lệ.
- Chặn ghi khi thiếu/sai mã hoặc chưa cấu hình.
- Ghi với mã đúng; schema validation, file/size limits, throttle, CSRF.
- Không ảnh hưởng localStorage nếu nạp lỗi.
- Nạp/khôi phục có xác nhận và không phá vỡ CRUD.

## 4. Bốn theme màu trong Settings

Các Demo mới nên **đề xuất** lựa chọn 4 theme, triển khai khi được phê duyệt:

| Theme | Accent tham khảo |
|---|---|
| Ruby | `#C62828` |
| Ocean | `#087E9B` |
| Emerald | `#16835D` |
| Indigo | `#4F46E5` |

- Mặc định của Demo cũ phải được giữ nguyên. Sales mặc định Ruby; Warehouse mặc định Indigo. Audit CSS thực tế để xác nhận token/palette, không ép đồng bộ phá UI.
- Thêm nhóm thẻ màu trong Settings, trạng thái selected rõ ràng, keyboard focus, `aria-pressed` và swatch.
- Dùng scoped CSS variables/design tokens cho sidebar, logo, active nav, nút chính và accent. Giữ nguyên semantic colors (nhập/xuất, thành công, lỗi, cảnh báo) nếu đổi gây hiểu nhầm.
- Biểu đồ dùng palette JS phải cập nhật accent phù hợp.
- Lưu theme bằng **khóa localStorage riêng cho mỗi Demo**, không trộn vào JSON nghiệp vụ:
  - Sales: `sales_employees_theme_v1`.
  - Warehouse: `warehouse_inventory_theme_v1`.
- Refresh giữ theme; nạp JSON mẫu, reset dữ liệu hoặc khôi phục JSON **không reset theme**. Có fallback nếu localStorage không khả dụng.
- Responsive 4 cột desktop / 2 tablet / 1 điện thoại khi phù hợp chiều rộng thực tế.
- Xử lý cache CSS/JS bằng asset versioning hoặc cơ chế build hiện có. Với file tĩnh tồn tại, có thể dùng `?v={{ filemtime(public_path('demo/.../css/app.css')) }}` trong Blade; tránh lỗi file không tồn tại.
- Không copy nguyên JS/CSS Demo khác nếu có dependency hoặc cấu trúc khác.

## 5. Sidebar Menu Builder trong trang Cài đặt (khi được phê duyệt)

Áp dụng như một **tính năng tái sử dụng có điều kiện** cho các Standalone Demo có sidebar. Khi chuyển HTML mới sang Blade, phải audit sidebar, Settings, điều hướng, DOM và cơ chế lưu hiện tại trước; đề xuất bổ sung chức năng này trong kế hoạch và chỉ triển khai khi người dùng đồng ý. Không tự động áp dụng cho mọi route hoặc Module.

### Vị trí và khả năng chỉnh sửa

- Đặt thẻ **Sidebar menu** và nút **Chỉnh sửa menu** ngay trong màn hình Cài đặt của demo (hash/route theo demo thực tế); không đặt nút chỉnh sửa thường trực trên header toàn hệ thống.
- Mở modal editor responsive, có vùng cuộn cho nội dung và thanh hành động dễ truy cập trên desktop/tablet/iPhone.
- **Header sidebar**: tên ứng dụng, dòng mô tả, icon hợp lệ, liên kết logo.
- **Footer sidebar**: dòng ghi chú và liên kết tùy chọn; không hardcode thương hiệu, dòng `Đang dùng dữ liệu mẫu` hoặc nút `Lưu trong trình duyệt` vào footer. Nếu demo có trạng thái dữ liệu quan trọng, hiển thị tại khu vực dữ liệu phù hợp thay vì footer tùy chỉnh.
- **Nhóm menu**: thêm, đổi tên, sắp xếp, xóa nhóm có xác nhận. **Mục menu**: thêm, sửa tên/icon/href, nhân bản, xóa và kéo thả sắp xếp trong/giữa nhóm; có thể hỗ trợ nút lên/xuống để dùng bằng bàn phím/mobile.
- Bảo toàn route, màn hình và quyền truy cập thực tế. Xóa mục khỏi sidebar **không** xóa route, chức năng hay dữ liệu nghiệp vụ. Chặn trùng ID, icon không hợp lệ, URL nguy hiểm và `javascript:`; liên kết nội bộ phải thuộc các view được phép, liên kết ngoài chỉ HTTPS.

### Mô hình cấu hình JSON và hành vi nút

Lưu **cả header, footer và groups** trong một cấu hình versioned, ví dụ:

```json
{
  "version": 1,
  "header": {"title": "Tên demo", "subtitle": "Mô tả", "icon": "layout-dashboard", "href": "#dashboard"},
  "footer": {"credit": "Đơn vị sử dụng", "href": ""},
  "groups": [
    {"title": "Tổng quan", "items": [
      {"id": "dashboard", "label": "Dashboard", "icon": "layout-dashboard", "href": "#dashboard"}
    ]}
  ]
}
```

Đây là **schema tham khảo**, không áp đặt JSON v1 mới cho demo đang hoạt động: một số demo cũ có thuộc tính `footer.showSave` phục vụ tương thích. Khi tái sử dụng, phải kiểm tra schema/validator thực tế, chuẩn hóa dữ liệu cũ và giữ khả năng import trước khi thay đổi contract.

- **Cập nhật giao diện**: validate, áp dụng ngay header/footer/menu và hiển thị modal thành công. Nếu yêu cầu giữ sau F5 trên thiết bị hiện tại, lưu vào khóa localStorage riêng theo demo. **Đóng** mà chưa cập nhật không áp dụng các thay đổi chưa xác nhận.
- **Lưu lên server**: API xác thực mới ghi cấu hình sidebar đang sử dụng; modal nhập mã quản trị nhỏ gọn, `type=password`, có ẩn/hiện mã và lỗi ngay dưới input. Sai mã (403) cho nhập lại; 419/422/429/503 và lỗi mạng có thông báo riêng, không hiển thị bí mật. Không dùng `window.prompt` để nhập mã.
- **Thư viện file JSON trên server**: lưu preset có tên, liệt kê, nhập, xuất, cập nhật và xóa file được chọn. Nếu trùng tên, **không tự tạo file mới**; phải chọn đúng file và xác nhận ghi đè. Xóa có xác nhận và xác thực; chỉ xóa preset, không xóa sidebar đang áp dụng hay dữ liệu nghiệp vụ. Modal thông báo kết quả thành công/thất bại.
- **Nhập từ server**: sau xác nhận, áp dụng ngay cấu hình nhập vào sidebar và lưu trạng thái browser-local nếu có yêu cầu giữ sau F5; **không** tự ghi đè cấu hình server đang hoạt động. **Khôi phục mặc định**: có xác nhận và thông báo, không tự xóa file server.
- Mỗi demo dùng namespace riêng cho localStorage, thư mục JSON private, route API, controller và schema. Ưu tiên tái sử dụng component/service đã được audit; không sao chép nguyên file JS từ demo khác nếu phụ thuộc `App`, DOM, hash hoặc biến toàn cục khác.

### Security, tests và UI acceptance

- JSON private trên Laravel local disk; API ghi/cập nhật/xóa yêu cầu mã quản trị được xác thực **ở server**, CSRF khi dùng session, throttle, giới hạn kích thước/số file, ID/path validation và không cho path traversal. Không tự thay `WAREHOUSE_DEMO_BACKUP_CODE`, giảm độ dài mã hoặc sửa `.env`.
- Focused tests: cấu trúc header/footer/groups lưu và đọc lại; trường tùy chọn rỗng qua `ConvertEmptyStringsToNull`; sai/thiếu mã, cấu hình sai, URL nguy hiểm; trùng tên trả conflict, ghi đè đúng ID, xóa đúng file, không ảnh hưởng active menu; backward compatibility với JSON cũ.
- JavaScript syntax check (ví dụ `node --check`) và browser regression: editor xuất hiện trong Cài đặt, không có lỗi console, cập nhật → đóng → F5, nhập từ server áp dụng ngay, lưu/xóa file và modal lỗi mã, kéo thả/nhân bản/xóa nhóm, desktop/tablet/iPhone, focus/scroll và các menu điều hướng.
- Chỉ ghi **TEST PASS** khi có output thật và **UI PASS** khi người dùng xác nhận. Không merge/deploy khi chưa đạt gate theo workflow.

Tham khảo triển khai hiện có: demo `/demo/quan-ly-cong-no#caidat` trên feature branch `feat/debt-management-blade-20261027`. **Đọc lại source và trạng thái merge mới nhất** trước khi reuse; không giả định branch này đã nằm trên `main`.

## 6.1. Route integration gate — bài học từ Hotel Uniform (2026-10-29)

Khi thêm route UI/API cho Demo, **phải audit file PHP thực tế sau khi ghi**, không chỉ xem diff hoặc chạy test giao diện:

1. `routes/web.php` phải bắt đầu bằng `<?php` (cho phép BOM/whitespace nếu source đã có nhưng không được chèn text route trước PHP). Không thêm route bằng cách prepend text trước opening tag. Dùng import/controller và vị trí nhóm route nhất quán.
2. Khi tích hợp `SharedSidebarMenuController`, kiểm tra **cả hai lớp allowlist**: controller `DEMOS`/mapping và toàn bộ route `->where('demo', ...)` của nhóm `/demo/{demo}/menu`. Đối chiếu GET/POST/PUT/DELETE, presets và default-preset; không chỉ thêm `GET /demo/{slug}`.
3. Chạy `php -l routes/web.php`, `php artisan route:list --path=demo/{demo-slug}`, rồi focused tests. Nếu route khai báo bị in ra stdout hoặc GET trả 404, dừng kiểm tra PHP opening tag, route constraint, route cache, middleware và thứ tự khai báo; không tự ý xóa route cache trên production.
4. Focused tests cần assert `GET /demo/{slug}` và tối thiểu `GET /demo/{slug}/menu`, `GET /demo/{slug}/menu/presets` nếu có editor; bổ sung contract cho các thao tác ghi được duyệt và xác thực mã quản trị. Test route-list và HTTP đều cần thiết; không thay thế lẫn nhau.
5. Khi sửa JS dùng chung, kiểm tra demo mới **và** demo cũ đang dùng chung builder. Không sửa UI chỉ để làm test PASS.

**Sự cố tham chiếu:** Hotel Uniform từng có route đặt trước `<?php` gây in source/404; sau đó menu 404 vì `where('demo', ...)` thiếu slug mới. Focused test sau sửa: 2 PASS/15 assertions; impacted regression sidebar: 22 PASS/114 assertions theo output máy Admin. Các kết quả này chỉ áp dụng cho phiên bản được kiểm thử, không phải chứng nhận tự động cho demo tiếp theo.

### Gate bảo mật và UX tích hợp

- JSON server: không chỉ test route UI. Kiểm tra unauthorized POST, mã sai/thiếu/chưa cấu hình, CSRF, throttle, validation schema, giới hạn file, path traversal, dữ liệu lỗi và xác nhận trước khi thay localStorage. Nếu chưa có các test này, đánh dấu **security tests pending**, không coi route smoke test là bằng chứng đủ.
- Theme: kiểm tra CSS variables thật trong `app.css`, palette JS/biểu đồ, lựa chọn 4 màu và mặc định, reload, localStorage key riêng, trạng thái focus/aria và không reset khi import JSON.
- Menu: dùng adapter của shared builder đã audit, không nhân bản editor; test cả cấu hình header/footer/groups, lưu/nạp JSON, active nav, badges, modal, keyboard/mobile và trạng thái sau F5.
- Các hàm HTML gốc như `saveToFile()`, `TEMPLATE=document.documentElement.outerHTML`, inline `onclick`, script nhúng và DOM động cần đối chiếu sau khi tách asset; không giả định `window.fn = ...` sẽ thay thế mọi binding. Phải kiểm tra runtime bằng browser.
- Trước PR: focused PASS → impacted regression PASS khi sửa shared component → UI PASS do người dùng xác nhận → tracked tree sạch → review PR → chỉ merge khi người dùng đồng ý.

## 6. Phase 2 — Implementation

1. Feature branch từ `main` mới nhất, không dùng worktree.
2. Controller + route chính; tách Blade layout/pages/partials, CSS/JS.
3. Bảo toàn DOM ID/event và localStorage; tránh phá JS tạo DOM động.
4. Bổ sung import/export JSON, API dataset, 4 theme và Sidebar Menu Builder **chỉ theo phạm vi duyệt**.
5. Bổ sung asset versioning và focused tests (route, Blade, JS contract, dataset security).
6. Cập nhật `docs/demos/{DEMO_NAME}.md` với kiến trúc, API, data schema, tests, UI checklist.
7. Commit/push theo batch; không sửa `.env`, database Production, file untracked.

## 7. Phase 3 — Git Pull / Test

Cung cấp lệnh theo branch/test thực tế (không chạy placeholder):

```bash
git fetch origin
git switch <feature-branch>
git pull --ff-only
php artisan test tests/Feature/<DemoTest>.php
```

Nếu branch local chưa tồn tại, hướng dẫn `git switch --track origin/<feature-branch>`; nếu đã tồn tại, dùng `git switch <feature-branch>`. Chỉ ghi TEST PASS sau output. Chạy impacted regression **sau** focused PASS; không chạy full suite nếu không cần.

## 8. Phase 4 — UI Regression

Kiểm tra desktop/tablet/iPhone; sidebar, điều hướng, modal, CRUD, localStorage, backup/restore JSON, thư viện server, xác thực mã quản trị, Excel/in báo cáo nếu có, responsive và console errors. Khi có Sidebar Menu Builder, áp dụng thêm toàn bộ acceptance ở mục 5.

Riêng theme: đổi cả bốn, refresh, sidebar/nút/biểu đồ, khôi phục JSON/nạp server không làm mất theme. Chỉ ghi **UI PASS** khi người dùng xác nhận.

## 9. Phase 5 — PR / Merge

Kiểm tra focused + impacted tests, `git diff --check`, tracked tree sạch, handoff; tạo/review PR. Nếu `main` tiến lên, đánh giá đồng bộ branch và test lại phần ảnh hưởng. **Chỉ merge khi các gate đạt và người dùng đồng ý**. Xác minh merge SHA và cung cấp lệnh đồng bộ `main`. Không tự deploy Production.

## 10. Tài liệu tham chiếu

- `docs/demos/SALES_EMPLOYEES.md`: `/demo/doanh-so-nhan-vien#settings` — JSON library, protected server backup, Ruby/Ocean/Emerald/Indigo.
- `docs/demos/WAREHOUSE_INVENTORY.md`: `/demo/quan-ly-xuat-nhap-ton#settings` — JSON library, protected server backup, Indigo/Ruby/Ocean/Emerald.

Đọc code **mới nhất** của từng Demo trước khi sao chép ý tưởng; tài liệu có thể ghi lại lịch sử triển khai, không phải mọi câu mô tả cũ đều phản ánh trạng thái mới nhất.

## 11. Checklist kết thúc

- [ ] HTML đã audit; kiến trúc và phạm vi được duyệt.
- [ ] Route/controller/Blade/assets độc lập; không phá hành vi gốc.
- [ ] Không có thư mục `public/demo/{demo-slug}/` xung đột với route UI; HTTP không-slash/slash đã kiểm tra; CSS/JS load đúng; không xóa file untracked.
- [ ] localStorage, JSON import/export hoạt động.
- [ ] Dataset server bảo mật, giới hạn và validation đầy đủ nếu áp dụng.
- [ ] 4 theme, persistence, responsive và cache busting nếu áp dụng.
- [ ] Sidebar Menu Builder: Settings, header/footer, CRUD nhóm/mục, JSON server, modal xác thực, import áp dụng ngay và regression nếu áp dụng.
- [ ] Focused tests PASS; impacted regression PASS khi cần.
- [ ] UI PASS do người dùng xác nhận.
- [ ] Tracked tree sạch, handoff cập nhật, PR review.
- [ ] Merge chỉ khi được phép; main sync và xác nhận SHA.
- [ ] Không thao tác Production trái quy trình.

**Audit → Plan → Approval → Implement → Pull/Test → Regression → UI PASS → PR → Approved Merge → Main Sync.**

## Shared Sidebar Menu Builder — Component chuẩn dùng chung (2026-10-28)

**Trạng thái:** Đã tích hợp trên feature branch `feat/shared-demo-sidebar-menu-20261028`. Người dùng đã xác nhận focused tests và UI PASS sau batch sửa Footer; **chưa đồng nghĩa đã merge vào `main`**. Khi áp dụng cho demo mới phải đối chiếu code và trạng thái GitHub mới nhất.

### Các route đang sử dụng

| Demo | Trang thiết lập | Adapter / lưu trữ |
| --- | --- | --- |
| Quản lý công nợ | `/demo/quan-ly-cong-no#caidat` | `DebtDemoMenuBridge`, giữ API và JSON/localStorage cũ |
| Doanh số nhân viên | `/demo/doanh-so-nhan-vien#settings` | DOM `#sb`, `#nav`, `#page-settings`, namespace riêng |
| Quản lý xuất nhập tồn | `/demo/quan-ly-xuat-nhap-ton#settings` | DOM `#sb`, `#nav`, `#page-settings`, namespace riêng |

### Source of truth / cấu trúc

- Blade component: `resources/views/components/demo/sidebar-menu-builder.blade.php`, dùng `<x-demo.sidebar-menu-builder slug="..." />` khi phù hợp với lifecycle của demo.
- Giao diện và hành vi editor chung: `public/demo/shared/sidebar-menu/menu-builder.js` và `menu-builder.css`. Không copy/fork editor riêng cho từng demo.
- API của Sales/Warehouse: `app/Http/Controllers/Demo/SharedSidebarMenuController.php`, `/demo/{demo}/menu` và `/presets`, lưu JSON riêng trong `storage/app` qua Laravel local disk.
- Debt Management: `public/demo-assets/quan-ly-cong-no/js/menu-builder.js` cung cấp `DebtDemoMenuBridge` và vẫn dùng `DebtManagementMenuController`; không xóa, đổi schema, khóa localStorage hoặc di chuyển JSON cũ nếu chưa có migration được duyệt.
- Handoff triển khai: `docs/demos/SHARED_SIDEBAR_MENU.md`. Audit adapter trước khi dùng cho demo mới, không giả định mọi sidebar có DOM giống nhau.

### Quy chuẩn UI/UX đã thống nhất

- Đặt nút **Chỉnh sửa menu** trong màn hình Cài đặt, không thêm nút toàn cục.
- Modal **căn giữa**, chiều rộng vừa phải trên desktop (hiện tối đa 1080px), responsive tablet và toàn màn hình trên mobile hẹp.
- Tabpanel gồm **Menu (mặc định)**, **Header**, **Footer**, **Thư viện JSON**. Chuyển tab không làm mất dữ liệu bản nháp.
- Chỉ vùng nội dung tab cuộn; thanh hành động **cố định ở đáy modal và căn giữa**. Giữ keyboard focus và trạng thái tab dễ nhận biết.
- Tab Menu chỉnh nhóm/mục, thứ tự, nhãn, icon và href. Tab Header chỉnh title, subtitle, icon và href.
- Tab Footer chỉ có hai ô: **Tên công ty** (schema `footer.credit`) và **Đường liên kết** (`footer.href`). Không hiển thị `footer.showSave` trong editor; thuộc tính này có thể tiếp tục tồn tại trong JSON để tương thích demo cũ.
- Footer sidebar phải có vùng hiển thị thực tế: Sales/Warehouse dùng `.sbfoot`; Warehouse đặt **bên dưới thẻ thống kê tồn kho**, không thay thế `.sbcard`. Debt sử dụng `.sidebar .credit` qua bridge. Khi có tên công ty và href HTTPS/hash hợp lệ, hiển thị liên kết; khi href rỗng, hiển thị văn bản; khi credit rỗng, không tạo nội dung giả.
- Tab Thư viện JSON giữ thao tác liệt kê, lưu, nạp, xóa preset; nút Cập nhật giao diện, Mặc định, Nhập/Xuất JSON và Lưu lên server nằm ở footer modal.

### Dữ liệu, bảo mật và tương thích

- Giữ cấu hình versioned `{version,header,footer,groups}`; `footer.credit` và `footer.href` là chuỗi, `footer.showSave` giữ trong schema runtime hiện tại để tương thích.
- Laravel `ConvertEmptyStringsToNull` có thể chuyển href rỗng thành null: chỉ chuẩn hóa **href tùy chọn** về `""` trước validation; không nới lỏng kiểm tra URL nguy hiểm.
- API ghi cần mã quản trị xác thực phía server, CSRF, throttle, giới hạn JSON/ID/path, URL HTTPS hoặc hash hợp lệ; không lưu bí mật vào trình duyệt hay hardcode.
- Không ảnh hưởng menu điều hướng, badge động, dữ liệu nghiệp vụ, theme, backup/restore hoặc các demo khác. Tránh nhân bản DOM làm mất sự kiện và badge live; kiểm tra kỹ sau mỗi thay đổi adapter.
- Mỗi demo giữ cấu hình server và localStorage riêng; **không** dùng chung dữ liệu menu chỉ vì dùng chung component.

### Acceptance khi tái sử dụng hoặc sửa component

1. Chạy `node --check` cho JS chung và JS adapter bị ảnh hưởng.
2. Chạy `php artisan test tests/Feature/SharedDemoSidebarMenuTest.php` trước, rồi focused/impacted regression cho từng demo liên quan.
3. Kiểm tra UI cả ba route trên desktop/tablet/iPhone: mở modal, tab mặc định Menu, chuyển tab, chỉnh Header/Footer, cập nhật, F5, lưu/nạp/xóa JSON server, liên kết Footer, điều hướng sidebar và badge động.
4. Không tuyên bố TEST PASS chỉ dựa vào commit; UI PASS phải do người dùng xác nhận cho **phạm vi thay đổi hiện tại**. Chỉ tạo/review PR và merge sau khi các gate đạt và được người dùng đồng ý.

**Quy tắc cho HTML Demo mới:** audit trước; chỉ tích hợp Sidebar Menu Builder khi có sidebar, có màn hình Settings phù hợp và được người dùng phê duyệt. Ưu tiên adapter mới dùng chung editor thay vì sao chép logic từ demo khác.
