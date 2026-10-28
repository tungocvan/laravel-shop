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
