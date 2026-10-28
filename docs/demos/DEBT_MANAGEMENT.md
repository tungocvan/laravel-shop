# Standalone Debt Management Demo — Quản lý công nợ

- Branch: feat/debt-management-blade-20261027 (base main bfc6fc552).
- Route: GET /demo/quan-ly-cong-no, name demo.debt-management.
- Controller: App\\Http\\Controllers\\Demo\\DebtManagementController.
- No Module, database migration, production operation or backend data mutation.
- Blade layout and shell are isolated under resources/views/demo/debt-management.
- CSS and original JS logic are separate under public/demo-assets/quan-ly-cong-no.
- The original app dynamically renders all navigation, sidebar, header, modals and pages into #app. Do not pre-render these with Blade without UI regression.
- Original HTML used a File System Access API to overwrite its own HTML; Laravel Blade cannot safely use that mechanism. Adapt persistence to browser-local localStorage and JSON download/restore.
- Focused test: php artisan test tests/Feature/StandaloneDebtManagementDemoTest.php.
- Impacted regression after focused PASS: php artisan test tests/Feature/StandaloneSalesDemoRouteTest.php tests/Feature/StandaloneWarehouseInventoryDemoTest.php.
- Manual UI gate: dashboard, partners, vouchers, aging, reconciliation, reports, Excel/print, backup/restore, reload persistence, tablet/iPhone, console errors.
- JSON server library and four-color theme Settings implemented on this branch, pending new focused/regression/UI gates.
- TEST PASS: pending local output. UI PASS: pending user confirmation. No PR merge/deploy authorized.

## JSON server library and themes (2026-10-27)

- Public GET list/load: `/demo/quan-ly-cong-no/datasets` and `/demo/quan-ly-cong-no/datasets/{dataset}`.
- Protected POST to `/demo/quan-ly-cong-no/datasets`: CSRF + throttle 5/min + `X-Demo-Backup-Code`; shares existing `config('warehouse_demo.backup_code')` (minimum 16 characters, unset disables writes). Do not expose or log secret.
- Private local disk `demo-datasets/debt-management`, maximum 10 MiB and 100 JSON files; independent of warehouse/sales.
- Data shape: `settings` object, `partners` list with unique string IDs, `vouchers` list with unique string IDs referencing existing partner IDs, optional `_app: congno`.
- The browser can list/load public samples after confirmation. Uploading a named backup requires YES and operator code; downloads local JSON before attempting POST. Browser CRUD never writes to server automatically.
- Appearance settings: Orange original (preserved default), and Ruby, Ocean, Emerald, Indigo alternatives. Stored separately as `localStorage['debt_management_theme_v1']`, independent of business JSON. Palette accent applies to chart.
- Assets intentionally live under `public/demo-assets/quan-ly-cong-no` to prevent Nginx 403 caused by physical directory collision with the GET route.
- Focused: `php artisan test tests/Feature/StandaloneDebtManagementDemoTest.php tests/Feature/DebtManagementDatasetLibraryTest.php`.
- Impacted after focused PASS: `php artisan test tests/Feature/SalesDemoDatasetLibraryTest.php tests/Feature/WarehouseDemoDatasetLibraryTest.php tests/Feature/StandaloneSalesDemoRouteTest.php tests/Feature/StandaloneWarehouseInventoryDemoTest.php`.
- UI: server empty/list/load, cancel/confirm, YES/wrong/valid code, failed server backup vs local download, CRUD/JSON restore, four alternate themes + original, refresh, responsive, chart colors, browser console.
- Previous UI PASS covered the earlier Blade-only version, not this updated feature batch. TEST/UI pending new output. No merge or deploy.

## Sidebar Menu Builder (2026-10-27)

