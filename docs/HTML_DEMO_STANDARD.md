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
public/demo/{demo-slug}/css/app.css
public/demo/{demo-slug}/js/app.js
docs/demos/{DEMO_NAME}.md
```

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

## 5. Phase 2 — Implementation

1. Feature branch từ `main` mới nhất, không dùng worktree.
2. Controller + route chính; tách Blade layout/pages/partials, CSS/JS.
3. Bảo toàn DOM ID/event và localStorage; tránh phá JS tạo DOM động.
4. Bổ sung import/export JSON, API dataset và 4 theme **chỉ theo phạm vi duyệt**.
5. Bổ sung asset versioning và focused tests (route, Blade, JS contract, dataset security).
6. Cập nhật `docs/demos/{DEMO_NAME}.md` với kiến trúc, API, data schema, tests, UI checklist.
7. Commit/push theo batch; không sửa `.env`, database Production, file untracked.

## 6. Phase 3 — Git Pull / Test

Cung cấp lệnh theo branch/test thực tế (không chạy placeholder):

```bash
git fetch origin
git switch <feature-branch>
git pull --ff-only
php artisan test tests/Feature/<DemoTest>.php
```

Nếu branch local chưa tồn tại, hướng dẫn `git switch --track origin/<feature-branch>`; nếu đã tồn tại, dùng `git switch <feature-branch>`. Chỉ ghi TEST PASS sau output. Chạy impacted regression **sau** focused PASS; không chạy full suite nếu không cần.

## 7. Phase 4 — UI Regression

Kiểm tra desktop/tablet/iPhone; sidebar, điều hướng, modal, CRUD, localStorage, backup/restore JSON, thư viện server, xác thực mã quản trị, Excel/in báo cáo nếu có, responsive và console errors.

Riêng theme: đổi cả bốn, refresh, sidebar/nút/biểu đồ, khôi phục JSON/nạp server không làm mất theme. Chỉ ghi **UI PASS** khi người dùng xác nhận.

## 8. Phase 5 — PR / Merge

Kiểm tra focused + impacted tests, `git diff --check`, tracked tree sạch, handoff; tạo/review PR. Nếu `main` tiến lên, đánh giá đồng bộ branch và test lại phần ảnh hưởng. **Chỉ merge khi các gate đạt và người dùng đồng ý**. Xác minh merge SHA và cung cấp lệnh đồng bộ `main`. Không tự deploy Production.

## 9. Tài liệu tham chiếu

- `docs/demos/SALES_EMPLOYEES.md`: `/demo/doanh-so-nhan-vien#settings` — JSON library, protected server backup, Ruby/Ocean/Emerald/Indigo.
- `docs/demos/WAREHOUSE_INVENTORY.md`: `/demo/quan-ly-xuat-nhap-ton#settings` — JSON library, protected server backup, Indigo/Ruby/Ocean/Emerald.

Đọc code **mới nhất** của từng Demo trước khi sao chép ý tưởng; tài liệu có thể ghi lại lịch sử triển khai, không phải mọi câu mô tả cũ đều phản ánh trạng thái mới nhất.

## 10. Checklist kết thúc

- [ ] HTML đã audit; kiến trúc và phạm vi được duyệt.
- [ ] Route/controller/Blade/assets độc lập; không phá hành vi gốc.
- [ ] localStorage, JSON import/export hoạt động.
- [ ] Dataset server bảo mật, giới hạn và validation đầy đủ nếu áp dụng.
- [ ] 4 theme, persistence, responsive và cache busting nếu áp dụng.
- [ ] Focused tests PASS; impacted regression PASS khi cần.
- [ ] UI PASS do người dùng xác nhận.
- [ ] Tracked tree sạch, handoff cập nhật, PR review.
- [ ] Merge chỉ khi được phép; main sync và xác nhận SHA.
- [ ] Không thao tác Production trái quy trình.

**Audit → Plan → Approval → Implement → Pull/Test → Regression → UI PASS → PR → Approved Merge → Main Sync.**
