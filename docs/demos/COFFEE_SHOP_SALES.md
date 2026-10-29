# Coffee Shop Sales standalone demo

- GET `/demo/quan-ly-ban-hang-ca-phe`; `CoffeeShopSalesController`
- Browser data: original `localStorage`, `IndexedDB`, embedded JSON `#du-lieu`. No DB migration.
- UI assets under `public/demo-assets/quan-ly-ban-hang-ca-phe` to avoid 301/403 Nginx path collision.
- Original HTML export template is embedded into external JS, preserving downloadable standalone file functionality.
- Theme key `coffee_shop_sales_theme_v1` is separate from business data.
- Server JSON library: GET list/show and POST snapshot at `/demo/quan-ly-ban-hang-ca-phe/datasets`; private `storage/app` via Laravel local disk, 10 MB limit, 100 files, throttle and CSRF. POST requires existing `warehouse_demo.backup_code` (minimum 16 characters); no new `.env` variable. Browser data remains authoritative until user explicitly restores a snapshot.
- Shared Sidebar Menu Builder uses `<x-demo.sidebar-menu-builder slug="quan-ly-ban-hang-ca-phe" />`, shared API and the existing dynamic `#nav` DOM. Settings page exposes the dataset library and four palettes; palette selection persists separately in localStorage.
- Dynamic DOM mount points remain Blade partials; do not replace business render logic merely to satisfy contract tests.
- Security: JSON snapshots are sensitive; list/show endpoints follow the existing demo library convention and should only be enabled in an appropriately access-controlled environment. Never put production credentials or customer data into public demo datasets.
- Focused: `php artisan test tests/Feature/CoffeeShopSalesDemoTest.php`.
- Manual UI: sales, stock deduction, import/export, save HTML, Excel, print, mobile, refresh, console errors. UI PASS requires user confirmation.

## Checkpoint

Run focused tests first, then impacted shared-menu regression. Verify manually: four themes persist after refresh; menu editor can open/save/preset and navigate; server save/list/restore; restore confirmation; sales/order/stock deduction; print, Excel, offline HTML export; desktop/tablet/iPhone. No UI PASS until user confirms.
