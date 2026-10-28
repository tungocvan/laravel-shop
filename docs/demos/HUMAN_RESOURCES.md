# Standalone demo: Quản lý nhân sự

Branch: `feat/hr-standalone-blade-20261028`

## Scope
- GET `/demo/quan-ly-nhan-su` through `HumanResourcesController`.
- Blade view and independent layout under `resources/views/demo/quan-ly-nhan-su/`.
- CSS/JS under `public/demo-assets/quan-ly-nhan-su/`, preserving employee CRUD, filters, dashboard, CSV and printing.
- Browser data `qlns_data_v2`, one-time read compatibility with `qlns_data_v1`, JSON download/import with schema checks.
- Theme Ruby/Ocean/Emerald/Indigo stored at `qlns_theme_v1`.
- JSON dataset library in local private storage `demo-datasets/human-resources`, GET list/show, POST write with configured `warehouse_demo.backup_code`, throttling, CSRF, schema/size/count/path controls.
- Shared Sidebar Menu Builder with HR bridge adapter, own server settings namespace `demo-settings/human-resources`; shared editor code reused.
- No database migrations, module registration, production configuration or `.env` changes.

## Security
The server write requires an existing configured secret of at least 16 characters. Never include this secret in the repository. GET endpoints are public and therefore dataset files must contain **demo/sample data only**; do not upload real employee personal information. Browser storage is per browser, not a multiuser HR backend.

## Git Pull / focused tests
```bash
git fetch origin
git switch feat/hr-standalone-blade-20261028
git pull --ff-only
php artisan test tests/Feature/HumanResourcesDemoRouteTest.php
```
Run impacted shared-menu tests and relevant existing demo route tests only after focused tests pass. UI requires manual desktop/tablet/iPhone checks: CRUD, filter, CSV, print, themes after refresh, JSON export/import, library auth/load, menu settings/presets, dynamic department/status sidebar.

## Acceptance status
Implementation committed on feature branch. TEST PASS not asserted; requires actual local test output. UI PASS requires user's manual confirmation. No PR/merge/deploy performed.

## Nginx 403 fix
Physical directory `public/demo/quan-ly-nhan-su` collided with GET route. Tracked assets moved to `public/demo-assets/quan-ly-nhan-su` and tracked originals deleted. After pull, verify `public/demo/quan-ly-nhan-su` no longer exists. Do not delete any residual untracked files without inspection. Check HTTP 200 for both slash and non-slash URLs; if residual directory exists, inspect first.
