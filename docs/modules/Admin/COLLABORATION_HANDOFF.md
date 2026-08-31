# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Major Refactor — Final Core Boundary Closeout**

Status: **IMPLEMENTED — FINAL AUTOMATED VERIFICATION PENDING / UI PASS**

Branch: `refactor/admin-core-boundary-cleanup`

Base: `cae7051d` (merged PR #115)

This checkpoint closes the Admin major-refactor boundary. `Modules/Admin` is treated as the authenticated Admin shell, navigation and layout-integration module. Business-domain ownership remains in specialized modules.

## Final canonical Admin boundary

`Modules/Admin/routes/web.php` is the runtime allow-list for Admin-owned presentation.

Canonical Admin route controllers are:

- `Modules\Admin\Http\Controllers\AdminController`
- `Modules\Admin\Http\Controllers\DashboardController`
- `Modules\Admin\Http\Controllers\MenuController`
- `Modules\Admin\Http\Controllers\ProfileController`

Canonical Admin surfaces are dashboard, menu management, profile and the `/admin/layout` hub with `general`, `header`, `sidebar`, `footer`, `design` and `navigation` sections.

`/admin/layout/sidebar` remains shell/sidebar configuration and links to `/admin/menus`; menu management is not merged into layout configuration.

`DatabaseController` is not part of the normal canonical route allow-list and remains a quarantined legacy/database boundary.

## Ownership moved out of Admin in this checkpoint

### Auth

Admin login/logout/Google authentication is Auth-owned.

- legacy Admin `AuthController` removed;
- legacy Admin `Auth/GoogleController` removed;
- `AuthService` moved from `Modules/Admin/Services` to `Modules/Auth/Services`;
- canonical `Modules/Auth/Http/Controllers/GoogleController` now resolves `Modules\Auth\Services\AuthService`.

### Order / Product / Role / Staff

Legacy Admin controllers for Order, Product, Product Commission, Role and Staff are removed. Canonical runtime stays in their specialized modules.

The duplicate Admin `Livewire/Orders/OrderDetailModal` is removed; canonical Order Livewire/runtime remains owned by `Modules/Order`.

### System settings

Deprecated Admin Livewire adapters for Advanced, Database, ENV, Mail, Modules, Momo, Setting, Social and Storage settings are removed. Canonical implementations remain in `Modules/System`.

Admin-owned layout configuration components remain preserved:

- `AdminLayoutConfig`
- `AdminLayoutDashboard`
- `AdminThemeEditor`

### Website integration

The deprecated Admin `HeaderMenuService` bridge is removed. `AdminLayoutConfig` now consumes the Website-owned `HeaderMenuService` directly only for the legacy one-way import into an empty Admin header user-menu configuration. `/admin/layout/header` remains Admin-owned; Website remains owner of HeaderMenu persistence and behavior.

## Deferred compatibility debt — Website refactor

Final retirement of remaining Website-owned compatibility adapters inside `Modules/Admin` is **intentionally deferred to the upcoming `Modules/Website` refactor**. This is not a blocker for closing the Admin major refactor because canonical Website runtime already owns these domains.

Examples intentionally deferred include Banner, Flash Sale, Affiliate and HeaderMenu model/service compatibility aliases that still remain in Admin.

The Website refactor must perform caller-proofed retirement/update of those adapters and update this boundary if any external compatibility contract is still required.

## Explicit quarantine / preserved persistence

The following are outside this closeout and remain intentionally preserved:

- `Modules/Admin/Http/Controllers/DatabaseController.php`
- `Modules/Admin/Services/DatabaseService.php`
- `Modules/Admin/Models/ModuleRouteTitle.php`
- `Modules/Admin/database/migrations/2026_08_04_000002_create_module_route_titles_table.php`

No destructive database operation is reactivated, moved, beautified or deleted in this phase. No schema/table removal is included.

## Final architectural audit

Repository audit confirms:

- Admin routes import only `AdminController`, `DashboardController`, `MenuController` and `ProfileController`;
- `/admin/layout` and all six layout sections remain Admin-owned;
- Auth Google controller depends on Auth-owned `AuthService`;
- specialized Order/Product/Role/Account routes remain outside Admin;
- deprecated Admin System settings wrappers are retired;
- deprecated Admin HeaderMenu service bridge is retired while Admin layout import behavior is preserved through the Website service;
- Database and `ModuleRouteTitle` remain explicitly quarantined/preserved;
- remaining Website compatibility aliases are documented as deferred Website-refactor debt rather than Admin runtime ownership.

## Contract protection

`tests/Feature/Admin/AdminOwnershipBoundaryContractTest.php` protects the canonical shell route/controller boundary, layout hub, menu ownership and closed Admin API surface.

`tests/Feature/Admin/AdminWebsitePresentationOwnershipContractTest.php` protects retired legacy runtime, Auth/Chat/specialized ownership, Admin layout integration and the explicit split between deferred Website compatibility debt versus Admin quarantine/persistence.

`tests/Feature/System/CanonicalSettingsServiceTest.php` protects System ownership after retirement of Admin settings adapters.

## Verification status

Manual Admin UI smoke: **PASS** (user-confirmed).

Final automated verification is required after pulling the latest branch commits. The closeout gate is:

```bash
php artisan optimize:clear

php artisan test tests/Feature/Admin
php artisan test tests/Feature/Auth
php artisan test tests/Feature/System
php artisan test tests/Feature/Order
php artisan test tests/Feature/Product
php artisan test tests/Feature/Role

php artisan route:list --path=admin
php artisan route:list --path=admin/layout
php artisan route:list --path=admin/login
php artisan route:list --path=admin/orders
php artisan route:list --path=admin/products
php artisan route:list --path=admin/roles

./vendor/bin/pint --test tests/Feature/Admin/AdminWebsitePresentationOwnershipContractTest.php tests/Feature/System/CanonicalSettingsServiceTest.php Modules/Admin/Livewire/Settings/AdminLayoutConfig.php Modules/Auth/Http/Controllers/GoogleController.php Modules/Auth/Services/AuthService.php
npm run build
```

Do not run a broad repository-wide refactor/format pass as part of this closeout.

## Acceptance criteria

- Admin shell/dashboard/menu/profile/layout boundary: **IMPLEMENTED**
- `/admin/layout` canonical hub: **PRESERVED**
- Auth ownership moved to `Modules/Auth`: **IMPLEMENTED**
- legacy Admin Order/Product/Role/Staff controllers: **REMOVED**
- duplicate Admin Order Livewire: **REMOVED**
- deprecated Admin System settings adapters: **REMOVED**
- Admin HeaderMenu service bridge: **REMOVED / INTEGRATION PRESERVED**
- Website compatibility adapter retirement: **DEFERRED TO WEBSITE REFACTOR BY DESIGN**
- Database destructive boundary: **QUARANTINED / UNCHANGED**
- `ModuleRouteTitle` persistence: **PRESERVED**
- schema/data changes: **NONE**
- manual Admin UI: **PASS**
- final automated regression/build: **PENDING**

## Next checkpoint

1. Pull the latest `refactor/admin-core-boundary-cleanup` branch and run the final verification gate above.
2. If all gates pass, mark this Admin Major Refactor **COMPLETE / PR READY**, open the closeout PR against `main`, review and merge.
3. Start the separate `Modules/Website` refactor. That phase owns the deferred Banner/FlashSale/Affiliate/HeaderMenu compatibility-debt cleanup and must not move those business domains back into Admin.