- Entry point: `/demo/quan-ly-cong-no#caidat` → **Chỉnh sửa menu**. No header edit button.
- Dynamic sidebar remains owned by the existing `App` renderer; `menu-builder.js` wraps `App.init` and the Settings view, preserving business routes and DOM event delegation.
- Editor supports drag/drop items within/between groups (desktop HTML5 drag/drop), rename group, add item/group, edit label/icon/href, duplicate, delete, reset default, import/export JSON.
- JSON schema: `{version:1,groups:[{title,items:[{id,label,icon,href}]}]}`. Unique IDs; hash routes or HTTPS external URLs only. Unknown internal hashes are disabled in sidebar; never execute arbitrary URL schemes.
- GET `/demo/quan-ly-cong-no/menu`: read-only public shared configuration (null means original defaults).
- POST `/demo/quan-ly-cong-no/menu`: operator-protected by existing `config('warehouse_demo.backup_code')` (min 16 chars), CSRF, throttle 5/min, max 64 KiB. Never place code in HTML/JS/localStorage/log. Server stores JSON on Laravel private local disk at `demo-settings/debt-management/sidebar-menu.json`; no database or .env edits.
- Import validates locally and requires confirmation; import only edits the pending configuration. **Lưu lên server** applies globally to all browsers after operator authorization. Closing editor reloads server state, discarding unsaved edits. Export downloads current editor JSON.
- Security: shared public GET exposes menu labels/hrefs; do not include sensitive internal links. Only trusted operators should write. Server save is not per-user.
- Focused tests: `php artisan test tests/Feature/StandaloneDebtManagementDemoTest.php tests/Feature/DebtManagementMenuTest.php`.
- After focused PASS, impacted tests: `php artisan test tests/Feature/DebtManagementDatasetLibraryTest.php tests/Feature/StandaloneSalesDemoRouteTest.php tests/Feature/StandaloneWarehouseInventoryDemoTest.php`.
- UI gate: edit entry in Settings, reorder within/across groups, rename/icon/href, duplicate/delete/reset, JSON import/export, failed/wrong/valid admin code, refresh and second browser, external HTTPS link, invalid hash, mobile responsive, keyboard navigation, no JS console errors.
- Pending focused and UI verification on this batch. No merge or deploy.

## Sidebar header/footer and named JSON presets (2026-10-27)

- Settings → Chỉnh sửa menu includes editable sidebar header (title, subtitle, icon, safe href) and footer (credit, safe href, show/hide Save button). Existing menu JSON version 1 without these sections remains valid; browser falls back to original header/footer.
- Named server JSON preset library: GET `/demo/quan-ly-cong-no/menu/presets`, GET `.../presets/{preset}`, POST `.../presets`. Presets are separate from the active menu file; private local disk `demo-settings/debt-management/presets/{id}.json`.
- Saving a named preset requires operator code + CSRF + throttle 5/min, max 64 KiB, max 100 presets, safe generated IDs. No overwrite/delete endpoint. Public listing/loading is intended only for non-sensitive demo menu metadata.
- **Lưu file JSON** creates a named preset; **Nhập từ server** loads a selected preset into editor only; **Lưu lên server** applies the editor configuration as active sidebar (shared across devices). Import/export local JSON still supported.
- Focused: `php artisan test tests/Feature/DebtManagementMenuTest.php tests/Feature/StandaloneDebtManagementDemoTest.php`.
- After focused PASS, impacted regression: `php artisan test tests/Feature/DebtManagementDatasetLibraryTest.php tests/Feature/StandaloneSalesDemoRouteTest.php tests/Feature/StandaloneWarehouseInventoryDemoTest.php`.
- UI checkpoint: header/footer edits, safe logo/footer links, save button visibility, named JSON save/list/import, active menu unchanged until explicit apply, browser refresh and second device, import/export compatibility, desktop/mobile and console errors.
- No local tests executed by assistant; TEST/UI PASS pending user verification. No merge/deploy.

## Sidebar update persistence (2026-10-28)

- **Cập nhật giao diện** validates editor JSON, saves to browser key `debt_management_sidebar_menu_v1`, applies sidebar immediately, and displays a success modal. Local changes survive editor close and page reload on the same browser.
- Initial load fetches shared server config, then overlays a valid browser-local configuration if present. Invalid local JSON is ignored.
- **Lưu lên server** writes shared JSON with operator code; on successful POST it clears the browser override so the shared configuration is authoritative. On failure the local override is not deleted.
- Editor close without clicking Update discards uncommitted editor changes, restoring the last applied configuration.
- UI regression: update → success modal → close → reload; update → cancel further edits; failed server save preserves local state; successful server save clears override and persists after reload; separate browser shows server state only.
- No TEST/UI PASS asserted for this change. No merge/deploy.
