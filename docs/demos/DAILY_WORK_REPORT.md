# Báo cáo công việc hằng ngày — Blade demo

- UI: `GET /demo/bao-cao-cong-viec-hang-ngay`; controller `DailyWorkReportController`.
- Assets: `public/demo-assets/bao-cao-cong-viec-hang-ngay` (không trùng UI route).
- Không có database; nghiệp vụ lưu ở `localStorage` key `bccv_phongexcel_v1|daily-work-report`.
- HTML gốc lưu tại `public/demo-assets/bao-cao-cong-viec-hang-ngay/original.html` để xuất file HTML offline. Cần kiểm tra an toàn nội dung trước khi publish.
- Phần tử DOM, inline handlers và JS nghiệp vụ giữ nguyên; sections tách Blade.
- Nút Lưu trong Laravel chỉ lưu localStorage, không ghi file server; sao lưu JSON và xuất Excel giữ nguyên.
- Thư viện JSON nghiệp vụ server chưa triển khai; Sidebar Menu Builder sử dụng component chuẩn `x-demo.sidebar-menu-builder` và `SharedSidebarMenuController` (không nhân bản editor).
- Focused test Blade trước đó được người dùng xác nhận 2 PASS; thay đổi sidebar mới chưa được kiểm thử. UI sidebar chưa xác nhận. Không merge/deploy.

- Bốn theme Ruby/Ocean/Emerald/Indigo được lưu độc lập trong `daily_work_report_theme_v1`.

## Shared Sidebar Menu Builder

- Settings `#settings` tự hiển thị thẻ **Sidebar menu / Chỉnh sửa menu** qua component dùng chung.
- Source of truth: `resources/views/components/demo/sidebar-menu-builder.blade.php`, `public/demo/shared/sidebar-menu/menu-builder.{js,css}`.
- API namespace riêng `/demo/bao-cao-cong-viec-hang-ngay/menu` và `/presets`; private storage `demo-settings/daily-work-report/`, localStorage `shared_demo_sidebar_menu_v1_bao-cao-cong-viec-hang-ngay`.
- Schema version 1 header/footer/groups, `footer.showSave` tương thích, badge động phải kiểm tra trên UI. POST/PUT/DELETE dùng CSRF, mã quản trị `config('warehouse_demo.backup_code')`, throttle.
- Không sửa dữ liệu báo cáo `S` hoặc khóa localStorage nghiệp vụ. Kiểm tra F5, modal Menu/Header/Footer/Thư viện JSON, link, CRUD menu, badge và responsive.
- Focused: `php artisan test tests/Feature/DailyWorkReportMenuTest.php tests/Feature/DailyWorkReportDemoTest.php`; sau PASS chạy `tests/Feature/SharedDemoSidebarMenuTest.php` và regression các demo liên quan.

## Phase 05 — Demo module migration (2026-10-29)

- Branch `refactor/demo-module-daily-work-report-20261029` from accepted Demo 04; no merge into main.
- Controller: `Modules/Demo/Http/Controllers/Projects/DailyWorkReport/`; Blade: `Modules/Demo/resources/views/projects/daily-work-report/`; CSS/JS and original offline HTML: `Modules/Demo/resources/assets/projects/daily-work-report/`.
- Route name/URL and all browser asset URLs unchanged through allowlisted module controller; original offline HTML source remains intact for export.
- Shared sidebar menu builder/preset JSON API unchanged. Browser business data, JSON export, Excel and offline HTML are preserved; no new server business JSON library.
- Five responsive preview cards including original theme, existing theme storage key unchanged.
- Focused: `php artisan test tests/Feature/DailyWorkReportDemoTest.php tests/Feature/DailyWorkReportMenuTest.php tests/Feature/DemoModuleDailyWorkReportOwnershipTest.php`.
- Impacted regression and manual UI acceptance pending. Do not merge or deploy.
