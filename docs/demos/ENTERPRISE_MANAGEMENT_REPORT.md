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
