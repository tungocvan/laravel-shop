# Tính giá thành sản phẩm — standalone Blade demo

- Branch: `feat/product-costing-blade-20261029`
- Route: `GET /demo/tinh-gia-thanh-san-pham`; assets: `public/demo-assets/tinh-gia-thanh-san-pham` (avoid public/demo Nginx collision).
- Original localStorage key: `tinh_gia_thanh_sp_v1`; original business JS and DOM IDs retained.
- 9 screen Blade partials, sidebar/overlay partials, external CSS/JS.
- Standalone HTML export uses original source snapshot embedded into app.js, not Laravel-rendered markup.
- Shared Sidebar Menu Builder uses `x-demo.sidebar-menu-builder` and existing menu controller allowlist.
- Five themes (original, Ruby, Ocean, Emerald, Indigo) stored separately in localStorage.
- JSON server snapshots under private `storage/app/demo-datasets/product-costing` (10 MB, 100 files, throttle); reads and writes require existing `warehouse_demo.backup_code`, 16+ characters. Browser state remains authoritative until confirmed restore.
- Focused test: `php artisan test tests/Feature/ProductCostingDemoTest.php`
- Impacted test: `php artisan test tests/Feature/CoffeeShopSalesDemoTest.php`
- UI acceptance pending: desktop/tablet/iPhone, menu edit, 5 themes, CRUD, BOM, costs, charts, Excel, HTML export, restore, localStorage and console.
- This patch must be committed to feature branch and route/shared menu allowlist integrated before checkpoint. No UI PASS claimed.

## Collaboration handoff — 2026-10-29

- Branch: `feat/product-costing-blade-20261029`.
- Scope: Standalone Product Costing Blade demo; shared sidebar allowlist; private JSON snapshot endpoints.
- Focused tests: PASS — 5 tests, 14 assertions.
- Impacted regression: PASS — 35 tests, 155 assertions.
- PHP syntax: PASS.
- JavaScript syntax: PASS.
- Git staged diff check: PASS.
- Full project regression: NOT APPLICABLE — scoped regression strategy.
- UI smoke: PENDING — desktop, tablet, iPhone, menu editor, five themes, CRUD, BOM, reports, Excel, HTML export, backup/restore.
- Known issues: No automated test failures observed; manual UI behavior not yet verified.
- Rollback: Revert the feature commit or PR if needed; preserve existing browser localStorage and private snapshot data.
- Next step: Commit/push feature branch, review PR, complete UI acceptance, request explicit approval before merge.
