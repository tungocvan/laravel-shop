# Quản lý đồng phục nhân viên khách sạn — Laravel Blade

- Feature branch: `feat/hotel-uniform-blade-20261029`.
- GET: `/demo/quan-ly-dong-phuc-khach-san`, controller `HotelUniformController`.
- Views: `resources/views/demo/quan-ly-dong-phuc-khach-san`; assets: `public/demo-assets/quan-ly-dong-phuc-khach-san`.
- HTML gốc giữ nguyên các màn hình, DOM IDs, JavaScript nghiệp vụ, CRUD, phiếu, Excel và in ấn; CSS/JS tách riêng.
- Browser data key: `dong_phuc_khach_san_v1|<pathname>` (riêng cho demo này). Không dùng database.
- Tính năng ghi đè file HTML gốc được thay bằng tải bản sao lưu JSON. Trình duyệt vẫn tự lưu bản nháp; khôi phục JSON có xác nhận.
- Shared sidebar editor dùng namespace `hotel-uniform`, server menu JSON và preset library; chọn file, cập nhật header/footer, đặt preset mặc định; bảo vệ ghi server bằng `warehouse_demo.backup_code`.
- Themes: original, Ruby, Ocean, Emerald, Indigo; lưu `hotel_uniform_theme_v1` trên localStorage.
- Không tự động khôi phục hoặc ghi đè dữ liệu server; không tác động demo khác.

## Checkpoint
- Focused test: `php artisan test tests/Feature/HotelUniformDemoTest.php`.
- JS syntax: `node --check public/demo-assets/quan-ly-dong-phuc-khach-san/js/app.js` và `node --check public/demo-assets/quan-ly-dong-phuc-khach-san/js/integrations.js`.
- **TEST PENDING**: chưa có output chạy test trên máy người dùng.
- **UI PENDING**: người dùng cần kiểm tra desktop/tablet/iPhone, 13 màn hình, CRUD, phiếu, Excel, in, localStorage, JSON, sidebar menu và themes.
- Sau focused PASS, chạy impacted tests của shared sidebar và các demo có liên quan.
- Rollback: revert PR/commits của feature branch; giữ nguyên browser localStorage và các file untracked.
- Chưa tạo PR, chưa merge/deploy.
