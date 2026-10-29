# HTML Demo → Laravel 12 Blade Workflow

## 1. Mục tiêu và điều kiện kích hoạt

Áp dụng khi người dùng cung cấp file HTML, URL HTML, link GitHub chứa HTML, hoặc yêu cầu chuyển HTML thành demo Laravel 12. **Việc gửi link chỉ kích hoạt tiếp nhận/audit, không mặc nhiên cấp quyền sửa code.** Xác định rõ yêu cầu tham khảo hay triển khai; nếu không rõ, hỏi trước khi thay đổi.

Tài liệu này là quy trình thao tác; **`docs/HTML_DEMO_STANDARD.md` là tiêu chuẩn kỹ thuật chi tiết bắt buộc**. Đọc cả hai từ `main` mới nhất trước khi audit, cùng `docs/GITHUB_COLLABORATION_WORKFLOW.md`. Khi có mâu thuẫn, đối chiếu workflow mới nhất và xin ý kiến người dùng. Công việc liên quan production phải đọc thêm `docs/PRODUCTION_DEBUG_PROMPT.md` từ `main` trước mọi phân tích/thao tác production.

## 2. Kiến trúc mặc định

- Laravel 12, PHP 8.3; demo độc lập, **không tạo Module** trừ khi được yêu cầu.
- **Một GET route chính cho giao diện mỗi demo**, một Controller riêng; API GET/POST phụ trợ cho JSON server/menu được phép **khi đã phê duyệt**, không coi là route UI bổ sung.
- Layout Blade riêng; mỗi chức năng/màn hình có Blade riêng; sidebar, header, modal tách thành partial.
- CSS/JS là file riêng trong **`public/demo-assets/<demo-slug>/`** (không đặt trong `public/demo/<demo-slug>/` trùng route UI, tránh Nginx 301/403); giữ nguyên DOM ID, CSS class, event handler, thứ tự tải script và dependency.
- Mặc định không dùng database/backend persistence; lưu demo vào localStorage với key riêng theo demo, tránh xung đột.
- Sao lưu/khôi phục JSON khi phù hợp, có xác nhận trước khi ghi đè; dữ liệu sao lưu phải tương thích với cấu trúc state thực tế.
- Không ảnh hưởng Module, demo hoặc route đang hoạt động; mobile-first, responsive desktop/tablet/iPhone.

Cấu trúc gợi ý:

```text
app/Http/Controllers/Demo/{DemoName}Controller.php
resources/views/demo/{demo-slug}/
  index.blade.php
  layouts/app.blade.php
  pages/*.blade.php
  partials/*.blade.php
public/demo-assets/{demo-slug}/
  css/app.css
  js/app.js
docs/demos/{DEMO_NAME}.md
tests/Feature/{DemoName}Test.php
```

## 3. Phase 1 — Audit read-only

1. Đọc `docs/GITHUB_COLLABORATION_WORKFLOW.md` từ `main` mới nhất và kiểm tra trạng thái repository/branches.
2. Kiểm tra route, controller, views, public assets và các demo hiện có để tránh trùng tên, đường dẫn hoặc chức năng.
3. Đọc **toàn bộ** HTML đầu vào, CSS, JavaScript, CDN, dữ liệu mẫu, lưu trữ, xuất/nhập file và dependency.
4. Lập danh sách màn hình/chức năng, CRUD, báo cáo, in, Excel, import/export JSON, localStorage và responsive.
5. Phân tích JavaScript sinh DOM động, script inline, global state, `document.documentElement.outerHTML`, lưu HTML offline, path tài nguyên, inline handlers và Blade escaping. **Không chỉ tách file bằng regex rồi mặc định hành vi tương đương.**
6. Nếu URL không truy cập được hoặc nội dung thiếu, yêu cầu file HTML gốc; không suy đoán.
7. Trình bày kiến trúc, phạm vi ảnh hưởng, rủi ro và kế hoạch; **chờ người dùng phê duyệt nếu là thay đổi lớn**.

## 4. Phase 2 — Implementation

