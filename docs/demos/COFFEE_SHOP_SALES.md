# Coffee Shop Sales standalone demo

- GET `/demo/quan-ly-ban-hang-ca-phe`; `CoffeeShopSalesController`
- Browser data: original `localStorage`, `IndexedDB`, embedded JSON `#du-lieu`. No DB migration.
- UI assets under `public/demo-assets/quan-ly-ban-hang-ca-phe` to avoid 301/403 Nginx path collision.
- Original HTML export template is embedded into external JS, preserving downloadable standalone file functionality.
- Theme key `coffee_shop_sales_theme_v1` is separate from business data.
- Server JSON library: GET list/show and POST snapshot (all require `X-Demo-Backup-Code`) at `/demo/quan-ly-ban-hang-ca-phe/datasets`; private `storage/app` via Laravel local disk, 10 MB limit, 100 files, throttle and CSRF. Every dataset endpoint requires existing `warehouse_demo.backup_code` (minimum 16 characters); no new `.env` variable. Browser data remains authoritative until user explicitly restores a snapshot.
- Shared Sidebar Menu Builder uses `<x-demo.sidebar-menu-builder slug="quan-ly-ban-hang-ca-phe" />`, shared API and the existing dynamic `#nav` DOM. Settings page exposes the dataset library and four palettes; palette selection persists separately in localStorage.
- Dynamic DOM mount points remain Blade partials; do not replace business render logic merely to satisfy contract tests.
- Security: JSON snapshots are sensitive; list/show/store now all require the same server-side backup code; reads are throttled and disabled if the code is not configured. Do not store the code in browser storage. This is a shared secret rather than per-user authorization: restrict demo access separately for sensitive or multi-tenant data. Never put production credentials or customer data into public demo datasets.
- Focused: `php artisan test tests/Feature/CoffeeShopSalesDemoTest.php`.
- Manual UI: sales, stock deduction, import/export, save HTML, Excel, print, mobile, refresh, console errors. UI PASS requires user confirmation.

## Checkpoint

Run focused tests first, then impacted shared-menu regression. Verify manually: four themes persist after refresh; menu editor can open/save/preset and navigate; server save/list/restore; restore confirmation; sales/order/stock deduction; print, Excel, offline HTML export; desktop/tablet/iPhone. No UI PASS until user confirms.

## Phase 04 — Demo module ownership migration (2026-10-29)

- Branch `refactor/demo-module-coffee-shop-sales-20261029` builds on accepted Phase 03; no merge into main.
- Controllers: `Modules/Demo/Http/Controllers/Projects/CoffeeShopSales/`; Blade: `Modules/Demo/resources/views/projects/coffee-shop-sales/`; CSS/JS: `Modules/Demo/resources/assets/projects/coffee-shop-sales/`.
- Shared module layout and module-owned shared menu editor are used; dynamic sidebar remains JS-owned to preserve existing behavior.
- The original JS with embedded offline HTML template is unchanged; browser storage, private server snapshots, theme selection and original asset URLs are retained.
- Focused: `php artisan test tests/Feature/CoffeeShopSalesDemoTest.php tests/Feature/DemoModuleCoffeeShopSalesOwnershipTest.php`.
- Impacted regression: Equipment Maintenance, Hotel Uniform, Rental Room ownership and SharedDemoSidebarMenu tests after focused PASS.
- TEST/UI PENDING. Do not merge or deploy.

## Phase 04 — Theme preview cards and JSON settings simplification

- The Coffee Shop Settings screen now offers five preview cards: original, Ruby, Ocean, Emerald and Indigo. The existing theme storage key is unchanged.
- The separate Coffee Shop server JSON library widget has been removed from Settings at user request. Menu JSON management remains in the shared menu editor; **menu presets are not equivalent to business data snapshots**.
- Dataset routes and private server snapshot files are intentionally retained for compatibility and to avoid deleting user data. Removing these endpoints or files would require separate authorization and data migration/retention review.
- TEST/UI PENDING after this change.
