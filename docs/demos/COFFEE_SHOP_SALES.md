# Coffee Shop Sales standalone demo

- GET `/demo/quan-ly-ban-hang-ca-phe`; `CoffeeShopSalesController`
- Browser data: original `localStorage`, `IndexedDB`, embedded JSON `#du-lieu`. No DB migration.
- UI assets under `public/demo-assets/quan-ly-ban-hang-ca-phe` to avoid 301/403 Nginx path collision.
- Original HTML export template is embedded into external JS, preserving downloadable standalone file functionality.
- Theme key `coffee_shop_sales_theme_v1` is separate from business data.
- Follow-up required: server JSON datasets and Shared Sidebar Menu Builder must be integrated with existing audited service; not included in this compatibility batch.
- Focused: `php artisan test tests/Feature/CoffeeShopSalesDemoTest.php`.
- Manual UI: sales, stock deduction, import/export, save HTML, Excel, print, mobile, refresh, console errors. UI PASS requires user confirmation.