1. Tạo feature branch từ `main` mới nhất; không sử dụng Git worktree. Nếu demo nền chưa nằm trên `main`, báo rõ dependency và thống nhất cách xử lý trước khi tạo branch phụ thuộc.
2. Tạo đúng một route GET giao diện và Controller tương ứng; API phụ trợ chỉ theo phạm vi duyệt. Kiểm tra `<?php` đầu `routes/web.php`, `php -l`, và toàn bộ `where('demo', ...)` khi tích hợp shared sidebar; không thay đổi route demo khác.
3. Tách layout, pages, partials, CSS, JS; bảo toàn hành vi hiện tại. Với màn hình tạo động bằng JS, có thể dùng Blade mount point riêng nhưng phải ghi rõ trong handoff.
4. Giữ localStorage key/schema khi nâng cấp demo hiện có, hoặc cung cấp migration dữ liệu có kiểm soát; không làm mất dữ liệu trình duyệt.
5. Đảm bảo các tài nguyên chạy với đường dẫn Laravel; kiểm tra trình tự tải JS và dữ liệu JSON nhúng.
6. Viết focused tests cho route, controller, view, các section và assets; với shared menu phải test `/menu` và `/menu/presets`, với JSON server phải test xác thực/validation/path traversal/CSRF/throttle; thêm kiểm tra chức năng liên quan khi có thể.
7. Cập nhật `docs/demos/{DEMO_NAME}.md` với branch, scope, architecture, tests, UI gate, known issues, rollback/next steps.
8. Commit từng batch có kiểm soát lên GitHub; không xóa file untracked, sửa `.env`, database hoặc production.

## 5. Phase 3 — Checkpoint Git Pull / Test

Dừng để người dùng đồng bộ branch và chạy focused tests. Cung cấp lệnh theo branch/test thực tế:

```bash
git fetch origin
git switch --track origin/<feature-branch>   # hoặc git switch nếu đã tồn tại
git pull --ff-only
php -l routes/web.php
php artisan route:list --path=demo/<demo-slug>
php artisan test tests/Feature/<DemoName>Test.php
git status --short
```

- **Không tuyên bố TEST PASS khi chưa có output thực tế.**
- Focused tests PASS trước module/impacted regression khi cần. Nếu thay đổi shared sidebar, chạy `SharedDemoSidebarMenuTest.php` và regression demo cũ liên quan; không chạy full suite theo mặc định.
- Khi FAIL, dừng, phân tích root cause, sửa đúng phạm vi rồi yêu cầu chạy lại.

## 6. Phase 4 — UI Regression

Người dùng kiểm tra và xác nhận UI PASS trên desktop, tablet và iPhone, bao gồm:

- Điều hướng từng màn hình, sidebar, header, modal, responsive, console errors.
- CRUD, tìm kiếm/lọc/phân trang nếu có; không thay đổi nghiệp vụ chỉ để tests PASS.
- localStorage qua reload; sao lưu JSON, khôi phục JSON và cảnh báo ghi đè nếu có.
- Xuất Excel, in báo cáo, nhập Excel, tải file nếu có.
- Kiểm tra dữ liệu cũ vẫn dùng được khi refactor demo đang hoạt động.

**Chỉ ghi UI PASS khi người dùng xác nhận cho đúng phiên bản/branch đang kiểm tra.** UI PASS của phiên bản trước không tự động chuyển sang phiên bản refactor.

## 7. Phase 5 — PR / Merge / Handoff

1. Kiểm tra focused tests, impacted regression và UI PASS; kiểm tra Git tracked working tree sạch.
2. Cập nhật handoff tại `docs/demos/{DEMO_NAME}.md` (không tạo Module handoff giả).
3. Tạo và review PR theo `docs/GITHUB_COLLABORATION_WORKFLOW.md`.
4. **Chỉ merge vào `main` khi người dùng cho phép rõ ràng.**
5. Sau merge, đồng bộ `main`, xác nhận merge commit. Không tự ý deploy production.

## 8. Quy tắc an toàn

- Không ghi đè/xóa demo đang hoạt động, không tạo route ngoài phạm vi, không sửa database production hoặc `.env`.
- Không xóa hoặc ghi đè file untracked, không dùng Git worktree.
- Không tự ý restart/rebuild/migrate/deploy.
- Không thay đổi UI để lách contract tests.
- Nếu HTML chỉ để tham khảo, dừng ở phân tích và đề xuất.
- Khi link không truy cập được, yêu cầu file thay vì giả định nội dung.

## 9. Mẫu yêu cầu ngắn cho chat mới

> Áp dụng `docs/HTML_DEMO_STANDARD.md`, `docs/HTML_DEMO_BLADE_WORKFLOW.md` và `docs/GITHUB_COLLABORATION_WORKFLOW.md` từ `main` mới nhất cho file hoặc URL HTML tôi gửi. Audit read-only trước, trình bày kiến trúc và kế hoạch, chỉ triển khai sau khi tôi phê duyệt. Dừng tại checkpoint Git Pull / Test. Không merge hoặc deploy nếu chưa được phép.
