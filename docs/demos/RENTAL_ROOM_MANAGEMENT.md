# Quản lý phòng trọ — HTML → Laravel Blade

## Nguồn và kiến trúc

- Nguồn: file đính kèm `Quản lý phòng trọ.html`, 202 KB; bảo toàn CSS, JavaScript nghiệp vụ và DOM ID.
- Route UI duy nhất: `GET /demo/quan-ly-phong-tro`.
- Controller: `app/Http/Controllers/Demo/RentalRoomManagementController.php`.
- View: `resources/views/demo/quan-ly-phong-tro/`, layout riêng, sidebar/header partial, 9 pages.
- Assets: `public/demo-assets/quan-ly-phong-tro/`; tránh xung đột Nginx 301/403.
- Dữ liệu nghiệp vụ giữ localStorage của HTML gốc, khóa có hậu tố `location.pathname`.
- Thêm 4 themes Ruby, Ocean, Emerald, Indigo bên cạnh màu nguyên bản; lưu riêng `rental_room_theme_v1`.
- Chỉnh sửa menu, nhóm, thứ tự, link, preset và mặc định bằng SharedSidebarMenuController; namespace `rental-room`.
- JSON backup/restore trình duyệt; server snapshots lưu private tại `storage/app/private/demo-datasets/rental-room` qua Storage local.
- API ghi menu/snapshot dùng mã quản trị hiện hữu `config('warehouse_demo.backup_code')` (không hardcode secret), CSRF, throttle, xác nhận ghi đè/khôi phục.
- Tách source theo Blade mà không thêm Module, migration hay DB.

## Regression gates

1. `php artisan test tests/Feature/RentalRoomManagementTest.php`
2. Sau focused PASS, chạy shared menu regression và regression demo đồng phục khách sạn.
3. Kiểm tra desktop/tablet/iPhone, 9 màn hình, CRUD, ghi điện nước, lập hóa đơn, thu tiền, báo cáo, xuất Excel, in, localStorage, JSON restore, 5 themes, chỉnh sửa menu, server presets, HTTP 301/403 và console JS.
4. Chỉ ghi UI PASS sau khi người dùng xác nhận.

## Checkpoint / handoff

Branch: `feat/rental-room-management-blade-20261029`.
Chưa chạy PHPUnit trong môi trường GitHub; cần kết quả test thực tế từ máy người dùng.
Không merge/deploy production trước khi có đủ test, UI PASS và phê duyệt.

## Xác nhận checkpoint (29/10/2026)

- Người dùng xác nhận **test + UI PASS** trong cuộc trò chuyện. Chưa có log PHPUnit chi tiết để đối chiếu số lượng assertion.
- UI đã kiểm tra sau các lần sửa menu modal và đồng bộ header/footer; người dùng xác nhận PASS.
- So sánh GitHub: feature branch ahead 31 commits, behind 0 so với main tại thời điểm kiểm tra.
- Chưa xác nhận module/impacted regression riêng hoặc tracked working tree trên máy người dùng; cần kiểm tra trước merge.
- PR chỉ để review, chưa được phép merge/deploy.

## Refactor Modules/Demo — Phase 01 (2026-10-29)

- Branch: `refactor/demo-module-rental-room-20261029`.
- Canonical UI/dataset controllers: `Modules/Demo/Http/Controllers/Projects/RentalRoom`.
- Canonical views: `Modules/Demo/resources/views/projects/rental-room` (namespace `demo::`).
- Source assets: `Modules/Demo/resources/assets/projects/rental-room`; published assets stay at existing public URLs.
- Canonical UI and dataset routes: `Modules/Demo/routes/web.php`.
- Shared sidebar routes/controller remain in legacy application paths until other 11 demos are migrated.
- Existing localStorage and private JSON dataset paths are preserved; no data migration.
- Tests and UI must be rerun after refactor; prior PASS is not transferable.
