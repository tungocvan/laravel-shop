# Báo cáo công việc hằng ngày — Blade demo

- UI: `GET /demo/bao-cao-cong-viec-hang-ngay`; controller `DailyWorkReportController`.
- Assets: `public/demo-assets/bao-cao-cong-viec-hang-ngay` (không trùng UI route).
- Không có database; nghiệp vụ lưu ở `localStorage` key `bccv_phongexcel_v1|daily-work-report`.
- HTML gốc lưu tại `public/demo-assets/bao-cao-cong-viec-hang-ngay/original.html` để xuất file HTML offline. Cần kiểm tra an toàn nội dung trước khi publish.
- Phần tử DOM, inline handlers và JS nghiệp vụ giữ nguyên; sections tách Blade.
- Nút Lưu trong Laravel chỉ lưu localStorage, không ghi file server; sao lưu JSON và xuất Excel giữ nguyên.
- Thư viện JSON server và Sidebar Menu Builder cần audit riêng, không triển khai trong batch này.
- Test chưa chạy trên môi trường Laravel; UI chưa xác nhận. Không merge/deploy.

- Bốn theme Ruby/Ocean/Emerald/Indigo được lưu độc lập trong `daily_work_report_theme_v1`.
