# Standalone Demo — Chấm công và Tính lương

## GitHub handoff (2026-10-28)

- Branch: `feat/attendance-payroll-blade-20261028` (from `main`).
- Route UI: `GET /demo/cham-cong-tinh-luong`.
- Controller: `App\Http\Controllers\Demo\AttendancePayrollController`.
- Blade: `resources/views/demo/cham-cong-tinh-luong/`.
- Assets: `public/demo-assets/cham-cong-tinh-luong/` (không tạo thư mục `public/demo/cham-cong-tinh-luong` để tránh Nginx 301/403).
- HTML gốc: `Chấm công và Tính lương.html` do người dùng cung cấp.
- Core và payroll engine được tách nguyên từ HTML gốc, không sửa công thức tính lương, bảo hiểm, thuế hoặc chấm công.
- `core.js`: điều chỉnh hành vi lưu: lưu JSON vào localStorage, không ghi đè HTML/Blade qua File System Access API.
- `extensions.js`: bốn theme Ruby/Ocean/Emerald/Indigo (mặc định Ocean), JSON import/export, thư viện JSON server, bridge shared sidebar menu.
- JSON server private disk `storage/app/demo-datasets/attendance-payroll/`; GET list/show công khai theo chuẩn demo, POST cần mã quản trị `config('warehouse_demo.backup_code')` >=16 ký tự, CSRF, throttle 5/phút, giới hạn 10 MiB/100 file. Không thay đổi `.env`.
- Sidebar menu sử dụng shared builder; demo này được thêm vào allowlist và bridge; có regression scope cho các demo dùng shared builder.

## UI/UX iteration — Sidebar icons and unified settings

- User reviewed screenshots comparing the HTML original and Blade demo, and approved restoring icons and merging the demo settings into the existing **Cài đặt** page.
- Root cause: payroll-specific shared menu bridge created new anchors with only a text span, dropping SVG icons from the original `ic()` registry. The business engine and the generic menu builder were not responsible for icon generation.
- The payroll bridge now renders safe whitelisted SVG icons using the original registry; aliases support `layout-dashboard`, `users-round`, `calendar-check`, `settings-2`, `database-backup`, `percent`.
- The existing `App.views.caidat.render` runs first; an extra section adds 4 themes, sidebar editor, JSON import/export and server library. The separate `demo-settings` view and sidebar item were removed.
- Default sidebar: Overview, Business, System with `Dữ liệu & sao lưu` and one `Cài đặt`. Existing saved menu presets are migrated in memory: legacy `demo-settings` is mapped to `caidat` and duplicate settings entries removed. Other custom items remain unchanged; server presets are not overwritten automatically.
- The shared builder change is limited to the payroll slug; other demos retain their existing behavior.
- Focused source contracts added to `tests/Feature/AttendancePayrollDemoTest.php`.
- UI regression required: old saved menu, newly created menu, SVG icons, all original business settings and new theme/JSON controls in the same page; desktop/tablet/iPhone and no JS errors.
- **TEST/UI PASS not claimed**; awaiting user execution.

- Sidebar footer cleanup: remove sample-data status and manual browser-save button from the payroll demo only; keep the credit, background localStorage autosave, JSON backup and server library. `Store.status()` is already null-safe when the status node is absent. Focused contract test covers this.

## Checkpoint Git Pull/Test

```bash
git fetch origin
git switch --track origin/feat/attendance-payroll-blade-20261028
git pull --ff-only
php artisan test tests/Feature/AttendancePayrollDemoTest.php tests/Feature/AttendancePayrollDatasetTest.php
git status --short
```

Nếu branch local đã tồn tại: `git switch feat/attendance-payroll-blade-20261028` rồi `git pull --ff-only`.

## Gates

- **TEST chưa xác nhận PASS**: cần người dùng chạy focused tests trên local Laravel 12, gửi output; sau đó chạy impacted regression cho HumanResources, Sales, Warehouse, Debt và shared sidebar.
- **UI chưa xác nhận PASS**: người dùng kiểm tra desktop/tablet/iPhone, CRUD nhân viên, chấm công, bảng lương, thuế, Excel, in, dữ liệu sau reload, 4 theme, JSON import/export, JSON server, menu editor và browser console.
- Kiểm tra cả URL `/demo/cham-cong-tinh-luong` và dạng có slash cuối; đảm bảo assets 200, không có thư mục vật lý xung đột.
- Lưu ý: Shared sidebar builder hiện dùng `window.prompt` cho mã quản trị; cần UI review theo tiêu chuẩn mới trước merge. Không tuyên bố UI PASS khi chưa được xác nhận.
- Không merge hoặc deploy production khi chưa có phê duyệt rõ ràng.
