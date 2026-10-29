# Báo cáo quản trị doanh nghiệp — Standalone Blade Demo

- UI: `GET /demo/bao-cao-quan-tri-doanh-nghiep`
- Controller: `EnterpriseManagementReportController`.
- View: `resources/views/demo/bao-cao-quan-tri-doanh-nghiep/`.
- Assets: `public/demo-assets/bao-cao-quan-tri-doanh-nghiep/` (không trùng UI route).
- Bảo toàn các DOM ID và JavaScript nghiệp vụ từ file HTML gốc.
- Dữ liệu lưu localStorage theo key nguyên bản, không có DB/API ghi server.
- Khi xuất HTML, ứng dụng tải bản gốc `original.html`, chèn dữ liệu và xuất file offline độc lập.
- Lưu ý: do tách JS khỏi HTML, `TEMPLATE` không còn là tài liệu standalone; hàm `buildHtml` được thay bằng tải mẫu gốc. UI cần kiểm tra tải HTML, Excel, print, nhập liệu, khôi phục JSON và các màn hình.
- Chưa xác nhận TEST PASS hoặc UI PASS. Không merge/deploy khi chưa được phê duyệt.


## Sidebar và theme — batch 1

- Màn hình: `/demo/bao-cao-quan-tri-doanh-nghiep#settings`, thẻ **Giao diện & Sidebar Menu**.
- Theme: Ruby/Ocean/Emerald/Indigo; browser-local key `enterprise_management_report_theme_v1`, không nằm trong JSON nghiệp vụ.
- Sidebar: sửa header, footer, nhóm/mục, icon, href; di chuyển bằng nút lên/xuống; lưu browser-local key `enterprise_management_report_sidebar_v1`. Chỉ cho phép hash view hiện có hoặc HTTPS; không xóa chức năng khi ẩn khỏi sidebar.
- `js/preferences.js` chạy sau `js/app.js`, không thay đổi business state `S`. KPI card `sbcard` giữ nguyên.
- **Chưa triển khai** API lưu/preset JSON trên server, xác thực mã quản trị, kéo thả giữa nhóm hoặc export/import menu. Đây là batch tiếp theo, không được xem là hoàn thành tiêu chuẩn server-side.
- Gate: `php artisan test tests/Feature/EnterpriseManagementReportDemoTest.php tests/Feature/EnterpriseManagementReportPreferencesTest.php`, `node --check public/demo-assets/bao-cao-quan-tri-doanh-nghiep/js/preferences.js`, sau đó regression demo Sales và UI desktop/tablet/iPhone.
- UI acceptance cần xác nhận lại sau thay đổi, đặc biệt F5, thay theme, chỉnh footer, các menu điều hướng và hành vi lưu HTML offline.


## Blade persistence safety (2026-10-28)

- Trên HTTP(S), nút lưu và Ctrl+S chỉ xác nhận localStorage của trình duyệt, không tạo/tải file HTML. `saveToFile()` giữ nhánh offline cho bản HTML gốc nếu được chạy trực tiếp từ `file:`.
- Không tuyên bố dữ liệu đã lưu lên server. Chức năng sao lưu/khôi phục JSON và Excel giữ nguyên.
- Đã audit source tham khảo trên main: `public/demo-assets/quan-ly-cong-no/js/menu-builder.js` và `app/Http/Controllers/Demo/DebtManagementMenuController.php`. Editor riêng `preferences.js` hiện **chưa** được thay bằng cơ chế dùng chung, chưa có API server/presets. Cần batch tiếp theo sau audit chi tiết schema/route/auth và focused tests bảo mật.
- Checkpoint: `php artisan test tests/Feature/EnterpriseManagementReportDemoTest.php tests/Feature/EnterpriseManagementReportPreferencesTest.php`, sau PASS chạy impacted regression; `node --check` cả `app.js` và `preferences.js`; UI cần xác nhận lại.


## Sidebar Menu Builder — shared API integration (2026-10-28)

- Tái sử dụng `app/Http/Controllers/Demo/SharedSidebarMenuController.php` và nhóm route `/demo/{demo}/menu` đã có; bổ sung namespace `bao-cao-quan-tri-doanh-nghiep` lưu private `storage/app/private/demo-settings/enterprise-management-report` (đường dẫn thực tế do Laravel local disk quyết định). Không tạo controller API trùng nghiệp vụ.
- UI `#settings`: chỉnh header/footer/groups/items; nút kéo thả desktop giữa các nhóm, nút lên/xuống cho mobile/bàn phím; JSON import/export; thư viện preset list/load/create/update/delete; modal mã quản trị (password, hiện/ẩn), thông báo lỗi HTTP; giữ thẻ KPI `sbcard`.
- API: GET menu và preset công khai, POST menu/preset và PUT/DELETE preset yêu cầu `X-Demo-Backup-Code` đối chiếu `config('warehouse_demo.backup_code')` dài tối thiểu 16 ký tự, CSRF, throttle 5/min. Không ghi secret vào HTML, localStorage hay log. Bổ sung phát hiện trùng tên 409, cập nhật đúng preset ID, giới hạn JSON 64 KiB, tối đa 100 preset.
- Cấu hình sidebar version 1, header/footer/groups, localStorage key `enterprise_management_report_sidebar_v1`. Browser-local ưu tiên khi đã tùy chỉnh; GET server áp dụng nếu browser chưa có bản riêng. Import server chỉ áp dụng local, không ghi đè menu active trên server.
- Handoff test: `tests/Feature/EnterpriseManagementReportMenuTest.php` (auth, 422, 409, private storage, preset isolation). Regression cross-demo cần kiểm tra vì SharedSidebarMenuController được thay đổi.
- **Chưa xác nhận** PHP tests, JS syntax, browser UI hoặc tương thích các demo khác. Chỉ ghi PASS khi có kết quả thực tế. Không merge/deploy.

## Phase 06 — Demo module migration (2026-10-29)

- Branch `refactor/demo-module-enterprise-management-report-20261029` inherits user-accepted Demo 05 and all prior migrations; main remains unmerged.
- Controller: `Modules/Demo/Http/Controllers/Projects/EnterpriseManagementReport/`; Blade: `Modules/Demo/resources/views/projects/enterprise-management-report/`; CSS/JS/preferences/original offline HTML: `Modules/Demo/resources/assets/projects/enterprise-management-report/`.
- UI and browser asset URLs unchanged, using a strict allowlisted asset controller. Offline original HTML is preserved; dynamic sidebar and server-backed menu preset workflow remain unchanged.
- Theme UI now offers five responsive palette preview cards including original, retaining the previous localStorage key.
- Focused tests: `php artisan test tests/Feature/EnterpriseManagementReportDemoTest.php tests/Feature/EnterpriseManagementReportPreferencesTest.php tests/Feature/DemoModuleEnterpriseManagementReportOwnershipTest.php`. Impacted and manual UI pending.
- No production deployment or main merge.
