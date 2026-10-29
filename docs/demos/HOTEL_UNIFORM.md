# Quản lý đồng phục nhân viên khách sạn — Laravel Blade

- Feature branch: `feat/hotel-uniform-blade-20261029`.
- GET: `/demo/quan-ly-dong-phuc-khach-san`, controller `HotelUniformController`.
- Views: `resources/views/demo/quan-ly-dong-phuc-khach-san`; assets: `public/demo-assets/quan-ly-dong-phuc-khach-san`.
- HTML gốc giữ nguyên các màn hình, DOM IDs, JavaScript nghiệp vụ, CRUD, phiếu, Excel và in ấn; CSS/JS tách riêng.
- Browser data key: `dong_phuc_khach_san_v1|<pathname>` (riêng cho demo này). Không dùng database.
- Tính năng ghi đè file HTML gốc được thay bằng tải bản sao lưu JSON. Trình duyệt vẫn tự lưu bản nháp; khôi phục JSON có xác nhận.
- Shared sidebar editor dùng namespace `hotel-uniform`, server menu JSON và preset library; chọn file, cập nhật header/footer, đặt preset mặc định; bảo vệ ghi server bằng `warehouse_demo.backup_code`.
- Themes: original, Ruby, Ocean, Emerald, Indigo; lưu `hotel_uniform_theme_v1` trên localStorage.
- Private server snapshots: GET/POST `/demo/quan-ly-dong-phuc-khach-san/datasets`, GET `/datasets/{dataset}`; `warehouse_demo.backup_code` (>=16 chars), CSRF cho POST, giới hạn 10 MB/100 bản, lưu trong `storage/app/demo-datasets/hotel-uniform` (không public). Khôi phục có xác nhận, không tự ghi đè.
- Không tự động khôi phục hoặc ghi đè dữ liệu server; không tác động demo khác.

## Checkpoint
- Focused test: `php artisan test tests/Feature/HotelUniformDemoTest.php`.
- JS syntax: `node --check public/demo-assets/quan-ly-dong-phuc-khach-san/js/app.js` và `node --check public/demo-assets/quan-ly-dong-phuc-khach-san/js/integrations.js`.
- **TEST PENDING**: chưa có output chạy test trên máy người dùng.
- **UI PENDING**: người dùng cần kiểm tra desktop/tablet/iPhone, 13 màn hình, CRUD, phiếu, Excel, in, localStorage, JSON, snapshots server, sidebar menu và themes.
- Sau focused PASS, chạy impacted tests của shared sidebar và các demo có liên quan.
- Rollback: revert PR/commits của feature branch; giữ nguyên browser localStorage và các file untracked.
- Chưa tạo PR, chưa merge/deploy.

## Route 404 correction — 2026-10-29
- Focused test output from Admin: 2 FAIL (HTTP 404); route declarations were printed to stdout before PHPUnit results.
- Root cause confirmed: four new route declarations had been prepended *before* the `<?php` opening tag of `routes/web.php`, so PHP emitted them as plain text and did not register routes.
- Fixed by moving exactly those four declarations inside the PHP block, preserving existing routes and all other demo files. Commit `f824fe7d`.
- Re-run focused test after pulling feature branch; **TEST PENDING**, do not claim PASS until actual results. No impacted regression until focused PASS.

## Shared menu 404 correction
- Admin focused tests after route PHP fix: **1 PASS, 1 FAIL**. The remaining 404 was `GET /demo/quan-ly-dong-phuc-khach-san/menu`.
- Root cause: `routes/web.php` has `where('demo', ...)` constraints for eight shared-menu routes, which omitted the new demo slug even though the controller allowlist was already updated.
- Added `quan-ly-dong-phuc-khach-san` to exactly those eight route constraints, including default preset selection. No unrelated demo routes changed.
- **Focused retest PENDING**. Only proceed to impacted regression after focused PASS.
